<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function paulus_form_current_action() {
	foreach ( array( 'action', 'action2' ) as $var ) {
		$action = paulus_form_superglobal_request( $var, null );

		if ( isset( $action ) and -1 !== $action ) {
			return $action;
		}
	}

	return false;
}

function paulus_form_admin_has_edit_cap() {
	return current_user_can( 'paulus_form_edit_contact_forms' );
}

function paulus_form_add_tag_generator( $name, $title, $elm_id, $callback, $options = array() ) {
	$tag_generator = Paulus_Form_TagGenerator::get_instance();
	return $tag_generator->add( $name, $title, $callback, $options );
}
