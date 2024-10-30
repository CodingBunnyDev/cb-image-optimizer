<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Function to enqueue admin styles for the Bulk Edit settings page
function coding_bunny_image_optimizer_styles() {
    // Check if we are on the correct admin page
    if ( isset( $_GET['page'] ) && ( $_GET['page'] === 'coding-bunny-image-optimizer' || $_GET['page'] === 'coding-bunny-image-optimizer-licence' ) ) {
        // Get the version of the CSS file based on its last modified time
        $version = filemtime( plugin_dir_path( __FILE__ ) . '../css/coding-bunny-image-optimizer.css' );

        // Enqueue the CSS file for admin styles
        wp_enqueue_style( 'coding-bunny-admin-styles', plugin_dir_url( __FILE__ ) . '../css/coding-bunny-image-optimizer.css', [], $version );
    }
}

// Hook the coding_bunny_bulk_admin_styles function into the admin_enqueue_scripts action
add_action( 'admin_enqueue_scripts', 'coding_bunny_image_optimizer_styles' );