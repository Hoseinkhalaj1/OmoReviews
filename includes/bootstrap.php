<?php
/**
 * OmoReviews bootstrap.
 *
 * این فایل شامل منطق راه‌اندازی افزونه است و می‌تواند رمزنگاری (encode) شود.
 * هدر افزونه در فایل اصلی (biz-reviews.php) به‌صورت رمزنگاری‌نشده باقی می‌ماند
 * تا وردپرس بتواند آن را بخواند.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BIZREV_PATH . 'includes/class-bizrev-loader.php';
require_once BIZREV_PATH . 'includes/class-bizrev-install.php';
require_once BIZREV_PATH . 'includes/class-bizrev-settings.php';
require_once BIZREV_PATH . 'includes/class-bizrev-data.php';
require_once BIZREV_PATH . 'includes/class-bizrev-rest.php';
require_once BIZREV_PATH . 'includes/class-bizrev-shortcode.php';
require_once BIZREV_PATH . 'includes/class-bizrev-public.php';
require_once BIZREV_PATH . 'includes/class-bizrev-admin.php';
require_once BIZREV_PATH . 'includes/omoreviews-functions.php';

register_activation_hook( BIZREV_FILE, array( 'BizRev_Install', 'activate' ) );
register_deactivation_hook( BIZREV_FILE, array( 'BizRev_Install', 'deactivate' ) );

/**
 * Boot the plugin.
 */
function bizrev() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new BizRev_Loader();
	}
	return $instance;
}
add_action( 'plugins_loaded', 'bizrev' );
