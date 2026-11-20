<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Paulus_Form_Help_Tabs {

	private $screen;

	public function __construct( WP_Screen $screen ) {
		$this->screen = $screen;
	}

	public function set_help_tabs( $screen_type ) {
		switch ( $screen_type ) {
			case 'list':
				$this->screen->add_help_tab( array(
					'id' => 'list_overview',
					'title' => __( 'Overview', 'paulus' ),
					'content' => $this->content( 'list_overview' ),
				) );

				$this->screen->add_help_tab( array(
					'id' => 'list_available_actions',
					'title' => __( 'Available Actions', 'paulus' ),
					'content' => $this->content( 'list_available_actions' ),
				) );

				$this->sidebar();

				return;
			case 'edit':
				$this->screen->add_help_tab( array(
					'id' => 'edit_overview',
					'title' => __( 'Overview', 'paulus' ),
					'content' => $this->content( 'edit_overview' ),
				) );

				$this->screen->add_help_tab( array(
					'id' => 'edit_form_tags',
					'title' => __( 'Form-tags', 'paulus' ),
					'content' => $this->content( 'edit_form_tags' ),
				) );

				$this->screen->add_help_tab( array(
					'id' => 'edit_mail_tags',
					'title' => __( 'Mail-tags', 'paulus' ),
					'content' => $this->content( 'edit_mail_tags' ),
				) );

				$this->sidebar();

				return;
			case 'integration':
				$this->screen->add_help_tab( array(
					'id' => 'integration_overview',
					'title' => __( 'Overview', 'paulus' ),
					'content' => $this->content( 'integration_overview' ),
				) );

				$this->sidebar();

				return;
		}
	}

	private function content( $name ) {
		$content = array();

		$content['list_overview'] = '<p>' . __( 'On this screen, you can manage contact forms your team has created with Forms. You can embed contact forms in post content or a block theme template by inserting a Forms block. For use in the classic editor environment, traditional shortcodes are also provided.', 'paulus' ) . '</p>';

		$content['list_available_actions'] = '<p>' . __( 'Hovering over a row in the contact forms list will display action links that allow you to manage your contact form. You can perform the following actions:', 'paulus' ) . '</p>';
		$content['list_available_actions'] .= '<p>' . __( '<strong>Edit</strong> - Navigates to the editing screen for that contact form. You can also reach that screen by clicking on the contact form title.', 'paulus' ) . '</p>';
		$content['list_available_actions'] .= '<p>' . __( '<strong>Duplicate</strong> - Clones that contact form. A cloned contact form inherits all content from the original, but has a different ID.', 'paulus' ) . '</p>';

		$content['edit_overview'] = '<p>' . __( 'On this screen, you can edit a contact form. A contact form is comprised of the following components:', 'paulus' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( '<strong>Title</strong> is the title of a contact form. This title is only used for labeling a contact form, and can be edited.', 'paulus' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( '<strong>Form</strong> is a content of HTML form. You can use arbitrary HTML, which is allowed inside a form element. You can also use Forms&#8217;s form-tags here.', 'paulus' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( '<strong>Mail</strong> manages a mail template (headers and message body) that this contact form will send when users submit it. You can use Forms&#8217;s mail-tags here.', 'paulus' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( '<strong>Mail (2)</strong> is an additional mail template that works similar to Mail. Mail (2) is different in that it is sent only when Mail has been sent successfully.', 'paulus' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( 'In <strong>Messages</strong>, you can edit various types of messages used for this contact form. These messages are relatively short messages, like a validation error message you see when you leave a required field blank.', 'paulus' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( '<strong>Additional Settings</strong> provides a place where you can customize the behavior of this contact form by adding code snippets.', 'paulus' ) . '</p>';

		$content['edit_form_tags'] = '<p>' . __( 'A form-tag is a short code enclosed in square brackets used in a form content. A form-tag generally represents an input field, and its components can be separated into four parts: type, name, options, and values. Forms supports several types of form-tags including text fields, number fields, date fields, checkboxes, radio buttons, menus, file-uploading fields, CAPTCHAs, and quiz fields.', 'paulus' ) . '</p>';
		$content['edit_form_tags'] .= '<p>' . __( 'While form-tags have a comparatively complex syntax, you do not need to know the syntax to add form-tags because you can use the straightforward tag generator (<strong>Generate Tag</strong> button on this screen).', 'paulus' ) . '</p>';

		$content['edit_mail_tags'] = '<p>' . __( 'A mail-tag is also a short code enclosed in square brackets that you can use in every Mail and Mail (2) field. A mail-tag represents a user input value through an input field of a corresponding form-tag.', 'paulus' ) . '</p>';
		$content['edit_mail_tags'] .= '<p>' . __( 'There are also special mail-tags that have specific names, but do not have corresponding form-tags. They are used to represent meta information of form submissions like the submitter&#8217;s IP address or the URL of the page.', 'paulus' ) . '</p>';

		$content['integration_overview'] = '<p>' . __( 'On this screen, you can manage services that are available through Forms. Using API will allow you to collaborate with any services that are available.', 'paulus' ) . '</p>';
		$content['integration_overview'] .= '<p>' . __( 'You may need to first sign up for an account with the service that you plan to use. When you do so, you would need to authorize Forms to access the service with your account.', 'paulus' ) . '</p>';
		$content['integration_overview'] .= '<p>' . __( 'Any information you provide will not be shared with service providers without your authorization.', 'paulus' ) . '</p>';

		if ( ! empty( $content[$name] ) ) {
			return $content[$name];
		}
	}

	public function sidebar() {
		$content = '<p><strong>' . __( 'For more information:', 'paulus' ) . '</strong></p>';
		$content .= '<p>' . paulus_form_link( '#', __( 'Docs', 'paulus' ) ) . '</p>';
		$content .= '<p>' . paulus_form_link( '#', __( 'FAQ', 'paulus' ) ) . '</p>';
		$content .= '<p>' . paulus_form_link( '#', __( 'Support', 'paulus' ) ) . '</p>';

		$this->screen->set_help_sidebar( $content );
	}
}
