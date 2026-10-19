<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end rendering and asset loading.
 */
class BizRev_Public {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'the_content', array( $this, 'maybe_append' ), 20 );
		// غیرفعال‌سازی کامنت پیش‌فرض وردپرس روی نوع‌های فعال (در صورت تنظیم)
		add_filter( 'comments_open', array( $this, 'maybe_close_comments' ), 20, 2 );
		add_filter( 'comments_array', array( $this, 'maybe_hide_comments' ), 20, 2 );
	}

	/**
	 * اگر مدیر خواسته باشد، کامنت وردپرس روی نوع‌های فعال بسته می‌شود.
	 */
	public function maybe_close_comments( $open, $post_id ) {
		if ( ! $open ) {
			return $open;
		}
		$s = BizRev_Settings::all();
		if ( empty( $s['disable_wp_comments'] ) ) {
			return $open;
		}
		$pt = get_post_type( $post_id );
		if ( in_array( $pt, (array) $s['enabled_post_types'], true ) ) {
			return false;
		}
		return $open;
	}

	/**
	 * کامنت‌های موجود وردپرس روی نوع‌های فعال پنهان می‌شوند.
	 */
	public function maybe_hide_comments( $comments, $post_id ) {
		$s = BizRev_Settings::all();
		if ( empty( $s['disable_wp_comments'] ) ) {
			return $comments;
		}
		$pt = get_post_type( $post_id );
		if ( in_array( $pt, (array) $s['enabled_post_types'], true ) ) {
			return array();
		}
		return $comments;
	}

	public function assets() {
		wp_register_style( 'bizrev', BIZREV_URL . 'public/css/bizrev.css', array(), BIZREV_VERSION );
		wp_register_script( 'bizrev', BIZREV_URL . 'public/js/bizrev.js', array(), BIZREV_VERSION, true );

		$s = BizRev_Settings::all();

		// بارگذاری فونت گوگل در صورت انتخاب
		if ( 'google' === $s['font_mode'] && ! empty( $s['font_family'] ) ) {
			$family = str_replace( ' ', '+', $s['font_family'] );
			wp_register_style(
				'bizrev-gfont',
				'https://fonts.googleapis.com/css2?family=' . rawurlencode( $s['font_family'] ) . ':wght@400;500;700&display=swap',
				array(),
				BIZREV_VERSION
			);
		}

		wp_localize_script( 'bizrev', 'BizRevData', array(
			'rest'      => esc_url_raw( rest_url( BizRev_Rest::NS ) ),
			'nonce'     => wp_create_nonce( 'bizrev_nonce' ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'maxStars'  => (int) $s['max_stars'],
			'ratingMode' => $s['rating_mode'],
			'i18n'      => array(
				'submitting' => BizRev_Settings::text( 'submitting' ),
				'voted'      => BizRev_Settings::text( 'helpful_yes' ),
				'error'      => BizRev_Settings::text( 'err_generic' ),
			),
		) );
	}

	/**
	 * CSS variables injected so the admin live-preview and the front-end
	 * stay visually identical. همه متغیرهای استایل از گروه style ساخته می‌شوند.
	 */
	public static function inline_vars( $s ) {
		$st   = isset( $s['style'] ) && is_array( $s['style'] ) ? $s['style'] : BizRev_Settings::default_style();
		$d    = BizRev_Settings::default_style();
		$font = self::resolve_font_family( $s );

		// نقشه: نام متغیر CSS => [ کلید استایل, واحد ]
		$map = array(
			'accent'          => array( 'accent', '' ),
			'star'            => array( 'star', '' ),
			'text'            => array( 'text', '' ),
			'muted'           => array( 'muted', '' ),
			'bg'              => array( 'bg', '' ),
			'soft'            => array( 'soft', '' ),
			'border'          => array( 'border', '' ),
			'pros'            => array( 'pros_color', '' ),
			'cons'            => array( 'cons_color', '' ),
			'rec'             => array( 'rec_color', '' ),
			'verified'        => array( 'verified_color', '' ),
			'radius'          => array( 'radius', 'px' ),
			'wrap-margin'     => array( 'wrap_margin', 'px' ),
			'summary-pad'     => array( 'summary_pad', 'px' ),
			'summary-gap'     => array( 'summary_gap', 'px' ),
			'form-pad'        => array( 'form_pad', 'px' ),
			'form-gap'        => array( 'form_gap', 'px' ),
			'field-gap'       => array( 'field_gap', 'px' ),
			'rating-box-gap'  => array( 'rating_box_gap', 'px' ),
			'criteria-gap'    => array( 'criteria_gap', 'px' ),
			'item-pad'        => array( 'item_pad', 'px' ),
			'item-gap'        => array( 'item_gap', 'px' ),
			'avatar-size'     => array( 'avatar_size', 'px' ),
			'star-size'       => array( 'star_size', 'px' ),
			'star-input-size' => array( 'star_input_size', 'px' ),
			'font-size'       => array( 'font_size', 'px' ),
			'line-height'     => array( 'line_height', '' ),
		);

		$out = '';
		foreach ( $map as $css_var => $info ) {
			list( $key, $unit ) = $info;
			$val = isset( $st[ $key ] ) && '' !== $st[ $key ] ? $st[ $key ] : ( $d[ $key ] ?? '' );
			if ( '' === $val ) {
				continue;
			}
			if ( 'px' === $unit ) {
				$val = (float) $val . 'px';
			} elseif ( '' === $unit && is_numeric( $val ) && in_array( $key, array( 'line_height' ), true ) ) {
				$val = (float) $val;
			} else {
				$val = $val; // رنگ
			}
			$out .= '--bizrev-' . $css_var . ':' . $val . ';';
		}
		$out .= '--bizrev-font:' . $font . ';';
		return $out;
	}

	/**
	 * Resolve the font-family string for the chosen mode.
	 * theme  → ارث از قالب (inherit)
	 * google → نام فونت گوگل
	 * custom → نام فونت سفارشی آپلودشده
	 */
	public static function resolve_font_family( $s ) {
		$fallback = 'inherit';
		if ( empty( $s['font_mode'] ) || 'theme' === $s['font_mode'] ) {
			return $fallback;
		}
		$name = trim( (string) $s['font_family'] );
		if ( '' === $name ) {
			return $fallback;
		}
		// fallback به فونت‌های سیستمی برای ایمنی
		return '"' . $name . '", Tahoma, Arial, sans-serif';
	}

	/**
	 * @font-face برای فونت سفارشی آپلودشده.
	 */
	public static function custom_font_face( $s ) {
		if ( 'custom' !== ( $s['font_mode'] ?? '' ) || empty( $s['font_url'] ) || empty( $s['font_family'] ) ) {
			return '';
		}
		$url    = esc_url( $s['font_url'] );
		$name   = esc_attr( $s['font_family'] );
		$format = 'woff2';
		if ( preg_match( '/\.(woff2|woff|ttf|otf)(\?|$)/i', $url, $m ) ) {
			$ext = strtolower( $m[1] );
			$map = array( 'woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype' );
			$format = $map[ $ext ] ?? 'woff2';
		}
		return sprintf(
			"@font-face{font-family:'%s';src:url('%s') format('%s');font-display:swap;font-weight:400 700;}",
			$name, $url, $format
		);
	}

	/**
	 * Resolve the login URL. If admin set a custom URL, use it; otherwise
	 * fall back to wp-login. Supports {redirect} placeholder.
	 */
	public static function login_url( $post_id, $s ) {
		$redirect = get_permalink( $post_id );
		$custom   = isset( $s['login_url'] ) ? trim( $s['login_url'] ) : '';

		if ( '' === $custom ) {
			return wp_login_url( $redirect );
		}
		if ( false !== strpos( $custom, '{redirect}' ) ) {
			return str_replace( '{redirect}', rawurlencode( $redirect ), $custom );
		}
		return add_query_arg( 'redirect_to', rawurlencode( $redirect ), $custom );
	}

	public function maybe_append( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$s = BizRev_Settings::all();
		$pt = get_post_type();
		if ( ! in_array( $pt, (array) $s['enabled_post_types'], true ) ) {
			return $content;
		}
		return $content . self::render( get_the_ID() );
	}

	/**
	 * Master render — used by both shortcode and auto-append.
	 */
	public static function render( $post_id ) {
		wp_enqueue_style( 'bizrev' );
		wp_enqueue_script( 'bizrev' );

		$s    = BizRev_Settings::for_post( $post_id );

		// فونت گوگل
		if ( 'google' === $s['font_mode'] && ! empty( $s['font_family'] ) && wp_style_is( 'bizrev-gfont', 'registered' ) ) {
			wp_enqueue_style( 'bizrev-gfont' );
		}
		// فونت سفارشی @font-face
		$face = self::custom_font_face( $s );
		if ( $face ) {
			wp_add_inline_style( 'bizrev', $face );
		}
		// CSS سفارشی مدیر
		if ( ! empty( $s['custom_css'] ) ) {
			wp_add_inline_style( 'bizrev', $s['custom_css'] );
		}

		$agg  = BizRev_Data::aggregates( $post_id );
		$tpl  = $s['template'];

		ob_start();

		$schema_on   = ! empty( $s['schema_enabled'] );
		$schema_type = in_array( $s['schema_type'] ?? '', array( 'Product', 'LocalBusiness', 'Service' ), true ) ? $s['schema_type'] : 'Product';
		$itemtype    = $schema_on ? ' itemscope itemtype="https://schema.org/' . esc_attr( $schema_type ) . '"' : '';

		echo '<div class="bizrev-wrap bizrev-tpl-' . esc_attr( $tpl ) . '" style="' . esc_attr( self::inline_vars( $s ) ) . '" dir="' . ( $s['rtl'] ? 'rtl' : 'ltr' ) . '"' . $itemtype . '>';

		// نام مورد برای microdata (aggregateRating از طریق JSON-LD ارائه می‌شود)
		if ( $schema_on ) {
			echo '<meta itemprop="name" content="' . esc_attr( get_the_title( $post_id ) ) . '">';
		}

		if ( $s['show_summary_bar'] ) {
			self::render_summary( $agg, $s );
		}

		self::render_form( $post_id, $s );

		echo '<div class="bizrev-toolbar">';
		echo '<span class="bizrev-count">' . esc_html( number_format_i18n( $agg['count'] ) ) . ' ' . esc_html( BizRev_Settings::text( 'count_suffix' ) ) . '</span>';
		echo '<select class="bizrev-sort" aria-label="مرتب‌سازی">';
		$sorts = array(
			'recent'      => BizRev_Settings::text( 'sort_recent' ),
			'rating_high' => BizRev_Settings::text( 'sort_rating_high' ),
			'rating_low'  => BizRev_Settings::text( 'sort_rating_low' ),
			'helpful'     => BizRev_Settings::text( 'sort_helpful' ),
		);
		foreach ( $sorts as $k => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $s['default_sort'], $k, false ), esc_html( $label ) );
		}
		echo '</select></div>';

		echo '<div class="bizrev-list" data-post="' . esc_attr( $post_id ) . '">';
		$reviews = BizRev_Data::query( array(
			'post_id'  => $post_id,
			'sort'     => $s['default_sort'],
			'per_page' => $s['per_page'],
			'page'     => 1,
		) );
		if ( $reviews ) {
			foreach ( $reviews as $r ) {
				self::render_review_item( $r, $s );
			}
		} else {
			echo '<p class="bizrev-empty">' . esc_html( BizRev_Settings::text( 'empty' ) ) . '</p>';
		}
		echo '</div>';

		$total = BizRev_Data::count( $post_id );
		if ( $total > $s['per_page'] ) {
			echo '<button type="button" class="bizrev-loadmore" data-page="1">' . esc_html( BizRev_Settings::text( 'loadmore' ) ) . '</button>';
		}

		echo '</div>'; // wrap

		// JSON-LD کامل برای موتورهای جستجو (مستقل از بارگذاری AJAX)
		if ( $schema_on && $agg['count'] > 0 ) {
			echo self::jsonld( $post_id, $s, $agg ); // phpcs:ignore
		}

		return ob_get_clean();
	}

	/**
	 * خروجی JSON-LD شامل aggregateRating و چند نظر اخیر.
	 * این روش قابل‌اعتمادترین راه برای دیده شدن امتیازها توسط گوگل است.
	 */
	public static function jsonld( $post_id, $s, $agg ) {
		$schema_type = in_array( $s['schema_type'] ?? '', array( 'Product', 'LocalBusiness', 'Service' ), true ) ? $s['schema_type'] : 'Product';

		$data = array(
			'@context' => 'https://schema.org',
			'@type'    => $schema_type,
			'name'     => wp_strip_all_tags( get_the_title( $post_id ) ),
			'url'      => get_permalink( $post_id ),
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => (string) $agg['average'],
				'reviewCount' => (string) $agg['count'],
				'bestRating'  => (string) $s['max_stars'],
				'worstRating' => '1',
			),
		);

		// چند نظر اخیر (حداکثر ۱۰) برای غنای داده
		$recent = BizRev_Data::query( array(
			'post_id'  => $post_id,
			'sort'     => 'recent',
			'per_page' => 10,
			'page'     => 1,
		) );
		$reviews = array();
		foreach ( $recent as $r ) {
			$reviews[] = array(
				'@type'         => 'Review',
				'author'        => array( '@type' => 'Person', 'name' => wp_strip_all_tags( $r->author_name ) ),
				'datePublished' => mysql2date( 'c', $r->created_at ),
				'reviewBody'    => wp_strip_all_tags( $r->content ),
				'reviewRating'  => array(
					'@type'       => 'Rating',
					'ratingValue' => (string) $r->rating_overall,
					'bestRating'  => (string) $s['max_stars'],
					'worstRating' => '1',
				),
			);
		}
		if ( $reviews ) {
			$data['review'] = $reviews;
		}

		return '<script type="application/ld+json">'
			. wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			. '</script>';
	}

	public static function render_summary( $agg, $s ) {
		$max = (int) $s['max_stars'];
		echo '<div class="bizrev-summary">';
		echo '<div class="bizrev-summary-score">';
		echo '<div class="bizrev-bignum">' . esc_html( number_format_i18n( $agg['average'], 1 ) ) . '</div>';
		echo self::stars_html( $agg['average'], $max ); // phpcs:ignore
		echo '<div class="bizrev-summary-count">' . esc_html( number_format_i18n( $agg['count'] ) ) . ' ' . esc_html( BizRev_Settings::text( 'count_suffix' ) ) . '</div>';
		echo '</div>';

		echo '<div class="bizrev-summary-dist">';
		for ( $i = $max; $i >= 1; $i-- ) {
			$n   = isset( $agg['dist'][ $i ] ) ? $agg['dist'][ $i ] : 0;
			$pct = $agg['count'] ? round( $n / $agg['count'] * 100 ) : 0;
			echo '<div class="bizrev-dist-row">';
			echo '<span class="bizrev-dist-label">' . esc_html( $i ) . ' ★</span>';
			echo '<span class="bizrev-dist-bar"><i style="width:' . esc_attr( $pct ) . '%"></i></span>';
			echo '<span class="bizrev-dist-pct">' . esc_html( $pct ) . '%</span>';
			echo '</div>';
		}
		echo '</div>';

		// میانگین معیارها
		if ( 'multi' === $s['rating_mode'] && ! empty( $agg['criteria'] ) ) {
			echo '<div class="bizrev-summary-criteria">';
			$labels = wp_list_pluck( $s['criteria'], 'label', 'key' );
			foreach ( $agg['criteria'] as $k => $v ) {
				$label = isset( $labels[ $k ] ) ? $labels[ $k ] : $k;
				$pct   = $max ? round( $v / $max * 100 ) : 0;
				echo '<div class="bizrev-crit-row">';
				echo '<span class="bizrev-crit-label">' . esc_html( $label ) . '</span>';
				echo '<span class="bizrev-crit-bar"><i style="width:' . esc_attr( $pct ) . '%"></i></span>';
				echo '<span class="bizrev-crit-val">' . esc_html( number_format_i18n( $v, 1 ) ) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		echo '</div>';
	}

	public static function stars_html( $value, $max = 5 ) {
		$value = (float) $value;
		$out   = '<span class="bizrev-stars" role="img" aria-label="' . esc_attr( $value . ' از ' . $max ) . '">';
		for ( $i = 1; $i <= $max; $i++ ) {
			$cls = 'bizrev-star';
			if ( $value >= $i ) {
				$cls .= ' is-full';
			} elseif ( $value >= $i - 0.5 ) {
				$cls .= ' is-half';
			}
			$out .= '<i class="' . esc_attr( $cls ) . '">★</i>';
		}
		$out .= '</span>';
		return $out;
	}

	public static function render_form( $post_id, $s ) {
		$logged = is_user_logged_in();
		$T = function( $k ) { return BizRev_Settings::text( $k ); };

		if ( $s['require_login'] && ! $logged ) {
			echo '<div class="bizrev-form-locked">' . esc_html( $T( 'login_required' ) )
				. ' <a href="' . esc_url( self::login_url( $post_id, $s ) ) . '">' . esc_html( $T( 'login_link_text' ) ) . '</a></div>';
			return;
		}

		echo '<form class="bizrev-form" data-post="' . esc_attr( $post_id ) . '">';
		echo '<span class="bizrev-form-title">' . esc_html( $T( 'form_title' ) ) . '</span>';

		// امتیازدهی
		if ( 'multi' === $s['rating_mode'] ) {
			echo '<div class="bizrev-criteria-input">';
			foreach ( $s['criteria'] as $c ) {
				echo '<div class="bizrev-crit-input-row">';
				echo '<label>' . esc_html( $c['label'] ) . '</label>';
				echo self::star_input( 'criteria[' . esc_attr( $c['key'] ) . ']', $s['max_stars'] ); // phpcs:ignore
				echo '</div>';
			}
			echo '</div>';
		} else {
			echo '<div class="bizrev-single-rating bizrev-crit-input-row"><label>' . esc_html( $T( 'rating_label' ) ) . '</label>';
			echo self::star_input( 'rating', $s['max_stars'] ); // phpcs:ignore
			echo '</div>';
		}

		echo '<textarea name="content" class="bizrev-input bizrev-textarea" placeholder="' . esc_attr( $T( 'content_ph' ) ) . '" required></textarea>';

		if ( $s['enable_pros_cons'] ) {
			echo '<div class="bizrev-proscons">';
			echo '<div class="bizrev-pc-col bizrev-pc-pros"><label>' . esc_html( $T( 'pros_label' ) ) . '</label>'
				. '<input type="text" name="pros[]" class="bizrev-input" placeholder="' . esc_attr( $T( 'pros_ph1' ) ) . '">'
				. '<input type="text" name="pros[]" class="bizrev-input" placeholder="' . esc_attr( $T( 'pros_ph2' ) ) . '"></div>';
			echo '<div class="bizrev-pc-col bizrev-pc-cons"><label>' . esc_html( $T( 'cons_label' ) ) . '</label>'
				. '<input type="text" name="cons[]" class="bizrev-input" placeholder="' . esc_attr( $T( 'cons_ph1' ) ) . '">'
				. '<input type="text" name="cons[]" class="bizrev-input" placeholder="' . esc_attr( $T( 'cons_ph2' ) ) . '"></div>';
			echo '</div>';
		}

		if ( $s['enable_recommend'] ) {
			echo '<label class="bizrev-recommend"><input type="checkbox" name="recommended" value="1"> ' . esc_html( $T( 'recommend_label' ) ) . '</label>';
		}

		if ( ! $logged ) {
			echo '<div class="bizrev-identity">';
			echo '<input type="text" name="author_name" class="bizrev-input" placeholder="' . esc_attr( $T( 'name_ph' ) ) . '" required>';
			echo '<input type="email" name="author_email" class="bizrev-input" placeholder="' . esc_attr( $T( 'email_ph' ) ) . '" required>';
			echo '</div>';
		}

		// هانی‌پات
		if ( $s['honeypot'] ) {
			echo '<input type="text" name="website_url" class="bizrev-hp" tabindex="-1" autocomplete="off" aria-hidden="true">';
		}

		echo '<button type="submit" class="bizrev-submit">' . esc_html( $T( 'submit_btn' ) ) . '</button>';
		echo '<div class="bizrev-form-msg" role="status"></div>';
		echo '</form>';
	}

	public static function star_input( $name, $max = 5 ) {
		$uid = 'bz' . wp_rand( 1000, 9999 );
		$out = '<span class="bizrev-star-input" data-name="' . esc_attr( $name ) . '">';
		for ( $i = 1; $i <= $max; $i++ ) {
			$out .= '<i class="bizrev-star-pick" data-val="' . esc_attr( $i ) . '" id="' . esc_attr( $uid . $i ) . '">★</i>';
		}
		$out .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="0"></span>';
		return $out;
	}

	/**
	 * One review item. Reused by REST list endpoint for load-more.
	 */
	public static function render_review_item( $r, $s = null ) {
		if ( null === $s ) {
			$s = BizRev_Settings::all();
		}
		$pros = json_decode( (string) $r->pros, true );
		$cons = json_decode( (string) $r->cons, true );
		$crit = json_decode( (string) $r->criteria_ratings, true );
		$labels = wp_list_pluck( $s['criteria'], 'label', 'key' );

		echo '<article class="bizrev-item" data-id="' . esc_attr( $r->id ) . '" itemprop="review" itemscope itemtype="https://schema.org/Review">';

		echo '<header class="bizrev-item-head">';
		echo '<div class="bizrev-avatar">' . esc_html( mb_substr( $r->author_name, 0, 1 ) ) . '</div>';
		echo '<div class="bizrev-meta">';
		echo '<span class="bizrev-author" itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . esc_html( $r->author_name ) . '</span>';
		if ( $r->verified && $s['show_verified_badge'] && ! empty( $s['verified_enabled'] ) ) {
			echo ' <span class="bizrev-verified" role="img" aria-label="' . esc_attr( BizRev_Settings::text( 'verified_title' ) ) . '" title="' . esc_attr( BizRev_Settings::text( 'verified_title' ) ) . '">' . esc_html( BizRev_Settings::text( 'verified_badge' ) ) . '</span>';
		}
		echo '</span>';
		echo '<time class="bizrev-date" datetime="' . esc_attr( mysql2date( 'c', $r->created_at ) ) . '" itemprop="datePublished">' . esc_html( self::jalali_or_date( $r->created_at ) ) . '</time>';
		echo '</div>';
		echo '<div class="bizrev-item-rating" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">'
			. '<meta itemprop="ratingValue" content="' . esc_attr( $r->rating_overall ) . '">'
			. '<meta itemprop="bestRating" content="' . esc_attr( $s['max_stars'] ) . '">'
			. '<meta itemprop="worstRating" content="1">'
			. self::stars_html( $r->rating_overall, $s['max_stars'] ) . '</div>'; // phpcs:ignore
		echo '</header>';

		echo '<div class="bizrev-item-content" itemprop="reviewBody">' . wp_kses_post( wpautop( $r->content ) ) . '</div>';

		if ( ( $pros && is_array( $pros ) ) || ( $cons && is_array( $cons ) ) ) {
			echo '<div class="bizrev-item-pc">';
			if ( $pros ) {
				echo '<ul class="bizrev-pros">';
				foreach ( $pros as $p ) {
					echo '<li>' . esc_html( $p ) . '</li>';
				}
				echo '</ul>';
			}
			if ( $cons ) {
				echo '<ul class="bizrev-cons">';
				foreach ( $cons as $c ) {
					echo '<li>' . esc_html( $c ) . '</li>';
				}
				echo '</ul>';
			}
			echo '</div>';
		}

		if ( 'multi' === $s['rating_mode'] && is_array( $crit ) && $crit ) {
			echo '<div class="bizrev-item-crit">';
			foreach ( $crit as $k => $v ) {
				$label = isset( $labels[ $k ] ) ? $labels[ $k ] : $k;
				echo '<span class="bizrev-crit-chip">' . esc_html( $label ) . ': ' . esc_html( number_format_i18n( $v, 1 ) ) . '</span>';
			}
			echo '</div>';
		}

		if ( $r->recommended ) {
			echo '<div class="bizrev-rec">👍 ' . esc_html( BizRev_Settings::text( 'recommend_badge' ) ) . '</div>';
		}

		// نوار پایین: مفید بودن + دکمه پاسخ
		$show_foot = $s['enable_helpful'] || $s['enable_replies'];
		if ( $show_foot ) {
			echo '<footer class="bizrev-item-foot">';
			if ( $s['enable_helpful'] ) {
				echo '<span class="bizrev-helpful-q">' . esc_html( BizRev_Settings::text( 'helpful_q' ) ) . '</span>';
				echo '<button type="button" class="bizrev-vote" data-vote="1" data-id="' . esc_attr( $r->id ) . '">' . esc_html( BizRev_Settings::text( 'helpful_yes' ) ) . ' (<b>' . esc_html( $r->helpful_yes ) . '</b>)</button>';
				echo '<button type="button" class="bizrev-vote" data-vote="0" data-id="' . esc_attr( $r->id ) . '">' . esc_html( BizRev_Settings::text( 'helpful_no' ) ) . ' (<b>' . esc_html( $r->helpful_no ) . '</b>)</button>';
			}
			if ( $s['enable_replies'] ) {
				echo '<button type="button" class="bizrev-reply-btn" data-id="' . esc_attr( $r->id ) . '">' . esc_html( BizRev_Settings::text( 'reply_btn' ) ) . '</button>';
			}
			echo '</footer>';
		}

		// پاسخ‌ها (یک‌سطحی) — همه پاسخ‌های این نظر زیرش
		if ( $s['enable_replies'] ) {
			$replies = BizRev_Data::get_replies( $r->id );
			echo '<div class="bizrev-replies" data-parent="' . esc_attr( $r->id ) . '">';
			foreach ( $replies as $rep ) {
				self::render_reply_item( $rep, $s );
			}
			echo '</div>';

			// فرم پاسخ (مخفی تا کلیک شود)
			echo '<form class="bizrev-reply-form" data-parent="' . esc_attr( $r->id ) . '" data-post="' . esc_attr( $r->post_id ) . '" hidden>';
			echo '<textarea name="content" class="bizrev-input" rows="2" placeholder="' . esc_attr( BizRev_Settings::text( 'reply_ph' ) ) . '" required></textarea>';
			if ( ! is_user_logged_in() ) {
				echo '<div class="bizrev-identity">';
				echo '<input type="text" name="author_name" class="bizrev-input" placeholder="' . esc_attr( BizRev_Settings::text( 'name_ph' ) ) . '" required>';
				echo '<input type="email" name="author_email" class="bizrev-input" placeholder="' . esc_attr( BizRev_Settings::text( 'email_ph' ) ) . '" required>';
				echo '</div>';
			}
			if ( $s['honeypot'] ) {
				echo '<input type="text" name="website_url" class="bizrev-hp" tabindex="-1" autocomplete="off" aria-hidden="true">';
			}
			echo '<div class="bizrev-reply-actions">';
			echo '<button type="submit" class="bizrev-submit bizrev-reply-send">' . esc_html( BizRev_Settings::text( 'reply_send' ) ) . '</button>';
			echo '<button type="button" class="bizrev-reply-cancel">' . esc_html( BizRev_Settings::text( 'reply_cancel' ) ) . '</button>';
			echo '</div>';
			echo '<div class="bizrev-form-msg" role="status"></div>';
			echo '</form>';
		}

		echo '</article>';
	}

	/**
	 * یک پاسخ (نظر فرزند). پاسخ مدیر نشان ویژه می‌گیرد.
	 */
	public static function render_reply_item( $rep, $s = null ) {
		if ( null === $s ) {
			$s = BizRev_Settings::all();
		}
		$is_owner = user_can( (int) $rep->user_id, 'moderate_comments' );
		$cls = 'bizrev-reply' . ( $is_owner ? ' is-owner' : '' );

		echo '<div class="' . esc_attr( $cls ) . '" itemprop="comment" itemscope itemtype="https://schema.org/Comment">';
		echo '<div class="bizrev-reply-head">';
		echo '<span class="bizrev-reply-author" itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . esc_html( $rep->author_name ) . '</span></span>';
		if ( $is_owner ) {
			echo ' <span class="bizrev-owner-badge">' . esc_html( BizRev_Settings::text( 'owner_reply_badge' ) ) . '</span>';
		}
		echo ' <time class="bizrev-reply-date" datetime="' . esc_attr( mysql2date( 'c', $rep->created_at ) ) . '" itemprop="dateCreated">' . esc_html( self::jalali_or_date( $rep->created_at ) ) . '</time>';
		echo '</div>';
		echo '<div class="bizrev-reply-text" itemprop="text">' . esc_html( $rep->content ) . '</div>';
		echo '</div>';
	}

	/**
	 * Jalali date if a converter exists (e.g. another plugin), else Gregorian.
	 */
	public static function jalali_or_date( $mysql ) {
		$ts = strtotime( $mysql );
		if ( function_exists( 'jdate' ) ) {
			return jdate( 'j F Y', $ts );
		}
		return date_i18n( 'j F Y', $ts );
	}
}
