<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Paulus_Form_Editor {

	private $contact_form;
	private $panels = array();

	public function __construct( Paulus_Form_ContactForm $contact_form ) {
		$this->contact_form = $contact_form;
	}

	public function add_panel( $panel_id, $title, $callback ) {
		if ( paulus_form_is_name( $panel_id ) ) {
			$this->panels[$panel_id] = array(
				'title' => $title,
				'callback' => $callback,
			);
		}
	}

	public function display() {
		if ( empty( $this->panels ) ) {
			return;
		}

		$active_panel_id = paulus_form_superglobal_get( 'active-tab' );

		if ( ! array_key_exists( $active_panel_id, $this->panels ) ) {
			$active_panel_id = array_key_first( $this->panels );
		}

		$formatter = new PAULUS_FORM_HTMLFormatter();

		$formatter->append_start_tag( 'nav', array(
			'id' => 'contact-form-editor-tabs',
			'role' => 'tablist',
			'aria-label' => __( 'Contact form editor tabs', 'paulus' ),
			'data-active-tab' => absint( array_search(
				$active_panel_id, array_keys( $this->panels ), true
			) ),
		) );

		foreach ( $this->panels as $panel_id => $panel ) {
			$active = $panel_id === $active_panel_id;

			$formatter->append_start_tag( 'button', array(
				'type' => 'button',
				'role' => 'tab',
				'aria-selected' => $active ? 'true' : 'false',
				'aria-controls' => $panel_id,
				'id' => sprintf( '%s-tab', $panel_id ),
				'tabindex' => $active ? '0' : '-1',
				'data-panel' => $panel_id,
			) );

			$formatter->append_preformatted( esc_html( $panel['title'] ) );
		}

		$formatter->end_tag( 'nav' );

		foreach ( $this->panels as $panel_id => $panel ) {
			$active = $panel_id === $active_panel_id;

			$formatter->append_start_tag( 'section', array(
				'role' => 'tabpanel',
				'aria-labelledby' => sprintf( '%s-tab', $panel_id ),
				'id' => $panel_id,
				'class' => 'contact-form-editor-panel',
				'tabindex' => '0',
				'hidden' => ! $active,
			) );

			if ( is_callable( $panel['callback'] ) ) {
				$formatter->call_user_func( $panel['callback'], $this->contact_form );
			}

			$formatter->end_tag( 'section' );
		}

		$formatter->print();
	}
}

function paulus_form_editor_panel_form( $post ) {
	$description = sprintf(
		/* translators: %s: URL to support page about the form template */
		__( 'You can edit the form template here. For details, see <a href="%s">Editing form template</a>.', 'paulus' ),
		'#'
	);

	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'h2' );
	$formatter->append_preformatted( esc_html( __( 'Form', 'paulus' ) ) );
	$formatter->end_tag( 'h2' );

	$formatter->append_start_tag( 'fieldset' );

	$formatter->append_start_tag( 'legend' );
	$formatter->append_preformatted( $description );
	$formatter->end_tag( 'legend' );

	$formatter->call_user_func( static function () {
		$tag_generator = Paulus_Form_TagGenerator::get_instance();
		$tag_generator->print_buttons();
	} );

	$formatter->append_start_tag( 'textarea', array(
		'id' => 'paulus-form-form',
		'name' => 'paulus-form-form',
		'cols' => 100,
		'rows' => 24,
		'class' => 'large-text code',
		'data-config-field' => 'form.body',
	) );

	$formatter->append_preformatted( esc_textarea( $post->prop( 'form' ) ) );

	$formatter->end_tag( 'textarea' );

	$formatter->print();
}

function paulus_form_editor_panel_mail( $post ) {
	paulus_form_editor_box_mail( $post );

	echo '<br class="clear" />';

	paulus_form_editor_box_mail( $post, array(
		'id' => 'paulus-form-mail-2',
		'name' => 'mail_2',
		'title' => __( 'Mail (2)', 'paulus' ),
		'use' => __( 'Use Mail (2)', 'paulus' ),
	) );
}

function paulus_form_editor_box_mail( $post, $options = '' ) {
	$options = wp_parse_args( $options, array(
		'id' => 'paulus-form-mail',
		'name' => 'mail',
		'title' => __( 'Mail', 'paulus' ),
		'use' => null,
	) );

	$id = esc_attr( $options['id'] );

	$mail = wp_parse_args( $post->prop( $options['name'] ), array(
		'active' => false,
		'recipient' => '',
		'sender' => '',
		'subject' => '',
		'body' => '',
		'additional_headers' => '',
		'attachments' => '',
		'use_html' => false,
		'exclude_blank' => false,
	) );

	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'div', array(
		'class' => 'contact-form-editor-box-mail',
		'id' => $id,
	) );

	$formatter->append_start_tag( 'h2' );
	$formatter->append_preformatted( esc_html( $options['title'] ) );
	$formatter->end_tag( 'h2' );

	if ( ! empty( $options['use'] ) ) {
		$formatter->append_start_tag( 'label', array(
			'for' => sprintf( '%s-active', $id ),
		) );

		$formatter->append_start_tag( 'input', array(
			'type' => 'checkbox',
			'id' => sprintf( '%s-active', $id ),
			'name' => sprintf( '%s[active]', $id ),
			'data-config-field' => '',
			'data-toggle' => sprintf( '%s-fieldset', $id ),
			'value' => '1',
			'checked' => $mail['active'],
		) );

		$formatter->append_whitespace();
		$formatter->append_preformatted( esc_html( $options['use'] ) );
		$formatter->end_tag( 'label' );

		$formatter->append_start_tag( 'p', array(
			'class' => 'description',
		) );

		$formatter->append_preformatted(
			esc_html( __( 'Mail (2) is an additional mail template often used as an autoresponder.', 'paulus' ) )
		);

		$formatter->end_tag( 'p' );
	}

	$formatter->append_start_tag( 'fieldset', array(
		'id' => sprintf( '%s-fieldset', $id ),
	) );

	$formatter->append_start_tag( 'legend' );

	$description = sprintf(
		/* translators: %s: URL to support page about the email template */
		__( 'You can edit the email template here. For details, see <a href="%s">Setting up mail</a>.', 'paulus' ),
		'#'
	);

	$formatter->append_preformatted( $description );

	$formatter->append_start_tag( 'br' );

	$formatter->append_preformatted(
		esc_html( __( 'In the following fields, you can use these mail-tags:', 'paulus' ) )
	);

	$formatter->append_start_tag( 'br' );

	$formatter->call_user_func( static function () use ( $post, $options ) {
		$post->suggest_mail_tags( $options['name'] );
	} );

	$formatter->end_tag( 'legend' );

	$formatter->append_start_tag( 'table', array(
		'class' => 'form-table',
	) );

	$formatter->append_start_tag( 'tbody' );

	$formatter->append_start_tag( 'tr' );

	$formatter->append_start_tag( 'th', array(
		'scope' => 'row',
	) );

	$formatter->append_start_tag( 'label', array(
		'for' => sprintf( '%s-recipient', $id ),
	) );

	$formatter->append_preformatted(
		esc_html( __( 'To', 'paulus' ) )
	);

	$formatter->append_start_tag( 'td' );

	$formatter->append_start_tag( 'input', array(
		'type' => 'text',
		'id' => sprintf( '%s-recipient', $id ),
		'name' => sprintf( '%s[recipient]', $id ),
		'class' => 'large-text code',
		'size' => 70,
		'value' => $mail['recipient'],
		'data-config-field' => sprintf( '%s.recipient', $options['name'] ),
	) );

	$formatter->end_tag( 'tr' );

	$formatter->append_start_tag( 'tr' );

	$formatter->append_start_tag( 'th', array(
		'scope' => 'row',
	) );

	$formatter->append_start_tag( 'label', array(
		'for' => sprintf( '%s-sender', $id ),
	) );

	$formatter->append_preformatted(
		esc_html( __( 'From', 'paulus' ) )
	);

	$formatter->append_start_tag( 'td' );

	$formatter->append_start_tag( 'input', array(
		'type' => 'text',
		'id' => sprintf( '%s-sender', $id ),
		'name' => sprintf( '%s[sender]', $id ),
		'class' => 'large-text code',
		'size' => 70,
		'value' => $mail['sender'],
		'data-config-field' => sprintf( '%s.sender', $options['name'] ),
	) );

	$formatter->end_tag( 'tr' );

	$formatter->append_start_tag( 'tr' );

	$formatter->append_start_tag( 'th', array(
		'scope' => 'row',
	) );

	$formatter->append_start_tag( 'label', array(
		'for' => sprintf( '%s-subject', $id ),
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Subject', 'paulus' ) )
	);

	$formatter->append_start_tag( 'td' );

	$formatter->append_start_tag( 'input', array(
		'type' => 'text',
		'id' => sprintf( '%s-subject', $id ),
		'name' => sprintf( '%s[subject]', $id ),
		'class' => 'large-text code',
		'size' => 70,
		'value' => $mail['subject'],
		'data-config-field' => sprintf( '%s.subject', $options['name'] ),
	) );

	$formatter->end_tag( 'tr' );

	$formatter->append_start_tag( 'tr' );

	$formatter->append_start_tag( 'th', array(
		'scope' => 'row',
	) );

	$formatter->append_start_tag( 'label', array(
		'for' => sprintf( '%s-additional-headers', $id ),
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Additional headers', 'paulus' ) )
	);

	$formatter->append_start_tag( 'td' );

	$formatter->append_start_tag( 'textarea', array(
		'id' => sprintf( '%s-additional-headers', $id ),
		'name' => sprintf( '%s[additional_headers]', $id ),
		'cols' => 100,
		'rows' => 4,
		'class' => 'large-text code',
		'data-config-field' => sprintf( '%s.additional_headers', $options['name'] ),
	) );

	$formatter->append_preformatted(
		esc_textarea( $mail['additional_headers'] )
	);

	$formatter->end_tag( 'tr' );

	$formatter->append_start_tag( 'tr' );

	$formatter->append_start_tag( 'th', array(
		'scope' => 'row',
	) );

	$formatter->append_start_tag( 'label', array(
		'for' => sprintf( '%s-body', $id ),
	) );

	$formatter->append_preformatted(
		esc_html( __( 'Message body', 'paulus' ) )
	);

	$formatter->append_start_tag( 'td' );

	$formatter->append_start_tag( 'textarea', array(
		'id' => sprintf( '%s-body', $id ),
		'name' => sprintf( '%s[body]', $id ),
		'cols' => 100,
		'rows' => 18,
		'class' => 'large-text code',
		'data-config-field' => sprintf( '%s.body', $options['name'] ),
	) );

	$formatter->append_preformatted(
		esc_textarea( $mail['body'] )
	);

	$formatter->end_tag( 'textarea' );

	$formatter->append_start_tag( 'p' );

	$formatter->append_start_tag( 'label', array(
		'for' => sprintf( '%s-exclude-blank', $id ),
	) );

	$formatter->append_start_tag( 'input', array(
		'type' => 'checkbox',
		'id' => sprintf( '%s-exclude-blank', $id ),
		'name' => sprintf( '%s[exclude_blank]', $id ),
		'value' => '1',
		'checked' => $mail['exclude_blank'],
	) );

	$formatter->append_whitespace();

	$formatter->append_preformatted(
		esc_html( __( 'Exclude a line from output if all of its mail-tags are blank', 'paulus' ) )
	);

	$formatter->append_start_tag( 'p' );

	$formatter->append_start_tag( 'label', array(
		'for' => sprintf( '%s-use-html', $id ),
	) );

	$formatter->append_start_tag( 'input', array(
		'type' => 'checkbox',
		'id' => sprintf( '%s-use-html', $id ),
		'name' => sprintf( '%s[use_html]', $id ),
		'value' => '1',
		'checked' => $mail['use_html'],
	) );

	$formatter->append_whitespace();

	$formatter->append_preformatted(
		esc_html( __( 'Use HTML content type', 'paulus' ) )
	);

	$formatter->end_tag( 'tr' );

	$formatter->append_start_tag( 'tr' );

	$formatter->append_start_tag( 'th', array(
		'scope' => 'row',
	) );

	$formatter->append_start_tag( 'label', array(
		'for' => sprintf( '%s-attachments', $id ),
	) );

	$formatter->append_preformatted(
		esc_html( __( 'File attachments', 'paulus' ) )
	);

	$formatter->append_start_tag( 'td' );

	$formatter->append_start_tag( 'textarea', array(
		'id' => sprintf( '%s-attachments', $id ),
		'name' => sprintf( '%s[attachments]', $id ),
		'cols' => 100,
		'rows' => 4,
		'class' => 'large-text code',
		'data-config-field' => sprintf( '%s.attachments', $options['name'] ),
	) );

	$formatter->append_preformatted(
		esc_textarea( $mail['attachments'] )
	);

	$formatter->end_tag( 'textarea' );
	$formatter->end_tag( 'tr' );

	$formatter->print();
}

function paulus_form_editor_panel_messages( $post ) {
	$description = sprintf(
		/* translators: %s: URL to support page about the messages editor */
		__( 'You can edit messages used in various situations here. For details, see <a href="%s">Editing messages</a>.', 'paulus' ),
		'#'
	);

	$messages = paulus_form_messages();

	if (
		isset( $messages['captcha_not_match'] ) and
		! paulus_form_use_really_simple_captcha()
	) {
		unset( $messages['captcha_not_match'] );
	}

	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'h2' );

	$formatter->append_preformatted(
		esc_html( __( 'Messages', 'paulus' ) )
	);

	$formatter->end_tag( 'h2' );

	$formatter->append_start_tag( 'fieldset' );

	$formatter->append_start_tag( 'legend' );
	$formatter->append_preformatted( $description );
	$formatter->end_tag( 'legend' );

	foreach ( $messages as $key => $arr ) {
		$field_id = sprintf( 'paulus-form-message-%s', strtr( $key, '_', '-' ) );
		$field_name = sprintf( 'paulus-form-messages[%s]', $key );

		$formatter->append_start_tag( 'p', array(
			'class' => 'description',
		) );

		$formatter->append_start_tag( 'label', array(
			'for' => $field_id,
		) );

		$formatter->append_preformatted( esc_html( $arr['description'] ) );
		$formatter->append_start_tag( 'br' );

		$formatter->append_start_tag( 'input', array(
			'type' => 'text',
			'id' => $field_id,
			'name' => $field_name,
			'class' => 'large-text',
			'size' => 70,
			'value' => $post->message( $key, false ),
			'data-config-field' => sprintf( 'messages.%s', $key ),
		) );
	}

	$formatter->print();
}

function paulus_form_editor_panel_additional_settings( $post ) {
	$description = sprintf(
		/* translators: %s: URL to support page about the additional settings editor */
		__( 'You can add customization code snippets here. For details, see <a href="%s">Additional settings</a>.', 'paulus' ),
		'#'
	);

	$formatter = new PAULUS_FORM_HTMLFormatter();

	$formatter->append_start_tag( 'h2' );

	$formatter->append_preformatted(
		esc_html( __( 'Additional Settings', 'paulus' ) )
	);

	$formatter->end_tag( 'h2' );

	$formatter->append_start_tag( 'fieldset' );

	$formatter->append_start_tag( 'legend' );
	$formatter->append_preformatted( $description );
	$formatter->end_tag( 'legend' );

	$formatter->append_start_tag( 'textarea', array(
		'id' => 'paulus-form-additional-settings',
		'name' => 'paulus-form-additional-settings',
		'cols' => 100,
		'rows' => 24,
		'class' => 'large-text',
		'data-config-field' => 'additional_settings.body',
	) );

	$formatter->append_preformatted(
		esc_textarea( $post->prop( 'additional_settings' ) )
	);

	$formatter->end_tag( 'textarea' );

	$formatter->print();
}
