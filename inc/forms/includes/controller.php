<?php
/**
 * Controller for front-end requests, scripts, and styles
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
	'parse_request',
	'paulus_form_control_init',
	20, 0
);

/**
 * Handles a submission in non-Ajax mode.
 */
function paulus_form_control_init() {
	if ( Paulus_Form_Submission::is_restful() ) {
		return;
	}

	if (
		$id = (int) paulus_form_superglobal_post( '_paulus_form' ) and
		$contact_form = paulus_form_contact_form( $id )
	) {
		$contact_form->submit();
	}
}


/**
 * Registers main scripts and styles.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		$assets = include paulus_form_plugin_path( 'includes/js/index.asset.php' );

		$assets = wp_parse_args( $assets, array(
			'dependencies' => array(),
			'version' => PAULUS_FORM_VERSION,
		) );

		wp_register_script(
			'paulus',
			paulus_form_plugin_url( 'includes/js/index.js' ),
			array_merge(
				$assets['dependencies'],
				array( 'swv' )
			),
			$assets['version'],
			array( 'in_footer' => true )
		);

		wp_set_script_translations( 'paulus', 'paulus' );

		wp_register_script(
			'paulus-form-html5-fallback',
			paulus_form_plugin_url( 'includes/js/html5-fallback.js' ),
			array( 'jquery-ui-datepicker' ),
			PAULUS_FORM_VERSION,
			array( 'in_footer' => true )
		);

		if ( paulus_form_load_js() ) {
			paulus_form_enqueue_scripts();
		}

		wp_register_style(
			'paulus',
			paulus_form_plugin_url( 'includes/css/styles.css' ),
			array(),
			PAULUS_FORM_VERSION,
			'all'
		);

		wp_register_style(
			'paulus-form-rtl',
			paulus_form_plugin_url( 'includes/css/styles-rtl.css' ),
			array( 'paulus' ),
			PAULUS_FORM_VERSION,
			'all'
		);

		wp_register_style(
			'jquery-ui-smoothness',
			paulus_form_plugin_url(
				'includes/js/jquery-ui/themes/smoothness/jquery-ui.min.css'
			),
			array(),
			'1.12.1',
			'screen'
		);

		if ( paulus_form_load_css() ) {
			paulus_form_enqueue_styles();
		}
	},
	10, 0
);


/**
 * Enqueues scripts.
 */
function paulus_form_enqueue_scripts() {
	wp_enqueue_script( 'paulus' );

	$paulus_form_obj = array(
		'api' => array(
			'root' => sanitize_url( get_rest_url() ),
			'namespace' => 'paulus-form/v1',
		),
	);

	if ( defined( 'WP_CACHE' ) and WP_CACHE ) {
		$paulus_form_obj = array_merge( $paulus_form_obj, array(
			'cached' => 1,
		) );
	}

	wp_add_inline_script( 'paulus',
		sprintf(
			'var paulus-form = %s;',
			wp_json_encode( $paulus_form_obj, JSON_PRETTY_PRINT )
		),
		'before'
	);

	do_action( 'paulus_form_enqueue_scripts' );
}


/**
 * Returns true if the main script is enqueued.
 */
function paulus_form_script_is() {
	return wp_script_is( 'paulus' );
}


/**
 * Enqueues styles.
 */
function paulus_form_enqueue_styles() {
	wp_enqueue_style( 'paulus' );

	if ( paulus_form_is_rtl() ) {
		wp_enqueue_style( 'paulus-form-rtl' );
	}

	do_action( 'paulus_form_enqueue_styles' );
}


/**
 * Returns true if the main stylesheet is enqueued.
 */
function paulus_form_style_is() {
	return wp_style_is( 'paulus' );
}


add_action(
	'wp_enqueue_scripts',
	'paulus_form_html5_fallback',
	20, 0
);

/**
 * Enqueues scripts and styles for the HTML5 fallback.
 */
function paulus_form_html5_fallback() {
	if ( ! paulus_form_support_html5_fallback() ) {
		return;
	}

	if ( paulus_form_script_is() ) {
		wp_enqueue_script( 'paulus-form-html5-fallback' );
	}

	if ( paulus_form_style_is() ) {
		wp_enqueue_style( 'jquery-ui-smoothness' );
	}
}
