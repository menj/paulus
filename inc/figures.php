<?php
/**
 * Photographs of Paul in the churches, from Wikimedia Commons and The Met, bundled at
 * web size in assets/images/church/ with their licences. Each is shown as a
 * captioned figure with its credit by [paulus_figure].
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Figure registry: file slug => alt text, author, licence, licence URL and
 * the Commons file page.
 *
 * @return array<string, array<string, string>>
 */
function paulus_figures() {
	return paulus_images_data( 'figures' );
}

/**
 * [paulus_figure name="slug"]Caption[/paulus_figure]
 *
 * @param array  $atts    Attributes: name, and wide="1" for a full-column figure.
 * @param string $caption Caption text (may contain <em>).
 * @return string
 */
function paulus_sc_figure( $atts, $caption = '' ) {
	$atts = shortcode_atts( array( 'name' => '', 'wide' => '0' ), $atts, 'paulus_figure' );
	$figs = paulus_figures();
	if ( ! isset( $figs[ $atts['name'] ] ) ) {
		return '';
	}
	$f    = $figs[ $atts['name'] ];
	$file = PAULUS_DIR . '/assets/images/church/' . $atts['name'] . '.avif';
	$size = paulus_image_size( $file );
	$size = $size ? $size : array( 1400, 1050 );
	$full = PAULUS_URI . '/assets/images/church/' . $atts['name'] . '.avif';
	$mid      = PAULUS_DIR . '/assets/images/church/' . $atts['name'] . '-720.avif';
	$mid_size = file_exists( $mid ) ? paulus_image_size( $mid ) : false;
	// The mid-size variant's own width, not an assumed 720: several of
	// the portrait figures were resized to a fixed height instead, so
	// their actual width differs and a hard-coded "720w" descriptor would
	// tell the browser the wrong thing to pick from.
	$set = $mid_size && $mid_size[0] < $size[0] ? PAULUS_URI . '/assets/images/church/' . $atts['name'] . '-720.avif ' . (int) $mid_size[0] . 'w, ' . $full . ' ' . (int) $size[0] . 'w' : '';
	$tall = $size[1] > $size[0];
	$lic  = $f['license_url'] ? '<a href="' . esc_url( $f['license_url'] ) . '" rel="license noopener" target="_blank">' . esc_html( $f['license'] ) . '</a>' : esc_html( $f['license'] );
	// A figure with a source links it, named for the site it is on
	// (Wikimedia Commons, the Met's collection); a figure supplied from
	// elsewhere (an empty source) gives author and licence only.
	$host  = (string) wp_parse_url( (string) $f['source'], PHP_URL_HOST );
	$label = false !== strpos( $host, 'wikimedia.org' ) ? 'Wikimedia Commons' : ( false !== strpos( $host, 'metmuseum.org' ) ? __( 'the Met’s collection', 'paulus' ) : ( false !== strpos( $host, 'nga.gov' ) ? __( 'the National Gallery of Art’s collection', 'paulus' ) : preg_replace( '/^www\./', '', $host ) ) );
	$cred = $f['source'] ? sprintf(
		/* translators: 1: author, 2: licence, 3: source link. */
		__( 'Image: %1$s, %2$s, via %3$s.', 'paulus' ),
		esc_html( $f['author'] ),
		$lic,
		'<a href="' . esc_url( $f['source'] ) . '" rel="noopener" target="_blank">' . esc_html( $label ) . '</a>'
	) : sprintf(
		/* translators: 1: author, 2: licence. */
		__( 'Image: %1$s, %2$s.', 'paulus' ),
		esc_html( $f['author'] ),
		$lic
	);
	return '<figure class="paulus-figure' . ( $tall ? ' paulus-figure--tall' : '' ) . ( '1' === $atts['wide'] ? ' paulus-figure--wide' : '' ) . ( ! empty( $f['cutout'] ) ? ' paulus-figure--cutout' : '' ) . '">'
		. '<a class="paulus-figure__zoom" href="' . esc_url( $full ) . '" data-lightbox="paulus-figures" data-title="' . esc_attr( wp_strip_all_tags( $caption ) . ' ' . wp_strip_all_tags( $cred ) ) . '" aria-label="' . esc_attr__( 'Open the full-size image', 'paulus' ) . '">'
		. '<img src="' . esc_url( $full ) . '"' . ( $set ? ' srcset="' . esc_attr( $set ) . '" sizes="(max-width: 760px) 100vw, 720px"' : '' ) . ' alt="' . esc_attr( $f['alt'] ) . '" width="' . (int) $size[0] . '" height="' . (int) $size[1] . '" loading="lazy" decoding="async">'
		. '</a>'
		. '<figcaption>' . wp_kses( $caption, array( 'em' => array(), 'a' => array( 'href' => true ) ) ) . ' <span class="paulus-figure__credit">' . $cred . '</span></figcaption>'
		. '</figure>';
}
add_shortcode( 'paulus_figure', 'paulus_sc_figure' );
