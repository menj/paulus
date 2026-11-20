<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$list_table = Paulus_Form_List_Table::get_instance();
$list_table->prepare_items();

$formatter = new PAULUS_FORM_HTMLFormatter( array(
	'allowed_html' => array_merge( paulus_form_kses_allowed_html(), array(
		'form' => array(
			'method' => true,
		),
	) ),
) );

$formatter->append_start_tag( 'div', array(
	'class' => 'wrap',
	'id' => 'paulus-form-contact-form-list-table',
) );

$formatter->append_start_tag( 'h1', array(
	'class' => 'wp-heading-inline',
) );

$formatter->append_preformatted(
	esc_html( __( 'Contact Forms', 'paulus' ) )
);

$formatter->end_tag( 'h1' );

if ( current_user_can( 'paulus_form_edit_contact_forms' ) ) {
	$formatter->append_preformatted(
		paulus_form_link(
			menu_page_url( 'paulus-form-new', false ),
			__( 'Add Contact Form', 'paulus' ),
			array( 'class' => 'page-title-action' )
		)
	);
}

if ( $search_keyword = paulus_form_superglobal_request( 's' ) ) {
	$formatter->append_start_tag( 'span', array(
		'class' => 'subtitle',
	) );

	$formatter->append_preformatted(
		sprintf(
			/* translators: %s: Search query. */
			__( 'Search results for: <strong>%s</strong>', 'paulus' ),
			esc_html( $search_keyword )
		)
	);

	$formatter->end_tag( 'span' );
}

$formatter->append_start_tag( 'hr', array(
	'class' => 'wp-header-end',
) );

$formatter->call_user_func( static function () {
	do_action( 'paulus_form_admin_warnings',
		'paulus-form', paulus_form_current_action(), null
	);

	do_action( 'paulus_form_admin_notices',
		'paulus-form', paulus_form_current_action(), null
	);
} );

$formatter->append_start_tag( 'form', array(
	'method' => 'get',
) );

$formatter->append_start_tag( 'input', array(
	'type' => 'hidden',
	'name' => 'page',
	'value' => paulus_form_superglobal_request( 'page' ),
) );

$formatter->call_user_func( static function () use ( $list_table ) {
	$list_table->search_box(
		__( 'Search Contact Forms', 'paulus' ),
		'paulus-form-contact'
	);

	$list_table->display();
} );

$formatter->print();
