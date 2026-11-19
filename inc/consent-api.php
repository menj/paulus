<?php
/**
 * Consent API: a native form of the plugin WP Consent API 2.1.0 (WordPress
 * Contributors, GPL v2 or later). It gives cookie plugins and the plugins
 * that set cookies one shared way to ask whether a visitor has consented:
 * wp_has_consent(), wp_set_consent(), the `wp_consent_api` JavaScript object,
 * the registration of plugins that follow the API, and the Site Health test.
 * The plugin's code is in inc/consent-api/, with its licence beside it.
 *
 * What changes in the theme: the script and stylesheet are
 * assets/js/wp-consent-api*.js and assets/css/wp-consent-api*.css, and the
 * plugin's `plugins_loaded` start-up is a direct call here. The module has no
 * settings and stores nothing in the database, so there is nothing to copy. Because
 * the theme loads after `plugins_loaded`, the functions exist from `after_setup_theme`
 * on: a plugin that calls wp_has_consent() earlier than that finds them missing.
 *
 * While the plugin itself is active the theme adds nothing, and
 * inc/plugin-retire.php switches the plugin off.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defined before anything can stand aside: inc/plugin-retire.php switches
 * the plugin off only while this module is loaded.
 *
 * @return bool
 */
function paulus_consent_api_ready() {
	foreach ( array( 'class-wp-consent-api-config', 'class-wp-consent-api-cookie-info', 'class-wp-consent-api-site-health', 'api-functions', 'wordpress-comments-functions' ) as $file ) {
		if ( ! is_readable( PAULUS_DIR . '/inc/consent-api/' . $file . '.php' ) ) {
			return false;
		}
	}
	return true;
}

if ( class_exists( 'WP_Consent_API', false ) ) {
	return;
}

// A half-uploaded theme: without every file the module stays out, and
// the plugin is not switched off (the ready function says so).
if ( ! paulus_consent_api_ready() ) {
	return;
}

// Declared inside a condition: a plain top-level class is hoisted and would
// exist before the stand-aside check above has run.
if ( ! class_exists( 'WP_Consent_API', false ) ) {
	/**
	 * The plugin's main class, with its constants pointed into the theme.
	 */
	class WP_Consent_API {
		/** @var WP_Consent_API|null */
		private static $instance;

		/** @var WP_Consent_API_Config */
		public static $config;

		/** @var WP_Consent_API_Site_Health */
		public static $site_health;

		/** @var WP_Consent_API_Cookie_Info */
		public static $cookie_info;

		/**
		 * @return WP_Consent_API
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Start the module.
		 */
		public static function init(): void {
			self::get_instance();
		}

		private function __construct() {
			// The plugin's asset paths read `assets/js/...` and `assets/css/...`,
			// which are the theme's own, so the URL is the theme root.
			define( 'WP_CONSENT_API_URL', trailingslashit( PAULUS_URI ) );
			define( 'WP_CONSENT_API_PATH', PAULUS_DIR . '/inc/consent-api/' );
			define( 'WP_CONSENT_API_PLUGIN', 'wp-consent-api/wp-consent-api.php' );
			define( 'WP_CONSENT_API_VERSION', '2.1.0' );
			define( 'WP_CONSENT_API_PLUGIN_FILE', WP_CONSENT_API_PATH . 'class-wp-consent-api-config.php' );

			require_once WP_CONSENT_API_PATH . 'class-wp-consent-api-config.php';
			require_once WP_CONSENT_API_PATH . 'class-wp-consent-api-cookie-info.php';
			require_once WP_CONSENT_API_PATH . 'class-wp-consent-api-site-health.php';
			require_once WP_CONSENT_API_PATH . 'api-functions.php';
			require_once WP_CONSENT_API_PATH . 'wordpress-comments-functions.php';

			self::$config      = new WP_Consent_API_Config();
			self::$site_health = new WP_Consent_API_Site_Health();
			self::$cookie_info = new WP_Consent_API_Cookie_Info();
		}
	}
}

// What the plugin did on `plugins_loaded` (priority 9), before the theme loaded.
WP_Consent_API::init();
