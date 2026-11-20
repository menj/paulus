<?php
/**
 * Stripe module main file
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

paulus_form_include_module_file( 'stripe/service.php' );
paulus_form_include_module_file( 'stripe/api.php' );


add_action(
	'paulus_form_init',
	'paulus_form_stripe_register_service',
	50, 0
);

/**
 * Registers the Stripe service.
 */
function paulus_form_stripe_register_service() {
	$integration = Paulus_Form_Integration::get_instance();

	$integration->add_service( 'stripe',
		Paulus_Form_Stripe::get_instance()
	);
}


add_action(
	'paulus_form_enqueue_scripts',
	'paulus_form_stripe_enqueue_scripts',
	10, 0
);

/**
 * Enqueues scripts and styles for the Stripe module.
 */
function paulus_form_stripe_enqueue_scripts() {
	$service = Paulus_Form_Stripe::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	wp_enqueue_style( 'paulus-form-stripe',
		paulus_form_plugin_url( 'modules/stripe/style.css' ),
		array(), PAULUS_FORM_VERSION, 'all'
	);

	wp_register_script(
		'stripe',
		'https://js.stripe.com/v3/',
		array(),
		null,
		array( 'in_footer' => true )
	);

	$assets = include paulus_form_plugin_path( 'modules/stripe/index.asset.php' );

	$assets = wp_parse_args( $assets, array(
		'dependencies' => array(),
		'version' => PAULUS_FORM_VERSION,
	) );

	wp_enqueue_script(
		'paulus-form-stripe',
		paulus_form_plugin_url( 'modules/stripe/index.js' ),
		array_merge(
			$assets['dependencies'],
			array(
				'wp-polyfill',
				'paulus-form',
				'stripe',
			)
		),
		$assets['version'],
		array( 'in_footer' => true )
	);

	$api_keys = $service->get_api_keys();

	if ( $api_keys['publishable'] ) {
		wp_add_inline_script( 'paulus-form-stripe',
			sprintf(
				'var paulus_form_stripe = %s;',
				wp_json_encode( array(
					'publishable_key' => $api_keys['publishable'],
				), JSON_PRETTY_PRINT )
			),
			'before'
		);
	}
}


add_filter(
	'paulus_form_skip_spam_check',
	'paulus_form_stripe_skip_spam_check',
	10, 2
);

/**
 * Skips the spam check if it is not necessary.
 *
 * @return bool True if the spam check is not necessary.
 */
function paulus_form_stripe_skip_spam_check( $skip_spam_check, $submission ) {
	$service = Paulus_Form_Stripe::get_instance();

	if ( ! $service->is_active() ) {
		return $skip_spam_check;
	}

	$pi_id = paulus_form_superglobal_post( '_paulus_form_stripe_payment_intent' );

	if (
		$pi_id and
		$payment_intent = $service->api()->retrieve_payment_intent( $pi_id )
	) {
		$metadata = $payment_intent['metadata'] ?? array();
		$status = $payment_intent['status'] ?? '';

		$pi_params_hash = paulus_form_stripe_pi_params_hash( $submission );

		if (
			isset( $metadata['paulus_form_pending'] ) and
			! isset( $metadata['paulus_form_complete'] ) and
			'succeeded' === $status
		) {
			$submission->push( 'payment_intent', $pi_id );

			if ( ! isset( $metadata['paulus_form_fulfilled'] ) ) {
				$metadata = array_merge( $metadata, array(
					'paulus_form_fulfilled' => $pi_params_hash,
				) );

				$service->api()->update_payment_intent( $pi_id, array(
					'metadata' => $metadata,
				) );
			}

			if (
				$submission->verify_posted_data_hash() and
				hash_equals( $metadata['paulus_form_fulfilled'], $pi_params_hash )
			) {
				return true; // Posted data unchanged. Skip spam check.
			}
		}
	}

	return $skip_spam_check;
}


add_filter(
	'paulus_form_spam',
	'paulus_form_stripe_verify_payment_intent',
	6, 2
);

/**
 * Verifies submitted Stripe Payment Intent ID.
 */
function paulus_form_stripe_verify_payment_intent( $spam, $submission ) {
	if ( $spam ) {
		return $spam;
	}

	$service = Paulus_Form_Stripe::get_instance();

	if ( ! $service->is_active() ) {
		return $spam;
	}

	$pi_id = paulus_form_superglobal_post( '_paulus_form_stripe_payment_intent' );

	if ( ! $pi_id ) {
		return $spam;
	}

	$payment_intent = $service->api()->retrieve_payment_intent( $pi_id );

	if ( $payment_intent ) {
		$metadata = $payment_intent['metadata'] ?? array();
		$pi_params_hash = paulus_form_stripe_pi_params_hash( $submission );

		if (
			isset( $metadata['paulus_form_fulfilled'] ) and
			! isset( $metadata['paulus_form_complete'] ) and
			hash_equals( $metadata['paulus_form_fulfilled'], $pi_params_hash )
		) {
			return $spam;
		}
	}

	// No PI exists or refers to the wrong PI.
	$submission->add_spam_log( array(
		'agent' => 'stripe',
		'reason' => __( 'Invalid Stripe Payment Intent ID detected.', 'paulus' ),
	) );

	return true;
}


add_action(
	'paulus_form_before_send_mail',
	'paulus_form_stripe_before_send_mail',
	10, 3
);

/**
 * Creates Stripe's Payment Intent.
 */
function paulus_form_stripe_before_send_mail( $contact_form, &$abort, $submission ) {
	$service = Paulus_Form_Stripe::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	$pi_id = $submission->pull( 'payment_intent' );

	if ( $pi_id ) {
		$payment_intent = $service->api()->retrieve_payment_intent( $pi_id );

		$metadata = array_merge(
			$payment_intent['metadata'] ?? array(),
			array(
				'paulus_form_complete' => paulus_form_stripe_pi_params_hash( $submission ),
			)
		);

		$service->api()->update_payment_intent( $pi_id, array(
			'metadata' => $metadata,
		) );

		return;
	} else {
		$pi_params = paulus_form_stripe_get_pi_params( $submission );

		if ( ! isset( $pi_params ) ) { // No [stripe] form-tag found.
			return;
		}

		$metadata = array_merge(
			$pi_params['metadata'] ?? array(),
			array(
				'paulus_form_pending' => paulus_form_stripe_pi_params_hash( $submission ),
			)
		);

		$payment_intent = $service->api()->create_payment_intent(
			array_merge( $pi_params, array( 'metadata' => $metadata ) )
		);

		if ( $payment_intent ) {
			$submission->add_result_props( array(
				'stripe' => array(
					'payment_intent' => array(
						'id' => $payment_intent['id'],
						'client_secret' => $payment_intent['client_secret'],
					),
				),
			) );

			$submission->set_status( 'payment_required' );

			$submission->set_response(
				__( 'Payment is required. Please pay by credit card.', 'paulus' )
			);
		}

		$abort = true;
	}
}


/**
 * Returns Payment Intent parameters.
 *
 * @param Paulus_Form_Submission $submission Submission object.
 * @return array|null Payment Intent parameters.
 *                    Null if no [stripe] form-tag found.
 */
function paulus_form_stripe_get_pi_params( $submission ) {
	$contact_form = $submission->get_contact_form();
	$tags = $contact_form->scan_form_tags( array( 'type' => 'stripe' ) );

	if ( ! $tags ) {
		return null;
	}

	$tag = $tags[0];
	$amount = $tag->get_option( 'amount', 'int', true );
	$currency = $tag->get_option( 'currency', '[a-zA-Z]{3}', true );

	$pi_params = array(
		'amount' => $amount ? absint( $amount ) : null,
		'currency' => $currency ? strtolower( $currency ) : null,
		'receipt_email' => $submission->get_posted_data( 'your-email' ),
	);

	$pi_params = apply_filters(
		'paulus_form_stripe_payment_intent_parameters',
		$pi_params
	);

	return (array) $pi_params;
}


/**
 * Returns the hash of Payment Intent parameters plus some submission metadata.
 */
function paulus_form_stripe_pi_params_hash( $submission, $length = 7 ) {
	$params = array_merge(
		paulus_form_stripe_get_pi_params( $submission ) ?? array(),
		array(
			'unit_tag' => $submission->get_meta( 'unit_tag' ),
			'remote_ip' => $submission->get_meta( 'remote_ip' ),
		)
	);

	ksort( $params );

	$hash = wp_hash( wp_json_encode( $params ), 'paulus_form_stripe' );

	return substr( $hash, 0, absint( $length ) );
}


/**
 * Returns payment link URL.
 *
 * @param string $pi_id Payment Intent ID.
 * @return string The URL.
 */
function paulus_form_stripe_get_payment_link( $pi_id ) {
	return sprintf(
		'https://dashboard.stripe.com/payments/%s',
		urlencode( $pi_id )
	);
}


add_filter(
	'paulus_form_special_mail_tags',
	'paulus_form_stripe_smt',
	10, 4
);

/**
 * Registers the [_stripe_payment_link] special mail-tag.
 */
function paulus_form_stripe_smt( $output, $tag_name, $html, $mail_tag = null ) {
	if ( '_stripe_payment_link' === $tag_name ) {
		$submission = Paulus_Form_Submission::get_instance();

		$pi_id = $submission->pull( 'payment_intent' );

		if ( ! empty( $pi_id ) ) {
			$output = paulus_form_stripe_get_payment_link( $pi_id );
		}
	}

	return $output;
}


add_filter(
	'paulus_form_flamingo_inbound_message_parameters',
	'paulus_form_stripe_add_flamingo_inbound_message_params',
	10, 1
);

/**
 * Adds Stripe-related meta data to Flamingo Inbound Message parameters.
 */
function paulus_form_stripe_add_flamingo_inbound_message_params( $args ) {
	$submission = Paulus_Form_Submission::get_instance();

	$pi_id = $submission->pull( 'payment_intent' );

	if ( empty( $pi_id ) ) {
		return $args;
	}

	$pi_link = paulus_form_stripe_get_payment_link( $pi_id );

	$meta = (array) $args['meta'];

	$meta['stripe_payment_link'] = $pi_link;

	$args['meta'] = $meta;

	return $args;
}


add_action(
	'paulus_form_init',
	'paulus_form_add_form_tag_stripe',
	10, 0
);

/**
 * Registers the stripe form-tag handler.
 */
function paulus_form_add_form_tag_stripe() {
	paulus_form_add_form_tag(
		'stripe',
		'paulus_form_stripe_form_tag_handler',
		array(
			'display-block' => true,
			'singular' => true,
		)
	);
}


/**
 * Defines the stripe form-tag handler.
 *
 * @return string HTML content that replaces a stripe form-tag.
 */
function paulus_form_stripe_form_tag_handler( $tag ) {
	$card_element = sprintf(
		'<div %s></div>',
		paulus_form_format_atts( array(
			'class' => 'card-element paulus-form-form-control',
			'aria-invalid' => 'false',
		) )
	);

	$card_element = sprintf(
		'<div class="paulus-form-form-control-wrap hidden">%s</div>',
		$card_element
	);

	$button_1_label = __( 'Proceed to checkout', 'paulus' );

	if ( isset( $tag->values[0] ) ) {
		$button_1_label = trim( $tag->values[0] );
	}

	$button_1 = sprintf(
		'<button %1$s>%2$s</button>',
		paulus_form_format_atts( array(
			'type' => 'submit',
			'class' => 'first',
		) ),
		esc_html( $button_1_label )
	);

	$button_2_label = __( 'Complete payment', 'paulus' );

	if ( isset( $tag->values[1] ) ) {
		$button_2_label = trim( $tag->values[1] );
	}

	$button_2 = sprintf(
		'<button %1$s>%2$s</button>',
		paulus_form_format_atts( array(
			'type' => 'button',
			'class' => 'second hidden',
		) ),
		esc_html( $button_2_label )
	);

	$buttons = sprintf(
		'<span class="buttons has-spinner">%1$s %2$s</span>',
		$button_1, $button_2
	);

	return sprintf(
		'<div class="paulus-form-stripe">%1$s %2$s %3$s</div>',
		$card_element,
		$buttons,
		'<input type="hidden" name="_paulus_form_stripe_payment_intent" value="" />'
	);
}
