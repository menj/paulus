<?php
/**
 * Number fields
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* form_tag handler */

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_number', 10, 0 );

function paulus_form_add_form_tag_number() {
	paulus_form_add_form_tag( array( 'number', 'number*', 'range', 'range*' ),
		'paulus_form_number_form_tag_handler',
		array(
			'name-attr' => true,
		)
	);
}

function paulus_form_number_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = paulus_form_get_validation_error( $tag->name );

	$class = paulus_form_form_controls_class( $tag->type );

	$class .= ' paulus-form-validates-as-number';

	if ( $validation_error ) {
		$class .= ' paulus-form-not-valid';
	}

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );
	$atts['min'] = $tag->get_option( 'min', 'signed_num', true );
	$atts['max'] = $tag->get_option( 'max', 'signed_num', true );
	$atts['step'] = $tag->get_option( 'step', 'num', true );
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

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' ) or $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = paulus_form_get_hangover( $tag->name, $value );

	$atts['value'] = $value;

	if ( 'range' === $tag->basetype ) {
		if ( ! paulus_form_is_number( $atts['min'] ) ) {
			$atts['min'] = '0';
		}

		if ( ! paulus_form_is_number( $atts['max'] ) ) {
			$atts['max'] = '100';
		}

		if ( '' === $atts['value'] ) {
			if ( $atts['min'] < $atts['max'] ) {
				$atts['value'] = ( $atts['min'] + $atts['max'] ) / 2;
			} else {
				$atts['value'] = $atts['min'];
			}
		}
	}

	$atts['type'] = $tag->basetype;
	$atts['name'] = $tag->name;

	$html = sprintf(
		'<span class="paulus-form-form-control-wrap" data-name="%1$s"><input %2$s />%3$s</span>',
		esc_attr( $tag->name ),
		paulus_form_format_atts( $atts ),
		$validation_error
	);

	return $html;
}


add_action(
	'paulus_form_swv_create_schema',
	'paulus_form_swv_add_number_rules',
	10, 2
);

function paulus_form_swv_add_number_rules( $schema, $contact_form ) {
	$tags = $contact_form->scan_form_tags( array(
		'basetype' => array( 'number', 'range' ),
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

		$schema->addRule(
			paulus_form_swv_create_rule( 'number', array(
				'field' => $tag->name,
				'error' => paulus_form_get_message( 'invalid_number' ),
			) )
		);

		$min = $tag->get_option( 'min', 'signed_num', true );
		$max = $tag->get_option( 'max', 'signed_num', true );

		if ( 'range' === $tag->basetype ) {
			if ( ! paulus_form_is_number( $min ) ) {
				$min = '0';
			}

			if ( ! paulus_form_is_number( $max ) ) {
				$max = '100';
			}
		}

		if ( paulus_form_is_number( $min ) ) {
			$schema->addRule(
				paulus_form_swv_create_rule( 'minnumber', array(
					'field' => $tag->name,
					'threshold' => $min,
					'error' => paulus_form_get_message( 'number_too_small' ),
				) )
			);
		}

		if ( paulus_form_is_number( $max ) ) {
			$schema->addRule(
				paulus_form_swv_create_rule( 'maxnumber', array(
					'field' => $tag->name,
					'threshold' => $max,
					'error' => paulus_form_get_message( 'number_too_large' ),
				) )
			);
		}
	}
}


/* Messages */

add_filter( 'paulus_form_messages', 'paulus_form_number_messages', 10, 1 );

function paulus_form_number_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_number' => array(
			'description' => __( 'Number format that the sender entered is invalid', 'paulus' ),
			'default' => __( 'Please enter a number.', 'paulus' ),
		),

		'number_too_small' => array(
			'description' => __( 'Number is smaller than minimum limit', 'paulus' ),
			'default' => __( 'This field has a too small number.', 'paulus' ),
		),

		'number_too_large' => array(
			'description' => __( 'Number is larger than maximum limit', 'paulus' ),
			'default' => __( 'This field has a too large number.', 'paulus' ),
		),
	) );
}


/* Tag generator */

add_action( 'paulus_form_admin_init', 'paulus_form_add_tag_generator_number', 18, 0 );

function paulus_form_add_tag_generator_number() {
	$tag_generator = Paulus_Form_TagGenerator::get_instance();

	$tag_generator->add( 'number', __( 'number', 'paulus' ),
		'paulus_form_tag_generator_number',
		array( 'version' => '2' )
	);
}

function paulus_form_tag_generator_number( $contact_form, $options ) {
	$field_types = array(
		'number' => array(
			'display_name' => __( 'Number field', 'paulus' ),
			'heading' => __( 'Number field form-tag generator', 'paulus' ),
			'description' => sprintf(
				/* translators: %s: URL to number field doc */
				__( 'Generates a form-tag for a <a href="%s">number input field</a>.', 'paulus' ),
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
		esc_html( $field_types['number']['heading'] )
	);

	$formatter->end_tag( 'h3' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		wp_kses_data( $field_types['number']['description'] )
	);

	$formatter->end_tag( 'header' );

	$formatter->append_start_tag( 'div', array(
		'class' => 'control-box',
	) );

	$formatter->call_user_func( static function () use ( $tgg, $field_types ) {
		$tgg->print( 'field_type', array(
			'with_required' => true,
			'select_options' => array(
				'number' => __( 'Spinbox', 'paulus' ),
				'range' => __( 'Slider', 'paulus' ),
			),
		) );

		$tgg->print( 'field_name' );

		$tgg->print( 'class_attr' );

		$tgg->print( 'min_max', array(
			'title' => __( 'Range', 'paulus' ),
			'min_option' => 'min:',
			'max_option' => 'max:',
		) );

		$tgg->print( 'default_value', array(
			'type' => 'number',
			'with_placeholder' => false,
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
