<?php
/**
 * Constant Contact module main file
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
	'paulus_form_init',
	'paulus_form_constant_contact_register_service',
	120, 0
);

/**
 * Registers the Constant Contact service.
 */
function paulus_form_constant_contact_register_service() {
	$integration = Paulus_Form_Integration::get_instance();

	$integration->add_service( 'constant_contact',
		Paulus_Form_ConstantContact::get_instance()
	);
}


/**
 * The Paulus_Form_Service subclass for Constant Contact.
 */
class Paulus_Form_ConstantContact extends Paulus_Form_Service {
	const service_name = 'constant_contact';

	private static $instance;

	protected $client_id = '';
	protected $client_secret = '';

	public static function get_instance() {
		self::$instance ??= new self();

		return self::$instance;
	}

	private function __construct() {
		$option = (array) Paulus_Form::get_option( self::service_name );
		$this->client_id = $option['client_id'] ?? '';
		$this->client_secret = $option['client_secret'] ?? '';
	}

	protected function reset_data() {
		Paulus_Form::update_option( self::service_name, array() );
	}

	public function get_title() {
		return __( 'Constant Contact', 'paulus' );
	}

	public function is_active() {
		return $this->client_id || $this->client_secret;
	}

	public function get_categories() {
		return array( 'email_marketing' );
	}

	public function icon() {
	}

	public function link() {
	}

	protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = add_query_arg(
			array( 'service' => self::service_name ),
			menu_page_url( 'paulus-form-integration', false )
		);

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	public function load( $action = '' ) {
		if (
			'setup' === $action and
			'POST' === paulus_form_superglobal_server( 'REQUEST_METHOD' )
		) {
			check_admin_referer( 'paulus-form-constant-contact-setup' );

			if ( paulus_form_superglobal_post( 'reset' ) ) {
				$this->reset_data();
				$message = 'reset';
			}

			wp_safe_redirect( $this->menu_page_url( array(
				'action' => 'setup',
				'message' => $message ?? '',
			) ) );

			exit();
		}
	}

	public function admin_notice( $message = '' ) {
		switch ( $message ) {
			case 'reset':
				wp_admin_notice(
					__( 'API configuration cleared.', 'paulus' ),
					array( 'type' => 'success' )
				);

				break;
		}
	}

	public function display( $action = '' ) {
		$formatter = new PAULUS_FORM_HTMLFormatter( array(
			'allowed_html' => array_merge( paulus_form_kses_allowed_html(), array(
				'form' => array(
					'action' => true,
					'method' => true,
				),
			) ),
		) );

		$formatter->append_start_tag( 'p' );

		$formatter->append_preformatted(
			paulus_form_link(
				'#',
				__( 'The Constant Contact integration has been removed.', 'paulus' )
			)
		);

		$formatter->end_tag( 'p' );

		if ( $this->is_active() ) {
			$formatter->append_start_tag( 'form', array(
				'method' => 'post',
				'action' => esc_url( $this->menu_page_url( 'action=setup' ) ),
			) );

			$formatter->call_user_func( static function () {
				wp_nonce_field( 'paulus-form-constant-contact-setup' );
			} );

			$formatter->append_start_tag( 'table', array(
				'class' => 'form-table',
			) );

			$formatter->append_start_tag( 'tbody' );

			$formatter->append_start_tag( 'tr' );

			$formatter->append_start_tag( 'th', array(
				'scope' => 'row',
			) );

			$formatter->append_start_tag( 'label', array(
				'for' => 'client_id',
			) );

			$formatter->append_preformatted(
				esc_html( __( 'API Key', 'paulus' ) )
			);

			$formatter->end_tag( 'th' );

			$formatter->append_start_tag( 'td' );

			$formatter->append_preformatted( esc_html( $this->client_id ) );

			$formatter->end_tag( 'tr' );

			$formatter->append_start_tag( 'tr' );

			$formatter->append_start_tag( 'th', array(
				'scope' => 'row',
			) );

			$formatter->append_start_tag( 'label', array(
				'for' => 'client_secret',
			) );

			$formatter->append_preformatted(
				esc_html( __( 'App Secret', 'paulus' ) )
			);

			$formatter->end_tag( 'th' );

			$formatter->append_start_tag( 'td' );

			$formatter->append_preformatted(
				esc_html( paulus_form_mask_password( $this->client_secret, 4, 4 ) )
			);

			$formatter->end_tag( 'table' );

			$formatter->call_user_func( function () {
				submit_button(
					_x( 'Remove Keys', 'API keys', 'paulus' ),
					'small', 'reset'
				);
			} );
		}

		$formatter->print();
	}

}
