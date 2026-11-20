<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
	'wp_enqueue_scripts',
	static function () {
		$assets = array();
		$asset_file = paulus_form_plugin_path( 'includes/swv/js/index.asset.php' );

		if ( file_exists( $asset_file ) ) {
			$assets = include $asset_file;
		}

		$assets = wp_parse_args( $assets, array(
			'dependencies' => array(),
			'version' => PAULUS_FORM_VERSION,
		) );

		wp_register_script( 'swv',
			paulus_form_plugin_url( 'includes/swv/js/index.js' ),
			$assets['dependencies'],
			$assets['version'],
			array( 'in_footer' => true )
		);
	},
	10, 0
);
