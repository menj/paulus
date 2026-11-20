<?php
/**
 * Hreflang – Admin Notices
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Hreflang_Admin_Notices' ) ) {

    class Hreflang_Admin_Notices {

        private static $instance;
        private $notices;

        const TYPES = 'error,warning,info,success';

        private function __construct() {
            $this->notices = new stdClass();
            foreach ( explode( ',', self::TYPES ) as $type ) {
                $this->notices->{$type} = array();
            }
            add_action( 'admin_init',    array( $this, 'handle_dismiss' ) );
            add_action( 'admin_notices', array( $this, 'render_notices' ) );
        }

        public static function get_instance() {
            if ( ! ( self::$instance instanceof self ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function handle_dismiss() {
            if ( ! isset( $_GET['paulus_hreflang_dismiss'] ) ) {
                return;
            }
            if ( ! isset( $_GET['_hm_dismiss_nonce'] )
                 || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_hm_dismiss_nonce'] ) ), 'paulus_hreflang_dismiss_notice' ) ) {
                return;
            }
            $key = sanitize_key( wp_unslash( $_GET['paulus_hreflang_dismiss'] ) );
            if ( $key ) {
                update_option( 'paulus_hreflang_dismissed_' . $key, true );
                wp_safe_redirect( remove_query_arg( array( 'paulus_hreflang_dismiss', '_hm_dismiss_nonce' ) ) );
                exit;
            }
        }

        public function render_notices() {
            foreach ( explode( ',', self::TYPES ) as $type ) {
                foreach ( $this->notices->{$type} as $notice ) {
                    if ( $notice->dismiss_key && get_option( 'paulus_hreflang_dismissed_' . $notice->dismiss_key ) ) {
                        continue;
                    }
                    $dismiss_url = '';
                    if ( $notice->dismiss_key ) {
                        $dismiss_url = wp_nonce_url(
                            add_query_arg( 'paulus_hreflang_dismiss', $notice->dismiss_key ),
                            'paulus_hreflang_dismiss_notice',
                            '_hm_dismiss_nonce'
                        );
                    }
                    ?>
                    <div class="notice notice-<?php echo esc_attr( $type ); ?><?php echo $dismiss_url ? ' is-dismissible' : ''; ?>"
                         <?php echo $dismiss_url ? 'data-dismiss-url="' . esc_attr( $dismiss_url ) . '"' : ''; ?>>
                        <p><strong>Hreflang:</strong> <?php echo wp_kses_post( $notice->message ); ?></p>
                    </div>
                    <?php
                }
            }
        }

        public function error( $msg, $key = false )   { $this->add( 'error', $msg, $key ); }
        public function warning( $msg, $key = false )  { $this->add( 'warning', $msg, $key ); }
        public function info( $msg, $key = false )     { $this->add( 'info', $msg, $key ); }
        public function success( $msg, $key = false )  { $this->add( 'success', $msg, $key ); }

        private function add( $type, $message, $dismiss_key ) {
            $n              = new stdClass();
            $n->message     = $message;
            $n->dismiss_key = $dismiss_key;
            $this->notices->{$type}[] = $n;
        }
    }
}
