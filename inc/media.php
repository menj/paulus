<?php
/**
 * Media: resized copies of uploads.
 *
 * WordPress makes a set of resized copies of every image added to the
 * Media Library, and a reduced "-scaled" copy of any image over 2560
 * pixels. Both can be switched off under Appearance, Theme Options, Media.
 * Copies made before the switch stay on disk until removed by hand.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * No resized copies: every registered size, the theme's and plugins'
 * included, is dropped for new uploads.
 *
 * @param array $sizes Sizes WordPress is about to generate.
 * @return array
 */
function paulus_media_sizes( $sizes ) {
	return paulus_option( 'media_no_sizes' ) ? array() : $sizes;
}
add_filter( 'intermediate_image_sizes_advanced', 'paulus_media_sizes', 99 );

/**
 * No "-scaled" copy of large uploads.
 *
 * @param int|false $threshold Pixel threshold.
 * @return int|false
 */
function paulus_media_big_threshold( $threshold ) {
	return paulus_option( 'media_no_scaled' ) ? false : $threshold;
}
add_filter( 'big_image_size_threshold', 'paulus_media_big_threshold', 99 );
