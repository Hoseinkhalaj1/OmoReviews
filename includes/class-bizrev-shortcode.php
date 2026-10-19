<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [biz_reviews id="123"] shortcode. id is optional; defaults to current post.
 */
class BizRev_Shortcode {

	public function __construct() {
		add_shortcode( 'biz_reviews', array( $this, 'render' ) );
		add_shortcode( 'omo_reviews', array( $this, 'render' ) );
		add_shortcode( 'omo_rating', array( $this, 'rating_badge' ) );
		add_shortcode( 'omo_count', array( $this, 'count' ) );
		add_shortcode( 'omo_average', array( $this, 'average' ) );
	}

	/**
	 * [omo_rating id="123"] — خلاصه امتیاز (ستاره + میانگین + تعداد)
	 */
	public function rating_badge( $atts ) {
		$atts = shortcode_atts( array(
			'id'      => 0,
			'stars'   => '1',
			'average' => '1',
			'count'   => '1',
		), $atts, 'omo_rating' );
		$post_id = (int) $atts['id'] ?: get_the_ID();
		return omoreviews_get_rating_badge( $post_id, array(
			'show_stars'   => '1' === (string) $atts['stars'],
			'show_average' => '1' === (string) $atts['average'],
			'show_count'   => '1' === (string) $atts['count'],
		) );
	}

	/**
	 * [omo_count id="123"] — فقط تعداد نظرات
	 */
	public function count( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'omo_count' );
		$post_id = (int) $atts['id'] ?: get_the_ID();
		return esc_html( number_format_i18n( omoreviews_get_count( $post_id ) ) );
	}

	/**
	 * [omo_average id="123"] — فقط میانگین امتیاز
	 */
	public function average( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'omo_average' );
		$post_id = (int) $atts['id'] ?: get_the_ID();
		return esc_html( number_format_i18n( omoreviews_get_average( $post_id ), 1 ) );
	}

	public function render( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'biz_reviews' );
		$post_id = (int) $atts['id'];
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		if ( ! $post_id ) {
			return '';
		}
		return BizRev_Public::render( $post_id );
	}
}
