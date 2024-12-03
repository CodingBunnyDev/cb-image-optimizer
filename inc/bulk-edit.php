<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'io_is_licence_active' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'settings-page.php';
}

add_action('restrict_manage_posts', 'add_webp_button_below_filter');

function add_webp_button_below_filter() {

	if (get_current_screen()->id !== 'upload') {
		return;
	}

	$is_license_active = io_is_licence_active();

	?>

	<div class="image-optimizer-bulk-actions" style="display: flex; justify-content: flex-start; align-items: center;">
		<button type="button" class="button optimize-button" id="convert_to_webp_delete" <?php echo !$is_license_active ? 'disabled' : ''; ?>><?php echo esc_html__('Optimize Selected Images', 'coding-bunny-image-optimizer'); ?></button>
		<button type="button" class="button optimize-all-button" id="convert_all_to_webp_delete" <?php echo !$is_license_active ? 'disabled' : ''; ?>><?php echo esc_html__('Optimize All Images', 'coding-bunny-image-optimizer'); ?></button>
		<div class="progress-bar-container" style="display: flex; align-items: center;">
			<div class="progress-bar" style="width: 200px; height: 20px; background-color: #e0e0e0; position: relative;">
				<div class="progress-bar-fill" style="width: 0; height: 100%; background-color: #7F54B2;"></div>
			</div>
			<span class="progress-percentage" style="margin-left: 10px;">0%</span>
		</div>
	</div>

	<style>
		.image-optimizer-bulk-actions .button {
			margin-right: 20px;
		}
		.optimize-button {
			border-color: #7F54B2 !important;
			color: #7F54B2 !important;
		}
		.optimize-button:hover {
			border-color: #7F54B2 !important;
			color: #7F54B2 !important;
		}
		.optimize-all-button {
			background-color: #7F54B2 !important;
			border-color: #7F54B2 !important;
			color: #FFFFFF !important;
		}
		.optimize-all-button:hover {
			background-color: #A98ED6 !important;
			border-color: #A98ED6 !important;
			color: #FFFFFF !important;
		}
		</style>

		<script type="text/javascript">
			jQuery(document).ready(function($) {

				$('.bulkactions').after($('.image-optimizer-bulk-actions'));

				function updateProgressBar(progress) {
					var percentage = progress + '%';
					$('.progress-bar-fill').css('width', percentage);
					$('.progress-percentage').text(percentage);
				}

				function optimizeImages(ids, action) {
					var totalImages = ids.length;
					var optimizedImages = 0;

					ids.forEach(function(id) {
						$.ajax({
							url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
							type: 'POST',
							data: {
								action: action,
								nonce: "<?php echo esc_js(wp_create_nonce('convert_webp_nonce')); ?>",
								ids: [id],
								action_choice: 'delete'
							},
							success: function(response) {
								if (response.success) {
									optimizedImages++;
									var progress = Math.round((optimizedImages / totalImages) * 100);
									updateProgressBar(progress);

									if (optimizedImages === totalImages) {
										alert('Optimization completed.');
										location.reload(); // Reload the page to update the media library
									}
								} else {
									alert('Error: ' + response.data);
								}
							}
						});
					});
				}

				$('#convert_to_webp_delete').on('click', function() {
					if ($(this).is(':disabled')) {
						alert('Please activate the license to use this feature.');
						return;
					}

					var selectedIds = [];
					$('input[type="checkbox"]:checked').each(function() {
						selectedIds.push($(this).val());
					});

					if (selectedIds.length === 0) {
						alert('Please select at least one image.');
						return;
					}

					if (confirm('With optimization you are about to permanently delete these images from your site. This action cannot be undone. "Cancel" to stop, "OK" to delete.')) {
						optimizeImages(selectedIds, 'convert_to_webp_multiple');
					}
				});

				$('#convert_all_to_webp_delete').on('click', function() {
					if ($(this).is(':disabled')) {
						alert('Please activate the license to use this feature.');
						return;
					}

					if (confirm('With optimization you are about to permanently delete these images from your site. This action cannot be undone. "Cancel" to stop, "OK" to delete.')) {
						$.ajax({
							url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
							type: 'POST',
							data: {
								action: 'get_all_image_ids',
								nonce: "<?php echo esc_js(wp_create_nonce('convert_webp_nonce')); ?>"
							},
							success: function(response) {
								if (response.success) {
									var allImageIds = response.data;
									optimizeImages(allImageIds, 'convert_all_to_webp');
								} else {
									alert('Error: ' + response.data);
								}
							}
						});
					}
				});
			});
			</script>
			<?php
		}

		// AJAX Functions for 'Optimize All Images'
		add_action('wp_ajax_get_all_image_ids', 'get_all_image_ids');
		function get_all_image_ids() {
			check_ajax_referer('convert_webp_nonce', 'nonce');

			if (!current_user_can('manage_options')) {
				wp_send_json_error(__('Unauthorized user', 'textdomain'));
			}

			$args = array(
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'post_status' => 'inherit',
				'posts_per_page' => -1,
				'fields' => 'ids'
			);

			$query = new WP_Query($args);
			if ($query->have_posts()) {
				$image_ids = $query->posts;
				wp_send_json_success($image_ids);
			} else {
				wp_send_json_error(__('No images found', 'textdomain'));
			}
		}

		// AJAX function to convert selected images in bulk (multiple)
		add_action('wp_ajax_convert_to_webp_multiple', 'convert_to_webp_multiple');
		function convert_to_webp_multiple() {
			check_ajax_referer('convert_webp_nonce', 'nonce');

			if (!current_user_can('manage_options')) {
				wp_send_json_error(__('Unauthorized user', 'textdomain'));
			}

			if (!isset($_POST['ids']) || !is_array($_POST['ids']) || empty($_POST['ids'])) {
				wp_send_json_error(__('No images selected', 'textdomain'));
			}

			$ids = array_map('intval', $_POST['ids']);
			$action_choice = sanitize_text_field($_POST['action_choice']);
			$convert_format = get_option('convert_format', 'webp');

			foreach ($ids as $post_id) {
				optimize_image($post_id, $convert_format, $action_choice);
			}

			wp_send_json_success(__('Operation completed.', 'textdomain'));
		}

		// AJAX function to convert all images in bulk
		add_action('wp_ajax_convert_all_to_webp', 'convert_all_to_webp');
		function convert_all_to_webp() {
			check_ajax_referer('convert_webp_nonce', 'nonce');

			if (!current_user_can('manage_options')) {
				wp_send_json_error(__('Unauthorized user', 'textdomain'));
			}

			$args = array(
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'post_status' => 'inherit',
				'posts_per_page' => -1,
			);

			$query = new WP_Query($args);
			$convert_format = get_option('convert_format', 'webp');
			$action_choice = 'delete';

			if ($query->have_posts()) {
				while ($query->have_posts()) {
					$query->the_post();
					$post_id = get_the_ID();
					optimize_image($post_id, $convert_format, $action_choice);
				}
				wp_reset_postdata();
			}

			wp_send_json_success(__('All images optimized.', 'textdomain'));
		}

		function optimize_image($post_id, $convert_format, $action_choice) {
			$image_path = get_attached_file($post_id);

			if (!file_exists($image_path)) {
				error_log("File not found: $image_path");
				return;
			}

			$info = pathinfo($image_path);
			$extension = isset($info['extension']) ? strtolower($info['extension']) : '';

			if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
				return;
			}

			$output_path = $info['dirname'] . '/' . $info['filename'] . '.' . $convert_format;
			$mime_type = 'image/' . $convert_format;

			if (file_exists($output_path)) {
				return;
			}

			if (convert_image($image_path, $output_path, $convert_format)) {

				update_attached_file($post_id, $output_path);

				$metadata = wp_generate_attachment_metadata($post_id, $output_path);
				$attachment = array(
					'ID' => $post_id,
					'post_mime_type' => $mime_type
				);
				wp_update_post($attachment);
				wp_update_attachment_metadata($post_id, $metadata);

				if ($action_choice === 'delete') {
					unlink($image_path);

					foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
						$old_file_path = $info['dirname'] . '/' . $info['filename'] . '.' . $ext;
						if (file_exists($old_file_path)) {
							unlink($old_file_path);
						}

						$intermediate_sizes = glob($info['dirname'] . '/' . $info['filename'] . '-*.' . $ext);
						foreach ($intermediate_sizes as $intermediate_file) {
							unlink($intermediate_file);
						}
					}

					global $wpdb;
					$wpdb->query(
					$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
					$info['basename'],
					$info['filename'] . '.' . $convert_format,
					'%' . $info['basename'] . '%'
					)
				);
			}
		}
	}

	function convert_image($source, $destination, $format) {

		$max_width = get_option('max_width', 1000);
		$max_height = get_option('max_height', 1000);
		$enable_resize = get_option('enable_resize', '1');

		$quality_webp = get_option('quality_webp', 80);
		$quality_avif = get_option('quality_avif', 80);

		if (extension_loaded('imagick')) {
			$imagick = new Imagick($source);

			$width = $imagick->getImageWidth();
			$height = $imagick->getImageHeight();

			if ($enable_resize === '1' && ($width > $max_width || $height > $max_height)) {
				$imagick->resizeImage($max_width, $max_height, Imagick::FILTER_LANCZOS, 1, true);
			}

			if ($format === 'webp') {
				$imagick->setImageCompressionQuality($quality_webp);
			} elseif ($format === 'avif') {
				$imagick->setImageCompressionQuality($quality_avif);
			}

			$imagick->setImageFormat($format);
			return $imagick->writeImage($destination);
		} elseif (function_exists('imagecreatefromjpeg') || function_exists('imagecreatefrompng') || function_exists('imagecreatefromgif')) {

			$image = null;
			$info = getimagesize($source);
			switch ($info[2]) {
				case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg($source);
				break;
				case IMAGETYPE_PNG:
				$image = imagecreatefrompng($source);
				break;
				case IMAGETYPE_GIF:
				$image = imagecreatefromgif($source);
				break;
			}
			if (!$image) {
				return false;
			}

			$width = imagesx($image);
			$height = imagesy($image);

			if ($enable_resize === '1' && ($width > $max_width || $height > $max_height)) {
				$aspect_ratio = $width / $height;
				if ($max_width / $max_height > $aspect_ratio) {
					$max_width = $max_height * $aspect_ratio;
				} else {
					$max_height = $max_width / $aspect_ratio;
				}
				$resized_image = imagecreatetruecolor($max_width, $max_height);
				imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $max_width, $max_height, $width, $height);
				$image = $resized_image;
			}
			imagepalettetotruecolor($image);
			if ($format === 'webp') {
				return imagewebp($image, $destination, $quality_webp);
			} elseif ($format === 'avif' && function_exists('imageavif')) {
				return imageavif($image, $destination, $quality_avif);
			}
		}
		return false;
	}
	add_filter( 'big_image_size_threshold', '__return_false' );