<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
	'paulus_form_update_option',
	'paulus_form_config_validator_update_option',
	10, 3
);

/**
 * Runs bulk validation after the reCAPTCHA integration option is updated.
 */
function paulus_form_config_validator_update_option( $name, $value, $old_option ) {
	if ( 'recaptcha' === $name ) {
		$contact_forms = Paulus_Form_ContactForm::find();

		$options = array(
			'include' => 'unsafe_email_without_protection',
		);

		foreach ( $contact_forms as $contact_form ) {
			$config_validator = new Paulus_Form_ConfigValidator( $contact_form, $options );
			$config_validator->restore();
			$config_validator->validate();
			$config_validator->save();
		}
	}
}
