<?php
/**
 * Plugin Name:       OmoReviews — سیستم نظرات حرفه‌ای دایرکتوری مشاغل
 * Plugin URI:        https://omoweb.org
 * Description:       افزونه نظرات و امتیازدهی چندمعیاره برای سایت‌های دایرکتوری مشاغل. با قالب‌های متعدد، نقاط ضعف و قوت، تأیید کسب‌وکار، فیلتر و مرتب‌سازی نظرات و پنل تنظیمات با پیش‌نمایش زنده.
 * Version:           1.0.0
 * Author:            OmoWeb
 * Author URI:        https://omoweb.org
 * Text Domain:       biz-reviews
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BIZREV_VERSION', '1.0.0' );
define( 'BIZREV_FILE', __FILE__ );
define( 'BIZREV_PATH', plugin_dir_path( __FILE__ ) );
define( 'BIZREV_URL', plugin_dir_url( __FILE__ ) );
define( 'BIZREV_BASENAME', plugin_basename( __FILE__ ) );

// نسخه بدون لایسنس — همیشه با دسترسی کامل فعال است.
if ( ! defined( 'BIZREV_LICENSED' ) ) {
	define( 'BIZREV_LICENSED', true );
}

/**
 * در نسخه بدون لایسنس، همیشه فعال است.
 */
function bizrev_is_licensed() {
	return true;
}

// بارگذاری منطق اصلی افزونه
require_once BIZREV_PATH . 'includes/bootstrap.php';