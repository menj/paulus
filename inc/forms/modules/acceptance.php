<?php
/**
 * Acceptance checkbox
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* form_tag handler */

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_acceptance', 10, 0 );

function paulus_form_add_form_tag_acceptance() {
	paulus_form_add_form_tag( 'acceptance',
		'paulus_form_acceptance_form_tag_handler',
		array(
			'name-attr' => true,
			'selectable-values' => true,
		)
	);
}

function paulus_form_acceptance_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = paulus_form_get_validation_error( $tag->name );

	$class = paulus_form_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' paulus-form-not-valid';
	}

	if ( $tag->has_option( 'invert' ) ) {
		$class .= ' invert';
	}

	if ( $tag->has_option( 'optional' ) ) {
		$class .= ' optional';
	}

	$atts = array(
		'class' => trim( $class ),
	);

	$item_atts = array(
		'type' => 'checkbox',
		'name' => $tag->name,
		'value' => '1',
		'tabindex' => $tag->get_option( 'tabindex', 'signed_int', true ),
		'checked' => $tag->has_option( 'default:on' ),
		'class' => $tag->get_class_option() ? $tag->get_class_option() : null,
		'id' => $tag->get_id_option(),
	);

	if ( $validation_error ) {
		$item_atts['aria-invalid'] = 'true';
		$item_atts['aria-describedby'] = paulus_form_get_validation_error_reference(
			$tag->name
		);
	} else {
		$item_atts['aria-invalid'] = 'false';
	}

	$item_atts = paulus_form_format_atts( $item_atts );

	$content = empty( $tag->content )
		? (string) reset( $tag->values )
		: $tag->content;

	$content = trim( $content );

	if ( $content ) {
		if ( $tag->has_option( 'label_first' ) ) {
			$html = sprintf(
				'<span class="paulus-form-list-item-label">%2$s</span><input %1$s />',
				$item_atts,
				$content
			);
		} else {
			$html = sprintf(
				'<input %1$s /><span class="paulus-form-list-item-label">%2$s</span>',
				$item_atts,
				$content
			);
		}

		$html = sprintf(
			'<span class="paulus-form-list-item"><label>%s</label></span>',
			$html
		);

	} else {
		$html = sprintf(
			'<span class="paulus-form-list-item"><input %1$s /></span>',
			$item_atts
		);
	}

	$html = sprintf(
		'<span class="paulus-form-form-control-wrap" data-name="%1$s"><span %2$s>%3$s</span>%4$s</span>',
		esc_attr( $tag->name ),
		paulus_form_format_atts( $atts ),
		$html,
		$validation_error
	);

	return $html;
}


/* Validation filter */

add_filter(
	'paulus_form_validate_acceptance',
	'paulus_form_acceptance_validation_filter',
	10, 2
);

function paulus_form_acceptance_validation_filter( $result, $tag ) {
	if ( ! paulus_form_acceptance_as_validation() ) {
		return $result;
	}

	if ( $tag->has_option( 'optional' ) ) {
		return $result;
	}

	$value = paulus_form_superglobal_post( $tag->name ) ? 1 : 0;

	$invert = $tag->has_option( 'invert' );

	if (
		$invert and $value or
		! $invert and ! $value
	) {
		$result->invalidate( $tag, paulus_form_get_message( 'accept_terms' ) );
	}

	return $result;
}


/* Acceptance filter */

add_filter( 'paulus_form_acceptance', 'paulus_form_acceptance_filter', 10, 2 );

function paulus_form_acceptance_filter( $accepted, $submission ) {
	$tags = paulus_form_scan_form_tags( array( 'type' => 'acceptance' ) );

	foreach ( $tags as $tag ) {
		if ( empty( $tag->name ) ) {
			continue;
		}

		$value = paulus_form_superglobal_post( $tag->name ) ? 1 : 0;

		$content = empty( $tag->content )
			? (string) reset( $tag->values )
			: $tag->content;

		$content = trim( $content );

		if ( $value and $content ) {
			$submission->add_consent( $tag->name, $content );
		}

		if ( $tag->has_option( 'optional' ) ) {
			continue;
		}

		$invert = $tag->has_option( 'invert' );

		if (
			$invert and $value or
			! $invert and ! $value
		) {
			$accepted = false;
		}
	}

	return $accepted;
}


add_filter(
	'paulus_form_form_class_attr',
	'paulus_form_acceptance_form_class_attr',
	10, 1
);

function paulus_form_acceptance_form_class_attr( $class_attr ) {
	if ( paulus_form_acceptance_as_validation() ) {
		return $class_attr . ' paulus-form-acceptance-as-validation';
	}

	return $class_attr;
}

function paulus_form_acceptance_as_validation() {
	if ( ! $contact_form = paulus_form_get_current_contact_form() ) {
		return false;
	}

	return $contact_form->is_true( 'acceptance_as_validation' );
}


add_filter(
	'paulus_form_mail_tag_replaced_acceptance',
	'paulus_form_acceptance_mail_tag',
	10, 4
);

function paulus_form_acceptance_mail_tag( $replaced, $submitted, $html, $mail_tag ) {
	$form_tag = $mail_tag->corresponding_form_tag();

	if ( ! $form_tag ) {
		return $replaced;
	}

	if ( ! empty( $submitted ) ) {
		$replaced = __( 'Consented', 'paulus' );
	} else {
		$replaced = __( 'Not consented', 'paulus' );
	}

	$content = empty( $form_tag->content )
		? (string) reset( $form_tag->values )
		: $form_tag->content;

	if ( ! $html ) {
		$content = wp_strip_all_tags( $content );
	}

	$content = trim( $content );

	if ( $content ) {
		$replaced = sprintf(
			/* translators: 1: 'Consented' or 'Not consented', 2: conditions */
			_x( '%1$s: %2$s', 'mail output for acceptance checkboxes', 'paulus' ),
			$replaced,
			$content
		);
	}

	return $replaced;
}


/* Tag generator */

add_action( 'paulus_form_admin_init', 'paulus_form_add_tag_generator_acceptance', 35, 0 );

function paulus_form_add_tag_generator_acceptance() {
	$tag_generator = Paulus_Form_TagGenerator::get_instance();

	$tag_generator->add( 'acceptance', __( 'acceptance', 'paulus' ),
		'paulus_form_tag_generator_acceptance',
		array( 'version' => '2' )
	);
}

function paulus_form_tag_generator_acceptance( $contact_form, $options ) {
	$field_types = array(
		'acceptance' => array(
			'display_name' => __( 'Acceptance checkbox', 'paulus' ),
			'heading' => __( 'Acceptance checkbox form-tag generator', 'paulus' ),
			'description' => sprintf(
				/* translators: %s: URL to acceptance checkbox doc */
				__( 'Generates a form-tag for an <a href="%s">acceptance checkbox</a>.', 'paulus' ),
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
		esc_html( $field_types['acceptance']['heading'] )
	);

	$formatter->end_tag( 'h3' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		wp_kses_data( $field_types['acceptance']['description'] )
	);

	$formatter->end_tag( 'header' );

	$formatter->append_start_tag( 'div', array(
		'class' => 'control-box',
	) );

	$formatter->call_user_func( static function () use ( $tgg, $field_types ) {
		$tgg->print( 'field_type', array(
			'with_optional' => true,
			'select_options' => array(
				'acceptance' => $field_types['acceptance']['display_name'],
			),
		) );

		$tgg->print( 'field_name' );

		$tgg->print( 'class_attr' );
	} );

	$formatter->append_start_tag( 'fieldset' );

	$formatter->append_start_tag( 'legend', array(
		'id' => $tgg->ref( 'value-legend' ),
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Condition', 'paulus' ) )
	);

	$formatter->end_tag( 'legend' );

	$formatter->append_start_tag( 'input', array(
		'type' => 'text',
		'required' => true,
		'value' => __( 'Put the condition for consent here.', 'paulus' ),
		'data-tag-part' => 'content',
		'aria-labelledby' => $tgg->ref( 'value-legend' ),
	) );

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
