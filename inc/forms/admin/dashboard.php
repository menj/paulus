<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$formatter = new PAULUS_FORM_HTMLFormatter();

$formatter->append_start_tag( 'div', array(
	'class' => 'wrap',
) );

$formatter->append_start_tag( 'h1' );

$formatter->append_preformatted(
	esc_html( __( 'Forms Dashboard', 'paulus' ) )
);

$formatter->end_tag( 'h1' );

$formatter->call_user_func( static function () {
	do_action( 'paulus_form_admin_warnings',
		'paulus-form-dashboard',
		paulus_form_current_action(),
		null
	);

	do_action( 'paulus_form_admin_notices',
		'paulus-form-dashboard',
		paulus_form_current_action(),
		null
	);
} );

$formatter->append_start_tag( 'div', array(
	'id' => 'dashboard-widgets-wrap',
) );

$formatter->call_user_func( 'wp_dashboard' );

$formatter->print();
