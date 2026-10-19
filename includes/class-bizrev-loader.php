<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the pieces together once plugins are loaded.
 */
class BizRev_Loader {

	public function __construct() {
		load_plugin_textdomain( 'biz-reviews', false, dirname( BIZREV_BASENAME ) . '/languages' );

		new BizRev_Rest();
		new BizRev_Shortcode();
		new BizRev_Public();

		// پنل مدیریت همیشه ساخته می‌شود؛ وضعیت لایسنس به آن پاس داده می‌شود.
		// بدون لایسنس: فقط نمایش نظرات (فقط‌خواندنی). با لایسنس: دسترسی کامل.
		if ( is_admin() ) {
			new BizRev_Admin();
		}
	}
}
