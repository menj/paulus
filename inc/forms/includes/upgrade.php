<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'paulus_form_upgrade', 'paulus_form_upgrade_58', 10, 2 );

/**
 * Runs functions necessary when upgrading from old plugin versions before 5.8.
 *
 * @since 5.8.0 New `_config_validation` post meta is introduced.
 */
function paulus_form_upgrade_58( $new_ver, $old_ver ) {
	if ( ! version_compare( $old_ver, '5.8-dev', '<' ) ) {
		return;
	}

	$posts = Paulus_Form_ContactForm::find( array(
		'post_status' => 'any',
		'posts_per_page' => -1,
	) );

	foreach ( $posts as $post ) {
		$post_id = $post->id();

		// Delete the old post meta for config-validation results.
		delete_post_meta( $post_id, '_config_errors' );

		// Add the contact form hash.
		add_post_meta( $post_id, '_hash',
			paulus_form_generate_contact_form_hash( $post_id ),
			true // Unique
		);
	}
}


add_action( 'paulus_form_upgrade', 'paulus_form_convert_to_cpt', 10, 2 );

/**
 * Converts old data in the dedicated database table to custom posts.
 *
 * @since 3.0.0 `paulus_form_contact_form` CPT is introduced.
 */
function paulus_form_convert_to_cpt( $new_ver, $old_ver ) {
	global $wpdb;

	if ( ! version_compare( $old_ver, '3.0-dev', '<' ) ) {
		return;
	}

	$old_rows = array();

	$table_exists = $wpdb->get_var( $wpdb->prepare(
		"SHOW TABLES LIKE %s",
		$wpdb->prefix . 'contact_form_7'
	) );

	if ( $table_exists ) {
		$old_rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM %i",
			$wpdb->prefix . 'contact_form_7'
		) );
	} elseif (
		$opt = get_option( 'wpcf7' ) and
		! empty( $opt['contact_forms'] )
	) {
		foreach ( (array) $opt['contact_forms'] as $key => $value ) {
			$old_rows[] = (object) array_merge(
				$value,
				array( 'cf7_unit_id' => $key )
			);
		}
	}

	foreach ( (array) $old_rows as $row ) {
		$var = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM %i WHERE meta_key = %s AND meta_value = %d",
			$wpdb->postmeta,
			'_old_cf7_unit_id',
			$row->cf7_unit_id
		) );

		if ( $var ) {
			continue;
		}

		$postarr = array(
			'post_type' => 'wpcf7_contact_form',
			'post_status' => 'publish',
			'post_title' => maybe_unserialize( $row->title ),
		);

		$post_id = wp_insert_post( $postarr );

		if ( $post_id ) {
			update_post_meta( $post_id, '_old_cf7_unit_id', $row->cf7_unit_id );

			$metas = array(
				'form',
				'mail',
				'mail_2',
				'messages',
				'additional_settings',
			);

			foreach ( $metas as $meta ) {
				update_post_meta( $post_id, '_' . $meta,
					paulus_form_normalize_newline_deep( maybe_unserialize( $row->{$meta} ) )
				);
			}
		}
	}
}


add_action( 'paulus_form_upgrade', 'paulus_form_prepend_underscore', 10, 2 );

/**
 * Prepends an underscore to post meta keys.
 */
function paulus_form_prepend_underscore( $new_ver, $old_ver ) {
	if ( version_compare( $old_ver, '3.0-dev', '<' ) ) {
		return;
	}

	if ( ! version_compare( $old_ver, '3.3-dev', '<' ) ) {
		return;
	}

	$posts = Paulus_Form_ContactForm::find( array(
		'post_status' => 'any',
		'posts_per_page' => -1,
	) );

	foreach ( $posts as $post ) {
		$props = $post->get_properties();

		foreach ( $props as $prop => $value ) {
			if ( metadata_exists( 'post', $post->id(), '_' . $prop ) ) {
				continue;
			}

			update_post_meta( $post->id(), '_' . $prop, $value );
			delete_post_meta( $post->id(), $prop );
		}
	}
}
