<?php
/**
 * Plugins the theme now does itself. When one of them is active, the theme
 * switches it off, once its native form is loaded, and says so in an admin
 * notice. The plugin's files stay where they are; nothing is deleted, and
 * the plugin's settings were copied into Theme Options by the module that
 * replaced it.
 *
 * A plugin cannot be switched off by the theme where it is network-active or
 * a must-use plugin; the notice says so and the plugin stays.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * The plugins the theme has taken over.
 *
 * name   What the plugin is called.
 * where  Where its settings are now.
 * ready  A function the native module defines: the plugin is only switched
 *        off while the module that replaces it is loaded.
 *
 * @return array<string,array<string,string>>
 */
function paulus_retired_plugins() {
	$list = array(
		'auto-justify-content/auto-justify-content.php' => array(
			'name'  => 'Auto Justify Content',
			'where' => __( 'Theme Options, Justify', 'paulus' ),
			'ready' => 'paulus_justify_import',
		),
		'citepress/citepress.php' => array(
			'name'  => 'CitePress',
			'where' => __( 'the CitePress menu', 'paulus' ),
			'ready' => 'paulus_citepress_ready',
		),
		'endmark/endmark.php' => array(
			'name'  => 'Endmark',
			'where' => __( 'Settings, Endmark', 'paulus' ),
			'ready' => 'paulus_endmark_ready',
		),
		'contact-form-7/wp-contact-form-7.php' => array(
			'name'  => 'Contact Form 7',
			'where' => __( 'the Contact menu', 'paulus' ),
			'ready' => 'paulus_cf7_ready',
		),
		'hrefmaster/hrefmaster.php' => array(
			'name'  => 'HrefMaster',
			'where' => __( 'the HrefMaster menu', 'paulus' ),
			'ready' => 'paulus_hrefmaster_ready',
		),
	);
	return (array) apply_filters( 'paulus_retired_plugins', $list );
}

/**
 * Switch off every plugin in the list that is active.
 *
 * @return string[] Names of the plugins switched off.
 */
function paulus_retire_plugins() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$done    = array();
	$blocked = array();
	foreach ( paulus_retired_plugins() as $file => $info ) {
		if ( ! empty( $info['ready'] ) && ! function_exists( $info['ready'] ) ) {
			continue;
		}
		if ( ! is_plugin_active( $file ) ) {
			continue;
		}
		if ( is_multisite() && is_plugin_active_for_network( $file ) ) {
			$blocked[] = $info['name'];
			continue;
		}
		// Silent: the plugin's own deactivation code is not run, so nothing
		// it might clear on deactivation is lost.
		deactivate_plugins( $file, true );
		$done[] = $info['name'];
	}
	if ( $done ) {
		set_transient( 'paulus_retired_done', $done, 10 * MINUTE_IN_SECONDS );
	}
	if ( $blocked ) {
		set_transient( 'paulus_retired_blocked', $blocked, 10 * MINUTE_IN_SECONDS );
	}
	return $done;
}

/** In the admin, for people who can manage plugins. */
function paulus_retire_on_admin() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	if ( ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
		return;
	}
	paulus_retire_plugins();
}
add_action( 'admin_init', 'paulus_retire_on_admin', 5 );

/** A plugin activated by hand is switched off again at once, with the notice. */
function paulus_retire_on_activation( $plugin ) {
	$list = paulus_retired_plugins();
	if ( isset( $list[ $plugin ] ) ) {
		paulus_retire_plugins();
	}
}
add_action( 'activated_plugin', 'paulus_retire_on_activation' );

/** The notice. */
function paulus_retire_notice() {
	$done    = get_transient( 'paulus_retired_done' );
	$blocked = get_transient( 'paulus_retired_blocked' );
	if ( is_array( $done ) && $done ) {
		delete_transient( 'paulus_retired_done' );
		$list = paulus_retired_plugins();
		$where = array();
		foreach ( $list as $info ) {
			if ( in_array( $info['name'], $done, true ) ) {
				$where[] = $info['where'];
			}
		}
		printf(
			'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: 1: plugin names, 2: where the settings are now. */
					__( 'The theme does the work of %1$s itself, so the plugin was switched off. Its settings are now under %2$s.', 'paulus' ),
					implode( ', ', $done ),
					implode( '; ', array_unique( $where ) )
				)
			)
		);
	}
	if ( is_array( $blocked ) && $blocked ) {
		delete_transient( 'paulus_retired_blocked' );
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: plugin names. */
					__( 'The theme does the work of %s itself, but the plugin is network-active, so only a network administrator can switch it off.', 'paulus' ),
					implode( ', ', $blocked )
				)
			)
		);
	}
}
add_action( 'admin_notices', 'paulus_retire_notice' );
add_action( 'network_admin_notices', 'paulus_retire_notice' );
