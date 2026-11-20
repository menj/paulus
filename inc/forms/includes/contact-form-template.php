<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Paulus_Form_ContactFormTemplate {

	public static function get_default( $prop = 'form' ) {
		if ( 'form' === $prop ) {
			$template = self::form();
		} elseif ( 'mail' === $prop ) {
			$template = self::mail();
		} elseif ( 'mail_2' === $prop ) {
			$template = self::mail_2();
		} elseif ( 'messages' === $prop ) {
			$template = self::messages();
		} else {
			$template = null;
		}

		return apply_filters( 'paulus_form_default_template', $template, $prop );
	}

	public static function form() {
		$template = sprintf(
			'
<label> %2$s
    [text* your-name autocomplete:name] </label>

<label> %3$s
    [email* your-email autocomplete:email] </label>

<label> %4$s
    [text* your-subject] </label>

<label> %5$s %1$s
    [textarea your-message] </label>

[submit "%6$s"]',
			__( '(optional)', 'paulus' ),
			__( 'Your name', 'paulus' ),
			__( 'Your email', 'paulus' ),
			__( 'Subject', 'paulus' ),
			__( 'Your message', 'paulus' ),
			__( 'Submit', 'paulus' )
		);

		return trim( $template );
	}

	public static function mail() {
		$template = array(
			'subject' => sprintf(
				/* translators: 1: blog name, 2: [your-subject] */
				_x( '%1$s "%2$s"', 'mail subject', 'paulus' ),
				'[_site_title]',
				'[your-subject]'
			),
			'sender' => sprintf(
				'%s <%s>',
				'[_site_title]',
				self::from_email()
			),
			'body' =>
				sprintf(
					/* translators: %s: [your-name] [your-email] */
					__( 'From: %s', 'paulus' ),
					'[your-name] [your-email]'
				) . "\n"
				. sprintf(
					/* translators: %s: [your-subject] */
					__( 'Subject: %s', 'paulus' ),
					'[your-subject]'
				) . "\n\n"
				. __( 'Message Body:', 'paulus' )
				. "\n" . '[your-message]' . "\n\n"
				. '-- ' . "\n"
				. sprintf(
					/* translators: 1: blog name, 2: blog URL */
					__( 'This is a notification that a contact form was submitted on your website (%1$s %2$s).', 'paulus' ),
					'[_site_title]',
					'[_site_url]'
				),
			'recipient' => '[_site_admin_email]',
			'additional_headers' => 'Reply-To: [your-email]',
			'attachments' => '',
			'use_html' => 0,
			'exclude_blank' => 0,
		);

		return $template;
	}

	public static function mail_2() {
		$template = array(
			'active' => false,
			'subject' => sprintf(
				/* translators: 1: blog name, 2: [your-subject] */
				_x( '%1$s "%2$s"', 'mail subject', 'paulus' ),
				'[_site_title]',
				'[your-subject]'
			),
			'sender' => sprintf(
				'%s <%s>',
				'[_site_title]',
				self::from_email()
			),
			'body' =>
				__( 'Message Body:', 'paulus' )
				. "\n" . '[your-message]' . "\n\n"
				. '-- ' . "\n"
				. sprintf(
					/* translators: 1: blog name, 2: blog URL */
					__( 'This email is a receipt for your contact form submission on our website (%1$s %2$s) in which your email address was used. If that was not you, please ignore this message.', 'paulus' ),
					'[_site_title]',
					'[_site_url]'
				),
			'recipient' => '[your-email]',
			'additional_headers' => sprintf(
				'Reply-To: %s',
				'[_site_admin_email]'
			),
			'attachments' => '',
			'use_html' => 0,
			'exclude_blank' => 0,
		);

		return $template;
	}

	public static function from_email() {
		$admin_email = get_option( 'admin_email' );

		if ( paulus_form_is_localhost() ) {
			return $admin_email;
		}

		$sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
		$sitename = strtolower( $sitename );

		if ( 'www.' === substr( $sitename, 0, 4 ) ) {
			$sitename = substr( $sitename, 4 );
		}

		if ( strpbrk( $admin_email, '@' ) === '@' . $sitename ) {
			return $admin_email;
		}

		return 'wordpress@' . $sitename;
	}

	public static function messages() {
		$messages = array();

		foreach ( paulus_form_messages() as $key => $arr ) {
			$messages[$key] = $arr['default'];
		}

		return $messages;
	}
}

function paulus_form_messages() {
	$messages = array(
		'mail_sent_ok' => array(
			'description' => __( 'Sender&#8217;s message was sent successfully', 'paulus' ),
			'default' => __( 'Thank you for your message. It has been sent.', 'paulus' ),
		),

		'mail_sent_ng' => array(
			'description' => __( 'Sender&#8217;s message failed to send', 'paulus' ),
			'default' => __( 'There was an error trying to send your message. Please try again later.', 'paulus' ),
		),

		'validation_error' => array(
			'description' => __( 'Validation errors occurred', 'paulus' ),
			'default' => __( 'One or more fields have an error. Please check and try again.', 'paulus' ),
		),

		'spam' => array(
			'description' => __( 'Submission was referred to as spam', 'paulus' ),
			'default' => __( 'There was an error trying to send your message. Please try again later.', 'paulus' ),
		),

		'accept_terms' => array(
			'description' => __( 'There are terms that the sender must accept', 'paulus' ),
			'default' => __( 'You must accept the terms and conditions before sending your message.', 'paulus' ),
		),

		'invalid_required' => array(
			'description' => __( 'There is a field that the sender must fill in', 'paulus' ),
			'default' => __( 'Please fill out this field.', 'paulus' ),
		),

		'invalid_too_long' => array(
			'description' => __( 'There is a field with input that is longer than the maximum allowed length', 'paulus' ),
			'default' => __( 'This field has a too long input.', 'paulus' ),
		),

		'invalid_too_short' => array(
			'description' => __( 'There is a field with input that is shorter than the minimum allowed length', 'paulus' ),
			'default' => __( 'This field has a too short input.', 'paulus' ),
		),
	);

	return apply_filters( 'paulus_form_messages', $messages );
}
