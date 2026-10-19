<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST endpoints. Front-end talks to these via fetch().
 */
class BizRev_Rest {

	const NS = 'bizrev/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes() {
		register_rest_route( self::NS, '/submit', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'submit' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( self::NS, '/vote', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'vote' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( self::NS, '/list', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'list' ),
			'permission_callback' => '__return_true',
		) );

		// گرفتن نانس تازه — برای سایت‌هایی که صفحه را کش می‌کنند
		register_rest_route( self::NS, '/nonce', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'fresh_nonce' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function fresh_nonce() {
		return new WP_REST_Response( array(
			'nonce'     => wp_create_nonce( 'bizrev_nonce' ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
		), 200 );
	}

	private function client_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return hash( 'sha256', $ip . '|' . $ua . '|' . wp_salt() );
	}

	/**
	 * آیا کاربر این محصول ووکامرس را خریده؟ روی سایت بدون ووکامرس همیشه false.
	 */
	private static function woo_has_bought( $post_id, $user_id, $email ) {
		if ( ! function_exists( 'wc_customer_bought_product' ) ) {
			return false;
		}
		if ( 'product' !== get_post_type( $post_id ) ) {
			return false;
		}
		return (bool) wc_customer_bought_product( $email, (int) $user_id, (int) $post_id );
	}

	public function submit( WP_REST_Request $req ) {
		$s = BizRev_Settings::all();

		// بررسی نانس — هم هدر سفارشی و هم هدر استاندارد REST پشتیبانی می‌شود
		$nonce = $req->get_header( 'X-BizRev-Nonce' );
		if ( ! $nonce ) {
			$nonce = $req->get_header( 'X-WP-Nonce' );
		}
		if ( ! $nonce ) {
			$nonce = $req->get_param( '_bizrev_nonce' );
		}
		$valid = wp_verify_nonce( $nonce, 'bizrev_nonce' ) || wp_verify_nonce( $nonce, 'wp_rest' );
		if ( ! $valid ) {
			return new WP_REST_Response( array( 'message' => 'نشست شما منقضی شده است. صفحه را تازه‌سازی کنید.' ), 403 );
		}

		// هانی‌پات ضد اسپم
		if ( $s['honeypot'] && ! empty( $req->get_param( 'website_url' ) ) ) {
			return new WP_REST_Response( array( 'message' => 'ارسال نامعتبر.' ), 400 );
		}

		$post_id = (int) $req->get_param( 'post_id' );
		if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) {
			return new WP_REST_Response( array( 'message' => 'مورد نامعتبر است.' ), 400 );
		}

		if ( $s['require_login'] && ! is_user_logged_in() ) {
			return new WP_REST_Response( array( 'message' => 'برای ثبت نظر باید وارد شوید.' ), 401 );
		}

		// محدودیت زمانی بین ارسال‌ها
		$throttle_key = 'bizrev_throttle_' . md5( $this->client_hash() );
		if ( $s['min_interval'] && get_transient( $throttle_key ) ) {
			return new WP_REST_Response( array( 'message' => 'لطفاً کمی صبر کنید و دوباره تلاش کنید.' ), 429 );
		}

		$user      = wp_get_current_user();
		$user_id   = $user->ID;
		$name      = $user_id ? $user->display_name : sanitize_text_field( $req->get_param( 'author_name' ) );
		$email     = $user_id ? $user->user_email : sanitize_email( $req->get_param( 'author_email' ) );
		$parent_id = (int) $req->get_param( 'parent_id' );

		// سلسله‌مراتب یک‌سطحی: اگر به یک پاسخ جواب داده شد، به نظر اصلیِ آن متصل می‌کنیم
		if ( $parent_id ) {
			$parent = BizRev_Data::get( $parent_id );
			if ( ! $parent ) {
				return new WP_REST_Response( array( 'message' => 'مورد نامعتبر است.' ), 400 );
			}
			if ( (int) $parent->parent_id !== 0 ) {
				$parent_id = (int) $parent->parent_id; // به جدّ (نظر اصلی) متصل شو
			}
			// پاسخ باید به نظری در همین پست باشد
			if ( (int) $parent->post_id !== $post_id ) {
				$post_id = (int) $parent->post_id;
			}
		}

		// نام و ایمیل برای همه (نظر اصلی و پاسخ) لازم است
		if ( ! $name ) {
			return new WP_REST_Response( array( 'message' => 'نام را وارد کنید.' ), 400 );
		}
		if ( ! is_email( $email ) ) {
			return new WP_REST_Response( array( 'message' => 'ایمیل معتبر وارد کنید.' ), 400 );
		}
		// جلوگیری از نظر اصلی تکراری (فقط برای نظر اصلی، نه پاسخ)
		if ( ! $parent_id && BizRev_Data::has_reviewed( $post_id, $user_id, $email ) ) {
			return new WP_REST_Response( array( 'message' => 'شما قبلاً برای این مورد نظر ثبت کرده‌اید.' ), 409 );
		}

		$content = sanitize_textarea_field( $req->get_param( 'content' ) );
		if ( mb_strlen( $content ) < 3 ) {
			return new WP_REST_Response( array( 'message' => 'متن نظر خیلی کوتاه است.' ), 400 );
		}

		// امتیاز چندمعیاره
		$criteria_in = $req->get_param( 'criteria' );
		$criteria    = array();
		$overall     = (float) $req->get_param( 'rating' );

		if ( 'multi' === $s['rating_mode'] && is_array( $criteria_in ) ) {
			$valid_keys = wp_list_pluck( $s['criteria'], 'key' );
			$sum = 0; $n = 0;
			foreach ( $criteria_in as $k => $v ) {
				if ( in_array( $k, $valid_keys, true ) ) {
					$v = max( 0, min( $s['max_stars'], (float) $v ) );
					$criteria[ $k ] = $v;
					$sum += $v; $n++;
				}
			}
			if ( $n ) {
				$overall = round( $sum / $n, 2 );
			}
		}
		$overall = max( 0, min( $s['max_stars'], $overall ) );
		if ( ! $parent_id && $overall <= 0 ) {
			return new WP_REST_Response( array( 'message' => 'لطفاً امتیاز بدهید.' ), 400 );
		}

		$pros = array_filter( array_map( 'sanitize_text_field', (array) $req->get_param( 'pros' ) ) );
		$cons = array_filter( array_map( 'sanitize_text_field', (array) $req->get_param( 'cons' ) ) );

		// تأیید خودکار خریدار واقعی از طریق ووکامرس (فقط برای نظر اصلی)
		$verified = ( ! $parent_id && ! empty( $s['verified_enabled'] ) && ! empty( $s['verified_woo'] ) )
			? ( self::woo_has_bought( $post_id, $user_id, $email ) ? 1 : 0 )
			: 0;

		$status = ( $s['moderation'] ) ? 'pending' : 'approved';
		// تأیید خودکار نظر کاربران وارد‌شده فقط در صورت فعال بودن گزینه
		if ( ! empty( $s['auto_approve_logged_in'] ) && $user_id ) {
			$status = 'approved';
		}

		$id = BizRev_Data::insert( array(
			'post_id'          => $post_id,
			'user_id'          => $user_id,
			'author_name'      => $name,
			'author_email'     => $email,
			'content'          => $content,
			'rating_overall'   => $parent_id ? 0 : $overall,
			'criteria_ratings' => $criteria ? wp_json_encode( $criteria ) : '',
			'pros'             => $pros ? wp_json_encode( array_values( $pros ) ) : '',
			'cons'             => $cons ? wp_json_encode( array_values( $cons ) ) : '',
			'recommended'      => $req->get_param( 'recommended' ) ? 1 : 0,
			'verified'         => $verified,
			'status'           => $status,
			'ip'               => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'parent_id'        => $parent_id,
		) );

		if ( ! $id ) {
			return new WP_REST_Response( array( 'message' => 'خطا در ثبت نظر.' ), 500 );
		}

		if ( $s['min_interval'] ) {
			set_transient( $throttle_key, 1, (int) $s['min_interval'] );
		}

		// اطلاع به مدیر
		if ( 'pending' === $status ) {
			wp_mail(
				get_option( 'admin_email' ),
				'نظر جدید در انتظار تأیید — ' . get_the_title( $post_id ),
				"یک نظر جدید ثبت شد و منتظر تأیید است.\nمدیریت: " . admin_url( 'admin.php?page=bizrev-reviews' )
			);
		}

		$msg = ( 'pending' === $status )
			? BizRev_Settings::text( 'msg_pending' )
			: BizRev_Settings::text( 'msg_approved' );

		return new WP_REST_Response( array(
			'message' => $msg,
			'status'  => $status,
			'id'      => $id,
		), 201 );
	}

	public function vote( WP_REST_Request $req ) {
		$nonce = $req->get_header( 'X-BizRev-Nonce' );
		if ( ! $nonce ) {
			$nonce = $req->get_header( 'X-WP-Nonce' );
		}
		$valid = wp_verify_nonce( $nonce, 'bizrev_nonce' ) || wp_verify_nonce( $nonce, 'wp_rest' );
		if ( ! $valid ) {
			return new WP_REST_Response( array( 'message' => 'نشست منقضی شده.' ), 403 );
		}
		$review_id = (int) $req->get_param( 'review_id' );
		$vote      = $req->get_param( 'vote' ) ? 1 : 0;
		$res       = BizRev_Data::vote( $review_id, $this->client_hash(), $vote );
		if ( false === $res ) {
			return new WP_REST_Response( array( 'message' => 'شما قبلاً رأی داده‌اید.' ), 409 );
		}
		return new WP_REST_Response( $res, 200 );
	}

	public function list( WP_REST_Request $req ) {
		$post_id = (int) $req->get_param( 'post_id' );
		$page    = max( 1, (int) $req->get_param( 'page' ) );
		$sort    = sanitize_key( $req->get_param( 'sort' ) );
		$s       = BizRev_Settings::for_post( $post_id );

		$reviews = BizRev_Data::query( array(
			'post_id'  => $post_id,
			'status'   => 'approved',
			'sort'     => $sort ? $sort : $s['default_sort'],
			'per_page' => $s['per_page'],
			'page'     => $page,
		) );

		ob_start();
		foreach ( $reviews as $r ) {
			BizRev_Public::render_review_item( $r, $s );
		}
		$html = ob_get_clean();

		$total = BizRev_Data::count( $post_id );
		$has_more = ( $page * $s['per_page'] ) < $total;

		return new WP_REST_Response( array(
			'html'     => $html,
			'has_more' => $has_more,
			'page'     => $page,
		), 200 );
	}
}
