<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
	'rest_api_init',
	static function () {
		$controller = new PAULUS_FORM_REST_Controller();
		$controller->register_routes();
	},
	10, 0
);


class PAULUS_FORM_REST_Controller {

	const route_namespace = 'paulus-form/v1';

	public function register_routes() {

		register_rest_route( self::route_namespace,
			'/contact-forms',
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_contact_forms' ),
					'permission_callback' => function () {
						if ( current_user_can( 'paulus_form_read_contact_forms' ) ) {
							return true;
						} else {
							return new WP_Error( 'paulus_form_forbidden',
								__( 'You are not allowed to access contact forms.', 'paulus' ),
								array( 'status' => $this->authreq_code() )
							);
						}
					},
				),
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'create_contact_form' ),
					'permission_callback' => function () {
						if ( current_user_can( 'paulus_form_edit_contact_forms' ) ) {
							return true;
						} else {
							return new WP_Error( 'paulus_form_forbidden',
								__( 'You are not allowed to create a contact form.', 'paulus' ),
								array( 'status' => $this->authreq_code() )
							);
						}
					},
				),
			)
		);

		register_rest_route( self::route_namespace,
			'/contact-forms/(?P<id>\d+)',
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_contact_form' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						$id = (int) $request->get_param( 'id' );

						if ( current_user_can( 'paulus_form_edit_contact_form', $id ) ) {
							return true;
						} else {
							return new WP_Error( 'paulus_form_forbidden',
								__( 'You are not allowed to access the requested contact form.', 'paulus' ),
								array( 'status' => $this->authreq_code() )
							);
						}
					},
				),
				array(
					'methods' => WP_REST_Server::EDITABLE,
					'callback' => array( $this, 'update_contact_form' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						$id = (int) $request->get_param( 'id' );

						if ( current_user_can( 'paulus_form_edit_contact_form', $id ) ) {
							return true;
						} else {
							return new WP_Error( 'paulus_form_forbidden',
								__( 'You are not allowed to access the requested contact form.', 'paulus' ),
								array( 'status' => $this->authreq_code() )
							);
						}
					},
				),
				array(
					'methods' => WP_REST_Server::DELETABLE,
					'callback' => array( $this, 'delete_contact_form' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						$id = (int) $request->get_param( 'id' );

						if ( current_user_can( 'paulus_form_delete_contact_form', $id ) ) {
							return true;
						} else {
							return new WP_Error( 'paulus_form_forbidden',
								__( 'You are not allowed to access the requested contact form.', 'paulus' ),
								array( 'status' => $this->authreq_code() )
							);
						}
					},
				),
			)
		);

		register_rest_route( self::route_namespace,
			'/contact-forms/(?P<id>\d+)/feedback',
			array(
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'create_feedback' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route( self::route_namespace,
			'/contact-forms/(?P<id>\d+)/feedback/schema',
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_schema' ),
					'permission_callback' => '__return_true',
				),
				'schema' => 'paulus_form_swv_get_meta_schema',
			)
		);

		register_rest_route( self::route_namespace,
			'/contact-forms/(?P<id>\d+)/refill',
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_refill' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	public function get_contact_forms( WP_REST_Request $request ) {
		$args = array();

		$per_page = $request->get_param( 'per_page' );

		if ( null !== $per_page ) {
			$args['posts_per_page'] = (int) $per_page;
		}

		$offset = $request->get_param( 'offset' );

		if ( null !== $offset ) {
			$args['offset'] = (int) $offset;
		}

		$order = $request->get_param( 'order' );

		if ( null !== $order ) {
			$args['order'] = (string) $order;
		}

		$orderby = $request->get_param( 'orderby' );

		if ( null !== $orderby ) {
			$args['orderby'] = (string) $orderby;
		}

		$search = $request->get_param( 'search' );

		if ( null !== $search ) {
			$args['s'] = (string) $search;
		}

		$items = Paulus_Form_ContactForm::find( $args );

		$response = array();

		foreach ( $items as $item ) {
			$response[] = array(
				'id' => $item->id(),
				'hash' => $item->hash(),
				'slug' => $item->name(),
				'title' => $item->title(),
				'locale' => $item->locale(),
			);
		}

		return rest_ensure_response( $response );
	}

	public function create_contact_form( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );

		if ( $id ) {
			return new WP_Error( 'paulus_form_post_exists',
				__( 'Cannot create existing contact form.', 'paulus' ),
				array( 'status' => 400 )
			);
		}

		$args = $request->get_params();
		$args['id'] = -1; // Create
		$context = $request->get_param( 'context' );
		$item = paulus_form_save_contact_form( $args, $context );

		if ( ! $item ) {
			return new WP_Error( 'paulus_form_cannot_save',
				__( 'There was an error saving the contact form.', 'paulus' ),
				array( 'status' => 500 )
			);
		}

		$response = array(
			'id' => $item->id(),
			'slug' => $item->name(),
			'title' => $item->title(),
			'locale' => $item->locale(),
			'properties' => $this->get_properties( $item ),
			'config_errors' => array(),
		);

		if ( paulus_form_validate_configuration() ) {
			$config_validator = new Paulus_Form_ConfigValidator( $item );
			$config_validator->validate();

			$response['config_errors'] = $config_validator->collect_error_messages(
				array( 'decodes_html_entities' => true )
			);

			if ( 'save' === $context ) {
				$config_validator->save();
			}
		}

		return rest_ensure_response( $response );
	}

	public function get_contact_form( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$item = paulus_form_contact_form( $id );

		if ( ! $item ) {
			return new WP_Error( 'paulus_form_not_found',
				__( 'The requested contact form was not found.', 'paulus' ),
				array( 'status' => 404 )
			);
		}

		$response = array(
			'id' => $item->id(),
			'slug' => $item->name(),
			'title' => $item->title(),
			'locale' => $item->locale(),
			'properties' => $this->get_properties( $item ),
		);

		return rest_ensure_response( $response );
	}

	public function update_contact_form( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$item = paulus_form_contact_form( $id );

		if ( ! $item ) {
			return new WP_Error( 'paulus_form_not_found',
				__( 'The requested contact form was not found.', 'paulus' ),
				array( 'status' => 404 )
			);
		}

		$args = $request->get_params();
		$context = $request->get_param( 'context' );
		$item = paulus_form_save_contact_form( $args, $context );

		if ( ! $item ) {
			return new WP_Error( 'paulus_form_cannot_save',
				__( 'There was an error saving the contact form.', 'paulus' ),
				array( 'status' => 500 )
			);
		}

		$response = array(
			'id' => $item->id(),
			'slug' => $item->name(),
			'title' => $item->title(),
			'locale' => $item->locale(),
			'properties' => $this->get_properties( $item ),
			'config_errors' => array(),
		);

		if ( paulus_form_validate_configuration() ) {
			$config_validator = new Paulus_Form_ConfigValidator( $item );
			$config_validator->validate();

			$response['config_errors'] = $config_validator->collect_error_messages(
				array( 'decodes_html_entities' => true )
			);

			if ( 'save' === $context ) {
				$config_validator->save();
			}
		}

		return rest_ensure_response( $response );
	}

	public function delete_contact_form( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$item = paulus_form_contact_form( $id );

		if ( ! $item ) {
			return new WP_Error( 'paulus_form_not_found',
				__( 'The requested contact form was not found.', 'paulus' ),
				array( 'status' => 404 )
			);
		}

		$result = $item->delete();

		if ( ! $result ) {
			return new WP_Error( 'paulus_form_cannot_delete',
				__( 'There was an error deleting the contact form.', 'paulus' ),
				array( 'status' => 500 )
			);
		}

		$response = array( 'deleted' => true );

		return rest_ensure_response( $response );
	}

	public function create_feedback( WP_REST_Request $request ) {
		$content_type = $request->get_header( 'Content-Type' ) ?? '';

		if ( ! str_starts_with( $content_type, 'multipart/form-data' ) ) {
			return new WP_Error( 'paulus_form_unsupported_media_type',
				__( 'The request payload format is not supported.', 'paulus' ),
				array( 'status' => 415 )
			);
		}

		$url_params = $request->get_url_params();

		$item = null;

		if ( ! empty( $url_params['id'] ) ) {
			$item = paulus_form_contact_form( $url_params['id'] );
		}

		if ( ! $item ) {
			return new WP_Error( 'paulus_form_not_found',
				__( 'The requested contact form was not found.', 'paulus' ),
				array( 'status' => 404 )
			);
		}

		$unit_tag = paulus_form_sanitize_unit_tag(
			$request->get_param( '_paulus_form_unit_tag' )
		);

		if ( empty( $unit_tag ) ) {
			return new WP_Error( 'paulus_form_unit_tag_not_found',
				__( 'There is no valid unit tag.', 'paulus' ),
				array( 'status' => 400 )
			);
		}

		$result = $item->submit();

		$response = array_merge( $result, array(
			'into' => sprintf( '#%s', $unit_tag ),
			'invalid_fields' => array(),
		) );

		if ( ! empty( $result['invalid_fields'] ) ) {
			$invalid_fields = array();

			foreach ( (array) $result['invalid_fields'] as $name => $field ) {
				if ( ! paulus_form_is_name( $name ) ) {
					continue;
				}

				$name = strtr( $name, '.', '_' );

				$invalid_fields[] = array(
					'field' => $name,
					'message' => $field['reason'],
					'idref' => $field['idref'],
					'error_id' => sprintf(
						'%1$s-ve-%2$s',
						$unit_tag,
						$name
					),
				);
			}

			$response['invalid_fields'] = $invalid_fields;
		}

		$response = paulus_form_apply_filters_deprecated(
			'paulus_form_ajax_json_echo',
			array( $response, $result ),
			'5.2',
			'paulus_form_feedback_response'
		);

		$response = apply_filters( 'paulus_form_feedback_response', $response, $result );

		return rest_ensure_response( $response );
	}


	public function get_schema( WP_REST_Request $request ) {
		$url_params = $request->get_url_params();

		$item = null;

		if ( ! empty( $url_params['id'] ) ) {
			$item = paulus_form_contact_form( $url_params['id'] );
		}

		if ( ! $item ) {
			return new WP_Error( 'paulus_form_not_found',
				__( 'The requested contact form was not found.', 'paulus' ),
				array( 'status' => 404 )
			);
		}

		$schema = $item->get_schema();

		$response = isset( $schema ) ? $schema->toArray() : array();

		return rest_ensure_response( $response );
	}


	public function get_refill( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$item = paulus_form_contact_form( $id );

		if ( ! $item ) {
			return new WP_Error( 'paulus_form_not_found',
				__( 'The requested contact form was not found.', 'paulus' ),
				array( 'status' => 404 )
			);
		}

		$response = paulus_form_apply_filters_deprecated(
			'paulus_form_ajax_onload',
			array( array() ),
			'5.2',
			'paulus_form_refill_response'
		);

		$response = apply_filters( 'paulus_form_refill_response', array() );

		return rest_ensure_response( $response );
	}

	private function get_properties( Paulus_Form_ContactForm $contact_form ) {
		$properties = $contact_form->get_properties();

		$properties['form'] = array(
			'content' => (string) $properties['form'],
			'fields' => array_map(
				static function ( Paulus_Form_FormTag $form_tag ) {
					return array(
						'type' => $form_tag->type,
						'basetype' => $form_tag->basetype,
						'name' => $form_tag->name,
						'options' => $form_tag->options,
						'raw_values' => $form_tag->raw_values,
						'labels' => $form_tag->labels,
						'values' => $form_tag->values,
						'pipes' => $form_tag->pipes instanceof Paulus_Form_Pipes
							? $form_tag->pipes->to_array()
							: $form_tag->pipes,
						'content' => $form_tag->content,
					);
				},
				$contact_form->scan_form_tags()
			),
		);

		$properties['additional_settings'] = array(
			'content' => (string) $properties['additional_settings'],
			'settings' => array_filter( array_map(
				static function ( $setting ) {
					$pattern = '/^([a-zA-Z0-9_]+)[\t ]*:(.*)$/';

					if ( preg_match( $pattern, $setting, $matches ) ) {
						$name = trim( $matches[1] );
						$value = trim( $matches[2] );

						if ( in_array( $value, array( 'on', 'true' ), true ) ) {
							$value = true;
						} elseif ( in_array( $value, array( 'off', 'false' ), true ) ) {
							$value = false;
						}

						return array( $name, $value );
					}

					return false;
				},
				explode( "\n", $properties['additional_settings'] )
			) ),
		);

		return $properties;
	}

	private function get_argument_schema() {
		return array(
			'id' => array(
				'description' => __( 'Unique identifier for the contact form.', 'paulus' ),
				'type' => 'integer',
				'required' => true,
			),
		);
	}

	private function authreq_code() {
		return rest_authorization_required_code();
	}

}
