<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI: review moderation + settings with live preview.
 */
class BizRev_Admin {

	/** @var bool|null وضعیت لایسنس، یک‌بار محاسبه و کش می‌شود */
	private $licensed = null;

	public function __construct( $licensed = null ) {
		// وضعیت لایسنس یک‌بار و زود (روی admin_init) محاسبه می‌شود تا در همه
		// هوک‌ها (منو، نوتیف، عملیات) یکسان و پایدار باشد.
		add_action( 'admin_init', array( $this, 'resolve_license' ), 1 );

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_bizrev_moderate', array( $this, 'handle_moderate' ) );
		add_action( 'admin_post_bizrev_reply', array( $this, 'handle_reply' ) );
		add_filter( 'upload_mimes', array( $this, 'allow_font_uploads' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_font_filetype' ), 10, 4 );
		add_action( 'admin_notices', array( $this, 'license_notice' ) );
	}

	/**
	 * وضعیت لایسنس را یک‌بار محاسبه و کش می‌کند.
	 */
	public function resolve_license() {
		if ( null === $this->licensed ) {
			$this->licensed = function_exists( 'bizrev_is_licensed' ) ? (bool) bizrev_is_licensed() : false;
		}
	}

	/**
	 * وضعیت لایسنس کش‌شده را برمی‌گرداند.
	 */
	private function is_licensed() {
		if ( null === $this->licensed ) {
			$this->resolve_license();
		}
		return $this->licensed;
	}

	/**
	 * اعلان پیشخوان وقتی لایسنس فعال نیست.
	 */
	public function license_notice() {
		if ( $this->is_licensed() ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'bizrev' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>'
			. '<strong>OmoReviews:</strong> '
			. esc_html__( 'لایسنس محصول فعال نیست. شما می‌توانید نظرات را مشاهده کنید، اما برای پاسخ دادن، تأیید نظرات و دسترسی به تنظیمات، لطفاً محصول را از طریق مارکت فعال‌سازی کنید.', 'biz-reviews' )
			. '</p></div>';
	}

	/**
	 * اجازه آپلود فایل‌های فونت در کتابخانه رسانه.
	 */
	public function allow_font_uploads( $mimes ) {
		$mimes['woff2'] = 'font/woff2';
		$mimes['woff']  = 'font/woff';
		$mimes['ttf']   = 'font/ttf';
		$mimes['otf']   = 'font/otf';
		return $mimes;
	}

	/**
	 * برخی سرورها نوع فایل فونت را اشتباه تشخیص می‌دهند؛ اصلاح می‌کنیم.
	 */
	public function fix_font_filetype( $data, $file, $filename, $mimes ) {
		if ( preg_match( '/\.(woff2|woff|ttf|otf)$/i', $filename, $m ) ) {
			$ext = strtolower( $m[1] );
			$map = array( 'woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf', 'otf' => 'font/otf' );
			$data['ext']  = $ext;
			$data['type'] = $map[ $ext ];
		}
		return $data;
	}

	public function menu() {
		$cap = 'manage_options';
		add_menu_page( 'نظرات OmoReviews', 'OmoReviews', $cap, 'bizrev-reviews', array( $this, 'page_reviews' ), 'dashicons-star-filled', 26 );
		add_submenu_page( 'bizrev-reviews', 'مدیریت نظرات', 'مدیریت نظرات', $cap, 'bizrev-reviews', array( $this, 'page_reviews' ) );

		// صفحه تنظیمات فقط با لایسنس فعال
		if ( $this->is_licensed() ) {
			add_submenu_page( 'bizrev-reviews', 'تنظیمات', 'تنظیمات', $cap, 'bizrev-settings', array( $this, 'page_settings' ) );
		}
	}

	public function assets( $hook ) {
		if ( false === strpos( $hook, 'bizrev' ) ) {
			return;
		}
		wp_enqueue_style( 'bizrev-admin', BIZREV_URL . 'admin/css/admin.css', array(), BIZREV_VERSION );
		wp_enqueue_style( 'bizrev', BIZREV_URL . 'public/css/bizrev.css', array(), BIZREV_VERSION );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_media(); // برای آپلود/انتخاب فایل فونت
		wp_enqueue_script( 'bizrev-admin', BIZREV_URL . 'admin/js/admin.js', array( 'jquery', 'wp-color-picker' ), BIZREV_VERSION, true );
	}

	public function register_settings() {
		if ( ! $this->is_licensed() ) {
			return;
		}
		register_setting( 'bizrev_group', BizRev_Settings::OPTION, array(
			'sanitize_callback' => array( $this, 'sanitize' ),
		) );
	}

	public function sanitize( $input ) {
		$d   = BizRev_Settings::defaults();
		$out = BizRev_Settings::all();

		$out['template']     = array_key_exists( $input['template'] ?? '', BizRev_Settings::templates() ) ? $input['template'] : $d['template'];
		$out['accent_color'] = sanitize_hex_color( $input['accent_color'] ?? $d['accent_color'] ) ?: $d['accent_color'];
		$out['star_color']   = sanitize_hex_color( $input['star_color'] ?? $d['star_color'] ) ?: $d['star_color'];
		$out['radius']       = max( 0, min( 40, (int) ( $input['radius'] ?? $d['radius'] ) ) );
		$out['rtl']          = empty( $input['rtl'] ) ? 0 : 1;

		// فونت
		$out['font_mode']   = in_array( $input['font_mode'] ?? '', array( 'theme', 'google', 'custom' ), true ) ? $input['font_mode'] : $d['font_mode'];
		$out['font_family'] = sanitize_text_field( $input['font_family'] ?? '' );
		$out['font_url']    = esc_url_raw( $input['font_url'] ?? '' );
		$out['font_size']   = max( 11, min( 22, (int) ( $input['font_size'] ?? $d['font_size'] ) ) );

		$out['rating_mode']  = in_array( $input['rating_mode'] ?? '', array( 'single', 'multi' ), true ) ? $input['rating_mode'] : $d['rating_mode'];
		$out['max_stars']    = max( 3, min( 10, (int) ( $input['max_stars'] ?? $d['max_stars'] ) ) );

		// معیارها
		$crit = array();
		if ( ! empty( $input['criteria'] ) && is_array( $input['criteria'] ) ) {
			foreach ( $input['criteria'] as $row ) {
				$label = sanitize_text_field( $row['label'] ?? '' );
				if ( '' === $label ) {
					continue;
				}
				$key = sanitize_key( $row['key'] ?? '' );
				if ( '' === $key ) {
					$key = sanitize_key( $label ) ?: 'c' . wp_rand( 100, 999 );
				}
				$crit[] = array( 'key' => $key, 'label' => $label );
			}
		}
		$out['criteria'] = $crit ?: $d['criteria'];

		foreach ( array( 'enable_pros_cons', 'enable_recommend', 'require_login', 'moderation', 'auto_approve_logged_in', 'enable_helpful', 'enable_replies', 'show_summary_bar', 'show_verified_badge', 'verified_enabled', 'verified_woo', 'honeypot' ) as $flag ) {
			$out[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
		}

		// لینک ورود سفارشی — placeholder {redirect} باید حفظ شود
		$login_raw = trim( $input['login_url'] ?? '' );
		if ( '' === $login_raw ) {
			$out['login_url'] = '';
		} else {
			$tmp = str_replace( '{redirect}', 'BIZREVREDIRECT', $login_raw );
			$tmp = esc_url_raw( $tmp );
			$out['login_url'] = str_replace( 'BIZREVREDIRECT', '{redirect}', $tmp );
		}

		$out['per_page']     = max( 1, min( 100, (int) ( $input['per_page'] ?? $d['per_page'] ) ) );
		$out['default_sort'] = in_array( $input['default_sort'] ?? '', array( 'recent', 'rating_high', 'rating_low', 'helpful' ), true ) ? $input['default_sort'] : $d['default_sort'];
		$out['min_interval'] = max( 0, (int) ( $input['min_interval'] ?? $d['min_interval'] ) );

		$pts = array();
		if ( ! empty( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			foreach ( $input['enabled_post_types'] as $pt ) {
				if ( post_type_exists( $pt ) ) {
					$pts[] = sanitize_key( $pt );
				}
			}
		}
		$out['enabled_post_types'] = $pts ?: $d['enabled_post_types'];

		// نادیده‌گرفتن نوع محتوا
		$out['pt_overrides'] = array();
		if ( ! empty( $input['pt_overrides'] ) && is_array( $input['pt_overrides'] ) ) {
			$valid_keys = wp_list_pluck( $out['criteria'], 'key' );
			foreach ( $input['pt_overrides'] as $pt => $ov ) {
				if ( ! post_type_exists( $pt ) ) {
					continue;
				}
				$entry = array();
				$entry['hide_pros_cons'] = empty( $ov['hide_pros_cons'] ) ? 0 : 1;
				$entry['hide_recommend'] = empty( $ov['hide_recommend'] ) ? 0 : 1;
				$entry['hidden_criteria'] = array();
				if ( ! empty( $ov['hidden_criteria'] ) && is_array( $ov['hidden_criteria'] ) ) {
					foreach ( $ov['hidden_criteria'] as $ck ) {
						$ck = sanitize_key( $ck );
						if ( in_array( $ck, $valid_keys, true ) ) {
							$entry['hidden_criteria'][] = $ck;
						}
					}
				}
				// فقط اگر چیزی تنظیم شده ذخیره کن
				if ( $entry['hide_pros_cons'] || $entry['hide_recommend'] || $entry['hidden_criteria'] ) {
					$out['pt_overrides'][ sanitize_key( $pt ) ] = $entry;
				}
			}
		}

		// متن‌ها
		$default_texts = BizRev_Settings::default_texts();
		$texts = array();
		if ( ! empty( $input['texts'] ) && is_array( $input['texts'] ) ) {
			foreach ( $default_texts as $key => $def ) {
				$val = isset( $input['texts'][ $key ] ) ? sanitize_text_field( $input['texts'][ $key ] ) : '';
				$texts[ $key ] = ( '' === $val ) ? $def : $val;
			}
		} else {
			$texts = $default_texts;
		}
		$out['texts'] = $texts;

		// استایل
		$default_style = BizRev_Settings::default_style();
		$schema        = BizRev_Settings::style_schema();
		$style = array();
		foreach ( $default_style as $key => $def ) {
			$type = isset( $schema[ $key ][1] ) ? $schema[ $key ][1] : 'px';
			$raw  = isset( $input['style'][ $key ] ) ? $input['style'][ $key ] : $def;
			if ( 'color' === $type ) {
				$style[ $key ] = sanitize_hex_color( $raw ) ?: $def;
			} elseif ( 'num' === $type ) {
				$style[ $key ] = is_numeric( $raw ) ? (float) $raw : $def;
			} else { // px
				$style[ $key ] = max( 0, (int) $raw );
			}
		}
		$out['style'] = $style;

		// CSS سفارشی — تگ‌ها حذف می‌شوند ولی محتوای CSS حفظ
		$out['custom_css'] = isset( $input['custom_css'] ) ? wp_strip_all_tags( $input['custom_css'] ) : '';

		// سئو
		$out['schema_enabled']      = empty( $input['schema_enabled'] ) ? 0 : 1;
		$out['schema_type']         = in_array( $input['schema_type'] ?? '', array( 'Product', 'LocalBusiness', 'Service' ), true ) ? $input['schema_type'] : 'Product';
		$out['disable_wp_comments'] = empty( $input['disable_wp_comments'] ) ? 0 : 1;

		return $out;
	}

	/* ---------- صفحه مدیریت نظرات ---------- */

	public function page_reviews() {
		$licensed = $this->is_licensed();
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'pending'; // phpcs:ignore
		global $wpdb;
		$table = BizRev_Data::table();
		$rows  = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT * FROM {$table} WHERE status = %s AND parent_id = 0 ORDER BY created_at DESC LIMIT 100",
			$status
		) );
		$counts = array();
		foreach ( array( 'pending', 'approved', 'spam', 'trash' ) as $st ) {
			$counts[ $st ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s AND parent_id = 0", $st ) ); // phpcs:ignore
		}
		include BIZREV_PATH . 'admin/view-reviews.php';
	}

	public function handle_moderate() {
		check_admin_referer( 'bizrev_moderate' );
		if ( ! current_user_can( 'manage_options' ) || ! $this->is_licensed() ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$id     = (int) ( $_POST['id'] ?? 0 );
		$action = sanitize_key( $_POST['do'] ?? '' );
		if ( 'delete' === $action ) {
			BizRev_Data::delete( $id );
		} elseif ( 'verify' === $action ) {
			BizRev_Data::set_verified( $id, 1 );
		} elseif ( 'unverify' === $action ) {
			BizRev_Data::set_verified( $id, 0 );
		} else {
			$map = array( 'approve' => 'approved', 'pending' => 'pending', 'spam' => 'spam', 'trash' => 'trash' );
			if ( isset( $map[ $action ] ) ) {
				BizRev_Data::update_status( $id, $map[ $action ] );
			}
		}
		wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=bizrev-reviews' ) );
		exit;
	}

	public function handle_reply() {
		check_admin_referer( 'bizrev_reply' );
		if ( ! current_user_can( 'manage_options' ) || ! $this->is_licensed() ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$parent  = BizRev_Data::get( (int) ( $_POST['parent_id'] ?? 0 ) );
		$content = sanitize_textarea_field( $_POST['reply'] ?? '' );
		if ( $parent && $content ) {
			$user = wp_get_current_user();
			BizRev_Data::insert( array(
				'post_id'     => $parent->post_id,
				'user_id'     => $user->ID,
				'author_name' => $user->display_name,
				'author_email' => $user->user_email,
				'content'     => $content,
				'status'      => 'approved',
				'parent_id'   => $parent->id,
			) );
		}
		wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=bizrev-reviews' ) );
		exit;
	}

	/* ---------- صفحه تنظیمات ---------- */

	public function page_settings() {
		if ( ! $this->is_licensed() ) {
			wp_die( esc_html__( 'برای دسترسی به تنظیمات، لایسنس محصول باید فعال باشد.', 'biz-reviews' ) );
		}
		$s = BizRev_Settings::all();
		include BIZREV_PATH . 'admin/view-settings.php';
	}
}
