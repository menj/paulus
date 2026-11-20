<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$integration = Paulus_Form_Integration::get_instance();

$service_name = paulus_form_superglobal_request( 'service' );
$service = null;

if ( $service_name and $integration->service_exists( $service_name ) ) {
	$service = $integration->get_service( $service_name );
}

$formatter = new PAULUS_FORM_HTMLFormatter( array(
	'allowed_html' => array_merge( paulus_form_kses_allowed_html(), array(
		'form' => array(
			'action' => true,
			'method' => true,
		),
	) ),
) );

$formatter->append_start_tag( 'div', array(
	'class' => 'wrap',
	'id' => 'paulus-form-integration',
) );

$formatter->append_start_tag( 'h1' );

$formatter->append_preformatted(
	esc_html( __( 'Integration with External API', 'paulus' ) )
);

$formatter->end_tag( 'h1' );

$formatter->append_start_tag( 'p' );

$formatter->append_preformatted(
	sprintf(
		/* translators: %s: URL to support page about integration with external APIs */
		__( 'You can expand the possibilities of your contact forms by integrating them with external services. For details, see <a href="%s">Integration with external APIs</a>.', 'paulus' ),
		'#'
	)
);

$formatter->end_tag( 'p' );

$formatter->call_user_func(
	static function () use ( $integration, $service, $service_name ) {
		do_action( 'paulus_form_admin_warnings',
			'paulus-form-integration', paulus_form_current_action(), $service
		);

		do_action( 'paulus_form_admin_notices',
			'paulus-form-integration', paulus_form_current_action(), $service
		);

		if ( $service ) {
			$message = paulus_form_superglobal_request( 'message' );
			$service->admin_notice( $message );

			$integration->list_services( array(
				'include' => $service_name,
			) );
		} else {
			$integration->list_services();
		}
	}
);

$formatter->print();
