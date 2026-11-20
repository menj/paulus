<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter(
	'paulus_form_pre_construct_contact_form_properties',
	'paulus_form_sendinblue_register_property',
	10, 2
);

/**
 * Registers the sendinblue contact form property.
 */
function paulus_form_sendinblue_register_property( $properties, $contact_form ) {
	$service = Paulus_Form_Sendinblue::get_instance();

	if ( $service->is_active() ) {
		$properties += array(
			'sendinblue' => array(),
		);
	}

	return $properties;
}


add_action(
	'paulus_form_save_contact_form',
	'paulus_form_sendinblue_save_contact_form',
	10, 3
);

/**
 * Saves the sendinblue property value.
 */
function paulus_form_sendinblue_save_contact_form( $contact_form, $args, $context ) {
	$service = Paulus_Form_Sendinblue::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	$prop = wp_parse_args(
		(array) paulus_form_superglobal_post( 'paulus-form-sendinblue', array() ),
		array(
			'enable_contact_list' => false,
			'contact_lists' => array(),
			'enable_transactional_email' => false,
			'email_template' => 0,
		)
	);

	$prop['contact_lists'] = array_map( 'absint', $prop['contact_lists'] );

	$prop['email_template'] = absint( $prop['email_template'] );

	$contact_form->set_properties( array(
		'sendinblue' => $prop,
	) );
}


add_filter(
	'paulus_form_editor_panels',
	'paulus_form_sendinblue_editor_panels',
	10, 1
);

/**
 * Builds the editor panel for the sendinblue property.
 */
function paulus_form_sendinblue_editor_panels( $panels ) {
	$service = Paulus_Form_Sendinblue::get_instance();

	if ( ! $service->is_active() ) {
		return $panels;
	}

	$editor_panel = static function () {
		$formatter = new PAULUS_FORM_HTMLFormatter();

		$formatter->append_start_tag( 'h2' );

		$formatter->append_preformatted(
			esc_html( __( 'Brevo', 'paulus' ) )
		);

		$formatter->end_tag( 'h2' );

		$formatter->append_start_tag( 'fieldset' );

		$formatter->append_start_tag( 'legend' );

		$formatter->append_preformatted( sprintf(
			/* translators: %s: URL to support page about Brevo integration */
			__( 'You can set up the Brevo integration here. For details, see <a href="%s">Brevo integration</a>.', 'paulus' ),
			'#'
		) );

		$formatter->end_tag( 'legend' );

		$formatter->append_start_tag( 'table', array(
			'id' => 'paulus-form-sendinblue-editor-table',
			'class' => 'form-table',
			'role' => 'presentation',
		) );

		$formatter->call_user_func( static function () {
			paulus_form_sendinblue_editor_table();
		} );

		$formatter->end_tag( 'table' );

		$formatter->print();
	};

	$panels += array(
		'sendinblue-panel' => array(
			'title' => __( 'Brevo', 'paulus' ),
			'callback' => $editor_panel,
		),
	);

	return $panels;
}


/**
 * Reusable content for the Brevo editor panel.
 */
function paulus_form_sendinblue_editor_table() {
	$contact_form = Paulus_Form_ContactForm::get_current();

	$prop = wp_parse_args(
		$contact_form->prop( 'sendinblue' ),
		array(
			'enable_contact_list' => false,
			'contact_lists' => array(),
			'enable_transactional_email' => false,
			'email_template' => 0,
		)
	);

	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'tbody' );

	$formatter->append_start_tag( 'tr', array(
		'class' => $prop['enable_contact_list'] ? '' : 'inactive',
	) );

	$formatter->append_start_tag( 'th', array(
		'scope' => 'row',
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Contact lists', 'paulus' ) )
	);

	$formatter->append_start_tag( 'td' );

	$formatter->append_start_tag( 'fieldset' );

	$formatter->append_start_tag( 'legend', array(
		'class' => 'screen-reader-text',
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Contact lists', 'paulus' ) )
	);

	$formatter->end_tag( 'legend' );

	$formatter->append_start_tag( 'label', array(
		'for' => 'paulus-form-sendinblue-enable-contact-list',
	) );

	$formatter->append_start_tag( 'input', array(
		'type' => 'checkbox',
		'name' => 'paulus-form-sendinblue[enable_contact_list]',
		'id' => 'paulus-form-sendinblue-enable-contact-list',
		'value' => '1',
		'checked' => $prop['enable_contact_list'],
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Add form submitters to your contact lists', 'paulus' ) )
	);

	$formatter->end_tag( 'tr' );

	$formatter->append_start_tag( 'tr' );

	$formatter->append_start_tag( 'th', array(
		'scope' => 'row',
	) );

	$formatter->append_start_tag( 'td', array(
		'id' => 'paulus-form-sendinblue-editor-lists',
	) );

	$formatter->call_user_func( static function () use ( $prop ) {
		paulus_form_sendinblue_editor_lists( $prop['contact_lists'], array(
			'clear_cache' => ! in_array(
				paulus_form_superglobal_get( 'message' ),
				array( 'saved', 'created' ),
				true
			),
			'run_query' => false,
		) );
	} );

	$formatter->end_tag( 'tr' );

	$formatter->append_start_tag( 'tr', array(
		'class' => $prop['enable_transactional_email'] ? '' : 'inactive',
	) );

	$formatter->append_start_tag( 'th', array(
		'scope' => 'row',
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Welcome email', 'paulus' ) )
	);

	$formatter->append_start_tag( 'td' );

	$formatter->append_start_tag( 'fieldset' );

	$formatter->append_start_tag( 'legend', array(
		'class' => 'screen-reader-text',
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Welcome email', 'paulus' ) )
	);

	$formatter->end_tag( 'legend' );

	$formatter->append_start_tag( 'label', array(
		'for' => 'paulus-form-sendinblue-enable-transactional-email',
	) );

	$formatter->append_start_tag( 'input', array(
		'type' => 'checkbox',
		'name' => 'paulus-form-sendinblue[enable_transactional_email]',
		'id' => 'paulus-form-sendinblue-enable-transactional-email',
		'value' => '1',
		'checked' => $prop['enable_transactional_email'],
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Send a welcome email to new contacts', 'paulus' ) )
	);

	$formatter->end_tag( 'fieldset' );

	$formatter->end_tag( 'tr' );

	$formatter->append_start_tag( 'tr' );

	$formatter->append_start_tag( 'th', array(
		'scope' => 'row',
	) );

	$formatter->append_start_tag( 'td', array(
		'id' => 'paulus-form-sendinblue-editor-templates',
	) );

	$formatter->call_user_func( static function () use ( $prop ) {
		paulus_form_sendinblue_editor_templates( $prop['email_template'], array(
			'clear_cache' => ! in_array(
				paulus_form_superglobal_get( 'message' ),
				array( 'saved', 'created' ),
				true
			),
			'run_query' => false,
		) );
	} );

	$formatter->end_tag( 'tr' );

	$formatter->print();
}


function paulus_form_sendinblue_editor_lists( $lists_selected, $options = '' ) {
	$options = wp_parse_args( $options, array(
		'clear_cache' => false,
		'run_query' => true,
	) );

	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'fieldset' );

	$lists = paulus_form_sendinblue_get_lists( $options );

	if ( ! isset( $lists ) ) {
		$formatter->append_start_tag( 'legend' );

		$formatter->append_preformatted(
			esc_html( __( 'Loading&hellip;', 'paulus' ) )
		);

		$formatter->end_tag( 'legend' );

		foreach ( $lists_selected as $list ) {
			$formatter->append_start_tag( 'input', array(
				'type' => 'hidden',
				'name' => 'paulus-form-sendinblue[contact_lists][]',
				'value' => $list,
			) );
		}

	} elseif ( $lists ) {
		$formatter->append_start_tag( 'legend' );

		$formatter->append_preformatted(
			esc_html( __( 'Select lists to which contacts are added:', 'paulus' ) )
		);

		$formatter->end_tag( 'legend' );

		$formatter->append_start_tag( 'ul' );

		foreach ( $lists as $list ) {
			$formatter->append_start_tag( 'li' );
			$formatter->append_start_tag( 'label' );

			$formatter->append_start_tag( 'input', array(
				'type' => 'checkbox',
				'name' => 'paulus-form-sendinblue[contact_lists][]',
				'value' => $list['id'],
				'checked' => in_array( $list['id'], $lists_selected ),
			) );

			$formatter->append_whitespace();

			$formatter->append_preformatted( esc_html( $list['name'] ) );

			$formatter->end_tag( 'li' );
		}

		$formatter->end_tag( 'ul' );
	} else {
		$formatter->append_start_tag( 'legend' );

		$formatter->append_preformatted(
			esc_html( __( 'You have no contact list yet.', 'paulus' ) )
		);

		$formatter->end_tag( 'legend' );
	}

	$formatter->end_tag( 'fieldset' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_start_tag( 'a', array(
		'href' => 'https://my.sendinblue.com/lists',
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Manage your contact lists', 'paulus' ) )
	);

	$formatter->end_tag( 'p' );

	$formatter->print();
}


function paulus_form_sendinblue_editor_templates( $template_selected, $options = '' ) {
	$options = wp_parse_args( $options, array(
		'clear_cache' => false,
		'run_query' => true,
	) );

	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'fieldset' );

	$templates = paulus_form_sendinblue_get_templates( $options );

	if ( ! isset( $templates ) ) {
		$formatter->append_start_tag( 'legend' );

		$formatter->append_preformatted(
			esc_html( __( 'Loading&hellip;', 'paulus' ) )
		);

		$formatter->end_tag( 'legend' );

		$formatter->append_start_tag( 'input', array(
			'type' => 'hidden',
			'name' => 'paulus-form-sendinblue[email_template]',
			'value' => $template_selected,
		) );

	} elseif ( $templates ) {
		$formatter->append_start_tag( 'legend' );

		$formatter->append_preformatted(
			esc_html( __( 'Select an email template:', 'paulus' ) )
		);

		$formatter->end_tag( 'legend' );

		$formatter->append_start_tag( 'select', array(
			'name' => 'paulus-form-sendinblue[email_template]',
		) );

		$formatter->append_start_tag( 'option', array(
			'value' => 0,
			'selected' => 0 === $template_selected,
		) );

		$formatter->append_preformatted(
			esc_html( __( '&mdash; Select &mdash;', 'paulus' ) )
		);

		$formatter->end_tag( 'option' );

		foreach ( $templates as $template ) {
			$formatter->append_start_tag( 'option', array(
				'value' => $template['id'],
				'selected' => $template_selected === $template['id'],
			) );

			$formatter->append_preformatted( esc_html( $template['name'] ) );

			$formatter->end_tag( 'option' );
		}

		$formatter->end_tag( 'select' );

	} else {
		$formatter->append_start_tag( 'legend' );

		$formatter->append_preformatted(
			esc_html( __( 'You have no active email template yet.', 'paulus' ) )
		);

		$formatter->end_tag( 'legend' );
	}

	$formatter->end_tag( 'fieldset' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_start_tag( 'a', array(
		'href' => 'https://my.sendinblue.com/camp/lists/template',
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Manage your email templates', 'paulus' ) )
	);

	$formatter->end_tag( 'p' );

	$formatter->print();
}


/**
 * Retrieves contact lists from Brevo's database.
 */
function paulus_form_sendinblue_get_lists( $options = '' ): ?array {
	$options = wp_parse_args( $options, array(
		'clear_cache' => false,
		'run_query' => true,
	) );

	static $lists = null;

	$service = Paulus_Form_Sendinblue::get_instance();

	if ( isset( $lists ) or ! $service->is_active() ) {
		return $lists;
	}

	if ( $options['clear_cache'] ) {
		delete_transient( 'paulus_form_sendinblue_lists' );
	}

	$cache = get_transient( 'paulus_form_sendinblue_lists' );

	if ( false !== $cache ) {
		return $lists = $cache;
	} elseif ( ! $options['run_query'] ) {
		return $lists = null;
	}

	$lists = array();

	$limit = 50;
	$offset = 0;

	while ( count( $lists ) < $limit * 10 ) {
		$lists_next = (array) $service->get_lists( array(
			'limit' => $limit,
			'offset' => $offset,
		) );

		if ( ! empty( $lists_next ) ) {
			$lists = array_merge( $lists, $lists_next );
		}

		if ( count( $lists_next ) < $limit ) {
			break;
		}

		$offset += $limit;
	}

	set_transient(
		'paulus_form_sendinblue_lists',
		$lists,
		12 * HOUR_IN_SECONDS
	);

	return $lists;
}


/**
 * Retrieves email templates from Brevo's database.
 */
function paulus_form_sendinblue_get_templates( $options = '' ): ?array {
	$options = wp_parse_args( $options, array(
		'clear_cache' => false,
		'run_query' => true,
	) );

	static $templates = null;

	$service = Paulus_Form_Sendinblue::get_instance();

	if ( isset( $templates ) or ! $service->is_active() ) {
		return $templates;
	}

	if ( $options['clear_cache'] ) {
		delete_transient( 'paulus_form_sendinblue_templates' );
	}

	$cache = get_transient( 'paulus_form_sendinblue_templates' );

	if ( false !== $cache ) {
		return $templates = $cache;
	} elseif ( ! $options['run_query'] ) {
		return $templates = null;
	}

	$templates = array();

	$limit = 50;
	$offset = 0;

	while ( count( $templates ) < $limit * 10 ) {
		$templates_next = (array) $service->get_templates( array(
			'limit' => $limit,
			'offset' => $offset,
		) );

		if ( ! empty( $templates_next ) ) {
			$templates = array_merge( $templates, $templates_next );
		}

		if ( count( $templates_next ) < $limit ) {
			break;
		}

		$offset += $limit;
	}

	set_transient(
		'paulus_form_sendinblue_templates',
		$templates,
		12 * HOUR_IN_SECONDS
	);

	return $templates;
}
