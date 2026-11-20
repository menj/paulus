<?php
/**
 * Quiz
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* form_tag handler */

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_quiz', 10, 0 );

function paulus_form_add_form_tag_quiz() {
	paulus_form_add_form_tag( 'quiz',
		'paulus_form_quiz_form_tag_handler',
		array(
			'name-attr' => true,
			'do-not-store' => true,
			'not-for-mail' => true,
		)
	);
}

function paulus_form_quiz_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = paulus_form_get_validation_error( $tag->name );

	$class = paulus_form_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' paulus-form-not-valid';
	}

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['maxlength'] = $tag->get_maxlength_option();
	$atts['minlength'] = $tag->get_minlength_option();

	if ( $atts['maxlength'] and $atts['minlength']
	and $atts['maxlength'] < $atts['minlength'] ) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );
	$atts['autocomplete'] = 'off';
	$atts['aria-required'] = 'true';

	if ( $validation_error ) {
		$atts['aria-invalid'] = 'true';
		$atts['aria-describedby'] = paulus_form_get_validation_error_reference(
			$tag->name
		);
	} else {
		$atts['aria-invalid'] = 'false';
	}

	$pipes = $tag->pipes;

	if ( $pipes instanceof Paulus_Form_Pipes
	and ! $pipes->zero() ) {
		$pipe = $pipes->random_pipe();
		$question = $pipe->before;
		$answer = $pipe->after;
	} else {
		// default quiz
		$question = '1+1=?';
		$answer = '2';
	}

	$answer = paulus_form_canonicalize( $answer, array(
		'strip_separators' => true,
	) );

	$atts['type'] = 'text';
	$atts['name'] = $tag->name;

	$html = sprintf(
		'<span class="paulus-form-form-control-wrap" data-name="%1$s"><label><span class="paulus-form-quiz-label">%2$s</span> <input %3$s /></label><input type="hidden" name="_paulus_form_quiz_answer_%4$s" value="%5$s" />%6$s</span>',
		esc_attr( $tag->name ),
		esc_html( $question ),
		paulus_form_format_atts( $atts ),
		$tag->name,
		wp_hash( $answer, 'paulus_form_quiz' ),
		$validation_error
	);

	return $html;
}


/* Validation filter */

add_filter( 'paulus_form_validate_quiz', 'paulus_form_quiz_validation_filter', 10, 2 );

function paulus_form_quiz_validation_filter( $result, $tag ) {
	$name = $tag->name;

	$answer = paulus_form_superglobal_post( $name );

	$answer = paulus_form_canonicalize( $answer, array(
		'strip_separators' => true,
	) );

	$answer_hash = wp_hash( $answer, 'paulus_form_quiz' );

	$expected_hash = paulus_form_superglobal_post( '_paulus_form_quiz_answer_' . $name );

	if ( ! hash_equals( $expected_hash, $answer_hash ) ) {
		$result->invalidate( $tag, paulus_form_get_message( 'quiz_answer_not_correct' ) );
	}

	return $result;
}


/* Ajax echo filter */

add_filter( 'paulus_form_refill_response', 'paulus_form_quiz_ajax_refill', 10, 1 );
add_filter( 'paulus_form_feedback_response', 'paulus_form_quiz_ajax_refill', 10, 1 );

function paulus_form_quiz_ajax_refill( $items ) {
	if ( ! is_array( $items ) ) {
		return $items;
	}

	$fes = paulus_form_scan_form_tags( array( 'type' => 'quiz' ) );

	if ( empty( $fes ) ) {
		return $items;
	}

	$refill = array();

	foreach ( $fes as $fe ) {
		$name = $fe['name'];
		$pipes = $fe['pipes'];

		if ( empty( $name ) ) {
			continue;
		}

		if ( $pipes instanceof Paulus_Form_Pipes
		and ! $pipes->zero() ) {
			$pipe = $pipes->random_pipe();
			$question = $pipe->before;
			$answer = $pipe->after;
		} else {
			// default quiz
			$question = '1+1=?';
			$answer = '2';
		}

		$answer = paulus_form_canonicalize( $answer, array(
			'strip_separators' => true,
		) );

		$refill[$name] = array( $question, wp_hash( $answer, 'paulus_form_quiz' ) );
	}

	if ( ! empty( $refill ) ) {
		$items['quiz'] = $refill;
	}

	return $items;
}


/* Messages */

add_filter( 'paulus_form_messages', 'paulus_form_quiz_messages', 10, 1 );

function paulus_form_quiz_messages( $messages ) {
	$messages = array_merge( $messages, array(
		'quiz_answer_not_correct' => array(
			'description' =>
				__( "Sender does not enter the correct answer to the quiz", 'paulus' ),
			'default' =>
				__( "The answer to the quiz is incorrect.", 'paulus' ),
		),
	) );

	return $messages;
}


/* Tag generator */

add_action( 'paulus_form_admin_init', 'paulus_form_add_tag_generator_quiz', 40, 0 );

function paulus_form_add_tag_generator_quiz() {
	$tag_generator = Paulus_Form_TagGenerator::get_instance();

	$tag_generator->add( 'quiz', __( 'quiz', 'paulus' ),
		'paulus_form_tag_generator_quiz',
		array( 'version' => '2' )
	);
}

function paulus_form_tag_generator_quiz( $contact_form, $options ) {
	$field_types = array(
		'quiz' => array(
			'display_name' => __( 'Quiz', 'paulus' ),
			'heading' => __( 'Quiz form-tag generator', 'paulus' ),
			'description' => sprintf(
				/* translators: %s: URL to quiz field doc */
				__( 'Generates a form-tag for a <a href="%s">quiz</a>.', 'paulus' ),
				'#'
			),
		),
	);

	$tgg = new Paulus_Form_TagGeneratorGenerator( $options['content'] );

	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'header', array(
		'class' => 'description-box',
	) );

	$formatter->append_start_tag( 'h3' );

	$formatter->append_preformatted(
		esc_html( $field_types['quiz']['heading'] )
	);

	$formatter->end_tag( 'h3' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_preformatted(
		wp_kses_data( $field_types['quiz']['description'] )
	);

	$formatter->end_tag( 'header' );

	$formatter->append_start_tag( 'div', array(
		'class' => 'control-box',
	) );

	$formatter->call_user_func( static function () use ( $tgg, $field_types ) {
		$tgg->print( 'field_type', array(
			'select_options' => array(
				'quiz' => $field_types['quiz']['display_name'],
			),
		) );

		$tgg->print( 'field_name' );

		$tgg->print( 'class_attr' );
	} );

	$formatter->append_start_tag( 'fieldset' );

	$formatter->append_start_tag( 'legend', array(
		'id' => $tgg->ref( 'selectable-values-legend' ),
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Questions and answers', 'paulus' ) )
	);

	$formatter->end_tag( 'legend' );

	$formatter->append_start_tag( 'span', array(
		'id' => $tgg->ref( 'selectable-values-description' ),
	) );

	$formatter->append_preformatted(
		esc_html( __( 'One pipe-separated question-answer pair (question|answer) per line.', 'paulus' ) )
	);

	$formatter->end_tag( 'span' );

	$formatter->append_start_tag( 'br' );

	$formatter->append_start_tag( 'textarea', array(
		'required' => true,
		'data-tag-part' => 'value',
		'aria-labelledby' => $tgg->ref( 'selectable-values-legend' ),
		'aria-describedby' => $tgg->ref( 'selectable-values-description' ),
	) );

	$formatter->append_preformatted(
		esc_html( __( 'The capital of Brazil? | Rio', 'paulus' ) )
	);

	$formatter->end_tag( 'textarea' );

	$formatter->end_tag( 'div' );

	$formatter->append_start_tag( 'footer', array(
		'class' => 'insert-box',
	) );

	$formatter->call_user_func( static function () use ( $tgg, $field_types ) {
		$tgg->print( 'insert_box_content' );
	} );

	$formatter->print();
}
