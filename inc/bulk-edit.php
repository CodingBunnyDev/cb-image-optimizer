<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'io_is_licence_active' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'settings-page.php';
}

// Adds the button just below the "Filter" button
add_action('restrict_manage_posts', 'add_webp_button_below_filter');

function add_webp_button_below_filter() {
    // Only for the media library
    if (get_current_screen()->id !== 'upload') {
        return;
    }

    // Check if the license is active
    $is_license_active = io_is_licence_active();

    ?>
    <!-- A separated bar with the buttons, which will appear below the Filter button -->
    <div class="image-optimizer-bulk-actions" style="display: flex; justify-content: flex-start; align-items: center;">
    <button type="button" class="button optimize-button" id="convert_to_webp_delete" <?php echo !$is_license_active ? 'disabled' : ''; ?>><?php echo esc_html__('Optimize Selected Images', 'textdomain'); ?></button>
    <button type="button" class="button optimize-all-button" id="convert_all_to_webp_delete" <?php echo !$is_license_active ? 'disabled' : ''; ?>><?php echo esc_html__('Optimize All Images', 'textdomain'); ?></button>
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
    // Move the button after the Filter button
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

    // Ensure the current user has the necessary capability
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

    // Ensure the current user has the necessary capability
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized user', 'textdomain'));
    }

    if (!isset($_POST['ids']) || !is_array($_POST['ids']) || empty($_POST['ids'])) {
        wp_send_json_error(__('No images selected', 'textdomain'));
    }

    $ids = array_map('intval', $_POST['ids']);
    $action_choice = sanitize_text_field($_POST['action_choice']);
    $convert_format = get_option('convert_format', 'webp'); // Get the conversion format from settings

    // Loop through each selected image
    foreach ($ids as $post_id) {
        optimize_image($post_id, $convert_format, $action_choice);
    }

    wp_send_json_success(__('Operation completed.', 'textdomain'));
}

// AJAX function to convert all images in bulk
add_action('wp_ajax_convert_all_to_webp', 'convert_all_to_webp');
function convert_all_to_webp() {
    check_ajax_referer('convert_webp_nonce', 'nonce');

    // Ensure the current user has the necessary capability
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
    $convert_format = get_option('convert_format', 'webp'); // Get the conversion format from settings
    $action_choice = 'delete'; // Default action choice

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

    // Check if the file exists
    if (!file_exists($image_path)) {
        error_log("File not found: $image_path"); // Debug log
        return; // Skip if the file does not exist
    }

    $info = pathinfo($image_path);
    $extension = isset($info['extension']) ? strtolower($info['extension']) : ''; // Ensure extension is not null

    // Check if the extension is supported
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        return; // Skip unsupported images
    }

    // Determine the output file path and MIME type based on the selected format
    $output_path = $info['dirname'] . '/' . $info['filename'] . '.' . $convert_format;
    $mime_type = 'image/' . $convert_format;

    // Check if the converted version already exists
    if (file_exists($output_path)) {
        return; // Skip if the converted file already exists
    }

    // Try to convert the image
    if (convert_image($image_path, $output_path, $convert_format)) {
        // Update the file path of the image in the media library to point to the converted file
        update_attached_file($post_id, $output_path);

        // Update the metadata to include the converted version
        $metadata = wp_generate_attachment_metadata($post_id, $output_path);

        // Update the MIME type of the attachment to the new format
        $attachment = array(
            'ID' => $post_id,
            'post_mime_type' => $mime_type // Update MIME type
        );
        wp_update_post($attachment); // Update the post with the new MIME type

        // Update the metadata
        wp_update_attachment_metadata($post_id, $metadata);

        // Delete the original image if the user chose to delete it
        if ($action_choice === 'delete') {
            unlink($image_path);

            // Delete files with the same name but different extensions
            foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
                $old_file_path = $info['dirname'] . '/' . $info['filename'] . '.' . $ext;
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }

                // Delete intermediate sizes
                $intermediate_sizes = glob($info['dirname'] . '/' . $info['filename'] . '-*.' . $ext);
                foreach ($intermediate_sizes as $intermediate_file) {
                    unlink($intermediate_file);
                }
            }

            // Update database references from the original extension to the new format
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
    // Retrieve maximum dimensions from settings
    $max_width = get_option('max_width', 1000);
    $max_height = get_option('max_height', 1000);
    $enable_resize = get_option('enable_resize', '1');

    // Retrieve quality settings from options
    $quality_webp = get_option('quality_webp', 80);
    $quality_avif = get_option('quality_avif', 80);

    if (extension_loaded('imagick')) {
        // Use Imagick if available
        $imagick = new Imagick($source);

        // Resize the image if necessary
        if ($enable_resize === '1') {
            $imagick->resizeImage($max_width, $max_height, Imagick::FILTER_LANCZOS, 1, true);
        }

        // Set the quality based on the selected format
        if ($format === 'webp') {
            $imagick->setImageCompressionQuality($quality_webp);
        } elseif ($format === 'avif') {
            $imagick->setImageCompressionQuality($quality_avif);
        }

        // Convert to the desired format
        $imagick->setImageFormat($format);
        return $imagick->writeImage($destination);
    } elseif (function_exists('imagecreatefromjpeg') || function_exists('imagecreatefrompng') || function_exists('imagecreatefromgif')) {
        // Use GD as a fallback
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

        // Resize the image if necessary
        if ($enable_resize === '1') {
            $width = imagesx($image);
            $height = imagesy($image);
            if ($width > $max_width || $height > $max_height) {
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
        }
        imagepalettetotruecolor($image);
        if ($format === 'webp') {
            return imagewebp($image, $destination, $quality_webp); // Use the selected WEBP quality
        } elseif ($format === 'avif' && function_exists('imageavif')) {
            return imageavif($image, $destination, $quality_avif); // Use the selected AVIF quality
        }
    }
    return false;
}

// Disable WordPress' automatic image scaling feature
add_filter( 'big_image_size_threshold', '__return_false' );