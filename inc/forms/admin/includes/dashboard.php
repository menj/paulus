<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once ABSPATH . 'wp-admin/includes/dashboard.php';

/**
 * Defines the core widgets for the Forms Dashboard.
 */
function paulus_form_dashboard_widgets() {
	return array(
		'paulus_form_dashboard_right_now' => array(
			'widget_name' => __( 'At a Glance', 'paulus' ),
		),
		'paulus_form_spam_protection' => array(
			'widget_name' => __( 'Spam Protection', 'paulus' ),
			'context' => 'side',
		),
		'paulus_form_save_messages' => array(
			'widget_name' => __( 'Save Messages', 'paulus' ),
			'context' => 'side',
		),
	);
}


/**
 * Adds dashboard widgets to the Forms Dashboard screen.
 */
function paulus_form_dashboard_setup() {
	foreach ( paulus_form_dashboard_widgets() as $widget_id => $widget ) {
		wp_add_dashboard_widget(
			$widget_id,
			$widget['widget_name'] ?? '',
			$widget['callback'] ?? $widget_id,
			$widget['control_callback'] ?? null,
			$widget['callback_args'] ?? null,
			$widget['context'] ?? 'normal',
			$widget['priority'] ?? 'core'
		);
	}

	do_action( 'paulus_form_dashboard_setup' );
}


/**
 * Defines the "At a Glance" dashboard widget content.
 */
function paulus_form_dashboard_right_now() {
	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'ul' );

	$formatter->append_start_tag( 'li', array(
		'class' => 'page-count',
	) );

	$formatter->append_start_tag( 'a', array(
		'href' => menu_page_url( 'paulus-form', false ),
	) );

	Paulus_Form_ContactForm::find();
	$count = Paulus_Form_ContactForm::count();

	$formatter->append_preformatted(
		esc_html( sprintf(
			/* translators: %s: number of contact forms */
			_n(
				'%s Contact form',
				'%s Contact forms',
				$count,
				'paulus'
			),
			number_format_i18n( $count )
		) )
	);

	if ( current_user_can( 'paulus_form_manage_integration' ) ) {
		$integration = Paulus_Form_Integration::get_instance();

		$services = array( 'sendinblue', 'turnstile', 'recaptcha', 'stripe' );

		foreach ( $services as $service_name ) {
			if ( $integration->service_exists( $service_name ) ) {
				$service = $integration->get_service( $service_name );

				if ( $service->is_active() ) {
					$formatter->append_start_tag( 'li', array(
						'class' => sprintf(
							'active-integration %s',
							$service_name
						),
					) );

					$setup_page_link = add_query_arg(
						array(
							'service' => $service_name,
							'action' => 'setup',
					 	),
						menu_page_url( 'paulus-form-integration', false )
					);

					$formatter->append_start_tag( 'a', array(
						'href' => $setup_page_link,
					) );

					$formatter->append_preformatted(
						sprintf(
							/* translators: %s: service name */
							__( '<strong>%s</strong> integration is active', 'paulus' ),
							$service->get_title()
						)
					);
				}
			}
		}
	}

	if ( defined( 'FLAMINGO_VERSION' ) ) {
		$formatter->append_start_tag( 'li', array(
			'class' => 'flamingo',
		) );

		$formatter->append_start_tag( 'a', array(
			'href' => menu_page_url( 'flamingo_inbound', false ),
		) );

		$formatter->append_preformatted(
			sprintf(
				/* translators: %s: Flamingo version */
				__( '<strong>Flamingo</strong> (version %s) is active', 'paulus' ),
				FLAMINGO_VERSION
			)
		);
	}

	$formatter->end_tag( 'ul' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		esc_html( sprintf(
			/* translators: %s: Forms version string */
			__( 'Forms version %s is running.', 'paulus' ),
			PAULUS_FORM_VERSION
		) )
	);

	$formatter->print();
}


/**
 * Defines the "Spam Protection" dashboard widget content.
 */
function paulus_form_spam_protection() {
	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		__( 'Spammers target everything. Your contact forms are not an exception.', 'paulus' )
	);

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		sprintf(
			/* translators: 1: URL to the Turnstile doc page, 2: URL to the Akismet doc page */
			__( 'To provide effective protection, Forms recommends using <a href="%1$s"><strong>Turnstile</strong></a> (good at blocking bots) and <a href="%2$s"><strong>Akismet</strong></a> (good at blocking human spammers) together.', 'paulus' ),
			'#',
			'#'
		)
	);

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		sprintf(
			/* translators: %s: URL to the Disallowed list doc page */
			__( 'You can also block submissions that contain specific keywords or come from specific IP addresses (<a href="%s"><strong>Disallowed list</strong></a>).', 'paulus' ),
			'#'
		)
	);

	$formatter->print();
}


/**
 * Defines the "Save Messages" dashboard widget content.
 */
function paulus_form_save_messages() {
	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		sprintf(
			/* translators: %s: URL to the Flamingo doc page */
			__( 'Forms itself doesn&#8217;t have a feature to store submitted messages; therefore, there is always a risk of losing important messages. Forms recommends installing a separate plugin called <a href="%s"><strong>Flamingo</strong></a>, which is dedicated to storing messages in the database.', 'paulus' ),
			'#'
		)
	);

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		sprintf(
			/* translators: %s: URL to the Brevo integration doc page */
			__( 'If you use contact forms for customer relationship management, consider activating the <a href="%s"><strong>Brevo</strong></a> integration. With the integration, Forms can safely connect to the leading CRM platform in Europe, while protecting user privacy.', 'paulus' ),
			'#'
		)
	);

	$formatter->print();
}
