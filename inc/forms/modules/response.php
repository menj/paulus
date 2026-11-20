<?php
/**
 * Response message box
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* form_tag handler */

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_response', 10, 0 );

function paulus_form_add_form_tag_response() {
	paulus_form_add_form_tag( 'response',
		'paulus_form_response_form_tag_handler',
		array(
			'display-block' => true,
		)
	);
}

function paulus_form_response_form_tag_handler( $tag ) {
	if ( $contact_form = paulus_form_get_current_contact_form() ) {
		return $contact_form->form_response_output();
	}
}
