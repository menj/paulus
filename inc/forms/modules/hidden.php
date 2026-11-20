<?php
/**
 * Hidden field
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_hidden', 10, 0 );

function paulus_form_add_form_tag_hidden() {
	paulus_form_add_form_tag( 'hidden',
		'paulus_form_hidden_form_tag_handler',
		array(
			'name-attr' => true,
			'display-hidden' => true,
		)
	);
}

function paulus_form_hidden_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$atts = array();

	$class = paulus_form_form_controls_class( $tag->type );
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();

	$value = (string) reset( $tag->values );
	$value = $tag->get_default_option( $value );
	$atts['value'] = $value;

	$atts['type'] = 'hidden';
	$atts['name'] = $tag->name;
	$atts = paulus_form_format_atts( $atts );

	$html = sprintf( '<input %s />', $atts );
	return $html;
}
