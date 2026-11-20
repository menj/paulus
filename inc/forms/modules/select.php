<?php
/**
 * Drop-down menu
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* form_tag handler */

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_select', 10, 0 );

function paulus_form_add_form_tag_select() {
	paulus_form_add_form_tag( array( 'select', 'select*' ),
		'paulus_form_select_form_tag_handler',
		array(
			'name-attr' => true,
			'selectable-values' => true,
		)
	);
}

function paulus_form_select_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = paulus_form_get_validation_error( $tag->name );

	$class = paulus_form_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' paulus-form-not-valid';
	}

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );
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

	$multiple = $tag->has_option( 'multiple' );
	$include_blank = $tag->has_option( 'include_blank' );
	$first_as_label = $tag->has_option( 'first_as_label' );

	if ( $tag->has_option( 'size' ) ) {
		$size = $tag->get_option( 'size', 'int', true );

		if ( $size ) {
			$atts['size'] = $size;
		} elseif ( $multiple ) {
			$atts['size'] = 4;
		} else {
			$atts['size'] = 1;
		}
	}

	if ( $data = (array) $tag->get_data_option() ) {
		$tag->values = array_merge( $tag->values, array_values( $data ) );
		$tag->labels = array_merge( $tag->labels, array_values( $data ) );
	}

	$values = $tag->values;
	$labels = $tag->labels;

	$default_choice = $tag->get_default_option( null, array(
		'multiple' => $multiple,
	) );

	if ( $include_blank or empty( $values ) ) {
		array_unshift(
			$labels,
			__( '&#8212;Please choose an option&#8212;', 'paulus' )
		);
		array_unshift( $values, '' );
	} elseif ( $first_as_label ) {
		$values[0] = '';
	}

	$html = '';
	$hangover = paulus_form_get_hangover( $tag->name );

	foreach ( $values as $key => $value ) {
		if ( $hangover ) {
			$selected = in_array( $value, (array) $hangover, true );
		} else {
			$selected = in_array( $value, (array) $default_choice, true );
		}

		$item_atts = array(
			'value' => $value,
			'selected' => $selected,
		);

		$label = isset( $labels[$key] ) ? $labels[$key] : $value;

		$html .= sprintf(
			'<option %1$s>%2$s</option>',
			paulus_form_format_atts( $item_atts ),
			esc_html( $label )
		);
	}

	$atts['multiple'] = (bool) $multiple;
	$atts['name'] = $tag->name . ( $multiple ? '[]' : '' );

	$html = sprintf(
		'<span class="paulus-form-form-control-wrap" data-name="%1$s"><select %2$s>%3$s</select>%4$s</span>',
		esc_attr( $tag->name ),
		paulus_form_format_atts( $atts ),
		$html,
		$validation_error
	);

	return $html;
}


add_action(
	'paulus_form_swv_create_schema',
	'paulus_form_swv_add_select_rules',
	10, 2
);

function paulus_form_swv_add_select_rules( $schema, $contact_form ) {
	$tags = $contact_form->scan_form_tags( array(
		'type' => array( 'select*' ),
	) );

	foreach ( $tags as $tag ) {
		$schema->addRule(
			paulus_form_swv_create_rule( 'required', array(
				'field' => $tag->name,
				'error' => paulus_form_get_message( 'invalid_required' ),
			) )
		);
	}
}


add_action(
	'paulus_form_swv_create_schema',
	'paulus_form_swv_add_select_enum_rules',
	20, 2
);

function paulus_form_swv_add_select_enum_rules( $schema, $contact_form ) {
	$tags = $contact_form->scan_form_tags( array(
		'basetype' => array( 'select' ),
	) );

	$values = array_reduce(
		$tags,
		function ( $values, $tag ) {
			if ( ! isset( $values[$tag->name] ) ) {
				$values[$tag->name] = array();
			}

			$tag_values = array_merge(
				(array) $tag->values,
				(array) $tag->get_data_option()
			);

			if ( $tag->has_option( 'first_as_label' ) ) {
				$tag_values = array_slice( $tag_values, 1 );
			}

			$values[$tag->name] = array_merge(
				$values[$tag->name],
				$tag_values
			);

			return $values;
		},
		array()
	);

	foreach ( $values as $field => $field_values ) {
		$field_values = array_map(
			static function ( $value ) {
				return html_entity_decode(
					(string) $value,
					ENT_QUOTES | ENT_HTML5,
					'UTF-8'
				);
			},
			$field_values
		);

		$field_values = array_filter(
			array_unique( $field_values ),
			static function ( $value ) {
				return '' !== $value;
			}
		);

		$schema->addRule(
			paulus_form_swv_create_rule( 'enum', array(
				'field' => $field,
				'accept' => array_values( $field_values ),
				'error' => $contact_form->filter_message(
					__( 'Undefined value was submitted through this field.', 'paulus' )
				),
			) )
		);
	}
}


/* Tag generator */

add_action( 'paulus_form_admin_init', 'paulus_form_add_tag_generator_menu', 25, 0 );

function paulus_form_add_tag_generator_menu() {
	$tag_generator = Paulus_Form_TagGenerator::get_instance();

	$tag_generator->add( 'menu', __( 'drop-down menu', 'paulus' ),
		'paulus_form_tag_generator_menu',
		array( 'version' => '2' )
	);
}

function paulus_form_tag_generator_menu( $contact_form, $options ) {
	$field_types = array(
		'select' => array(
			'display_name' => __( 'Drop-down menu', 'paulus' ),
			'heading' => __( 'Drop-down menu form-tag generator', 'paulus' ),
			'description' => sprintf(
				/* translators: %s: URL to drop-down menu doc */
				__( 'Generates a form-tag for a <a href="%s">drop-down menu</a>.', 'paulus' ),
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
		esc_html( $field_types['select']['heading'] )
	);

	$formatter->end_tag( 'h3' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		wp_kses_data( $field_types['select']['description'] )
	);

	$formatter->end_tag( 'header' );

	$formatter->append_start_tag( 'div', array(
		'class' => 'control-box',
	) );

	$formatter->call_user_func( static function () use ( $tgg, $field_types ) {
		$tgg->print( 'field_type', array(
			'with_required' => true,
			'select_options' => array(
				'select' => $field_types['select']['display_name'],
			),
		) );

		$tgg->print( 'field_name' );

		$tgg->print( 'class_attr' );

		$tgg->print( 'selectable_values', array(
			'first_as_label' => true,
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
