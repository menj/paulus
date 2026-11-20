<?php
/**
 * Really Simple CAPTCHA
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* form_tag handler */

add_action( 'paulus_form_init', 'paulus_form_add_form_tag_captcha', 10, 0 );

function paulus_form_add_form_tag_captcha() {
	// CAPTCHA-Challenge (image)
	paulus_form_add_form_tag( 'captchac',
		'paulus_form_captchac_form_tag_handler',
		array(
			'name-attr' => true,
			'zero-controls-container' => true,
			'not-for-mail' => true,
		)
	);

	// CAPTCHA-Response (input)
	paulus_form_add_form_tag( 'captchar',
		'paulus_form_captchar_form_tag_handler',
		array(
			'name-attr' => true,
			'do-not-store' => true,
			'not-for-mail' => true,
		)
	);
}

function paulus_form_captchac_form_tag_handler( $tag ) {
	if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
		return wp_kses_data( sprintf(
			/* translators: %s: URL to the Really Simple CAPTCHA plugin page */
			__( '<strong>Warning:</strong> The <a href="%s">Really Simple CAPTCHA</a> plugin is not active.', 'paulus' ),
			'https://wordpress.org/plugins/really-simple-captcha/'
		) );
	}

	if ( empty( $tag->name ) ) {
		return '';
	}

	$class = paulus_form_form_controls_class( $tag->type );
	$class .= ' paulus-form-captcha-' . str_replace( ':', '', $tag->name );

	$atts = array();
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();

	$op = array( // Default
		'img_size' => array( 72, 24 ),
		'base' => array( 6, 18 ),
		'font_size' => 14,
		'font_char_width' => 15,
	);

	$op = array_merge( $op, paulus_form_captchac_options( $tag->options ) );

	if ( ! $filename = paulus_form_generate_captcha( $op ) ) {
		return '';
	}

	if ( ! empty( $op['img_size'] ) ) {
		if ( isset( $op['img_size'][0] ) ) {
			$atts['width'] = $op['img_size'][0];
		}

		if ( isset( $op['img_size'][1] ) ) {
			$atts['height'] = $op['img_size'][1];
		}
	}

	$atts['alt'] = 'captcha';
	$atts['src'] = paulus_form_captcha_url( $filename );

	$atts = paulus_form_format_atts( $atts );

	$prefix = substr( $filename, 0, strrpos( $filename, '.' ) );

	$html = sprintf(
		'<input type="hidden" name="%1$s" value="%2$s" /><img %3$s />',
		esc_attr( sprintf( '_paulus_form_captcha_challenge_%s', $tag->name ) ),
		esc_attr( $prefix ),
		$atts
	);

	return $html;
}

function paulus_form_captchar_form_tag_handler( $tag ) {
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

	if (
		$atts['maxlength'] and
		$atts['minlength'] and
		$atts['maxlength'] < $atts['minlength']
	) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );
	$atts['autocomplete'] = 'off';

	if ( $validation_error ) {
		$atts['aria-invalid'] = 'true';
		$atts['aria-describedby'] = paulus_form_get_validation_error_reference(
			$tag->name
		);
	} else {
		$atts['aria-invalid'] = 'false';
	}

	$value = (string) reset( $tag->values );

	if ( paulus_form_is_posted() ) {
		$value = '';
	}

	if (
		$tag->has_option( 'placeholder' ) or
		$tag->has_option( 'watermark' )
	) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$atts['value'] = $value;
	$atts['type'] = 'text';
	$atts['name'] = $tag->name;

	$html = sprintf(
		'<span class="paulus-form-form-control-wrap" data-name="%1$s"><input %2$s />%3$s</span>',
		esc_attr( $tag->name ),
		paulus_form_format_atts( $atts ),
		$validation_error
	);

	return $html;
}


/* Validation filter */

add_filter(
	'paulus_form_validate_captchar',
	'paulus_form_captcha_validation_filter',
	10, 2
);

function paulus_form_captcha_validation_filter( $result, $tag ) {
	$prefix = paulus_form_superglobal_post( '_paulus_form_captcha_challenge_' . $tag->name );
	$response = paulus_form_canonicalize( paulus_form_superglobal_post( $tag->name ) );

	if ( ! paulus_form_check_captcha( $prefix, $response ) ) {
		$result->invalidate( $tag, paulus_form_get_message( 'captcha_not_match' ) );
	}

	paulus_form_remove_captcha( $prefix );

	return $result;
}


/* Ajax echo filter */

add_filter( 'paulus_form_refill_response', 'paulus_form_captcha_ajax_refill', 10, 1 );
add_filter( 'paulus_form_feedback_response', 'paulus_form_captcha_ajax_refill', 10, 1 );

function paulus_form_captcha_ajax_refill( $items ) {
	if ( ! is_array( $items ) ) {
		return $items;
	}

	$tags = paulus_form_scan_form_tags( array( 'type' => 'captchac' ) );

	if ( empty( $tags ) ) {
		return $items;
	}

	$refill = array();

	foreach ( $tags as $tag ) {
		$name = $tag->name;
		$options = $tag->options;

		if ( empty( $name ) ) {
			continue;
		}

		$op = paulus_form_captchac_options( $options );

		if ( $filename = paulus_form_generate_captcha( $op ) ) {
			$captcha_url = paulus_form_captcha_url( $filename );
			$refill[$name] = $captcha_url;
		}
	}

	if ( ! empty( $refill ) ) {
		$items['captcha'] = $refill;
	}

	return $items;
}


/* Messages */

add_filter( 'paulus_form_messages', 'paulus_form_captcha_messages', 10, 1 );

function paulus_form_captcha_messages( $messages ) {
	$messages = array_merge( $messages, array(
		'captcha_not_match' => array(
			'description' =>
				__( 'The code that sender entered does not match the CAPTCHA', 'paulus' ),
			'default' =>
				__( 'Your entered code is incorrect.', 'paulus' ),
		),
	) );

	return $messages;
}


/* Warning message */

add_action(
	'paulus_form_admin_warnings',
	'paulus_form_captcha_display_warning_message',
	10, 3
);

function paulus_form_captcha_display_warning_message( $page, $action, $object ) {
	if ( $object instanceof Paulus_Form_ContactForm ) {
		$contact_form = $object;
	} else {
		return;
	}

	$has_tags = (bool) $contact_form->scan_form_tags(
		array( 'type' => array( 'captchac' ) )
	);

	if ( ! $has_tags ) {
		return;
	}

	if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
		return;
	}

	$uploads_dir = paulus_form_captcha_tmp_dir();
	paulus_form_init_captcha();

	if ( ! is_dir( $uploads_dir ) or ! wp_is_writable( $uploads_dir ) ) {
		wp_admin_notice(
			sprintf(
				/* translators: %s: Path to the temporary folder */
				__( 'This contact form contains CAPTCHA fields, but the temporary folder for the files (%s) does not exist or is not writable. You can create the folder or change its permission manually.', 'paulus' ),
				$uploads_dir
			),
			array( 'type' => 'warning' )
		);
	}

	if (
		! function_exists( 'imagecreatetruecolor' ) or
		! function_exists( 'imagettftext' )
	) {
		wp_admin_notice(
			__( 'This contact form contains CAPTCHA fields, but the necessary libraries (GD and FreeType) are not available on your server.', 'paulus' ),
			array( 'type' => 'warning' )
		);
	}
}


/* CAPTCHA functions */

function paulus_form_init_captcha() {
	static $captcha = null;

	if ( $captcha ) {
		return $captcha;
	}

	if ( class_exists( 'ReallySimpleCaptcha' ) ) {
		$captcha = new ReallySimpleCaptcha();
	} else {
		return false;
	}

	$dir = trailingslashit( paulus_form_captcha_tmp_dir() );

	$captcha->tmp_dir = $dir;

	if ( is_callable( array( $captcha, 'make_tmp_dir' ) ) ) {
		$result = $captcha->make_tmp_dir();

		if ( ! $result ) {
			return false;
		}

		return $captcha;
	}

	$result = wp_mkdir_p( $dir );

	if ( ! $result ) {
		return false;
	}

	$htaccess_file = path_join( $dir, '.htaccess' );

	if ( file_exists( $htaccess_file ) ) {
		list( $first_line_comment ) = (array) file(
			$htaccess_file,
			FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
		);

		if ( '# Apache 2.4+' === $first_line_comment ) {
			return $captcha;
		}
	}

	$filesystem = Paulus_Form_Filesystem::get_instance();

	$htaccess_body = '
# Apache 2.4+
<IfModule authz_core_module>
    Require all denied
    <FilesMatch "^\w+\.(jpe?g|gif|png)$">
        Require all granted
    </FilesMatch>
</IfModule>

# Apache 2.2
<IfModule !authz_core_module>
    Order deny,allow
    Deny from all
    <FilesMatch "^\w+\.(jpe?g|gif|png)$">
        Allow from all
    </FilesMatch>
</IfModule>
';

	$filesystem->put_contents( $htaccess_file, ltrim( $htaccess_body ) );

	return $captcha;
}


/**
 * Returns the directory path for Really Simple CAPTCHA files.
 *
 * @return string Directory path.
 */
function paulus_form_captcha_tmp_dir() {
	if ( defined( 'PAULUS_FORM_CAPTCHA_TMP_DIR' ) ) {
		$dir = path_join( WP_CONTENT_DIR, PAULUS_FORM_CAPTCHA_TMP_DIR );
		wp_mkdir_p( $dir );

		if ( paulus_form_is_file_path_in_content_dir( $dir ) ) {
			return $dir;
		}
	}

	$dir = path_join( paulus_form_upload_dir( 'dir' ), 'paulus_form_captcha' );
	wp_mkdir_p( $dir );
	return $dir;
}


function paulus_form_captcha_tmp_url() {
	if ( defined( 'PAULUS_FORM_CAPTCHA_TMP_URL' ) ) {
		return PAULUS_FORM_CAPTCHA_TMP_URL;
	} else {
		return path_join( paulus_form_upload_dir( 'url' ), 'paulus_form_captcha' );
	}
}

function paulus_form_captcha_url( $filename ) {
	$url = path_join( paulus_form_captcha_tmp_url(), $filename );

	if ( is_ssl() and 'http:' === substr( $url, 0, 5 ) ) {
		$url = 'https:' . substr( $url, 5 );
	}

	return apply_filters( 'paulus_form_captcha_url', sanitize_url( $url ) );
}

function paulus_form_generate_captcha( $options = null ) {
	if ( ! $captcha = paulus_form_init_captcha() ) {
		return false;
	}

	if (
		! is_dir( $captcha->tmp_dir ) or
		! wp_is_writable( $captcha->tmp_dir )
	) {
		return false;
	}

	$img_type = imagetypes();

	if ( $img_type & IMG_PNG ) {
		$captcha->img_type = 'png';
	} elseif ( $img_type & IMG_GIF ) {
		$captcha->img_type = 'gif';
	} elseif ( $img_type & IMG_JPG ) {
		$captcha->img_type = 'jpeg';
	} else {
		return false;
	}

	if ( is_array( $options ) ) {
		if ( isset( $options['img_size'] ) ) {
			$captcha->img_size = $options['img_size'];
		}

		if ( isset( $options['base'] ) ) {
			$captcha->base = $options['base'];
		}

		if ( isset( $options['font_size'] ) ) {
			$captcha->font_size = $options['font_size'];
		}

		if ( isset( $options['font_char_width'] ) ) {
			$captcha->font_char_width = $options['font_char_width'];
		}

		if ( isset( $options['fg'] ) ) {
			$captcha->fg = $options['fg'];
		}

		if ( isset( $options['bg'] ) ) {
			$captcha->bg = $options['bg'];
		}
	}

	$prefix = wp_rand();
	$captcha_word = $captcha->generate_random_word();
	return $captcha->generate_image( $prefix, $captcha_word );
}

function paulus_form_check_captcha( $prefix, $response ) {
	if ( ! $captcha = paulus_form_init_captcha() ) {
		return false;
	}

	return $captcha->check( $prefix, $response );
}

function paulus_form_remove_captcha( $prefix ) {
	if ( ! $captcha = paulus_form_init_captcha() ) {
		return false;
	}

	// Forms generates $prefix with wp_rand()
	if ( preg_match( '/[^0-9]/', $prefix ) ) {
		return false;
	}

	$captcha->remove( $prefix );
}

add_action( 'shutdown', 'paulus_form_cleanup_captcha_files', 20, 0 );

function paulus_form_cleanup_captcha_files() {
	if ( ! $captcha = paulus_form_init_captcha() ) {
		return false;
	}

	if ( is_callable( array( $captcha, 'cleanup' ) ) ) {
		return $captcha->cleanup();
	}

	$dir = trailingslashit( paulus_form_captcha_tmp_dir() );

	if (
		! is_dir( $dir ) or
		! is_readable( $dir ) or
		! wp_is_writable( $dir )
	) {
		return false;
	}

	$filesystem = Paulus_Form_Filesystem::get_instance();

	if ( $handle = opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( ! preg_match( '/^[0-9]+\.(php|txt|png|gif|jpeg)$/', $file ) ) {
				continue;
			}

			$stat = stat( path_join( $dir, $file ) );

			if ( $stat['mtime'] + HOUR_IN_SECONDS < time() ) {
				$filesystem->delete( path_join( $dir, $file ) );
			}
		}

		closedir( $handle );
	}
}

function paulus_form_captchac_options( $options ) {
	if ( ! is_array( $options ) ) {
		return array();
	}

	$op = array();
	$image_size_array = preg_grep( '%^size:[smlSML]$%', $options );

	if ( $image_size = array_shift( $image_size_array ) ) {
		preg_match( '%^size:([smlSML])$%', $image_size, $is_matches );

		switch ( strtolower( $is_matches[1] ) ) {
			case 's':
				$op['img_size'] = array( 60, 20 );
				$op['base'] = array( 6, 15 );
				$op['font_size'] = 11;
				$op['font_char_width'] = 13;
				break;
			case 'l':
				$op['img_size'] = array( 84, 28 );
				$op['base'] = array( 6, 20 );
				$op['font_size'] = 17;
				$op['font_char_width'] = 19;
				break;
			case 'm':
			default:
				$op['img_size'] = array( 72, 24 );
				$op['base'] = array( 6, 18 );
				$op['font_size'] = 14;
				$op['font_char_width'] = 15;
		}
	}

	$fg_color_array = preg_grep(
		'%^fg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%',
		$options
	);

	if ( $fg_color = array_shift( $fg_color_array ) ) {
		preg_match(
			'%^fg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%',
			$fg_color,
			$fc_matches
		);

		if ( 3 === strlen( $fc_matches[1] ) ) {
			$r = substr( $fc_matches[1], 0, 1 );
			$g = substr( $fc_matches[1], 1, 1 );
			$b = substr( $fc_matches[1], 2, 1 );

			$op['fg'] = array(
				hexdec( $r . $r ),
				hexdec( $g . $g ),
				hexdec( $b . $b ),
			);
		} elseif ( 6 === strlen( $fc_matches[1] ) ) {
			$r = substr( $fc_matches[1], 0, 2 );
			$g = substr( $fc_matches[1], 2, 2 );
			$b = substr( $fc_matches[1], 4, 2 );

			$op['fg'] = array(
				hexdec( $r ),
				hexdec( $g ),
				hexdec( $b ),
			);
		}
	}

	$bg_color_array = preg_grep(
		'%^bg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%',
		$options
	);

	if ( $bg_color = array_shift( $bg_color_array ) ) {
		preg_match(
			'%^bg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%',
			$bg_color,
			$bc_matches
		);

		if ( 3 === strlen( $bc_matches[1] ) ) {
			$r = substr( $bc_matches[1], 0, 1 );
			$g = substr( $bc_matches[1], 1, 1 );
			$b = substr( $bc_matches[1], 2, 1 );

			$op['bg'] = array(
				hexdec( $r . $r ),
				hexdec( $g . $g ),
				hexdec( $b . $b ),
			);
		} elseif ( 6 === strlen( $bc_matches[1] ) ) {
			$r = substr( $bc_matches[1], 0, 2 );
			$g = substr( $bc_matches[1], 2, 2 );
			$b = substr( $bc_matches[1], 4, 2 );

			$op['bg'] = array(
				hexdec( $r ),
				hexdec( $g ),
				hexdec( $b ),
			);
		}
	}

	return $op;
}
