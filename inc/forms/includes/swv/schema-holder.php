<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use PaulusFormLibs\FormDataTree\{ FormDataTree };

trait PAULUS_FORM_SWV_SchemaHolder {

	protected $schema;


	/**
	 * Retrieves SWV schema for this holder object (contact form).
	 *
	 * @return PAULUS_FORM_SWV_Schema The schema object.
	 */
	public function get_schema() {
		if ( isset( $this->schema ) ) {
			return $this->schema;
		}

		$schema = new PAULUS_FORM_SWV_Schema( array(
			'locale' => isset( $this->locale ) ? $this->locale : '',
		) );

		do_action( 'paulus_form_swv_create_schema', $schema, $this );

		return $this->schema = $schema;
	}


	/**
	 * Validates form inputs based on the schema and given context.
	 */
	public function validate_schema( $context, Paulus_Form_Validation $validity ) {
		$schema = $this->get_schema();

		$form_data = new FormDataTree( array(
			'post' => wp_unslash( $_POST ),
		) );

		$context = wp_parse_args( $context );

		foreach ( $schema->validate( $form_data, $context ) as $wp_error ) {
			if ( ! is_wp_error( $wp_error ) ) {
				continue;
			}

			$error = $wp_error->get_error_data();
			$rule = $error->rule;

			if ( isset( $rule->field ) and $validity->is_valid( $rule->field ) ) {
				$validity->invalidate( $rule->field, $wp_error );
			}
		}
	}

}
