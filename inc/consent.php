<?php
/**
 * Consent API: one shared way for cookie and consent plugins, and for the plugins
 * that set cookies, to ask whether a visitor has consented: wp_has_consent(),
 * wp_set_consent(), the `wp_consent_api` JavaScript object, the registration of
 * plugins that follow the API, and a Site Health test. The code is in
 * inc/consent/, with its licence beside it; the script and stylesheet are
 * assets/js/paulus-consent*.js and assets/css/paulus-consent*.css. The module
 * has no settings and stores nothing in the database.
 *
 * The function names and the script handle `wp-consent-api` are the API itself,
 * which other plugins call, so they are the one thing kept. Because the theme
 * loads after `plugins_loaded`, the functions exist from `after_setup_theme`
 * on: a plugin that calls wp_has_consent() earlier finds them missing.
 *
 * inc/plugin-retire.php switches a leftover copy of the earlier plugin off, and
 * this module adds nothing while it is still loaded.
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
function paulus_consent_ready() {
	foreach ( array( 'class-config', 'class-cookie-info', 'class-site-health', 'api-functions', 'wordpress-comments-functions' ) as $file ) {
		if ( ! is_readable( PAULUS_DIR . '/inc/consent/' . $file . '.php' ) ) {
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
if ( ! paulus_consent_ready() ) {
	return;
}

// Declared inside a condition: a plain top-level class is hoisted and would
// exist before the stand-aside check above has run.
if ( ! class_exists( 'Paulus_Consent', false ) ) {
	/**
	 * The plugin's main class, with its constants pointed into the theme.
	 */
	class Paulus_Consent {
		/** @var Paulus_Consent|null */
		private static $instance;

		/** @var Paulus_Consent_Config */
		public static $config;

		/** @var Paulus_Consent_Site_Health */
		public static $site_health;

		/** @var Paulus_Consent_Cookie_Info */
		public static $cookie_info;

		/**
		 * @return Paulus_Consent
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
			define( 'PAULUS_CONSENT_URL', trailingslashit( PAULUS_URI ) );
			define( 'PAULUS_CONSENT_PATH', PAULUS_DIR . '/inc/consent/' );
			define( 'PAULUS_CONSENT_VERSION', '2.1.0' );

			require_once PAULUS_CONSENT_PATH . 'class-config.php';
			require_once PAULUS_CONSENT_PATH . 'class-cookie-info.php';
			require_once PAULUS_CONSENT_PATH . 'class-site-health.php';
			require_once PAULUS_CONSENT_PATH . 'api-functions.php';
			require_once PAULUS_CONSENT_PATH . 'wordpress-comments-functions.php';

			self::$config      = new Paulus_Consent_Config();
			self::$site_health = new Paulus_Consent_Site_Health();
			self::$cookie_info = new Paulus_Consent_Cookie_Info();
		}
	}
}

// What the plugin did on `plugins_loaded` (priority 9), before the theme loaded.
Paulus_Consent::init();
