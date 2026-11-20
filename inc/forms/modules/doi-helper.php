<?php
/**
 * Double Opt-In Helper module
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'paulus_form_doi', 'paulus_form_doihelper_start_session', 10, 3 );

/**
 * Starts a double opt-in session.
 */
function paulus_form_doihelper_start_session( $agent_name, $args, &$token ) {
	if ( isset( $token ) ) {
		return;
	}

	if ( ! function_exists( 'doihelper_start_session' ) ) {
		return;
	}

	$submission = Paulus_Form_Submission::get_instance();

	if ( ! $submission ) {
		return;
	}

	$contact_form = $submission->get_contact_form();

	$do_doi = apply_filters( 'paulus_form_do_doi',
		! $contact_form->is_false( 'doi' ),
		$agent_name,
		$args
	);

	if ( ! $do_doi ) {
		return;
	}

	$token = doihelper_start_session( $agent_name, $args );
}
