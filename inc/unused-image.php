<?php 

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add toolbar to the Media Library page
add_action('admin_footer-upload.php', 'cb_add_toolbar_media_library');

function cb_add_toolbar_media_library() {
    $licence_active = io_is_licence_active();
    $disable_unused_images = get_option('disable_unused_images', '1');
    
    if (!$licence_active || $disable_unused_images === '0') {
        return;
    }
    ?>
    <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                
                var customToolbar = $('<div class="custom-toolbar"></div>');
                
                var title = $('<span class="custom-toolbar-title">Unused Images Finder</span>');
                customToolbar.append(title);
                
                var markUnusedImagesButton = $('<button class="button mark-button">Mark Unused Images</button>');
                markUnusedImagesButton.on('click', function() {
                    const button = $(this);
                    button.prop("disabled", true).text("Searching...");

                    $.ajax({
                        url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
                        type: "POST",
                        dataType: "json",
                        data: {
                            action: "cb_mark_unused_images",
                            security: "<?php echo esc_js(wp_create_nonce('cb_mark_unused_images_action')); ?>"
                        },
                        success: function (response) {
                            if (response.success) {
                                alert(response.data.message + "\nUsed Images: " + response.data.used_count + "\nUnused Images: " + response.data.unused_count);
                                location.reload();
                            } else {
                                alert("Error: " + response.data.message);
                            }
                            button.prop("disabled", false).text("Mark Unused Images");
                        },
                        error: function () {
                            alert("An error occurred while processing. Please check the console for details.");
                            button.prop("disabled", false).text("Mark Unused Images");
                        },
                    });
                });

                var removeMarkButton = $('<button class="button unmark-button">Remove Mark</button>');
                removeMarkButton.on('click', function() {
                    const button = $(this);
                    button.prop("disabled", true).text("Searching...");

                    $.ajax({
                        url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
                        type: "POST",
                        dataType: "json",
                        data: {
                            action: "cb_remove_delete_prefix",
                            security: "<?php echo esc_js(wp_create_nonce('cb_remove_delete_prefix_action')); ?>"
                        },
                        success: function (response) {
                            if (response.success) {
                                alert(response.data.message + "\nUpdated Images: " + response.data.updated_count);
                                location.reload();
                            } else {
                                alert("Error: " + response.data.message);
                            }
                            button.prop("disabled", false).text("Remove Mark");
                        },
                        error: function () {
                            alert("An error occurred while processing. Please check the console for details.");
                            button.prop("disabled", false).text("Remove Mark");
                        },
                    });
                });

                customToolbar.append(markUnusedImagesButton);
                customToolbar.append(removeMarkButton);
                
                $('.tablenav.top').after(customToolbar);
            });
        })(jQuery);
    </script>

    <style type="text/css">
        .custom-toolbar {
		width: 99%;
		margin-bottom: 20px;
        margin-top: 30px;
        padding: 10px;
        background: white;
        border: 1px solid #7F54B2;
        display: flex;
        align-items: center;
		border-radius: 5px;
    }
    .custom-toolbar-title {
        font-weight: 700;
        margin-right: 10px;
    }
    .mark-button {
        margin-right: 10px !important;
        background-color: #7F54B2 !important;
        border-color: #7F54B2 !important;
        color: #FFFFFF !important;
    }
    .mark-button:hover {
        background-color: #A98ED6 !important;
        border-color: #A98ED6 !important;
    }
    .unmark-button {
        border-color: #7F54B2 !important;
        color: #7F54B2 !important;
    }
    .unmark-button:hover {
        border-color: #7F54B2 !important;
        color: #7F54B2 !important;
    }
    </style>
    <?php
}

// Functionality to add buttons for image management
add_action('restrict_manage_posts', 'cb_add_image_management_buttons');

function cb_add_image_management_buttons() {
    if (!current_user_can('manage_options')) {
        return;
    }

    ?>
    <script>
        jQuery(document).ready(function ($) {
            $("#cb-mark-unused-images-button").on("click", function () {
                const button = $(this);
                button.prop("disabled", true).text("Searching...");

                $.ajax({
                    url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "cb_mark_unused_images",
                        security: "<?php echo esc_js(wp_create_nonce('cb_mark_unused_images_action')); ?>"
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.data.message + "\nUsed Images: " + response.data.used_count + "\nUnused Images: " + response.data.unused_count);
                            location.reload();
                        } else {
                            alert("Error: " + response.data.message);
                        }
                        button.prop("disabled", false).text("Mark Unused Images");
                    },
                    error: function () {
                        alert("An error occurred while processing. Please check the console for details.");
                        button.prop("disabled", false).text("Mark Unused Images");
                    },
                });
            });

            $("#cb-remove-delete-prefix-button").on("click", function () {
                const button = $(this);
                button.prop("disabled", true).text("Searching...");

                $.ajax({
                    url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "cb_remove_delete_prefix",
                        security: "<?php echo esc_js(wp_create_nonce('cb_remove_delete_prefix_action')); ?>"
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.data.message + "\nUpdated Images: " + response.data.updated_count);
                            location.reload();
                        } else {
                            alert("Error: " + response.data.message);
                        }
                        button.prop("disabled", false).text("Remove Mark");
                    },
                    error: function () {
                        alert("An error occurred while processing. Please check the console for details.");
                        button.prop("disabled", false).text("Remove Mark");
                    },
                });
            });
        });
    </script>
    <?php
}

// Hook to add buttons in the Media Library interface
add_action('restrict_manage_posts', 'cb_add_image_management_buttons');

function cb_show_button_in_media_library($post_type) {
	
    if ($post_type !== 'attachment') {
        remove_action('restrict_manage_posts', 'cb_add_image_management_buttons');
    }
}

// Add buttons only when the Media Library is loaded
add_action('load-upload.php', function () {
    cb_show_button_in_media_library(get_current_screen()->post_type);
});

// AJAX handler to mark unused images
function cb_mark_unused_images_ajax_handler() {

    check_ajax_referer('cb_mark_unused_images_action', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have sufficient permissions.']);
    }

    global $wpdb;

    $used_images = [];
    $unused_count = 0;
    $used_count = 0;

    $site_logo_id = get_theme_mod('custom_logo');
    $site_icon_id = get_option('site_icon');
    $ids_to_include = array_filter([$site_logo_id, $site_icon_id]);

    foreach ($ids_to_include as $id) {
        $url = untrailingslashit(esc_url_raw(wp_get_attachment_url($id)));
        if ($url) {
            $used_images[] = $url;
        }
    }

    if (class_exists('WooCommerce')) {

        $products = wc_get_products(['limit' => -1]);
        foreach ($products as $product) {

            $used_images[] = untrailingslashit(esc_url_raw(wp_get_attachment_url(get_post_thumbnail_id($product->get_id()))));
            
            $gallery_image_ids = $product->get_gallery_image_ids();
            foreach ($gallery_image_ids as $id) {
                $used_images[] = untrailingslashit(esc_url_raw(wp_get_attachment_url($id)));
            }
        }
    }

    $posts = $wpdb->get_results("SELECT ID, post_content FROM $wpdb->posts WHERE post_status IN ('publish', 'draft', 'private')", ARRAY_A);
    foreach ($posts as $post) {

        preg_match_all('/https?:\/\/[^\s"]+\.(jpg|jpeg|png|gif|webp|svg)/i', $post['post_content'], $matches);
        if (!empty($matches[0])) {
            $used_images = array_merge($used_images, $matches[0]);
        }

        $thumbnail_id = get_post_thumbnail_id($post['ID']);
        if ($thumbnail_id) {
            $used_images[] = untrailingslashit(esc_url_raw(wp_get_attachment_url($thumbnail_id)));
        }

        $meta_fields = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d", $post['ID']), ARRAY_A);
        foreach ($meta_fields as $meta) {
            $meta_data = maybe_unserialize($meta['meta_value']);
            if (is_array($meta_data)) {
                $used_images = array_merge($used_images, cb_extract_image_urls_from_elementor_data($meta_data));
            }
        }
    }

    $taxonomies = ['category', 'product_cat'];
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
        foreach ($terms as $term) {
            $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
            if ($thumbnail_id) {
                $used_images[] = untrailingslashit(esc_url_raw(wp_get_attachment_url($thumbnail_id)));
            }
        }
    }

    $media_query = new WP_Query([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'post_mime_type' => ['image', 'image/svg+xml'],
        'posts_per_page' => -1,
    ]);

    if ($media_query->have_posts()) {
        while ($media_query->have_posts()) {
            $media_query->the_post();
            $media_id = get_the_ID();
            $media_url = untrailingslashit(esc_url_raw(wp_get_attachment_url($media_id)));
            $current_title = get_the_title($media_id);

            if (!in_array($media_url, $used_images)) {

                if (strpos($current_title, 'Unused_') !== 0) {
                    wp_update_post(['ID' => $media_id, 'post_title' => "Unused_" . $current_title]);
                    $unused_count++;
                }
            } else {
                $used_count++;
            }
        }
    }

    wp_reset_postdata();

    wp_send_json_success([
        'message'     => 'Image analysis complete.',
        'used_count'  => $used_count,
        'unused_count' => $unused_count,
    ]);
}
add_action('wp_ajax_cb_mark_unused_images', 'cb_mark_unused_images_ajax_handler');

// AJAX handler to remove "Unused_" prefix from image titles
function cb_remove_delete_prefix_ajax_handler() {
	
    check_ajax_referer('cb_remove_delete_prefix_action', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have sufficient permissions.']);
    }

    global $wpdb;

    $updated_count = 0;

    $media_query = new WP_Query([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'post_mime_type' => ['image', 'image/svg+xml'],
        'posts_per_page' => -1,
    ]);

    if ($media_query->have_posts()) {
        while ($media_query->have_posts()) {
            $media_query->the_post();
            $media_id = get_the_ID();
            $current_title = get_the_title($media_id);

            if (strpos($current_title, 'Unused_') === 0) {
                $new_title = substr($current_title, 7);
                wp_update_post(['ID' => $media_id, 'post_title' => $new_title]);
                $updated_count++;
            }
        }
    }

    wp_reset_postdata();

    wp_send_json_success([
        'message'       => 'Unused_ prefix removal complete.',
        'updated_count' => $updated_count,
    ]);
}
add_action('wp_ajax_cb_remove_delete_prefix', 'cb_remove_delete_prefix_ajax_handler');

// Helper function to extract image URLs from Elementor data
function cb_extract_image_urls_from_elementor_data($data) {
    $urls = [];
    foreach ($data as $element) {
        if (isset($element['settings'])) {
            foreach ($element['settings'] as $key => $value) {
                if (is_string($value) && preg_match('/https?:\/\/[^\s"]+\.(jpg|jpeg|png|gif|webp|svg)/i', $value)) {
                    $urls[] = $value;
                }
                if (is_array($value) && isset($value['url'])) {
                    $urls[] = $value['url'];
                }
            }
        }
        if (isset($element['elements']) && is_array($element['elements'])) {
            $urls = array_merge($urls, cb_extract_image_urls_from_elementor_data($element['elements']));
        }
    }
    return $urls;
}