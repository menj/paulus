<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
	'wp_ajax_paulus-form-sendinblue-contact-lists',
	'paulus_form_ajax_sendinblue_contact_lists',
	10, 0
);

/**
 * Ajax action to update the contact lists in the Brevo editor panel.
 */
function paulus_form_ajax_sendinblue_contact_lists() {
	$contact_form = paulus_form_contact_form( paulus_form_superglobal_get( 'id' ) );

	$prop = wp_parse_args(
		$contact_form ? $contact_form->prop( 'sendinblue' ) : array(),
		array(
			'enable_contact_list' => false,
			'contact_lists' => array(),
			'enable_transactional_email' => false,
			'email_template' => 0,
		)
	);

	paulus_form_sendinblue_editor_lists( $prop['contact_lists'] );

	wp_die();
}


add_action(
	'wp_ajax_paulus-form-sendinblue-email-template',
	'paulus_form_ajax_sendinblue_email_template',
	10, 0
);

/**
 * Ajax action to update the email templates in the Brevo editor panel.
 */
function paulus_form_ajax_sendinblue_email_template() {
	$contact_form = paulus_form_contact_form( paulus_form_superglobal_get( 'id' ) );

	$prop = wp_parse_args(
		$contact_form ? $contact_form->prop( 'sendinblue' ) : array(),
		array(
			'enable_contact_list' => false,
			'contact_lists' => array(),
			'enable_transactional_email' => false,
			'email_template' => 0,
		)
	);

	paulus_form_sendinblue_editor_templates( $prop['email_template'] );

	wp_die();
}
