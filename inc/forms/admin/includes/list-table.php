<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Paulus_Form_List_Table extends WP_List_Table {

	private static $instance;

	public static function get_instance() {
		self::$instance ??= new self();

		return self::$instance;
	}

	private function __construct() {
		parent::__construct( array(
			'singular' => 'post',
			'plural' => 'posts',
			'ajax' => false,
		) );
	}

	public function prepare_items() {
		$current_orderby = paulus_form_superglobal_get( 'orderby' );
		$current_order = paulus_form_superglobal_get( 'order' );

		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		foreach ( array_keys( $columns ) as $column_key ) {
			if ( isset( $sortable[$column_key] ) ) {
				$orderby = $sortable[$column_key][0] ?? '';
				$initial_order = $sortable[$column_key][4] ?? '';

				if ( '' === $current_orderby and $initial_order ) {
					$current_orderby = $orderby;
					$current_order = $initial_order;

					break;
				}
			}
		}

		$per_page = $this->get_items_per_page( 'paulus_form_contact_forms_per_page' );

		$args = array(
			'posts_per_page' => $per_page,
			'orderby' => $current_orderby,
			'order' => $current_order,
			'offset' => ( $this->get_pagenum() - 1 ) * $per_page,
			's' => paulus_form_superglobal_get( 's' ),
		);

		$this->items = Paulus_Form_ContactForm::find( $args );

		$total_items = Paulus_Form_ContactForm::count();
		$total_pages = ceil( $total_items / $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page,
		) );
	}

	public function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Title', 'paulus' ),
			'author' => __( 'Author', 'paulus' ),
			'date' => __( 'Date', 'paulus' ),
		);
	}

	protected function get_sortable_columns() {
		return array(
			'title' => array(
				'title',
				false,
				__( 'Title', 'paulus' ),
				__( 'Table ordered by Title.', 'paulus' ),
				'asc',
			),
			'author' => array(
				'author',
				false,
				__( 'Author', 'paulus' ),
				__( 'Table ordered by Author.', 'paulus' ),
			),
			'date' => array(
				'date',
				true,
				__( 'Date', 'paulus' ),
				__( 'Table ordered by Date.', 'paulus' ),
			),
		);
	}

	protected function get_bulk_actions() {
		$post_type_obj = get_post_type_object( Paulus_Form_ContactForm::post_type );

		if ( current_user_can( $post_type_obj->cap->edit_posts ) ) {
			return array(
				'delete' => __( 'Delete', 'paulus' ),
			);
		} else {
			return array();
		}
	}

	public function column_default( $item, $column_name ) {
		do_action( 'paulus_form_manage_custom_column', $column_name, $item->id() );
	}

	public function column_cb( $item ) {
		$post_type_obj = get_post_type_object( Paulus_Form_ContactForm::post_type );
		$show = current_user_can( $post_type_obj->cap->edit_post, $item->id() );

		if ( ! $show ) {
			return '';
		}

		$formatter = new PAULUS_FORM_HTMLFormatter();

		$formatter->append_start_tag( 'input', array(
			'id' => sprintf( 'cb-select-%d', $item->id() ),
			'type' => 'checkbox',
			'name' => sprintf( '%s[]', $this->_args['singular'] ),
			'value' => $item->id(),
		) );

		$formatter->append_start_tag( 'label', array(
			'for' => sprintf( 'cb-select-%d', $item->id() ),
		) );

		$formatter->append_start_tag( 'span', array(
			'class' => 'screen-reader-text',
		) );

		$formatter->append_preformatted(
			esc_html( sprintf(
				/* translators: %s: contact form title */
				__( 'Select %s', 'paulus' ),
				$item->title()
			) )
		);

		$formatter->print();
	}

	public function column_title( $item ) {
		$formatter = new PAULUS_FORM_HTMLFormatter();

		$formatter->append_start_tag( 'strong' );

		$edit_link = add_query_arg(
			array(
				'post' => absint( $item->id() ),
				'action' => 'edit',
			),
			menu_page_url( 'paulus-form', false )
		);

		$formatter->append_start_tag( 'a', array(
			'class' => 'row-title',
			'href' => esc_url( $edit_link ),
			'aria-label' => sprintf(
				/* translators: %s: title of contact form */
				__( 'Edit &#8220;%s&#8221;', 'paulus' ),
				$item->title()
			),
		) );

		$formatter->append_preformatted( esc_html( $item->title() ) );

		$formatter->end_tag( 'strong' );

		$post_type_obj = get_post_type_object( Paulus_Form_ContactForm::post_type );

		if ( $item->is_true( 'subscribers_only' ) ) {
			$formatter->append_start_tag( 'span', array(
				'class' => 'tag info',
			) );

			$formatter->append_preformatted(
				esc_html( __( 'subscribers only', 'paulus' ) )
			);

			$formatter->end_tag( 'span' );
		}

		if ( $item->is_true( 'demo_mode' ) ) {
			$formatter->append_start_tag( 'span', array(
				'class' => 'tag info',
			) );

			$formatter->append_preformatted(
				esc_html( __( 'demo mode', 'paulus' ) )
			);

			$formatter->end_tag( 'span' );
		}

		if ( $item->is_true( 'skip_mail' ) ) {
			$formatter->append_start_tag( 'span', array(
				'class' => 'tag info',
			) );

			$formatter->append_preformatted(
				esc_html( __( 'skip mail', 'paulus' ) )
			);

			$formatter->end_tag( 'span' );
		}

		if ( $item->is_true( 'do_not_store' ) ) {
			$formatter->append_start_tag( 'span', array(
				'class' => 'tag info',
			) );

			$formatter->append_preformatted(
				esc_html( __( 'do not store', 'paulus' ) )
			);

			$formatter->end_tag( 'span' );
		}

		if (
			paulus_form_validate_configuration() and
			current_user_can( $post_type_obj->cap->edit_post, $item->id() )
		) {
			$config_validator = new Paulus_Form_ConfigValidator( $item );
			$config_validator->restore();

			if ( $count_errors = $config_validator->count_errors() ) {
				$formatter->append_start_tag( 'span', array(
					'class' => 'tag warning',
				) );

				$formatter->append_preformatted(
					esc_html( __( 'has config errors', 'paulus' ) )
				);

				$formatter->end_tag( 'span' );
			}
		}

		$formatter->print();
	}

	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $column_name !== $primary ) {
			return '';
		}

		$edit_link = add_query_arg(
			array(
				'post' => absint( $item->id() ),
				'action' => 'edit',
			),
			menu_page_url( 'paulus-form', false )
		);

		$actions = array(
			'edit' => paulus_form_link( $edit_link, __( 'Edit', 'paulus' ) ),
		);

		if ( current_user_can( 'paulus_form_edit_contact_form', $item->id() ) ) {
			$copy_link = add_query_arg(
				array(
					'post' => absint( $item->id() ),
					'action' => 'copy',
				),
				menu_page_url( 'paulus-form', false )
			);

			$copy_link = wp_nonce_url(
				$copy_link,
				'paulus-form-copy-contact-form_' . absint( $item->id() )
			);

			$actions = array_merge( $actions, array(
				'copy' => paulus_form_link(
					$copy_link,
					__( 'Duplicate', 'paulus' )
				),
			) );
		}

		return $this->row_actions( $actions );
	}

	public function column_author( $item ) {
		$post = get_post( $item->id() );

		if ( ! $post ) {
			return;
		}

		$author = get_userdata( $post->post_author );

		if ( false === $author ) {
			return;
		}

		echo esc_html( $author->display_name );
	}

	public function column_date( $item ) {
		$datetime = get_post_datetime( $item->id() );

		if ( false === $datetime ) {
			return '';
		}

		$t_time = sprintf(
			/* translators: 1: date, 2: time */
			__( '%1$s at %2$s', 'paulus' ),
			/* translators: date format, see https://www.php.net/date */
			$datetime->format( __( 'Y/m/d', 'paulus' ) ),
			/* translators: time format, see https://www.php.net/date */
			$datetime->format( __( 'g:i a', 'paulus' ) )
		);

		echo $t_time;
	}
}
