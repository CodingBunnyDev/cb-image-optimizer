<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Function to check if a new version of the plugin is available
function coding_bunny_image_optimizer_check_version() {
	$current_version = defined('CODING_BUNNY_IMAGE_OPTIMIZER_VERSION') ? sanitize_text_field(CODING_BUNNY_IMAGE_OPTIMIZER_VERSION) : '1.2.3';
	$url = esc_url_raw('https://www.coding-bunny.com/plugins-updates/io-check-version.php');

	$response = wp_remote_post($url, [
		'body' => [
		'version' => $current_version,
	],
	'timeout' => 15,
	'sslverify' => true,
	]);

	if (is_wp_error($response)) {
		error_log('Error checking for plugin updates: ' . $response->get_error_message());
		return false;
	}

	$body = wp_remote_retrieve_body($response);
	$decoded_body = json_decode($body, true);

	if (is_array($decoded_body) && isset($decoded_body['update_available']) && $decoded_body['update_available']) {
		return [
			'update_available' => true,
			'latest_version'   => sanitize_text_field($decoded_body['latest_version']),
			'download_url'     => esc_url_raw($decoded_body['download_url']),
		];
	}

	return ['update_available' => false];
}

// Function to show an update notice in the WordPress admin dashboard
function coding_bunny_image_optimizer_version_update_notice() {
	$update_check = coding_bunny_image_optimizer_check_version();

	if ($update_check['update_available']) {
		echo '<div class="notice notice-warning is-dismissible">';
		echo '<p>';
		echo sprintf(
		__('A new version (%s) of the <b>CodingBunny Image Optimizer</b> plugin is available. <a href="%s">Download the latest version here.</a>', 'coding-bunny-image-optimizer'),
		esc_html($update_check['latest_version']),
		esc_url($update_check['download_url'])
	);
	echo '</p>';
	echo '</div>';
}
}
add_action('admin_notices', 'coding_bunny_image_optimizer_version_update_notice');