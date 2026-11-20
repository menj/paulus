<?php
/**
 * Submit button
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* form_tag handler */

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_submit', 10, 0 );

function paulus_form_add_form_tag_submit() {
	paulus_form_add_form_tag( 'submit', 'paulus_form_submit_form_tag_handler' );
}

function paulus_form_submit_form_tag_handler( $tag ) {
	$class = paulus_form_form_controls_class( $tag->type, 'has-spinner' );

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

	$value = isset( $tag->values[0] ) ? $tag->values[0] : '';

	if ( empty( $value ) ) {
		$value = __( 'Send', 'paulus' );
	}

	$atts['type'] = 'submit';
	$atts['value'] = $value;

	$atts = paulus_form_format_atts( $atts );

	$html = sprintf( '<input %1$s />', $atts );

	return $html;
}


/* Tag generator */

add_action( 'paulus_form_admin_init', 'paulus_form_add_tag_generator_submit', 55, 0 );

function paulus_form_add_tag_generator_submit() {
	$tag_generator = Paulus_Form_TagGenerator::get_instance();

	$tag_generator->add( 'submit', __( 'submit', 'paulus' ),
		'paulus_form_tag_generator_submit',
		array( 'version' => '2' )
	);
}

function paulus_form_tag_generator_submit( $contact_form, $options ) {
	$field_types = array(
		'submit' => array(
			'display_name' => __( 'Submit button', 'paulus' ),
			'heading' => __( 'Submit button form-tag generator', 'paulus' ),
			'description' => sprintf(
				/* translators: %s: URL to submit button doc */
				__( 'Generates a form-tag for a <a href="%s">submit button</a>.', 'paulus' ),
				'#'
			),
		),
	);

	$tgg = new Paulus_Form_TagGeneratorGenerator( $options['content'] );

	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'header', array(
		'class' => 'description-box',
	) );

	$formatter->append_start_tag( 'h3' );

	$formatter->append_preformatted(
		esc_html( $field_types['submit']['heading'] )
	);

	$formatter->end_tag( 'h3' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		wp_kses_data( $field_types['submit']['description'] )
	);

	$formatter->end_tag( 'header' );

	$formatter->append_start_tag( 'div', array(
		'class' => 'control-box',
	) );

	$formatter->call_user_func( static function () use ( $tgg, $field_types ) {
		$tgg->print( 'field_type', array(
			'select_options' => array(
				'submit' => $field_types['submit']['display_name'],
			),
		) );

		$tgg->print( 'class_attr' );

		$tgg->print( 'default_value', array(
			'title' => __( 'Label', 'paulus' ),
		) );
	} );

	$formatter->end_tag( 'div' );

	$formatter->append_start_tag( 'footer', array(
		'class' => 'insert-box',
	) );

	$formatter->call_user_func( static function () use ( $tgg, $field_types ) {
		$tgg->print( 'insert_box_content' );
	} );

	$formatter->print();
}
