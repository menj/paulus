<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Paulus_Form_Service' ) ) {
	return;
}

class Paulus_Form_Stripe extends Paulus_Form_Service {

	private static $instance;
	private $api_keys;


	public static function get_instance() {
		self::$instance ??= new self();

		return self::$instance;
	}


	private function __construct() {
		$option = Paulus_Form::get_option( 'stripe' );

		if (
			isset( $option['api_keys']['publishable'] ) and
			isset( $option['api_keys']['secret'] )
		) {
			$this->api_keys = array(
				'publishable' => $option['api_keys']['publishable'],
				'secret' => $option['api_keys']['secret'],
			);
		}
	}


	public function get_title() {
		return __( 'Stripe', 'paulus' );
	}


	public function is_active() {
		return (bool) $this->get_api_keys();
	}


	public function api() {
		if ( $this->is_active() ) {
			$api = new Paulus_Form_Stripe_API( $this->api_keys['secret'] );
			return $api;
		}
	}


	public function get_api_keys() {
		return $this->api_keys;
	}


	public function get_categories() {
		return array( 'payments' );
	}


	public function icon() {
	}


	public function link() {
		echo wp_kses_data( paulus_form_link(
			'https://stripe.com/',
			'stripe.com'
		) );
	}


	protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'paulus-form-integration', false );
		$url = add_query_arg( array( 'service' => 'stripe' ), $url );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}


	protected function save_data() {
		Paulus_Form::update_option( 'stripe', array(
			'api_keys' => $this->api_keys,
		) );
	}


	protected function reset_data() {
		$this->api_keys = null;
		$this->save_data();
	}


	public function load( $action = '' ) {
		if (
			'setup' === $action and
			'POST' === paulus_form_superglobal_server( 'REQUEST_METHOD' )
		) {
			check_admin_referer( 'paulus-form-stripe-setup' );

			if ( ! empty( $_POST['reset'] ) ) {
				$this->reset_data();
				$redirect_to = $this->menu_page_url( 'action=setup' );
			} else {
				$publishable = paulus_form_superglobal_post( 'publishable' );
				$secret = paulus_form_superglobal_post( 'secret' );

				if ( $publishable and $secret ) {
					$this->api_keys = array(
						'publishable' => $publishable,
						'secret' => $secret,
					);

					$this->save_data();

					$redirect_to = $this->menu_page_url( array(
						'message' => 'success',
					) );
				} else {
					$redirect_to = $this->menu_page_url( array(
						'action' => 'setup',
						'message' => 'invalid',
					) );
				}
			}

			wp_safe_redirect( $redirect_to );
			exit();
		}
	}


	public function admin_notice( $message = '' ) {
		if ( 'invalid' === $message ) {
			wp_admin_notice(
				__( '<strong>Error:</strong> Invalid key values.', 'paulus' ),
				array( 'type' => 'error' )
			);
		}

		if ( 'success' === $message ) {
			wp_admin_notice(
				__( 'Settings saved.', 'paulus' ),
				array( 'type' => 'success' )
			);
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
			esc_html( __( 'Stripe is a simple and powerful way to accept payments online. Stripe has no setup fees, no monthly fees, and no hidden costs. Millions of businesses rely on Stripe&#8217;s software tools to accept payments securely and expand globally.', 'paulus' ) )
		);

		$formatter->end_tag( 'p' );

		$formatter->append_start_tag( 'p' );
		$formatter->append_start_tag( 'strong' );

		$formatter->append_preformatted(
			paulus_form_link(
				'#',
				__( 'Stripe integration', 'paulus' )
			)
		);

		$formatter->end_tag( 'p' );

		if ( $this->is_active() ) {
			$formatter->append_start_tag( 'p', array(
				'class' => 'dashicons-before dashicons-yes',
			) );

			$formatter->append_preformatted(
				esc_html( __( 'Stripe is active on this site.', 'paulus' ) )
			);

			$formatter->end_tag( 'p' );
		}

		if ( 'setup' === $action ) {
			$formatter->call_user_func( function () {
				$this->display_setup();
			} );
		} elseif ( is_ssl() or WP_DEBUG ) {
			$formatter->append_start_tag( 'p' );

			$formatter->append_start_tag( 'a', array(
				'href' => esc_url( $this->menu_page_url( 'action=setup' ) ),
				'class' => 'button',
			) );

			$formatter->append_preformatted(
				esc_html( __( 'Setup integration', 'paulus' ) )
			);

			$formatter->end_tag( 'p' );
		} else {
			$formatter->append_start_tag( 'p', array(
				'class' => 'dashicons-before dashicons-warning',
			) );

			$formatter->append_preformatted(
				esc_html( __( 'Stripe is not available on this site. It requires an HTTPS-enabled site.', 'paulus' ) )
			);

			$formatter->end_tag( 'p' );
		}

		$formatter->print();
	}


	private function display_setup() {
		$api_keys = $this->get_api_keys();

		if ( $api_keys ) {
			$publishable = $api_keys['publishable'];
			$secret = $api_keys['secret'];
		} else {
			$publishable = '';
			$secret = '';
		}

?>
<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>">
<?php wp_nonce_field( 'paulus-form-stripe-setup' ); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="publishable"><?php echo esc_html( __( 'Publishable Key', 'paulus' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( $publishable );
			echo sprintf(
				'<input type="hidden" value="%s" id="publishable" name="publishable" />',
				esc_attr( $publishable )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%s" id="publishable" name="publishable" class="regular-text code" />',
				esc_attr( $publishable )
			);
		}
	?></td>
</tr>
<tr>
	<th scope="row"><label for="secret"><?php echo esc_html( __( 'Secret Key', 'paulus' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( paulus_form_mask_password( $secret ) );
			echo sprintf(
				'<input type="hidden" value="%s" id="secret" name="secret" />',
				esc_attr( $secret )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%s" id="secret" name="secret" class="regular-text code" />',
				esc_attr( $secret )
			);
		}
	?></td>
</tr>
</tbody>
</table>
<?php
		if ( $this->is_active() ) {
			submit_button(
				_x( 'Remove Keys', 'API keys', 'paulus' ),
				'small', 'reset'
			);
		} else {
			submit_button( __( 'Save Changes', 'paulus' ) );
		}
?>
</form>
<?php
	}
}
