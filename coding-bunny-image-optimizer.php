<?php

/**
 * Plugin Name: CodingBunny Image Optimizer
 * Plugin URI:  https://coding-bunny.com/image-optimizer/
 * Description: Speed up your site! Compress and optimize images automatically.
 * Version:     1.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      CodingBunny
 * Author URI:  https://coding-bunny.com
 * Text Domain: coding-bunny-image-optimizer
 * Domain Path: /languages
 * License: GNU General Public License v3.0 or later
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define the path to the 'inc' directory which contains additional files to include
$inc_dir = plugin_dir_path( __FILE__ ) . 'inc/';

// List of files required for the plugin to function
$files_to_include = [
    'admin-menu.php',        // Handles the admin menu for the plugin
    'licence-validation.php',// Licence page
	'updates-check.php',     // Updates check
	'settings-page.php',     // Defines the settings page for the plugin
	'bulk-edit.php',     	 // Bulk edit function
    'enqueue-scripts.php'    // Enqueues the necessary CSS and JS files
];

// Loop through the array of files and securely include them if they exist
foreach ( $files_to_include as $file ) {
    $file_path = $inc_dir . $file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

// Load plugin text domain for translations
// This function allows the plugin to be translated into other languages
function coding_bunny_image_optimizer_load_textdomain() {
    load_plugin_textdomain( 'coding-bunny-image-optimizer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
// Hook the function into the 'plugins_loaded' action to ensure text domain is loaded after the plugin is fully initialized
add_action( 'plugins_loaded', 'coding_bunny_image_optimizer_load_textdomain' );

// Add "Settings" link in the plugins list page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'coding_bunny_image_optimizer_action_links' );
function coding_bunny_image_optimizer_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=coding-bunny-image-optimizer' ) ) . '">' . __( 'Settings', 'coding-bunny-image-optimizer' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

// Add "Get PRO" link in the plugins list page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'coding_bunny_add_pro_link' );
function coding_bunny_add_pro_link( $links ) {
    // Controlla se la licenza non è attiva
    if ( ! io_is_licence_active() ) {
        // Crea il link per la versione PRO con supporto per le traduzioni
        $pro_link = '<a href="https://coding-bunny.com/image-optimizer/" style="color: #00A32A; font-weight: bold;">' . __( 'Get CodingBunny Image Optimizer PRO!', 'coding-bunny-image-optimizer' ) . '</a>';
        
        // Inserisce il link in cima all'array dei link del plugin
        array_unshift( $links, $pro_link );
    }
    
    // Restituisce l'array dei link modificato
    return $links;
}