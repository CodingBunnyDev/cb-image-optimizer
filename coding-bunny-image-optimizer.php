<?php

/**
* Plugin Name: CodingBunny Image Optimizer
* Plugin URI:  https://coding-bunny.com/image-optimizer/
* Description: Speed up your site! Compress and optimize images automatically.
* Version:     1.2.3
* Requires at least: 6.0
* Requires PHP: 8.0
* Author:      CodingBunny
* Author URI:  https://coding-bunny.com
* Text Domain: coding-bunny-image-optimizer
* Domain Path: /languages
* License: GNU General Public License v3.0 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$inc_dir = plugin_dir_path( __FILE__ ) . 'inc/';

$files_to_include = [
	'admin-menu.php',        // Handles the admin menu for the plugin
	'licence-validation.php',// Licence page
	'updates-check.php',     // Updates check
	'settings-page.php',     // Defines the settings page for the plugin
	'bulk-edit.php',         // Bulk edit function
	'enqueue-scripts.php'    // Enqueues the necessary CSS and JS files
];

foreach ( $files_to_include as $file ) {
	$file_path = $inc_dir . $file;
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	} else {
		error_log("File not found: $file_path");
	}
}

// Load plugin text domain for translations
function coding_bunny_image_optimizer_load_textdomain() {
	load_plugin_textdomain( 'coding-bunny-image-optimizer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'coding_bunny_image_optimizer_load_textdomain' );

// Add "Settings" link in the plugins list page
function coding_bunny_image_optimizer_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=coding-bunny-image-optimizer' ) ) . '">' . esc_html__( 'Settings', 'coding-bunny-image-optimizer' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'coding_bunny_image_optimizer_action_links' );

// Add "Get PRO" link in the plugins list page
function coding_bunny_add_pro_link( $links ) {
	if ( ! io_is_licence_active() ) {
		$pro_link = '<a href="https://coding-bunny.com/image-optimizer/" style="color: #00A32A; font-weight: bold;">' . esc_html__( 'Get CodingBunny Image Optimizer PRO!', 'coding-bunny-image-optimizer' ) . '</a>';
		array_unshift( $links, $pro_link );
	}
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'coding_bunny_add_pro_link' );

	return $links;
}