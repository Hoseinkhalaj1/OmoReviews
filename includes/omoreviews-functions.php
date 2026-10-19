<?php
/**
 * توابع عمومی OmoReviews برای استفاده در قالب، افزونه‌ها یا هر جای دیگر.
 *
 * این توابع رابط رسمی و پایدار افزونه هستند. با خیال راحت در قالب
 * (functions.php یا فایل‌های تمپلیت) از آن‌ها استفاده کنید.
 *
 * @package OmoReviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * آیا افزونه برای این نوع محتوا فعال است؟
 *
 * @param int|null $post_id شناسه پست (پیش‌فرض: پست جاری)
 * @return bool
 */
function omoreviews_is_enabled( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return false;
	}
	$s = BizRev_Settings::all();
	return in_array( get_post_type( $post_id ), (array) $s['enabled_post_types'], true );
}

/**
 * همه آمار یک پست را یک‌جا برمی‌گرداند.
 *
 * @param int|null $post_id
 * @return array {
 *     @type int    $count    تعداد کل نظرات (نظر اصلی، تأییدشده)
 *     @type float  $average  میانگین امتیاز کلی
 *     @type array  $dist     توزیع ستاره‌ها [5=>n, 4=>n, ...]
 *     @type array  $criteria میانگین هر معیار [key => avg]
 *     @type int    $max      حداکثر ستاره
 * }
 */
function omoreviews_get_stats( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return array( 'count' => 0, 'average' => 0, 'dist' => array(), 'criteria' => array(), 'max' => 5 );
	}
	$agg        = BizRev_Data::aggregates( $post_id );
	$agg['max'] = (int) BizRev_Settings::get( 'max_stars', 5 );
	return $agg;
}

/**
 * تعداد نظرات یک پست.
 *
 * @param int|null $post_id
 * @return int
 */
function omoreviews_get_count( $post_id = null ) {
	$stats = omoreviews_get_stats( $post_id );
	return (int) $stats['count'];
}

/**
 * میانگین امتیاز یک پست (عدد اعشاری، مثلاً 4.5).
 *
 * @param int|null $post_id
 * @return float
 */
function omoreviews_get_average( $post_id = null ) {
	$stats = omoreviews_get_stats( $post_id );
	return (float) $stats['average'];
}

/**
 * میانگین یک معیار خاص (مثلاً 'price' یا 'quality').
 *
 * @param string   $criterion_key کلید معیار
 * @param int|null $post_id
 * @return float
 */
function omoreviews_get_criteria_average( $criterion_key, $post_id = null ) {
	$stats = omoreviews_get_stats( $post_id );
	return isset( $stats['criteria'][ $criterion_key ] ) ? (float) $stats['criteria'][ $criterion_key ] : 0.0;
}

/**
 * HTML ستاره‌های یک امتیاز را برمی‌گرداند (برای نمایش سریع).
 *
 * @param float    $value امتیاز
 * @param int|null $max   حداکثر ستاره (پیش‌فرض از تنظیمات)
 * @return string
 */
function omoreviews_get_stars_html( $value, $max = null ) {
	$max = $max ? (int) $max : (int) BizRev_Settings::get( 'max_stars', 5 );
	return BizRev_Public::stars_html( (float) $value, $max );
}

/**
 * بلوک کامل نظرات یک پست را برمی‌گرداند (همان چیزی که شورت‌کد می‌سازد).
 *
 * @param int|null $post_id
 * @return string
 */
function omoreviews_get_reviews_html( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return '';
	}
	return BizRev_Public::render( $post_id );
}

/**
 * بلوک کامل نظرات را مستقیم چاپ می‌کند.
 *
 * @param int|null $post_id
 */
function omoreviews_the_reviews( $post_id = null ) {
	echo omoreviews_get_reviews_html( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * یک خلاصه فشرده (میانگین + تعداد + ستاره) را به‌صورت HTML برمی‌گرداند.
 * برای نمایش در کارت‌های لیست، آرشیو، ویجت و... مناسب است.
 *
 * @param int|null $post_id
 * @param array    $args {
 *     @type bool $show_count نمایش تعداد نظرات (پیش‌فرض true)
 *     @type bool $show_stars نمایش ستاره‌ها (پیش‌فرض true)
 *     @type bool $show_average نمایش عدد میانگین (پیش‌فرض true)
 * }
 * @return string
 */
function omoreviews_get_rating_badge( $post_id = null, $args = array() ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return '';
	}
	// اطمینان از بارگذاری استایل حتی وقتی badge خارج از بلوک اصلی استفاده می‌شود
	if ( wp_style_is( 'bizrev', 'registered' ) && ! wp_style_is( 'bizrev', 'enqueued' ) ) {
		wp_enqueue_style( 'bizrev' );
	}
	$args  = wp_parse_args( $args, array(
		'show_count'   => true,
		'show_stars'   => true,
		'show_average' => true,
	) );
	$stats = omoreviews_get_stats( $post_id );

	if ( $stats['count'] < 1 ) {
		return '<span class="omoreviews-badge omoreviews-badge--empty">' . esc_html( BizRev_Settings::text( 'empty' ) ) . '</span>';
	}

	$out = '<span class="omoreviews-badge">';
	if ( $args['show_average'] ) {
		$out .= '<b class="omoreviews-badge-avg">' . esc_html( number_format_i18n( $stats['average'], 1 ) ) . '</b>';
	}
	if ( $args['show_stars'] ) {
		$out .= BizRev_Public::stars_html( $stats['average'], $stats['max'] );
	}
	if ( $args['show_count'] ) {
		$out .= '<span class="omoreviews-badge-count">(' . esc_html( number_format_i18n( $stats['count'] ) )
			. ' ' . esc_html( BizRev_Settings::text( 'count_suffix' ) ) . ')</span>';
	}
	$out .= '</span>';
	return $out;
}

/**
 * خلاصه امتیاز را مستقیم چاپ می‌کند.
 *
 * @param int|null $post_id
 * @param array    $args
 */
function omoreviews_the_rating_badge( $post_id = null, $args = array() ) {
	echo omoreviews_get_rating_badge( $post_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
