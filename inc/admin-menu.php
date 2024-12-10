<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function coding_bunny_image_optimizer_menu() {
	add_menu_page(
	esc_html__( 'CodingBunny Image Optimizer', 'coding-bunny-image-optimizer' ),
	esc_html__( 'Image Optimizer', 'coding-bunny-image-optimizer' ),
	'manage_options',
	'coding-bunny-image-optimizer',
	'coding_bunny_image_optimizer_settings_page',
	'dashicons-format-gallery',
	11
);
}
add_action( 'admin_menu', 'coding_bunny_image_optimizer_menu' );