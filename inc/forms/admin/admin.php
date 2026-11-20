<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once PAULUS_FORM_PLUGIN_DIR . '/admin/includes/admin-functions.php';
require_once PAULUS_FORM_PLUGIN_DIR . '/admin/includes/list-table.php';
require_once PAULUS_FORM_PLUGIN_DIR . '/admin/includes/help-tabs.php';
require_once PAULUS_FORM_PLUGIN_DIR . '/admin/includes/tag-generator.php';
require_once PAULUS_FORM_PLUGIN_DIR . '/admin/includes/config-validator.php';
require_once PAULUS_FORM_PLUGIN_DIR . '/admin/includes/ajax-actions.php';


add_action(
	'admin_init',
	static function () {
		do_action( 'paulus_form_admin_init' );
	},
	10, 0
);


add_action(
	'admin_menu',
	'paulus_form_admin_menu',
	9, 0
);

function paulus_form_admin_menu() {
	do_action( 'paulus_form_admin_menu' );

	add_menu_page(
		__( 'Forms Dashboard', 'paulus' ),
		__( 'Contact', 'paulus' )
			. paulus_form_admin_menu_change_notice(),
		'paulus_form_read_contact_forms',
		'paulus-form-dashboard',
		'paulus_form_admin_dashboard_page',
		'dashicons-email',
		30
	);

	$dashboard = add_submenu_page(
		'paulus-form-dashboard',
		__( 'Forms Dashboard', 'paulus' ),
		__( 'Dashboard', 'paulus' )
			. paulus_form_admin_menu_change_notice( 'paulus-form-dashboard' ),
		'paulus_form_read_contact_forms',
		'paulus-form-dashboard',
		'paulus_form_admin_dashboard_page'
	);

	add_action( 'load-' . $dashboard, 'paulus_form_load_dashboard_page', 10, 0 );

	$edit = add_submenu_page(
		'paulus-form-dashboard',
		paulus_form_get_current_contact_form()
			? __( 'Edit Contact Form', 'paulus' )
			: __( 'Contact Forms', 'paulus' ),
		__( 'Contact Forms', 'paulus' )
			. paulus_form_admin_menu_change_notice( 'paulus-form' ),
		'paulus_form_read_contact_forms',
		'paulus-form',
		'paulus_form_admin_management_page'
	);

	add_action( 'load-' . $edit, 'paulus_form_load_contact_form_admin', 10, 0 );

	$addnew = add_submenu_page(
		'paulus-form-dashboard',
		__( 'Add Contact Form', 'paulus' ),
		__( 'Add Contact Form', 'paulus' )
			. paulus_form_admin_menu_change_notice( 'paulus-form-new' ),
		'paulus_form_edit_contact_forms',
		'paulus-form-new',
		'paulus_form_admin_add_new_page'
	);

	add_action( 'load-' . $addnew, 'paulus_form_load_contact_form_admin', 10, 0 );

	$integration = Paulus_Form_Integration::get_instance();

	if ( $integration->service_exists() ) {
		$integration = add_submenu_page(
			'paulus-form-dashboard',
			__( 'Integration with External API', 'paulus' ),
			__( 'Integration', 'paulus' )
				. paulus_form_admin_menu_change_notice( 'paulus-form-integration' ),
			'paulus_form_manage_integration',
			'paulus-form-integration',
			'paulus_form_admin_integration_page'
		);

		add_action( 'load-' . $integration, 'paulus_form_load_integration_page', 10, 0 );
	}
}


function paulus_form_admin_menu_change_notice( $menu_slug = '' ) {
	$counts = apply_filters( 'paulus_form_admin_menu_change_notice',
		array(
			'paulus-form' => 0,
			'paulus-form-new' => 0,
			'paulus-form-integration' => 0,
		)
	);

	if ( empty( $menu_slug ) ) {
		$count = absint( array_sum( $counts ) );
	} elseif ( isset( $counts[$menu_slug] ) ) {
		$count = absint( $counts[$menu_slug] );
	} else {
		$count = 0;
	}

	if ( $count ) {
		return sprintf(
			' <span class="update-plugins %1$d"><span class="plugin-count">%2$s</span></span>',
			$count,
			esc_html( number_format_i18n( $count ) )
		);
	}

	return '';
}


add_action(
	'admin_enqueue_scripts',
	'paulus_form_admin_enqueue_scripts',
	10, 1
);

function paulus_form_admin_enqueue_scripts( $hook_suffix ) {
	if ( ! str_contains( $hook_suffix, 'paulus-form' ) ) {
		return;
	}

	wp_enqueue_style( 'paulus-form-admin',
		paulus_form_plugin_url( 'admin/includes/css/styles.css' ),
		array(), PAULUS_FORM_VERSION, 'all'
	);

	if ( paulus_form_is_rtl() ) {
		wp_enqueue_style( 'paulus-form-admin-rtl',
			paulus_form_plugin_url( 'admin/includes/css/styles-rtl.css' ),
			array(), PAULUS_FORM_VERSION, 'all'
		);
	}

	$assets = include paulus_form_plugin_path( 'admin/includes/js/index.asset.php' );

	$assets = wp_parse_args( $assets, array(
		'dependencies' => array(),
		'version' => PAULUS_FORM_VERSION,
	) );

	wp_enqueue_script( 'paulus-form-admin',
		paulus_form_plugin_url( 'admin/includes/js/index.js' ),
		$assets['dependencies'],
		$assets['version'],
		array( 'in_footer' => true )
	);

	wp_set_script_translations( 'paulus-form-admin', 'paulus' );

	$paulus_form_obj = array(
		'apiSettings' => array(
			'root' => sanitize_url( rest_url( 'paulus-form/v1' ) ),
			'namespace' => 'paulus-form/v1',
		),
	);

	$post = paulus_form_get_current_contact_form();

	if ( $post ) {
		$paulus_form_obj = array_merge( $paulus_form_obj, array(
			'nonce' => array(
				'save' => wp_create_nonce(
					sprintf(
						'paulus-form-save-contact-form_%s',
						$post->initial() ? -1 : $post->id()
					)
				),
				'copy' => wp_create_nonce(
					sprintf(
						'paulus-form-copy-contact-form_%s',
						$post->initial() ? -1 : $post->id()
					)
				),
				'delete' => wp_create_nonce(
					sprintf(
						'paulus-form-delete-contact-form_%s',
						$post->initial() ? -1 : $post->id()
					)
				),
			),
			'configValidator' => array(
				'errors' => array(),
				'docUrl' => Paulus_Form_ConfigValidator::get_doc_link(),
			),
		) );

		if (
			current_user_can( 'paulus_form_edit_contact_form', $post->id() ) and
			paulus_form_validate_configuration()
		) {
			$config_validator = new Paulus_Form_ConfigValidator( $post );
			$config_validator->restore();

			$paulus_form_obj['configValidator'] = array_merge(
				$paulus_form_obj['configValidator'],
				array(
					'errors' => $config_validator->collect_error_messages(
						array( 'decodes_html_entities' => true )
					),
				)
			);
		}
	}

	wp_add_inline_script( 'paulus-form-admin',
		sprintf(
			'var paulusForm = %s;',
			wp_json_encode( $paulus_form_obj, JSON_PRETTY_PRINT )
		),
		'before'
	);
}


add_filter(
	'set_screen_option_paulus_form_contact_forms_per_page',
	static function ( $result, $option, $value ) {
		$paulus_form_screens = array(
			'paulus_form_contact_forms_per_page',
		);

		if ( in_array( $option, $paulus_form_screens, true ) ) {
			$result = $value;
		}

		return $result;
	},
	10, 3
);


function paulus_form_load_dashboard_page() {
	do_action( 'paulus_form_admin_load',
		paulus_form_superglobal_get( 'page' ),
		paulus_form_current_action()
	);

	require_once PAULUS_FORM_PLUGIN_DIR . '/admin/includes/dashboard.php';

	paulus_form_dashboard_setup();

	wp_enqueue_script( 'dashboard' );
}


function paulus_form_admin_dashboard_page() {
	global $wp_meta_boxes;

	$screen = get_current_screen();
	$page = $screen->id;
	$core_widget_ids = array_keys( paulus_form_dashboard_widgets() );

	foreach ( $wp_meta_boxes[$page] as $context => $priorities ) {
		foreach ( $priorities as $priority => $boxes ) {
			if ( ! in_array( $priority, array( 'high', 'core' ), true ) ) {
				continue;
			}

			foreach ( $boxes as $box_id => $box ) {
				if ( in_array( $box_id, $core_widget_ids, true ) ) {
					continue;
				}

				if ( ! isset( $priorities['default'][$box_id] ) ) {
					$wp_meta_boxes[$page][$context]['default'][$box_id] = $box;
				} elseif ( ! isset( $priorities['low'][$box_id] ) ) {
					$wp_meta_boxes[$page][$context]['low'][$box_id] = $box;
				}

				unset( $wp_meta_boxes[$page][$context][$priority][$box_id] );
			}
		}
	}

	require_once PAULUS_FORM_PLUGIN_DIR . '/admin/dashboard.php';
}


function paulus_form_load_contact_form_admin() {
	global $plugin_page;

	$action = paulus_form_current_action();

	do_action( 'paulus_form_admin_load',
		paulus_form_superglobal_get( 'page' ),
		$action
	);

	if ( 'save' === $action ) {
		$id = paulus_form_superglobal_post( 'post_ID', '-1' );

		check_admin_referer( 'paulus-form-save-contact-form_' . $id );

		if ( ! current_user_can( 'paulus_form_edit_contact_form', $id ) ) {
			wp_die(
				esc_html( __( 'You are not allowed to edit this item.', 'paulus' ) )
			);
		}

		$contact_form = paulus_form_save_contact_form(
			array_merge(
				wp_unslash( $_REQUEST ),
				array(
					'id' => $id,
					'title' => paulus_form_superglobal_post( 'post_title', null ),
					'locale' => paulus_form_superglobal_post( 'paulus-form-locale', null ),
					'form' => paulus_form_superglobal_post( 'paulus-form-form', '' ),
					'mail' => paulus_form_superglobal_post( 'paulus-form-mail', array() ),
					'mail_2' => paulus_form_superglobal_post( 'paulus-form-mail-2', array() ),
					'messages' => paulus_form_superglobal_post( 'paulus-form-messages', array() ),
					'additional_settings' => paulus_form_superglobal_post( 'paulus-form-additional-settings', '' ),
				)
			)
		);

		if ( $contact_form and paulus_form_validate_configuration() ) {
			$config_validator = new Paulus_Form_ConfigValidator( $contact_form );
			$config_validator->validate();
			$config_validator->save();
		}

		$query = array(
			'post' => $contact_form ? $contact_form->id() : 0,
			'active-tab' => paulus_form_canonicalize_name(
				paulus_form_superglobal_post( 'active-tab' )
			),
		);

		if ( ! $contact_form ) {
			$query['message'] = 'failed';
		} elseif ( -1 === (int) $id ) {
			$query['message'] = 'created';
		} else {
			$query['message'] = 'saved';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'paulus-form', false ) );
		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'copy' === $action ) {
		$id = absint( $_POST['post_ID'] ?? $_REQUEST['post'] ?? '' );

		check_admin_referer( 'paulus-form-copy-contact-form_' . $id );

		if ( ! current_user_can( 'paulus_form_edit_contact_form', $id ) ) {
			wp_die(
				esc_html( __( 'You are not allowed to edit this item.', 'paulus' ) )
			);
		}

		$query = array();

		if ( $contact_form = paulus_form_contact_form( $id ) ) {
			$new_contact_form = $contact_form->copy();
			$new_contact_form->save();

			$query['post'] = $new_contact_form->id();
			$query['message'] = 'created';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'paulus-form', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete' === $action ) {
		$nonce_action = 'bulk-posts';

		if (
			$post_id = paulus_form_superglobal_post( 'post_ID' ) or
			! is_array( $post_id = paulus_form_superglobal_request( 'post', array() ) )
		) {
			$nonce_action = sprintf( 'paulus-form-delete-contact-form_%s', $post_id );
		}

		check_admin_referer( $nonce_action );

		$posts = array_filter( (array) $post_id );

		$deleted = 0;

		foreach ( $posts as $post ) {
			$post = Paulus_Form_ContactForm::get_instance( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can( 'paulus_form_delete_contact_form', $post->id() ) ) {
				wp_die(
					esc_html( __( 'You are not allowed to delete this item.', 'paulus' ) )
				);
			}

			if ( ! $post->delete() ) {
				wp_die(
					esc_html( __( 'Error in deleting.', 'paulus' ) )
				);
			}

			$deleted += 1;
		}

		$query = array();

		if ( ! empty( $deleted ) ) {
			$query['message'] = 'deleted';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'paulus-form', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	$post = null;

	if ( 'paulus-form-new' === $plugin_page ) {
		$post = Paulus_Form_ContactForm::get_template( array(
			'locale' => paulus_form_superglobal_get( 'locale', null ),
		) );
	} elseif ( $post_id = paulus_form_superglobal_get( 'post' ) ) {
		$post = Paulus_Form_ContactForm::get_instance( $post_id );
	}

	$current_screen = get_current_screen();

	$help_tabs = new Paulus_Form_Help_Tabs( $current_screen );

	if ( $post and current_user_can( 'paulus_form_edit_contact_form', $post->id() ) ) {
		$help_tabs->set_help_tabs( 'edit' );
	} else {
		// Construct a list table before get_column_headers() is called.
		Paulus_Form_List_Table::get_instance();

		add_screen_option( 'per_page', array(
			'default' => 20,
			'option' => 'paulus_form_contact_forms_per_page',
		) );

		$help_tabs->set_help_tabs( 'list' );
	}
}


function paulus_form_admin_management_page() {
	if ( $post = paulus_form_get_current_contact_form() ) {
		$post_id = $post->initial() ? -1 : $post->id();

		require_once PAULUS_FORM_PLUGIN_DIR . '/admin/includes/editor.php';
		require_once PAULUS_FORM_PLUGIN_DIR . '/admin/edit-contact-form.php';
		return;
	}

	if (
		'validate' === paulus_form_current_action() and
		paulus_form_validate_configuration() and
		current_user_can( 'paulus_form_edit_contact_forms' )
	) {
		paulus_form_admin_bulk_validate_page();
		return;
	}

	require_once PAULUS_FORM_PLUGIN_DIR . '/admin/contact-forms.php';
}


function paulus_form_admin_add_new_page() {
	$post = paulus_form_get_current_contact_form();

	if ( ! $post ) {
		$post = Paulus_Form_ContactForm::get_template();
	}

	$post_id = -1;

	require_once PAULUS_FORM_PLUGIN_DIR . '/admin/includes/editor.php';
	require_once PAULUS_FORM_PLUGIN_DIR . '/admin/edit-contact-form.php';
}


function paulus_form_load_integration_page() {
	do_action( 'paulus_form_admin_load',
		paulus_form_superglobal_get( 'page' ),
		paulus_form_current_action()
	);

	$integration = Paulus_Form_Integration::get_instance();

	if (
		$service_name = paulus_form_superglobal_request( 'service' ) and
		$integration->service_exists( $service_name )
	) {
		$service = $integration->get_service( $service_name );
		$service->load( paulus_form_current_action() );
	}

	$help_tabs = new Paulus_Form_Help_Tabs( get_current_screen() );
	$help_tabs->set_help_tabs( 'integration' );
}


function paulus_form_admin_integration_page() {
	require_once PAULUS_FORM_PLUGIN_DIR . '/admin/integration.php';
}


add_action( 'paulus_form_admin_notices', 'paulus_form_admin_updated_message', 10, 3 );

function paulus_form_admin_updated_message( $page, $action, $object ) {
	if ( ! in_array( $page, array( 'paulus-form', 'paulus-form-new' ), true ) ) {
		return;
	}

	$message_type = paulus_form_superglobal_request( 'message' );

	if ( ! $message_type ) {
		return;
	}

	$notice_type = 'success';

	if ( 'created' === $message_type ) {
		$message = __( 'Contact form created.', 'paulus' );
	} elseif ( 'saved' === $message_type ) {
		$message = __( 'Contact form saved.', 'paulus' );
	} elseif ( 'deleted' === $message_type ) {
		$message = __( 'Contact form deleted.', 'paulus' );
	} elseif ( 'failed' === $message_type ) {
		$notice_type = 'error';
		$message = __( 'There was an error saving the contact form.', 'paulus' );
	} elseif ( 'validated' === $message_type ) {
		$bulk_validate = Paulus_Form::get_option( 'bulk_validate', array() );
		$count_invalid = absint( $bulk_validate['count_invalid'] ?? 0 );

		if ( $count_invalid ) {
			$notice_type = 'warning';

			$message = sprintf(
				/* translators: %s: number of contact forms */
				_n(
					'Configuration validation completed. %s invalid contact form was found.',
					'Configuration validation completed. %s invalid contact forms were found.',
					$count_invalid, 'paulus'
				),
				number_format_i18n( $count_invalid )
			);
		} else {
			$message = __( 'Configuration validation completed. No invalid contact form was found.', 'paulus' );
		}
	}

	if ( ! empty( $message ) ) {
		wp_admin_notice(
			$message,
			array( 'type' => $notice_type )
		);
	}
}




add_action( 'paulus_form_admin_warnings', 'paulus_form_old_wp_version_error', 10, 3 );

function paulus_form_old_wp_version_error( $page, $action, $object ) {
	$wp_version = get_bloginfo( 'version' );

	if ( version_compare( $wp_version, PAULUS_FORM_REQUIRED_WP_VERSION, '<' ) ) {
		wp_admin_notice(
			sprintf(
				/* translators: 1: version of Forms, 2: version of WordPress, 3: URL */
				__( '<strong>Forms %1$s requires WordPress %2$s or higher.</strong> Please <a href="%3$s">update WordPress</a> first.', 'paulus' ),
				PAULUS_FORM_VERSION,
				PAULUS_FORM_REQUIRED_WP_VERSION,
				admin_url( 'update-core.php' )
			),
			array( 'type' => 'warning' )
		);
	}
}


add_action( 'paulus_form_admin_warnings', 'paulus_form_not_allowed_to_edit', 10, 3 );

function paulus_form_not_allowed_to_edit( $page, $action, $object ) {
	if ( $object instanceof Paulus_Form_ContactForm ) {
		$contact_form = $object;
	} else {
		return;
	}

	if ( ! current_user_can( 'paulus_form_edit_contact_form', $contact_form->id() ) ) {
		wp_admin_notice(
			__( 'You are not allowed to edit this contact form.', 'paulus' ),
			array( 'type' => 'warning' )
		);
	}
}


add_action( 'paulus_form_admin_warnings', 'paulus_form_ctct_deprecated_warning', 10, 3 );

function paulus_form_ctct_deprecated_warning( $page, $action, $object ) {
	$service = Paulus_Form_ConstantContact::get_instance();

	if ( $service->is_active() ) {
		wp_admin_notice(
			sprintf(
				/* translators: 1: URL to announcement post, 2: URL to Brevo integration doc */
				__( 'Forms has completed the <a href="%1$s">removal of the Constant Contact integration</a>. We recommend <a href="%2$s">Brevo</a> as an alternative.', 'paulus' ),
				'#',
				'#'
			),
			array( 'type' => 'warning' )
		);
	}
}


add_action( 'paulus_form_admin_warnings', 'paulus_form_captcha_future_warning', 10, 3 );

function paulus_form_captcha_future_warning( $page, $action, $object ) {
	$service = PAULUS_FORM_RECAPTCHA::get_instance();

	if ( $service->is_active() ) {
		wp_admin_notice(
			sprintf(
				/* translators: %s: URL to Turnstile integration doc */
				__( '<strong>Attention reCAPTCHA users:</strong> Google attempts to make all reCAPTCHA users migrate to reCAPTCHA Enterprise, meaning Google charges you for API calls exceeding the free tier. Forms supports <a href="%s">Cloudflare Turnstile</a>, and we recommend it unless you have reasons to use reCAPTCHA.', 'paulus' ),
				'#'
			),
			array( 'type' => 'warning' )
		);
	}
}
