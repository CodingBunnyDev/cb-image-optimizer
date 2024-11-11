<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Function to add a menu item to the WordPress admin
function coding_bunny_image_optimizer_menu() {
    add_menu_page(
        esc_html__( 'CodingBunny Image Optimizer', 'coding-bunny-image-optimizer' ), // Page title
        esc_html__( 'Image Optimizer', 'coding-bunny-image-optimizer' ), // Menu title
        'manage_options', // Capability required
        'coding-bunny-image-optimizer', // Menu slug
        'coding_bunny_image_optimizer_settings_page', // Callback function
        'dashicons-format-gallery', // Menu icon
        11 // Menu position
    );
}

// Hook the coding_bunny_image_optimizer_menu function into the admin_menu action
add_action( 'admin_menu', 'coding_bunny_image_optimizer_menu' );