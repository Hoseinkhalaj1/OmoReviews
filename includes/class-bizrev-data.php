<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access layer for reviews. All DB reads/writes funnel through here.
 */
class BizRev_Data {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'bizrev_reviews';
	}

	public static function votes_table() {
		global $wpdb;
		return $wpdb->prefix . 'bizrev_votes';
	}

	/**
	 * Insert a review. $data is already-sanitized array.
	 *
	 * @return int|false review id or false
	 */
	public static function insert( $data ) {
		global $wpdb;

		$defaults = array(
			'post_id'          => 0,
			'user_id'          => 0,
			'author_name'      => '',
			'author_email'     => '',
			'title'            => '',
			'content'          => '',
			'rating_overall'   => 0,
			'criteria_ratings' => '',
			'pros'             => '',
			'cons'             => '',
			'recommended'      => 0,
			'verified'         => 0,
			'status'           => 'pending',
			'ip'               => '',
			'parent_id'        => 0,
			'created_at'       => current_time( 'mysql' ),
		);
		$data = wp_parse_args( $data, $defaults );

		$ok = $wpdb->insert( self::table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( false === $ok ) {
			return false;
		}
		$id = (int) $wpdb->insert_id;

		do_action( 'bizrev_review_inserted', $id, $data );
		self::bust_aggregate_cache( (int) $data['post_id'] );
		return $id;
	}

	public static function update_status( $id, $status ) {
		global $wpdb;
		$id     = (int) $id;
		$status = in_array( $status, array( 'pending', 'approved', 'spam', 'trash' ), true ) ? $status : 'pending';
		$row    = self::get( $id );
		$wpdb->update( self::table(), array( 'status' => $status ), array( 'id' => $id ) ); // phpcs:ignore
		if ( $row ) {
			self::bust_aggregate_cache( (int) $row->post_id );
		}
		return true;
	}

	public static function set_verified( $id, $verified ) {
		global $wpdb;
		$id  = (int) $id;
		$row = self::get( $id );
		$wpdb->update( self::table(), array( 'verified' => $verified ? 1 : 0 ), array( 'id' => $id ) ); // phpcs:ignore
		if ( $row ) {
			self::bust_aggregate_cache( (int) $row->post_id );
		}
		return true;
	}

	public static function delete( $id ) {
		global $wpdb;
		$id  = (int) $id;
		$row = self::get( $id );
		$wpdb->delete( self::table(), array( 'id' => $id ) ); // phpcs:ignore
		$wpdb->delete( self::votes_table(), array( 'review_id' => $id ) ); // phpcs:ignore
		if ( $row ) {
			self::bust_aggregate_cache( (int) $row->post_id );
		}
		return true;
	}

	public static function get( $id ) {
		global $wpdb;
		$id = (int) $id;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore
	}

	/**
	 * Query reviews for a post with sorting + pagination.
	 */
	public static function query( $args = array() ) {
		global $wpdb;
		$args = wp_parse_args( $args, array(
			'post_id'   => 0,
			'status'    => 'approved',
			'parent_id' => 0,
			'sort'      => 'recent',
			'per_page'  => 10,
			'page'      => 1,
		) );

		$where  = array( 'parent_id = %d' );
		$params = array( (int) $args['parent_id'] );

		if ( $args['post_id'] ) {
			$where[]  = 'post_id = %d';
			$params[] = (int) $args['post_id'];
		}
		if ( 'any' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		switch ( $args['sort'] ) {
			case 'rating_high':
				$order = 'rating_overall DESC, created_at DESC';
				break;
			case 'rating_low':
				$order = 'rating_overall ASC, created_at DESC';
				break;
			case 'helpful':
				$order = 'helpful_yes DESC, created_at DESC';
				break;
			default:
				$order = 'created_at DESC';
		}

		$per_page = max( 1, (int) $args['per_page'] );
		$offset   = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;

		$sql = 'SELECT * FROM ' . self::table() . ' WHERE ' . implode( ' AND ', $where )
			. " ORDER BY {$order} LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore
	}

	public static function count( $post_id, $status = 'approved', $parent_id = 0 ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			'SELECT COUNT(*) FROM ' . self::table() . ' WHERE post_id = %d AND status = %s AND parent_id = %d',
			(int) $post_id, $status, (int) $parent_id
		) );
	}

	public static function get_replies( $parent_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			'SELECT * FROM ' . self::table() . ' WHERE parent_id = %d AND status = %s ORDER BY created_at ASC',
			(int) $parent_id, 'approved'
		) );
	}

	/**
	 * همه پاسخ‌ها بدون فیلتر وضعیت (برای پنل مدیریت).
	 */
	public static function get_all_replies( $parent_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			'SELECT * FROM ' . self::table() . ' WHERE parent_id = %d ORDER BY created_at ASC',
			(int) $parent_id
		) );
	}

	/**
	 * Aggregate rating data for a post: average, count, star distribution,
	 * and per-criteria averages. Cached in a transient.
	 */
	public static function aggregates( $post_id ) {
		$post_id = (int) $post_id;
		$key     = 'bizrev_agg_' . $post_id;
		$cached  = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			'SELECT rating_overall, criteria_ratings FROM ' . self::table()
			. ' WHERE post_id = %d AND status = %s AND parent_id = 0',
			$post_id, 'approved'
		) );

		$count = count( $rows );
		$dist  = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
		$sum   = 0.0;
		$crit  = array();

		foreach ( $rows as $r ) {
			$val  = (float) $r->rating_overall;
			$sum += $val;
			$bucket = max( 1, min( 5, (int) round( $val ) ) );
			$dist[ $bucket ]++;

			$cr = json_decode( (string) $r->criteria_ratings, true );
			if ( is_array( $cr ) ) {
				foreach ( $cr as $k => $v ) {
					if ( ! isset( $crit[ $k ] ) ) {
						$crit[ $k ] = array( 'sum' => 0, 'n' => 0 );
					}
					$crit[ $k ]['sum'] += (float) $v;
					$crit[ $k ]['n']++;
				}
			}
		}

		$crit_avg = array();
		foreach ( $crit as $k => $c ) {
			$crit_avg[ $k ] = $c['n'] ? round( $c['sum'] / $c['n'], 2 ) : 0;
		}

		$data = array(
			'count'    => $count,
			'average'  => $count ? round( $sum / $count, 2 ) : 0,
			'dist'     => $dist,
			'criteria' => $crit_avg,
		);

		set_transient( $key, $data, HOUR_IN_SECONDS );
		return $data;
	}

	public static function bust_aggregate_cache( $post_id ) {
		delete_transient( 'bizrev_agg_' . (int) $post_id );
	}

	/**
	 * Record a helpful vote. $hash uniquely identifies a voter.
	 *
	 * @return array|false updated counts
	 */
	public static function vote( $review_id, $hash, $vote ) {
		global $wpdb;
		$review_id = (int) $review_id;
		$vote      = $vote ? 1 : 0;

		$exists = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			'SELECT id FROM ' . self::votes_table() . ' WHERE review_id = %d AND voter_hash = %s',
			$review_id, $hash
		) );
		if ( $exists ) {
			return false; // already voted
		}

		$wpdb->insert( self::votes_table(), array( // phpcs:ignore
			'review_id'  => $review_id,
			'voter_hash' => $hash,
			'vote'       => $vote,
			'created_at' => current_time( 'mysql' ),
		) );

		$col = $vote ? 'helpful_yes' : 'helpful_no';
		$wpdb->query( $wpdb->prepare( // phpcs:ignore
			'UPDATE ' . self::table() . " SET {$col} = {$col} + 1 WHERE id = %d",
			$review_id
		) );

		$row = self::get( $review_id );
		return array(
			'yes' => (int) $row->helpful_yes,
			'no'  => (int) $row->helpful_no,
		);
	}

	public static function has_reviewed( $post_id, $user_id, $email = '' ) {
		global $wpdb;
		if ( $user_id ) {
			return (bool) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
				'SELECT id FROM ' . self::table() . ' WHERE post_id = %d AND user_id = %d AND parent_id = 0',
				(int) $post_id, (int) $user_id
			) );
		}
		if ( $email ) {
			return (bool) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
				'SELECT id FROM ' . self::table() . ' WHERE post_id = %d AND author_email = %s AND parent_id = 0',
				(int) $post_id, $email
			) );
		}
		return false;
	}
}
