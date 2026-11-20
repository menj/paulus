<?php
/**
 * Textarea
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* form_tag handler */

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_textarea', 10, 0 );

function paulus_form_add_form_tag_textarea() {
	paulus_form_add_form_tag( array( 'textarea', 'textarea*' ),
		'paulus_form_textarea_form_tag_handler', array( 'name-attr' => true )
	);
}

function paulus_form_textarea_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = paulus_form_get_validation_error( $tag->name );

	$class = paulus_form_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' paulus-form-not-valid';
	}

	$atts = array();

	$atts['cols'] = $tag->get_cols_option( '40' );
	$atts['rows'] = $tag->get_rows_option( '10' );
	$atts['maxlength'] = $tag->get_maxlength_option( '2000' );
	$atts['minlength'] = $tag->get_minlength_option();

	if (
		$atts['maxlength'] and $atts['minlength'] and
		$atts['maxlength'] < $atts['minlength']
	) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );
	$atts['readonly'] = $tag->has_option( 'readonly' );
	$atts['autocomplete'] = $tag->get_autocomplete_option();

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	if ( $validation_error ) {
		$atts['aria-invalid'] = 'true';
		$atts['aria-describedby'] = paulus_form_get_validation_error_reference(
			$tag->name
		);
	} else {
		$atts['aria-invalid'] = 'false';
	}

	$value = empty( $tag->content )
		? (string) reset( $tag->values )
		: $tag->content;

	if ( $tag->has_option( 'placeholder' ) or $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = paulus_form_get_hangover( $tag->name, $value );

	$atts['name'] = $tag->name;

	$html = sprintf(
		'<span class="paulus-form-form-control-wrap" data-name="%1$s"><textarea %2$s>%3$s</textarea>%4$s</span>',
		esc_attr( $tag->name ),
		paulus_form_format_atts( $atts ),
		esc_textarea( $value ),
		$validation_error
	);

	return $html;
}


add_action(
	'paulus_form_swv_create_schema',
	'paulus_form_swv_add_textarea_rules',
	10, 2
);

function paulus_form_swv_add_textarea_rules( $schema, $contact_form ) {
	$tags = $contact_form->scan_form_tags( array(
		'basetype' => array( 'textarea' ),
	) );

	foreach ( $tags as $tag ) {
		if ( $tag->is_required() ) {
			$schema->addRule(
				paulus_form_swv_create_rule( 'required', array(
					'field' => $tag->name,
					'error' => paulus_form_get_message( 'invalid_required' ),
				) )
			);
		}

		if ( $minlength = $tag->get_minlength_option() ) {
			$schema->addRule(
				paulus_form_swv_create_rule( 'minlength', array(
					'field' => $tag->name,
					'threshold' => absint( $minlength ),
					'error' => paulus_form_get_message( 'invalid_too_short' ),
				) )
			);
		}

		if ( $maxlength = $tag->get_maxlength_option( '2000' ) ) {
			$schema->addRule(
				paulus_form_swv_create_rule( 'maxlength', array(
					'field' => $tag->name,
					'threshold' => absint( $maxlength ),
					'error' => paulus_form_get_message( 'invalid_too_long' ),
				) )
			);
		}
	}
}


/* Tag generator */

add_action( 'paulus_form_admin_init', 'paulus_form_add_tag_generator_textarea', 20, 0 );

function paulus_form_add_tag_generator_textarea() {
	$tag_generator = Paulus_Form_TagGenerator::get_instance();

	$tag_generator->add( 'textarea',
		__( 'text area', 'paulus' ),
		'paulus_form_tag_generator_textarea',
		array( 'version' => '2' )
	);
}

function paulus_form_tag_generator_textarea( $contact_form, $options ) {
	$field_types = array(
		'textarea' => array(
			'display_name' => __( 'Text area', 'paulus' ),
			'heading' => __( 'Text area form-tag generator', 'paulus' ),
			'description' => sprintf(
				/* translators: %s: URL to text input area doc */
				__( 'Generates a form-tag for a <a href="%s">multi-line plain text input area</a>.', 'paulus' ),
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
		esc_html( $field_types['textarea']['heading'] )
	);

	$formatter->end_tag( 'h3' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		wp_kses_data( $field_types['textarea']['description'] )
	);

	$formatter->end_tag( 'header' );

	$formatter->append_start_tag( 'div', array(
		'class' => 'control-box',
	) );

	$formatter->call_user_func( static function () use ( $tgg, $field_types ) {
		$tgg->print( 'field_type', array(
			'with_required' => true,
			'select_options' => array(
				'textarea' => $field_types['textarea']['display_name'],
			),
		) );

		$tgg->print( 'field_name' );

		$tgg->print( 'class_attr' );

		$tgg->print( 'min_max', array(
			'title' => __( 'Length', 'paulus' ),
			'min_option' => 'minlength:',
			'max_option' => 'maxlength:',
		) );

		$tgg->print( 'default_value', array(
			'with_placeholder' => true,
			'use_content' => true,
		) );
	} );

	$formatter->end_tag( 'div' );

	$formatter->append_start_tag( 'footer', array(
		'class' => 'insert-box',
	) );

	$formatter->call_user_func( static function () use ( $tgg, $field_types ) {
		$tgg->print( 'insert_box_content' );

		$tgg->print( 'mail_tag_tip' );
	} );

	$formatter->print();
}
