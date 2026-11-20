<?php // phpcs:ignore -- Ignore the missing class- prefix from file & "\r\n" notice for some machines.

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

if ( ! class_exists( 'Paulus_Consent_Config' ) ) {
	/**
	 * Paulus_Consent_Config definition.
	 *
	 */
	class Paulus_Consent_Config {
		/**
		 * Instance.
		 *
		 *
		 * @var Paulus_Consent_Config|null
		 */
		private static $instance;

		/**
		 * Constructor.
		 *
		 *
		 * @return void
		 */
		public function __construct() {
			if ( isset( self::$instance ) ) {
				// translators: %s the name of the PHP Class used.
				wp_die( esc_html( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'paulus' ), get_class( $this ) ) ) );
			}

			self::$instance = $this;
		}

		/**
		 * Get default filterable list of active consent types.
		 *
		 *
		 * @return array
		 */
		public function consent_types(): array {
			return apply_filters(
				'wp_consent_types',
				array(
					'optin',
					'optout',
				)
			);
		}

		/**
		 * Get default filterable cookie prefix.
		 *
		 *
		 * @return string
		 */
		public function consent_cookie_prefix(): string {
			return apply_filters(
				'wp_consent_cookie_prefix',
				'wp_consent'
			);
		}

		/**
		 * Get default filterable list of active consent categories.
		 *
		 *
		 * @return array
		 */
		public function consent_categories(): array {
			return apply_filters(
				'wp_consent_categories',
				array(
					'functional',
					'preferences',
					'statistics',
					'statistics-anonymous',
					'marketing',
				)
			);
		}

		/**
		 * Get default filterable list of possible consent values.
		 *
		 *
		 * @return array $consent_values
		 */
		public function consent_values(): array {
			return apply_filters(
				'wp_consent_values',
				array(
					'allow',
					'deny',
				)
			);
		}

		/**
		 * Get default filterable cookie expiration.
		 *
		 *
		 * @return int Cookie expiration in days.
		 */
		public function cookie_expiration_days(): int {
			return apply_filters( 'wp_cookie_expiration', 30 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is intended for Core.
		}
	}
}
