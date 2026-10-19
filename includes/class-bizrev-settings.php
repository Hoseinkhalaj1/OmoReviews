<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central settings store. Everything configurable from the admin panel
 * lives here so the live-preview panel and the front-end share one source.
 */
class BizRev_Settings {

	const OPTION = 'bizrev_settings';

	public static function defaults() {
		return array(
			// عمومی
			'enabled_post_types' => array( 'post' ), // پیش‌فرض؛ برای دایرکتوری معمولاً نوع پست مشاغل
			'template'           => 'cards',          // cards | classic | timeline | compact | magazine | grid
			'accent_color'       => '#2563eb',
			'star_color'         => '#f59e0b',
			'radius'             => 14,
			'rtl'                => 1,

			// فونت
			'font_mode'          => 'theme',  // theme | google | custom
			'font_family'        => '',       // نام فونت گوگل یا فونت سفارشی
			'font_url'           => '',        // آدرس فایل فونت آپلودشده (woff2/woff/ttf)
			'font_size'          => 15,        // اندازه پایه (px)

			// امتیازدهی
			'rating_mode'        => 'multi', // single | multi
			'criteria'           => array(
				array( 'key' => 'quality', 'label' => 'کیفیت خدمات' ),
				array( 'key' => 'price',   'label' => 'قیمت' ),
				array( 'key' => 'staff',   'label' => 'برخورد پرسنل' ),
				array( 'key' => 'location', 'label' => 'دسترسی و موقعیت' ),
			),
			'max_stars'          => 5,

			// فرم
			'enable_pros_cons'   => 1,
			'enable_recommend'   => 1,
			'require_login'      => 0,
			'login_url'          => '', // خالی = صفحه ورود پیش‌فرض وردپرس
			'moderation'         => 1, // نظرات بعد از تأیید نمایش داده شوند
			'auto_approve_logged_in' => 0, // نظر کاربران وارد‌شده خودکار تأیید شود؟
			'enable_helpful'     => 1, // مفید بود / نبود
			'enable_replies'     => 1, // پاسخ صاحب کسب‌وکار

			// نادیده‌گرفتن (override) برای نوع‌های محتوای خاص
			// ساختار: array( 'post' => array( 'hide_pros_cons'=>1, 'hide_recommend'=>1, 'hidden_criteria'=>array('price') ) )
			'pt_overrides'       => array(),

			// نمایش
			'per_page'           => 10,
			'default_sort'       => 'recent', // recent | rating_high | rating_low | helpful
			'show_summary_bar'   => 1, // نوار خلاصه امتیاز + توزیع ستاره‌ها
			'show_verified_badge' => 1,

			// تأیید هویت خریدار
			'verified_enabled'   => 1, // فعال بودن کل قابلیت نشان «خریدار تأییدشده»
			'verified_woo'       => 1, // تشخیص خودکار خرید از طریق ووکامرس (اگر نصب باشد)

			// ضد اسپم
			'honeypot'           => 1,
			'min_interval'       => 30, // ثانیه بین دو ارسال از یک IP

			// سئو / داده ساختاریافته
			'schema_enabled'     => 1,          // افزودن microdata و JSON-LD
			'schema_type'        => 'Product',  // Product | LocalBusiness | Service
			'disable_wp_comments' => 1,         // غیرفعال‌سازی کامنت پیش‌فرض وردپرس روی نوع‌های فعال

			// متن‌های قابل شخصی‌سازی
			'texts'              => self::default_texts(),

			// استایل پیشرفته (همه فاصله‌ها، رنگ‌ها، اندازه‌ها)
			'style'              => self::default_style(),

			// CSS سفارشی اضافی
			'custom_css'         => '',
		);
	}

	/**
	 * همه رشته‌های متنی قابل ویرایش توسط مدیر.
	 */
	public static function default_texts() {
		return array(
			'form_title'        => 'ثبت نظر شما',
			'rating_label'      => 'امتیاز شما',
			'content_ph'        => 'تجربه خود را بنویسید…',
			'pros_label'        => 'نقاط قوت',
			'cons_label'        => 'نقاط ضعف',
			'pros_ph1'          => 'مثلاً برخورد عالی',
			'pros_ph2'          => 'یک مورد دیگر',
			'cons_ph1'          => 'مثلاً زمان انتظار',
			'cons_ph2'          => 'یک مورد دیگر',
			'recommend_label'   => 'این کسب‌وکار را توصیه می‌کنم',
			'recommend_badge'   => 'این کسب‌وکار را توصیه می‌کند',
			'name_ph'           => 'نام شما',
			'email_ph'          => 'ایمیل (نمایش داده نمی‌شود)',
			'submit_btn'        => 'ارسال نظر',
			'submitting'        => 'در حال ارسال…',
			'login_required'    => 'برای ثبت نظر لطفاً وارد شوید.',
			'login_link_text'   => 'وارد شوید',
			'count_suffix'      => 'نظر',
			'sort_recent'       => 'جدیدترین',
			'sort_rating_high'  => 'بیشترین امتیاز',
			'sort_rating_low'   => 'کمترین امتیاز',
			'sort_helpful'      => 'مفیدترین',
			'empty'             => 'هنوز نظری ثبت نشده است. اولین نفر باشید!',
			'loadmore'          => 'نمایش نظرات بیشتر',
			'verified_badge'    => '✔ کاربر تأییدشده',
			'verified_title'    => 'کاربر تأییدشده',
			'helpful_q'         => 'این نظر مفید بود؟',
			'helpful_yes'       => 'بله',
			'helpful_no'        => 'خیر',
			'reply_prefix'      => 'پاسخ',
			'reply_btn'         => 'پاسخ',
			'reply_ph'          => 'پاسخ خود را بنویسید…',
			'reply_send'        => 'ارسال پاسخ',
			'reply_cancel'      => 'انصراف',
			'replies_toggle'    => 'نمایش پاسخ‌ها',
			'owner_reply_badge' => 'پاسخ صاحب کسب‌وکار',
			'msg_pending'       => 'نظر شما ثبت شد و پس از تأیید نمایش داده می‌شود.',
			'msg_approved'      => 'نظر شما با موفقیت ثبت شد.',
			'err_generic'       => 'خطایی رخ داد.',
			'err_session'       => 'نشست شما منقضی شده است. صفحه را تازه‌سازی کنید.',
			'err_name'          => 'نام را وارد کنید.',
			'err_email'         => 'ایمیل معتبر وارد کنید.',
			'err_short'         => 'متن نظر خیلی کوتاه است.',
			'err_rating'        => 'لطفاً امتیاز بدهید.',
			'err_login'         => 'برای ثبت نظر باید وارد شوید.',
			'err_duplicate'     => 'شما قبلاً برای این مورد نظر ثبت کرده‌اید.',
			'err_throttle'      => 'لطفاً کمی صبر کنید و دوباره تلاش کنید.',
			'err_voted'         => 'شما قبلاً رأی داده‌اید.',
		);
	}

	/**
	 * همه متغیرهای استایل پیشرفته. کلید = نام متغیر CSS (بدون پیشوند --omorev-).
	 * مقدار = پیش‌فرض. type برای نوع کنترل در پنل.
	 */
	public static function default_style() {
		return array(
			// رنگ‌ها
			'accent'          => '#2563eb',
			'star'            => '#f59e0b',
			'text'            => '#1f2430',
			'muted'           => '#7a8194',
			'bg'              => '#ffffff',
			'soft'            => '#f4f6fb',
			'border'          => '#e6e9f0',
			'pros_color'      => '#16a34a',
			'cons_color'      => '#dc2626',
			'rec_color'       => '#16a34a',
			'verified_color'  => '#16a34a',
			// گردی
			'radius'          => 14,
			// فاصله‌ها (px)
			'wrap_margin'     => 32,   // فاصله کل بلوک از بالا/پایین
			'summary_pad'     => 24,   // پدینگ نوار خلاصه
			'summary_gap'     => 24,   // فاصله نوار خلاصه تا فرم
			'form_pad'        => 22,   // پدینگ فرم
			'form_gap'        => 26,   // فاصله فرم تا لیست
			'field_gap'       => 12,   // فاصله بین فیلدهای فرم
			'rating_box_gap'  => 18,   // فاصله باکس امتیازها تا بقیه فرم (و تا کامنت)
			'criteria_gap'    => 14,   // فاصله بین معیارهای امتیاز
			'item_pad'        => 20,   // پدینگ هر کامنت
			'item_gap'        => 16,   // فاصله بین کامنت‌ها
			'avatar_size'     => 44,   // اندازه آواتار
			'star_size'       => 17,   // اندازه ستاره نمایشی
			'star_input_size' => 24,   // اندازه ستاره فرم
			// متن
			'font_size'       => 15,
			'line_height'     => 1.9,
		);
	}

	/**
	 * فراداده متغیرهای استایل برای ساخت خودکار کنترل‌های پنل.
	 * [ key => [ label, type(color|px|num), group ] ]
	 */
	public static function style_schema() {
		return array(
			'accent'          => array( 'رنگ اصلی', 'color', 'colors' ),
			'star'            => array( 'رنگ ستاره', 'color', 'colors' ),
			'text'            => array( 'رنگ متن', 'color', 'colors' ),
			'muted'           => array( 'رنگ متن کم‌رنگ', 'color', 'colors' ),
			'bg'              => array( 'رنگ پس‌زمینه باکس', 'color', 'colors' ),
			'soft'            => array( 'رنگ پس‌زمینه ملایم', 'color', 'colors' ),
			'border'          => array( 'رنگ خطوط', 'color', 'colors' ),
			'pros_color'      => array( 'رنگ نقاط قوت', 'color', 'colors' ),
			'cons_color'      => array( 'رنگ نقاط ضعف', 'color', 'colors' ),
			'rec_color'       => array( 'رنگ متن توصیه', 'color', 'colors' ),
			'verified_color'  => array( 'رنگ نشان تأیید', 'color', 'colors' ),
			'radius'          => array( 'گردی گوشه‌ها', 'px', 'sizing' ),
			'wrap_margin'     => array( 'فاصله کل بلوک (بالا/پایین)', 'px', 'spacing' ),
			'summary_pad'     => array( 'پدینگ نوار خلاصه', 'px', 'spacing' ),
			'summary_gap'     => array( 'فاصله خلاصه تا فرم', 'px', 'spacing' ),
			'form_pad'        => array( 'پدینگ فرم', 'px', 'spacing' ),
			'form_gap'        => array( 'فاصله فرم تا لیست', 'px', 'spacing' ),
			'field_gap'       => array( 'فاصله بین فیلدهای فرم', 'px', 'spacing' ),
			'rating_box_gap'  => array( 'فاصله باکس امتیاز تا کامنت', 'px', 'spacing' ),
			'criteria_gap'    => array( 'فاصله بین معیارها', 'px', 'spacing' ),
			'item_pad'        => array( 'پدینگ هر کامنت', 'px', 'spacing' ),
			'item_gap'        => array( 'فاصله بین کامنت‌ها', 'px', 'spacing' ),
			'avatar_size'     => array( 'اندازه آواتار', 'px', 'sizing' ),
			'star_size'       => array( 'اندازه ستاره نمایشی', 'px', 'sizing' ),
			'star_input_size' => array( 'اندازه ستاره فرم', 'px', 'sizing' ),
			'font_size'       => array( 'اندازه فونت پایه', 'px', 'sizing' ),
			'line_height'     => array( 'ارتفاع خط', 'num', 'sizing' ),
		);
	}

	public static function style_groups() {
		return array(
			'colors'  => 'رنگ‌ها',
			'spacing' => 'فاصله‌ها',
			'sizing'  => 'اندازه‌ها',
		);
	}

	/**
	 * یک متن را برمی‌گرداند؛ اگر مدیر سفارشی نکرده باشد پیش‌فرض.
	 */
	public static function text( $key, $fallback = '' ) {
		$all = self::all();
		if ( ! empty( $all['texts'][ $key ] ) ) {
			return $all['texts'][ $key ];
		}
		$d = self::default_texts();
		return isset( $d[ $key ] ) ? $d[ $key ] : $fallback;
	}

	/**
	 * یک مقدار استایل را برمی‌گرداند.
	 */
	public static function style( $key ) {
		$all = self::all();
		if ( isset( $all['style'][ $key ] ) && '' !== $all['style'][ $key ] ) {
			return $all['style'][ $key ];
		}
		$d = self::default_style();
		return isset( $d[ $key ] ) ? $d[ $key ] : '';
	}

	public static function all() {
		$saved = get_option( self::OPTION, array() );
		$merged = wp_parse_args( $saved, self::defaults() );
		// merge عمیق برای آرایه‌های تو در تو
		$merged['texts'] = wp_parse_args(
			isset( $saved['texts'] ) && is_array( $saved['texts'] ) ? $saved['texts'] : array(),
			self::default_texts()
		);
		$merged['style'] = wp_parse_args(
			isset( $saved['style'] ) && is_array( $saved['style'] ) ? $saved['style'] : array(),
			self::default_style()
		);
		return $merged;
	}

	public static function get( $key, $fallback = null ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : $fallback;
	}

	/**
	 * تنظیمات نهایی برای یک پست خاص، با اعمال نادیده‌گرفتن نوع محتوا.
	 * مثلاً در مقالات می‌توان نقاط قوت/ضعف یا یک معیار خاص را مخفی کرد.
	 */
	public static function for_post( $post_id ) {
		$s = self::all();
		$pt = get_post_type( $post_id );
		if ( ! $pt || empty( $s['pt_overrides'][ $pt ] ) ) {
			return $s;
		}
		$ov = $s['pt_overrides'][ $pt ];

		// مخفی کردن نقاط قوت/ضعف
		if ( ! empty( $ov['hide_pros_cons'] ) ) {
			$s['enable_pros_cons'] = 0;
		}
		// مخفی کردن توصیه
		if ( ! empty( $ov['hide_recommend'] ) ) {
			$s['enable_recommend'] = 0;
		}
		// حذف معیارهای مخفی‌شده
		if ( ! empty( $ov['hidden_criteria'] ) && is_array( $ov['hidden_criteria'] ) && ! empty( $s['criteria'] ) ) {
			$hidden = $ov['hidden_criteria'];
			$s['criteria'] = array_values( array_filter( $s['criteria'], function( $c ) use ( $hidden ) {
				return ! in_array( $c['key'], $hidden, true );
			} ) );
		}
		return $s;
	}

	public static function templates() {
		return array(
			'cards'    => 'کارت‌ها (مدرن)',
			'classic'  => 'کلاسیک',
			'timeline' => 'تایم‌لاین',
			'compact'  => 'فشرده',
			'magazine' => 'مجله‌ای',
			'grid'     => 'شبکه‌ای',
		);
	}

	/**
	 * فونت‌های فارسی محبوب موجود در Google Fonts.
	 * key = نام family برای @import و font-family
	 */
	public static function google_fonts() {
		return array(
			'Vazirmatn' => 'وزیرمتن (Vazirmatn)',
			'Estedad'   => 'استعداد (Estedad)',
			'Sahel'     => 'ساحل (Sahel)',
			'Samim'     => 'صمیم (Samim)',
			'Shabnam'   => 'شبنم (Shabnam)',
			'Gandom'    => 'گندم (Gandom)',
			'Tanha'     => 'تنها (Tanha)',
			'Noto Naskh Arabic' => 'نوتو نسخ (Noto Naskh Arabic)',
			'Lalezar'   => 'لاله‌زار (Lalezar)',
		);
	}
}
