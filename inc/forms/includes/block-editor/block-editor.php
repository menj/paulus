<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action(
	'init',
	'paulus_form_init_block_editor_assets',
	10, 0
);

function paulus_form_init_block_editor_assets() {
	register_block_type(
		paulus_form_plugin_path( 'includes/block-editor' )
	);
}
