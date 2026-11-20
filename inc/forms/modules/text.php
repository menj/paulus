<?php
/**
 * Text fields
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* form_tag handler */

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_text', 10, 0 );

function paulus_form_add_form_tag_text() {
	paulus_form_add_form_tag(
		array( 'text', 'text*', 'email', 'email*', 'url', 'url*', 'tel', 'tel*' ),
		'paulus_form_text_form_tag_handler',
		array(
			'name-attr' => true,
		)
	);
}

function paulus_form_text_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = paulus_form_get_validation_error( $tag->name );

	$class = paulus_form_form_controls_class( $tag->type, 'paulus-form-text' );

	if ( in_array( $tag->basetype, array( 'email', 'url', 'tel' ), true ) ) {
		$class .= ' paulus-form-validates-as-' . $tag->basetype;
	}

	if ( $validation_error ) {
		$class .= ' paulus-form-not-valid';
	}

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['maxlength'] = $tag->get_maxlength_option( '400' );
	$atts['minlength'] = $tag->get_minlength_option();

	if (
		$atts['maxlength'] and $atts['minlength'] and
		$atts['maxlength'] < $atts['minlength']
	) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['list'] = $tag->get_option( 'list', 'id', true );
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

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' ) or $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = paulus_form_get_hangover( $tag->name, $value );

	$atts['value'] = $value;
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
	'paulus_form_swv_add_text_rules',
	10, 2
);

function paulus_form_swv_add_text_rules( $schema, $contact_form ) {
	$tags = $contact_form->scan_form_tags( array(
		'basetype' => array( 'text', 'email', 'url', 'tel' ),
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

		if ( 'email' === $tag->basetype ) {
			$schema->addRule(
				paulus_form_swv_create_rule( 'email', array(
					'field' => $tag->name,
					'error' => paulus_form_get_message( 'invalid_email' ),
				) )
			);
		}

		if ( 'url' === $tag->basetype ) {
			$schema->addRule(
				paulus_form_swv_create_rule( 'url', array(
					'field' => $tag->name,
					'error' => paulus_form_get_message( 'invalid_url' ),
				) )
			);
		}

		if ( 'tel' === $tag->basetype ) {
			$schema->addRule(
				paulus_form_swv_create_rule( 'tel', array(
					'field' => $tag->name,
					'error' => paulus_form_get_message( 'invalid_tel' ),
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

		if ( $maxlength = $tag->get_maxlength_option( '400' ) ) {
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


/* Messages */

add_filter( 'paulus_form_messages', 'paulus_form_text_messages', 10, 1 );

function paulus_form_text_messages( $messages ) {
	$messages = array_merge( $messages, array(
		'invalid_email' => array(
			'description' =>
				__( 'Email address that the sender entered is invalid', 'paulus' ),
			'default' =>
				__( 'Please enter an email address.', 'paulus' ),
		),

		'invalid_url' => array(
			'description' =>
				__( 'URL that the sender entered is invalid', 'paulus' ),
			'default' =>
				__( 'Please enter a URL.', 'paulus' ),
		),

		'invalid_tel' => array(
			'description' =>
				__( 'Telephone number that the sender entered is invalid', 'paulus' ),
			'default' =>
				__( 'Please enter a telephone number.', 'paulus' ),
		),
	) );

	return $messages;
}


/* Tag generator */

add_action( 'paulus_form_admin_init', 'paulus_form_add_tag_generator_text', 15, 0 );

function paulus_form_add_tag_generator_text() {
	$tag_generator = Paulus_Form_TagGenerator::get_instance();

	$basetypes = array(
		'text' => __( 'text', 'paulus' ),
		'email' => __( 'email', 'paulus' ),
		'url' => __( 'URL', 'paulus' ),
		'tel' => __( 'tel', 'paulus' ),
	);

	foreach ( $basetypes as $id => $title ) {
		$tag_generator->add( $id, $title,
			'paulus_form_tag_generator_text',
			array( 'version' => '2' )
		);
	}
}

function paulus_form_tag_generator_text( $contact_form, $options ) {
	$field_types = array(
		'text' => array(
			'display_name' => __( 'Text field', 'paulus' ),
			'heading' => __( 'Text field form-tag generator', 'paulus' ),
			'description' => sprintf(
				/* translators: %s: URL to text fields doc */
				__( 'Generates a form-tag for a <a href="%s">single-line plain text input field</a>.', 'paulus' ),
				'#'
			),
			'maybe_purpose' => 'author_name',
		),
		'email' => array(
			'display_name' => __( 'Email address field', 'paulus' ),
			'heading' => __( 'Email address field form-tag generator', 'paulus' ),
			'description' => sprintf(
				/* translators: %s: URL to text fields doc */
				__( 'Generates a form-tag for an <a href="%s">email address input field</a>.', 'paulus' ),
				'#'
			),
			'maybe_purpose' => 'author_email',
		),
		'url' => array(
			'display_name' => __( 'URL field', 'paulus' ),
			'heading' => __( 'URL field form-tag generator', 'paulus' ),
			'description' => sprintf(
				/* translators: %s: URL to text fields doc */
				__( 'Generates a form-tag for a <a href="%s">URL input field</a>.', 'paulus' ),
				'#'
			),
			'maybe_purpose' => 'author_url',
		),
		'tel' => array(
			'display_name' => __( 'Telephone number field', 'paulus' ),
			'heading' => __( 'Telephone number field form-tag generator', 'paulus' ),
			'description' => sprintf(
				/* translators: %s: URL to text fields doc */
				__( 'Generates a form-tag for a <a href="%s">telephone number input field</a>.', 'paulus' ),
				'#'
			),
			'maybe_purpose' => 'author_tel',
		),
	);

	$basetype = $options['id'];

	if ( ! in_array( $basetype, array_keys( $field_types ), true ) ) {
		$basetype = 'text';
	}

	$tgg = new Paulus_Form_TagGeneratorGenerator( $options['content'] );

	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'header', array(
		'class' => 'description-box',
	) );

	$formatter->append_start_tag( 'h3' );

	$formatter->append_preformatted(
		esc_html( $field_types[$basetype]['heading'] )
	);

	$formatter->end_tag( 'h3' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		wp_kses_data( $field_types[$basetype]['description'] )
	);

	$formatter->end_tag( 'header' );

	$formatter->append_start_tag( 'div', array(
		'class' => 'control-box',
	) );

	$formatter->call_user_func( static function () use ( $tgg, $field_types, $basetype ) {
		$tgg->print( 'field_type', array(
			'with_required' => true,
			'select_options' => array(
				$basetype => $field_types[$basetype]['display_name'],
			),
		) );

		$tgg->print( 'field_name', array(
			'ask_if' => $field_types[$basetype]['maybe_purpose']
		) );

		$tgg->print( 'class_attr' );

		$tgg->print( 'min_max', array(
			'title' => __( 'Length', 'paulus' ),
			'min_option' => 'minlength:',
			'max_option' => 'maxlength:',
		) );

		$tgg->print( 'default_value', array(
			'with_placeholder' => true,
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
