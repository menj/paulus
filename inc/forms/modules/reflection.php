<?php
/**
 * Reflection module
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_reflection', 10, 0 );

/**
 * Registers reflection-related form-tag types.
 */
function paulus_form_add_form_tag_reflection() {
	paulus_form_add_form_tag( 'reflection',
		'paulus_form_reflection_form_tag_handler',
		array(
			'name-attr' => true,
			'display-block' => true,
			'not-for-mail' => true,
		)
	);

	paulus_form_add_form_tag( 'output',
		'paulus_form_output_form_tag_handler',
		array(
			'name-attr' => true,
			'not-for-mail' => true,
		)
	);
}


/**
 * The form-tag handler for the reflection type.
 */
function paulus_form_reflection_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$values = $tag->values ? $tag->values : array( '' );

	if ( ! paulus_form_get_validation_error( $tag->name ) ) {
		$hangover = array_filter( (array) paulus_form_get_hangover( $tag->name ) );

		if ( $hangover ) {
			$values = $hangover;
		}
	}

	$content = array_reduce(
		$values,
		static function ( $carry, $item ) use ( $tag ) {
			$output_tag = sprintf(
				'<output %1$s>%2$s</output>',
				paulus_form_format_atts( array(
					'name' => $tag->name,
					'data-default' => $item,
				) ),
				( '' !== $item ) ? esc_html( $item ) : '&nbsp;'
			);

			return $carry . $output_tag;
		},
		''
	);

	$html = sprintf(
		'<fieldset %1$s>%2$s</fieldset>',
		paulus_form_format_atts( array(
			'data-reflection-of' => $tag->name,
			'class' => $tag->get_class_option(
				paulus_form_form_controls_class( $tag->type )
			),
			'id' => $tag->get_id_option(),
		) ),
		$content
	);

	return $html;
}


/**
 * The form-tag handler for the output type.
 */
function paulus_form_output_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$value = (string) reset( $tag->values );

	if ( ! paulus_form_get_validation_error( $tag->name ) ) {
		$hangover = array_filter( (array) paulus_form_get_hangover( $tag->name ) );

		if ( $hangover ) {
			$value = (string) reset( $hangover );
		}
	}

	$html = sprintf(
		'<output %1$s>%2$s</output>',
		paulus_form_format_atts( array(
			'data-reflection-of' => $tag->name,
			'data-default' => $value,
			'name' => $tag->name,
			'class' => $tag->get_class_option(
				paulus_form_form_controls_class( $tag->type )
			),
			'id' => $tag->get_id_option(),
		) ),
		esc_html( $value )
	);

	return $html;
}
