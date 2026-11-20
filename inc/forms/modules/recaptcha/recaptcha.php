<?php
/**
 * reCAPTCHA module main file
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

paulus_form_include_module_file( 'recaptcha/service.php' );


add_action( 'paulus_form_init', 'paulus_form_recaptcha_register_service', 40, 0 );

/**
 * Registers the reCAPTCHA service.
 */
function paulus_form_recaptcha_register_service() {
	$integration = Paulus_Form_Integration::get_instance();

	$integration->add_service( 'recaptcha',
		PAULUS_FORM_RECAPTCHA::get_instance()
	);
}


add_action(
	'wp_enqueue_scripts',
	'paulus_form_recaptcha_enqueue_scripts',
	20, 0
);

/**
 * Enqueues frontend scripts for reCAPTCHA.
 */
function paulus_form_recaptcha_enqueue_scripts() {
	$service = PAULUS_FORM_RECAPTCHA::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	$url = 'https://www.google.com/recaptcha/api.js';

	if ( apply_filters( 'paulus_form_use_recaptcha_net', false ) ) {
		$url = 'https://www.recaptcha.net/recaptcha/api.js';
	}

	wp_register_script( 'google-recaptcha',
		add_query_arg(
			array(
				'render' => $service->get_sitekey(),
			),
			$url
		),
		array(),
		'3.0',
		array( 'in_footer' => true )
	);

	$assets = include paulus_form_plugin_path( 'modules/recaptcha/index.asset.php' );

	$assets = wp_parse_args( $assets, array(
		'dependencies' => array(),
		'version' => PAULUS_FORM_VERSION,
	) );

	wp_register_script(
		'paulus-form-recaptcha',
		paulus_form_plugin_url( 'modules/recaptcha/index.js' ),
		array_merge(
			$assets['dependencies'],
			array(
				'google-recaptcha',
				'wp-polyfill',
			)
		),
		$assets['version'],
		array( 'in_footer' => true )
	);

	wp_enqueue_script( 'paulus-form-recaptcha' );

	$paulus_form_recaptcha_obj = array(
		'sitekey' => $service->get_sitekey(),
		'actions' => apply_filters( 'paulus_form_recaptcha_actions', array(
			'homepage' => 'homepage',
			'contactform' => 'contactform',
		) ),
	);

	wp_add_inline_script( 'paulus-form-recaptcha',
		sprintf(
			'var paulus_form_recaptcha = %s;',
			wp_json_encode( $paulus_form_recaptcha_obj, JSON_PRETTY_PRINT )
		),
		'before'
	);
}


add_filter(
	'paulus_form_form_hidden_fields',
	'paulus_form_recaptcha_add_hidden_fields',
	100, 1
);

/**
 * Adds hidden form field for reCAPTCHA.
 */
function paulus_form_recaptcha_add_hidden_fields( $fields ) {
	$service = PAULUS_FORM_RECAPTCHA::get_instance();

	if ( ! $service->is_active() ) {
		return $fields;
	}

	return array_merge( $fields, array(
		'_paulus_form_recaptcha_response' => '',
	) );
}


add_filter( 'paulus_form_spam', 'paulus_form_recaptcha_verify_response', 9, 2 );

/**
 * Verifies reCAPTCHA token on the server side.
 */
function paulus_form_recaptcha_verify_response( $spam, $submission ) {
	if ( $spam ) {
		return $spam;
	}

	$service = PAULUS_FORM_RECAPTCHA::get_instance();

	if ( ! $service->is_active() ) {
		return $spam;
	}

	$token = paulus_form_superglobal_post( '_paulus_form_recaptcha_response' );

	if ( $service->verify( $token ) ) { // Human
		$spam = false;
	} else { // Bot
		$spam = true;

		if ( '' === $token ) {
			$submission->add_spam_log( array(
				'agent' => 'recaptcha',
				'reason' => __( 'reCAPTCHA response token is empty.', 'paulus' ),
			) );
		} else {
			$submission->add_spam_log( array(
				'agent' => 'recaptcha',
				'reason' => sprintf(
					/* translators: 1: value of reCAPTCHA score 2: value of reCAPTCHA threshold */
					__( 'reCAPTCHA score (%1$.2f) is lower than the threshold (%2$.2f).', 'paulus' ),
					$service->get_last_score(),
					$service->get_threshold()
				),
			) );
		}
	}

	return $spam;
}


add_action( 'paulus_form_init', 'paulus_form_recaptcha_add_form_tag_recaptcha', 10, 0 );

/**
 * Registers form-tag types for reCAPTCHA.
 */
function paulus_form_recaptcha_add_form_tag_recaptcha() {
	$service = PAULUS_FORM_RECAPTCHA::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	paulus_form_add_form_tag( 'recaptcha',
		'__return_empty_string', // no output
		array( 'display-block' => true )
	);
}


add_action( 'paulus_form_upgrade', 'paulus_form_upgrade_recaptcha_v2_v3', 10, 2 );

/**
 * Adds warnings for users upgrading from reCAPTCHA v2 to v3.
 */
function paulus_form_upgrade_recaptcha_v2_v3( $new_ver, $old_ver ) {
	if ( version_compare( '5.1-dev', $old_ver, '<=' ) ) {
		return;
	}

	$service = PAULUS_FORM_RECAPTCHA::get_instance();

	if ( ! $service->is_active() or $service->get_global_sitekey() ) {
		return;
	}

	// Maybe v2 keys are used now. Warning necessary.
	Paulus_Form::update_option( 'recaptcha_v2_v3_warning', true );
	Paulus_Form::update_option( 'recaptcha', null );
}


add_action( 'paulus_form_admin_menu', 'paulus_form_admin_init_recaptcha_v2_v3', 10, 0 );

/**
 * Adds filters and actions for warnings.
 */
function paulus_form_admin_init_recaptcha_v2_v3() {
	if ( ! Paulus_Form::get_option( 'recaptcha_v2_v3_warning' ) ) {
		return;
	}

	add_filter(
		'paulus_form_admin_menu_change_notice',
		'paulus_form_admin_menu_change_notice_recaptcha_v2_v3',
		10, 1
	);

	add_action(
		'paulus_form_admin_warnings',
		'paulus_form_admin_warnings_recaptcha_v2_v3',
		5, 3
	);
}


/**
 * Increments the admin menu counter for the Integration page.
 */
function paulus_form_admin_menu_change_notice_recaptcha_v2_v3( $counts ) {
	$counts['paulus-form-integration'] += 1;
	return $counts;
}


/**
 * Prints warnings on the admin screen.
 */
function paulus_form_admin_warnings_recaptcha_v2_v3( $page, $action, $object ) {
	if ( 'paulus-form-integration' !== $page ) {
		return;
	}

	wp_admin_notice(
		sprintf(
			/* translators: %s: URL to support page about reCAPTCHA */
			__( 'API keys for reCAPTCHA v3 are different from those for v2; keys for v2 do not work with the v3 API. You need to register your sites again to get new keys for v3. For details, see <a href="%s">reCAPTCHA (v3)</a>.', 'paulus' ),
			'#'
		),
		array( 'type' => 'warning' )
	);
}
