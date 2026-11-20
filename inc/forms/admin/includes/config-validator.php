<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'paulus_form_admin_menu', 'paulus_form_admin_init_bulk_cv', 10, 0 );

function paulus_form_admin_init_bulk_cv() {
	if (
		! paulus_form_validate_configuration() or
		! current_user_can( 'paulus_form_edit_contact_forms' )
	) {
		return;
	}

	$result = Paulus_Form::get_option( 'bulk_validate' );
	$last_important_update = Paulus_Form_ConfigValidator::last_important_update;

	if (
		! empty( $result['version'] ) and
		version_compare( $last_important_update, $result['version'], '<=' )
	) {
		return;
	}

	add_filter( 'paulus_form_admin_menu_change_notice',
		'paulus_form_admin_menu_change_notice_bulk_cv',
		10, 1
	);

	add_action( 'paulus_form_admin_warnings',
		'paulus_form_admin_warnings_bulk_cv',
		5, 3
	);
}

function paulus_form_admin_menu_change_notice_bulk_cv( $counts ) {
	$counts['paulus-form'] += 1;
	return $counts;
}

function paulus_form_admin_warnings_bulk_cv( $page, $action, $object ) {
	if ( 'paulus-form' === $page and 'validate' === $action ) {
		return;
	}

	wp_admin_notice(
		sprintf(
			'%1$s &raquo; %2$s',
			__( 'Misconfiguration leads to mail delivery failure or other troubles. Validate your contact forms now.', 'paulus' ),
			paulus_form_link(
				add_query_arg(
					array( 'action' => 'validate' ),
					menu_page_url( 'paulus-form', false )
				),
				__( 'Validate Forms Configuration', 'paulus' )
			)
		),
		array( 'type' => 'warning' )
	);
}

add_action( 'paulus_form_admin_load', 'paulus_form_load_bulk_validate_page', 10, 2 );

function paulus_form_load_bulk_validate_page( $page, $action ) {
	if (
		'paulus-form' !== $page or
		'validate' !== $action or
		! paulus_form_validate_configuration() or
		'POST' !== paulus_form_superglobal_server( 'REQUEST_METHOD' )
	) {
		return;
	}

	check_admin_referer( 'paulus-form-bulk-validate' );

	if ( ! current_user_can( 'paulus_form_edit_contact_forms' ) ) {
		wp_die( wp_kses_data( __( 'You are not allowed to validate configuration.', 'paulus' ) ) );
	}

	$contact_forms = Paulus_Form_ContactForm::find();

	$result = array(
		'timestamp' => time(),
		'version' => PAULUS_FORM_VERSION,
		'count_valid' => 0,
		'count_invalid' => 0,
	);

	foreach ( $contact_forms as $contact_form ) {
		$config_validator = new Paulus_Form_ConfigValidator( $contact_form );
		$config_validator->validate();
		$config_validator->save();

		if ( $config_validator->is_valid() ) {
			$result['count_valid'] += 1;
		} else {
			$result['count_invalid'] += 1;
		}
	}

	Paulus_Form::update_option( 'bulk_validate', $result );

	$redirect_to = add_query_arg(
		array(
			'message' => 'validated',
		),
		menu_page_url( 'paulus-form', false )
	);

	wp_safe_redirect( $redirect_to );
	exit();
}

function paulus_form_admin_bulk_validate_page() {
	$contact_forms = Paulus_Form_ContactForm::find();
	$count = Paulus_Form_ContactForm::count();

	$submit_text = sprintf(
		/* translators: %s: number of contact forms */
		_n(
			'Validate %s contact form now',
			'Validate %s contact forms now',
			$count, 'paulus'
		),
		number_format_i18n( $count )
	);

	$formatter = new PAULUS_FORM_HTMLFormatter( array(
		'allowed_html' => array_merge( paulus_form_kses_allowed_html(), array(
			'form' => array(
				'action' => true,
				'method' => true,
			),
		) ),
	) );

	$formatter->append_start_tag( 'div', array(
		'class' => 'wrap',
	) );

	$formatter->append_start_tag( 'h1' );

	$formatter->append_preformatted(
		esc_html( __( 'Validate Configuration', 'paulus' ) )
	);

	$formatter->end_tag( 'h1' );

	$formatter->append_start_tag( 'form', array(
		'method' => 'post',
		'action' => '',
	) );

	$formatter->append_start_tag( 'p' );

	$formatter->call_user_func( static function () {
		wp_nonce_field( 'paulus-form-bulk-validate' );
	} );

	$formatter->append_start_tag( 'input', array(
		'type' => 'hidden',
		'name' => 'action',
		'value' => 'validate',
	) );

	$formatter->append_start_tag( 'input', array(
		'type' => 'submit',
		'class' => 'button',
		'value' => $submit_text,
	) );

	$formatter->end_tag( 'form' );

	$formatter->append_preformatted(
		paulus_form_link(
			'#',
			__( 'FAQ about Configuration Validator', 'paulus' )
		)
	);

	$formatter->print();
}
