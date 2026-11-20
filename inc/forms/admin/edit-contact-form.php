<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$formatter = new PAULUS_FORM_HTMLFormatter( array(
	'allowed_html' => array_merge( paulus_form_kses_allowed_html(), array(
		'form' => array(
			'method' => true,
			'action' => true,
			'id' => true,
			'class' => true,
			'disabled' => true,
		),
	) ),
) );

$formatter->append_start_tag( 'div', array(
	'class' => 'wrap',
	'id' => 'paulus-form-contact-form-editor',
) );

$formatter->append_start_tag( 'h1', array(
	'class' => 'wp-heading-inline',
) );

$formatter->append_preformatted(
	esc_html( $post->initial()
		? __( 'Add Contact Form', 'paulus' )
		: __( 'Edit Contact Form', 'paulus' )
	)
);

$formatter->end_tag( 'h1' );

if ( ! $post->initial() and current_user_can( 'paulus_form_edit_contact_forms' ) ) {
	$formatter->append_whitespace();

	$formatter->append_preformatted(
		paulus_form_link(
			menu_page_url( 'paulus-form-new', false ),
			__( 'Add Contact Form', 'paulus' ),
			array( 'class' => 'page-title-action' )
		)
	);
}

$formatter->append_start_tag( 'hr', array(
	'class' => 'wp-header-end',
) );

$formatter->call_user_func( static function () use ( $post ) {
	do_action( 'paulus_form_admin_warnings',
		$post->initial() ? 'paulus-form-new' : 'paulus-form',
		paulus_form_current_action(),
		$post
	);

	do_action( 'paulus_form_admin_notices',
		$post->initial() ? 'paulus-form-new' : 'paulus-form',
		paulus_form_current_action(),
		$post
	);
} );

if ( $post ) {
	$formatter->append_start_tag( 'form', array(
		'method' => 'post',
		'action' => esc_url( add_query_arg(
			array( 'post' => $post_id ),
			menu_page_url( 'paulus-form', false )
		) ),
		'id' => 'paulus-form-admin-form-element',
		'disabled' => ! current_user_can( 'paulus_form_edit_contact_form', $post_id ),
	) );

	if ( current_user_can( 'paulus_form_edit_contact_form', $post_id ) ) {
		$formatter->call_user_func( static function () use ( $post_id ) {
			wp_nonce_field( 'paulus-form-save-contact-form_' . $post_id );
		} );
	}

	$formatter->append_start_tag( 'input', array(
		'type' => 'hidden',
		'id' => 'post_ID',
		'name' => 'post_ID',
		'value' => (int) $post_id,
	) );

	$formatter->append_start_tag( 'input', array(
		'type' => 'hidden',
		'id' => 'paulus-form-locale',
		'name' => 'paulus-form-locale',
		'value' => $post->locale(),
	) );

	$formatter->append_start_tag( 'input', array(
		'type' => 'hidden',
		'id' => 'hiddenaction',
		'name' => 'action',
		'value' => 'save',
	) );

	$formatter->append_start_tag( 'input', array(
		'type' => 'hidden',
		'id' => 'active-tab',
		'name' => 'active-tab',
		'value' => paulus_form_superglobal_get( 'active-tab' ),
	) );

	$formatter->append_start_tag( 'div', array(
		'id' => 'poststuff',
	) );

	$formatter->append_start_tag( 'div', array(
		'id' => 'post-body',
		'class' => 'metabox-holder columns-2 wp-clearfix',
	) );

	$formatter->append_start_tag( 'div', array(
		'id' => 'post-body-content',
	) );

	$formatter->append_start_tag( 'div', array(
		'id' => 'titlediv',
	) );

	$formatter->append_start_tag( 'div', array(
		'id' => 'titlewrap',
	) );

	$formatter->append_start_tag( 'input', array(
		'type' => 'text',
		'name' => 'post_title',
		'value' => $post->initial() ? '' : $post->title(),
		'id' => 'title',
		'spellcheck' => 'true',
		'autocomplete' => 'off',
		'disabled' => ! current_user_can( 'paulus_form_edit_contact_form', $post_id ),
		'placeholder' => __( 'Enter title here', 'paulus' ),
		'aria-label' => __( 'Enter title here', 'paulus' ),
	) );

	$formatter->end_tag( 'div' ); // #titlewrap

	$formatter->append_start_tag( 'div', array(
		'class' => 'inside',
	) );

	if ( ! $post->initial() ) {
		$formatter->append_start_tag( 'div', array(
			'id' => 'paulus-form-shortcode-wrap',
			'class' => 'hide-shortcode',
		) );

		$formatter->append_start_tag( 'button', array(
			'class' => 'toggle-shortcode alignright button-link',
			'type' => 'button',
		) );

		$formatter->append_preformatted(
			esc_html( __( 'Toggle shortcode', 'paulus' ) )
		);

		$formatter->end_tag( 'button' );

		$formatter->append_start_tag( 'div', array(
			'id' => 'paulus-form-shortcode-inside',
		) );

		if ( $shortcode = $post->shortcode() ) {
			$formatter->append_start_tag( 'p', array(
				'class' => 'description',
			) );

			$formatter->append_start_tag( 'label', array(
				'for' => 'paulus-form-shortcode',
			) );

			$formatter->append_preformatted(
				esc_html( __( 'Shortcodes are only needed in the Classic Editor environment; otherwise, use the Forms block in the Block Editor.', 'paulus' ) )
			);

			$formatter->end_tag( 'label' );

			$formatter->append_whitespace();

			$formatter->append_start_tag( 'span', array(
				'class' => 'shortcode wp-ui-highlight',
			) );

			$formatter->append_start_tag( 'input', array(
				'type' => 'text',
				'id' => 'paulus-form-shortcode',
				'readonly' => true,
				'class' => 'large-text code selectable',
				'value' => $shortcode,
			) );

			$formatter->end_tag( 'p' );
		}

		$formatter->end_tag( 'div' ); // #paulus-form-shortcode-inside
		$formatter->end_tag( 'div' ); // #paulus-form-shortcode-wrap
	}

	$formatter->end_tag( 'div' ); // .inside
	$formatter->end_tag( 'div' ); // #titlediv
	$formatter->end_tag( 'div' ); // #post-body-content

	$formatter->append_start_tag( 'div', array(
		'id' => 'postbox-container-1',
		'class' => 'postbox-container',
	) );

	if ( current_user_can( 'paulus_form_edit_contact_form', $post_id ) ) {
		$formatter->append_start_tag( 'section', array(
			'id' => 'submitdiv',
			'class' => 'postbox',
		) );

		$formatter->append_start_tag( 'h2' );

		$formatter->append_preformatted(
			esc_html( __( 'Status', 'paulus' ) )
		);

		$formatter->end_tag( 'h2' );

		$formatter->append_start_tag( 'div', array(
			'class' => 'inside',
		) );

		$formatter->append_start_tag( 'div', array(
			'class' => 'submitbox',
			'id' => 'submitpost',
		) );

		$formatter->append_start_tag( 'div', array(
			'id' => 'minor-publishing-actions',
		) );

		$formatter->append_start_tag( 'div', array(
			'class' => 'hidden',
		) );

		$formatter->append_start_tag( 'button', array(
			'type' => 'submit',
			'class' => 'button-primary',
			'name' => 'paulus-form-save',
			'value' => __( 'Save', 'paulus' ),
		) );

		$formatter->append_preformatted(
			esc_html( __( 'Save', 'paulus' ) )
		);

		$formatter->end_tag( 'div' ); // .hidden

		if ( ! $post->initial() ) {
			$formatter->append_start_tag( 'button', array(
				'type' => 'submit',
				'name' => 'paulus-form-copy',
				'class' => 'copy button-link',
				'value' => __( 'Duplicate', 'paulus' ),
			) );

			$formatter->append_preformatted(
				esc_html( __( 'Duplicate', 'paulus' ) )
			);

			$formatter->end_tag( 'button' );
		}

		$formatter->end_tag( 'div' ); // #minor-publishing-actions

		$formatter->append_start_tag( 'div', array(
			'id' => 'misc-publishing-actions',
		) );

		$formatter->call_user_func( static function () use ( $post_id ) {
			do_action( 'paulus_form_admin_misc_pub_section', $post_id );
		} );

		$formatter->end_tag( 'div' ); // #misc-publishing-actions

		$formatter->append_start_tag( 'div', array(
			'id' => 'major-publishing-actions',
		) );

		if ( ! $post->initial() ) {
			$formatter->append_start_tag( 'div', array(
				'id' => 'delete-action',
			) );

			$formatter->append_start_tag( 'button', array(
				'type' => 'submit',
				'name' => 'paulus-form-delete',
				'class' => 'delete submitdelete',
				'value' => __( 'Delete', 'paulus' ),
			) );

			$formatter->append_preformatted(
				esc_html( __( 'Delete', 'paulus' ) )
			);

			$formatter->end_tag( 'div' ); // #delete-action
		}

		$formatter->append_start_tag( 'div', array(
			'id' => 'publishing-action',
		) );

		$formatter->append_preformatted( '<span class="spinner"></span>' );

		$formatter->append_start_tag( 'button', array(
			'type' => 'submit',
			'class' => 'button-primary',
			'name' => 'paulus-form-save',
			'value' => __( 'Save', 'paulus' ),
		) );

		$formatter->append_preformatted(
			esc_html( __( 'Save', 'paulus' ) )
		);

		$formatter->end_tag( 'div' ); // #publishing-action

		$formatter->append_preformatted( '<div class="clear"></div>' );

		$formatter->end_tag( 'div' ); // #major-publishing-actions
		$formatter->end_tag( 'div' ); // #submitpost
		$formatter->end_tag( 'div' ); // .inside
		$formatter->end_tag( 'section' ); // #submitdiv
	}

	$formatter->end_tag( 'div' ); // #postbox-container-1

	$formatter->append_start_tag( 'div', array(
		'id' => 'postbox-container-2',
		'class' => 'postbox-container',
	) );

	$formatter->append_start_tag( 'div', array(
		'id' => 'contact-form-editor',
	) );

	$formatter->call_user_func( static function () use ( $post, $post_id ) {
		$editor = new Paulus_Form_Editor( $post );
		$panels = array();

		if ( current_user_can( 'paulus_form_edit_contact_form', $post_id ) ) {
			$panels = array(
				'form-panel' => array(
					'title' => __( 'Form', 'paulus' ),
					'callback' => 'paulus_form_editor_panel_form',
				),
				'mail-panel' => array(
					'title' => __( 'Mail', 'paulus' ),
					'callback' => 'paulus_form_editor_panel_mail',
				),
				'messages-panel' => array(
					'title' => __( 'Messages', 'paulus' ),
					'callback' => 'paulus_form_editor_panel_messages',
				),
			);

			$additional_settings = $post->prop( 'additional_settings' );

			if ( ! is_scalar( $additional_settings ) ) {
				$additional_settings = '';
			}

			$additional_settings = trim( $additional_settings );
			$additional_settings = explode( "\n", $additional_settings );
			$additional_settings = array_filter( $additional_settings );
			$additional_settings = count( $additional_settings );

			$panels['additional-settings-panel'] = array(
				'title' => $additional_settings
					? sprintf(
						/* translators: %d: number of additional settings */
						__( 'Additional Settings (%d)', 'paulus' ),
						$additional_settings
					)
					: __( 'Additional Settings', 'paulus' ),
				'callback' => 'paulus_form_editor_panel_additional_settings',
			);
		}

		$panels = apply_filters( 'paulus_form_editor_panels', $panels );

		foreach ( $panels as $id => $panel ) {
			$editor->add_panel( $id, $panel['title'], $panel['callback'] );
		}

		$editor->display();
	} );

	$formatter->end_tag( 'div' ); // #contact-form-editor
	$formatter->end_tag( 'div' ); // #postbox-container-2
	$formatter->end_tag( 'div' ); // #post-body

	$formatter->append_preformatted( '<br class="clear" />' );

	$formatter->end_tag( 'div' ); // #poststuff
	$formatter->end_tag( 'form' );
}

$formatter->end_tag( 'div' ); // .wrap

$formatter->print();

$tag_generator = Paulus_Form_TagGenerator::get_instance();
$tag_generator->print_panels( $post );

do_action( 'paulus_form_admin_footer', $post );
