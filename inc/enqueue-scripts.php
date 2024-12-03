<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function coding_bunny_image_optimizer_styles() {
	if ( isset( $_GET['page'] ) ) {
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		if ( $page === 'coding-bunny-image-optimizer' || $page === 'coding-bunny-image-optimizer-licence' ) {

			$css_file = plugin_dir_path( __FILE__ ) . '../css/coding-bunny-image-optimizer.css';
			if ( file_exists( $css_file ) ) {
				$version = filemtime( $css_file );

				wp_enqueue_style( 'coding-bunny-admin-styles', plugin_dir_url( __FILE__ ) . '../css/coding-bunny-image-optimizer.css', [], $version );
			}
		}
	}
}
add_action( 'admin_enqueue_scripts', 'coding_bunny_image_optimizer_styles' );