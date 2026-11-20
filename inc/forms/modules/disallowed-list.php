<?php
/**
 * Disallowed list
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'paulus_form_spam', 'paulus_form_disallowed_list', 10, 2 );

function paulus_form_disallowed_list( $spam, $submission ) {
	if ( $spam ) {
		return $spam;
	}

	$target = paulus_form_array_flatten( $submission->get_posted_data() );
	$target[] = $submission->get_meta( 'remote_ip' );
	$target[] = $submission->get_meta( 'user_agent' );
	$target = implode( "\n", $target );

	$word = paulus_form_check_disallowed_list( $target );

	$word = paulus_form_apply_filters_deprecated(
		'paulus_form_submission_is_blacklisted',
		array( $word, $submission ),
		'5.3',
		'paulus_form_submission_has_disallowed_words'
	);

	$word = apply_filters(
		'paulus_form_submission_has_disallowed_words',
		$word,
		$submission
	);

	if ( $word ) {
		if ( is_bool( $word ) ) {
			$reason = __( 'Disallowed words are used.', 'paulus' );
		} else {
			$reason = sprintf(
				/* translators: %s: comma separated list of disallowed words */
				__( 'Disallowed words (%s) are used.', 'paulus' ),
				implode( ', ', (array) $word )
			);
		}

		$submission->add_spam_log( array(
			'agent' => 'disallowed_list',
			'reason' => $reason,
		) );
	}

	$spam = (bool) $word;

	return $spam;
}

function paulus_form_check_disallowed_list( $target ) {
	$mod_keys = get_option( 'disallowed_keys' );

	if ( is_scalar( $mod_keys ) ) {
		$mod_keys = trim( $mod_keys );
	} else {
		$mod_keys = '';
	}

	if ( '' === $mod_keys ) {
		return false;
	}

	foreach ( explode( "\n", $mod_keys ) as $word ) {
		$word = trim( $word );
		$length = strlen( $word );

		if ( $length < 2 or 256 < $length ) {
			continue;
		}

		$pattern = sprintf( '#%s#i', preg_quote( $word, '#' ) );

		if ( preg_match( $pattern, $target ) ) {
			return $word;
		}
	}

	return false;
}

function paulus_form_blacklist_check( $target ) {
	paulus_form_deprecated_function(
		__FUNCTION__,
		'5.3',
		'paulus_form_check_disallowed_list'
	);

	return paulus_form_check_disallowed_list( $target );
}
