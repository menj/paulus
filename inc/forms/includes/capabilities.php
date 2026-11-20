<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'map_meta_cap', 'paulus_form_map_meta_cap', 10, 4 );

function paulus_form_map_meta_cap( $caps, $cap, $user_id, $args ) {
	$meta_caps = array(
		'paulus_form_edit_contact_form' => PAULUS_FORM_ADMIN_READ_WRITE_CAPABILITY,
		'paulus_form_edit_contact_forms' => PAULUS_FORM_ADMIN_READ_WRITE_CAPABILITY,
		'paulus_form_read_contact_form' => PAULUS_FORM_ADMIN_READ_CAPABILITY,
		'paulus_form_read_contact_forms' => PAULUS_FORM_ADMIN_READ_CAPABILITY,
		'paulus_form_delete_contact_form' => PAULUS_FORM_ADMIN_READ_WRITE_CAPABILITY,
		'paulus_form_delete_contact_forms' => PAULUS_FORM_ADMIN_READ_WRITE_CAPABILITY,
		'paulus_form_manage_integration' => 'manage_options',
		'paulus_form_submit' => 'read',
	);

	$meta_caps = apply_filters( 'paulus_form_map_meta_cap', $meta_caps );

	$caps = array_diff( $caps, array_keys( $meta_caps ) );

	if ( isset( $meta_caps[$cap] ) ) {
		$caps[] = $meta_caps[$cap];
	}

	return $caps;
}
