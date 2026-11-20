<?php
/**
 * Turnstile module main file
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once path_join( __DIR__, 'service.php' );


add_action( 'paulus_form_init', 'paulus_form_turnstile_register_service', 35, 0 );

/**
 * Registers the Turnstile service.
 */
function paulus_form_turnstile_register_service() {
	$integration = Paulus_Form_Integration::get_instance();

	$integration->add_service( 'turnstile',
		Paulus_Form_Turnstile::get_instance()
	);
}


add_action( 'wp_enqueue_scripts', 'paulus_form_turnstile_enqueue_scripts', 10, 0 );

/**
 * Enqueues the Turnstile script.
 */
function paulus_form_turnstile_enqueue_scripts() {
	$service = Paulus_Form_Turnstile::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	wp_enqueue_script(
		'cloudflare-turnstile',
		'https://challenges.cloudflare.com/turnstile/v0/api.js',
		array(),
		null,
		array(
			'strategy' => 'async',
		)
	);

	wp_add_inline_script(
		'cloudflare-turnstile',
		"document.addEventListener( 'paulusFormsubmit', e => turnstile.reset() );"
	);
}


add_action( 'paulus_form_init', 'paulus_form_add_form_tag_turnstile', 10, 0 );

/**
 * Registers the Turnstile form-tag type.
 */
function paulus_form_add_form_tag_turnstile() {
	$service = Paulus_Form_Turnstile::get_instance();

	if ( ! $service->is_active() ) {
		paulus_form_add_form_tag(
			'turnstile',
			'__return_empty_string',
			array(
				'display-block' => true,
			)
		);

		return;
	}

	paulus_form_add_form_tag(
		'turnstile',
		'paulus_form_turnstile_form_tag_handler',
		array(
			'display-block' => true,
			'singular' => true,
		)
	);
}


/**
 * The Turnstile form-tag handler.
 */
function paulus_form_turnstile_form_tag_handler( $tag ) {
	$service = Paulus_Form_Turnstile::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	return sprintf(
		'<div %s></div>',
		paulus_form_format_atts( array(
			'class' => 'paulus-form-turnstile cf-turnstile',
			'data-sitekey' => $service->get_sitekey(),
			'data-response-field-name' => '_paulus_form_turnstile_response',
			'data-action' => $tag->get_option(
				'action', '[-0-9a-zA-Z_]{1,32}', true
			),
			'data-appearance' => $tag->get_option(
				'appearance', '(always|execute|interaction-only)', true
			),
			'data-size' => $tag->get_option(
				'size', '(normal|flexible|compact)', true
			),
			'data-theme' => $tag->get_option( 'theme', '(light|dark|auto)', true ),
			'data-language' => $tag->get_option( 'language', '[a-z-]{2,5}', true ),
			'data-tabindex' => $tag->get_option( 'tabindex', 'signed_int', true ),
		) )
	);
}


add_filter( 'paulus_form_form_elements', 'paulus_form_turnstile_prepend_widget', 10, 1 );

/**
 * Prepends a Turnstile widget to the form content if the form template
 * does not include a Turnstile form-tag.
 */
function paulus_form_turnstile_prepend_widget( $content ) {
	$service = Paulus_Form_Turnstile::get_instance();

	if ( ! $service->is_active() ) {
		return $content;
	}

	$contact_form = Paulus_Form_ContactForm::get_current();
	$manager = Paulus_Form_FormTagsManager::get_instance();

	$tags = $contact_form->scan_form_tags( array(
		'type' => 'turnstile',
	) );

	if ( empty( $tags ) ) {
		$content = $manager->replace_all( '[turnstile]' ) . "\n\n" . $content;
	}

	return $content;
}


add_filter( 'paulus_form_spam', 'paulus_form_turnstile_verify_response', 9, 2 );

/**
 * Verifies the Turnstile response token.
 *
 * @param bool $spam The spam/ham status inherited from preceding callbacks.
 * @param Paulus_Form_Submission $submission The submission object.
 * @return bool True if the submitter is a bot, false if a human.
 */
function paulus_form_turnstile_verify_response( $spam, $submission ) {
	if ( $spam ) {
		return $spam;
	}

	$service = Paulus_Form_Turnstile::get_instance();

	if ( ! $service->is_active() ) {
		return $spam;
	}

	$token = paulus_form_superglobal_post( '_paulus_form_turnstile_response' );

	if ( $service->verify( $token ) ) { // Human
		$spam = false;
	} else { // Bot
		$spam = true;

		if ( '' === $token ) {
			$submission->add_spam_log( array(
				'agent' => 'turnstile',
				'reason' => __( 'Turnstile token is empty.', 'paulus' ),
			) );
		} else {
			$submission->add_spam_log( array(
				'agent' => 'turnstile',
				'reason' => __( 'Turnstile validation failed.', 'paulus' ),
			) );
		}
	}

	return $spam;
}


add_filter(
	'paulus_form_flamingo_inbound_message_parameters',
	'paulus_form_flamingo_inbound_message_parameters_turnstile',
	10, 1
);

/**
 * Passes response data from Turnstile siteverify API to Flamingo.
 */
function paulus_form_flamingo_inbound_message_parameters_turnstile( $params ) {
	$meta = null;

	if ( $submission = Paulus_Form_Submission::get_instance() ) {
		$meta = $submission->pull( 'turnstile' );
	}

	if ( isset( $meta ) ) {
		$params['meta']['turnstile'] = wp_json_encode( $meta );
	}

	return $params;
}
