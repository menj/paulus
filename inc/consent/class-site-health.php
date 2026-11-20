<?php // phpcs:ignore -- Ignore the wrong filename (class- prefix) & "\r\n" notice for some machines.
/**
 * This file is part of Consent API.
 *
 * Copyright 2020 Rogier Lankhorst and the WordPress Core Privacy team.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/.
 *
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

if ( ! class_exists( 'Paulus_Consent_Site_Health' ) ) {
	/**
	 * Paulus_Consent class.
	 */
	class Paulus_Consent_Site_Health {
		/**
		 * Instance.
		 *
		 *
		 * @var Paulus_Consent_Site_Health|null
		 */
		private static $instance;

		/**
		 * Constructor.
		 *
		 */
		public function __construct() {
			if ( isset( self::$instance ) ) {
				// translators: %s the name of the PHP Class used.
				wp_die( esc_html( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'paulus' ), get_class( $this ) ) ) );
			}

			add_filter( 'site_status_tests', array( $this, 'consent_api_integration_check' ) );

			self::$instance = $this;
		}

		/**
		 * Attach the Consent API Site Health tests.
		 *
		 *
		 * @param array $tests The Site Health tests.
		 * @return array
		 */
		public function consent_api_integration_check( $tests ) {
			$tests['direct']['paulus-consent'] = array(
				'label' => __( 'Consent API test', 'paulus' ),
				'test'  => array( $this, 'paulus_consent_test' ),
			);

			return $tests;
		}

		/**
		 * Run the Consent API Site Health tests.
		 *
		 *
		 * @return array $result The Consent API Site Health tests results.
		 */
		public function paulus_consent_test() {
			$plugins                      = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				// Network-activated plugins are kept in their own option.
				$plugins = array_merge( $plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
			}
			$not_registered               = array();
			$plugins_without_registration = false;

			foreach ( $plugins as $plugin ) {
				if ( ! consent_api_registered( $plugin ) ) {
					$not_registered[]             = $plugin;
					$plugins_without_registration = true;
				}
			}

			$result = array(
				'label'       => __( 'All plugins have declared to use the Consent API', 'paulus' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Compliance', 'paulus' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'All plugins have declared in their code that they are following the guidelines from the Consent API. When used in combination with a Cookie Management plugin, this will improve compliancy for your site.', 'paulus' )
				),
				'actions'     => '',
				'test'        => 'paulus-consent',
			);

			if ( $plugins_without_registration ) {
				$result['status']      = 'recommended';
				$result['label']       = __( 'One or more plugins are not conforming to the Consent API.', 'paulus' );
				$result['description'] = '<span class="title">' . __( 'Not all plugins have declared to follow Consent API guidelines. Please contact the developer.', 'paulus' ) . '</span>';
				$result['actions']     = '<p>' . implode( '<p>', $not_registered );
			}

			return $result;
		}
	}
}
