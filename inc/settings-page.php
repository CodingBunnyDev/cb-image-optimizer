<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Function to check if the licence is valid
function io_is_licence_active() {
    $licence_data = get_option( 'coding_bunny_image_optimizer_licence_data', ['key' => '', 'email' => ''] );
    $licence_key = $licence_data['key'];
    $licence_email = $licence_data['email'];

    if ( empty( $licence_key ) || empty( $licence_email ) ) {
        return false;
    }

    $response = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );
    return $response['success'];
}

/**
 * Retrieve actual dimensions of intermediate image sizes.
 */
function coding_bunny_get_intermediate_image_sizes_with_dimensions() {
    $sizes = [];
    $image_sizes = get_intermediate_image_sizes();
    
    foreach ( $image_sizes as $size ) {
        $data = wp_get_additional_image_sizes();
        if ( isset( $data[ $size ] ) ) {
            $sizes[ $size ] = $data[ $size ];
        } else {
            // For default sizes (thumbnail, medium, large, etc.)
            $sizes[ $size ] = [
                'width'  => get_option( $size . '_size_w' ),
                'height' => get_option( $size . '_size_h' ),
            ];
        }
    }

    return $sizes;
}

/**
 * Callback function to display content of settings page
 */
function coding_bunny_image_optimizer_settings_page() {
    // Verify user permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
	
	$licence_active = io_is_licence_active();

    // Update settings if form is submitted
    if ( isset( $_POST['submit'] ) ) {
        check_admin_referer( 'coding_bunny_image_settings_update' );

        // Validate and sanitize input
        $max_width = isset( $_POST['max_width'] ) ? absint( $_POST['max_width'] ) : 1000;
        $max_height = isset( $_POST['max_height'] ) ? absint( $_POST['max_height'] ) : 1000;
        $convert_format = isset( $_POST['convert_format'] ) ? sanitize_text_field( wp_unslash( $_POST['convert_format'] ) ) : 'webp';
        $enable_resize = isset( $_POST['enable_resize'] ) ? '1' : '0';
        $enable_conversion = isset( $_POST['enable_conversion'] ) ? '1' : '0';
		$quality_webp = isset( $_POST['quality_webp'] ) ? absint( $_POST['quality_webp'] ) : 80;
		$quality_avif = isset( $_POST['quality_avif'] ) ? absint( $_POST['quality_avif'] ) : 80;

        // Save selected intermediate image sizes
        $enabled_image_sizes = isset( $_POST['enabled_image_sizes'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['enabled_image_sizes'] ) ) : [];

        update_option( 'max_width', $max_width );
        update_option( 'max_height', $max_height );
        update_option( 'convert_format', $convert_format );
        update_option( 'enable_resize', $enable_resize );
        update_option( 'enable_conversion', $enable_conversion );
        update_option( 'enabled_image_sizes', $enabled_image_sizes );
		update_option( 'quality_webp', $quality_webp );
		update_option( 'quality_avif', $quality_avif );
    }

    // Retrieve current values from options
    $max_width = get_option( 'max_width', 1000 );
    $max_height = get_option( 'max_height', 1000 );
    $convert_format = get_option( 'convert_format', 'webp' );
    $enable_resize = get_option( 'enable_resize', '1' );
    $enable_conversion = get_option( 'enable_conversion', '1' );
    $enabled_image_sizes = get_option( 'enabled_image_sizes', array() );
    $intermediate_sizes = coding_bunny_get_intermediate_image_sizes_with_dimensions();

    // Determine the image processing libraries in use
    $using_gd = extension_loaded( 'gd' );
    $using_imagick = extension_loaded( 'imagick' );

    if ( $using_gd && $using_imagick ) {
        // translators: %s: libraries used
        $message = sprintf( __( 'Your site is using the libraries %s', 'coding-bunny-image-optimizer' ), esc_html( 'GD e Imagick' ) );
        $icon = '<span style="color: green;">●</span>';
    } elseif ( $using_imagick ) {
        // translators: %s: library used
        $message = sprintf( __( 'Your site is using the libraries %s', 'coding-bunny-image-optimizer' ), esc_html( 'Imagick' ) );
        $icon = '<span style="color: green;">●</span>';
    } elseif ( $using_gd ) {
        // translators: %s: library used
        $message = sprintf( __( 'Your site is using the libraries %s', 'coding-bunny-image-optimizer' ), esc_html( 'GD' ) );
        $icon = '<span style="color: green;">●</span>';
    } else {
        $message = __( 'Your site does not use the libraries required for the correct functioning of the plugin.', 'coding-bunny-image-optimizer' );
        $icon = '<span style="color: red;">●</span>';
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'CodingBunny Image Optimizer', 'coding-bunny-image-optimizer' ); ?> 
           <span style="font-size: 10px;">v<?php echo CODING_BUNNY_IMAGE_OPTIMIZER_VERSION; ?></span></h1>
        <p><?php echo wp_kses_post( $icon ) . ' ' . esc_html( $message ); ?></p>
        <form method="post" action="">
            <?php wp_nonce_field( 'coding_bunny_image_settings_update' ); ?>			
            <h3><b><?php esc_html_e( 'Convert JPEG, PNG and GIF images to WEBP or AVIF format.', 'coding-bunny-image-optimizer' ); ?></b></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="enable_conversion"><?php esc_html_e( 'Enable conversion', 'coding-bunny-image-optimizer' ); ?></label></th>
                    <td>
                        <label class="toggle-label">
                            <input type="checkbox" class="toggle" id="enable_conversion" name="enable_conversion" value="1" <?php checked( $enable_conversion, '1' ); ?> />
                            <span class="slider"></span>
                            <?php esc_html_e( 'Convert images to the selected format.', 'coding-bunny-image-optimizer' ); ?>
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="convert_format"><?php esc_html_e( 'Conversion format', 'coding-bunny-image-optimizer' ); ?></label></th>
                    <td>
                        <label>
                            <input type="radio" id="convert_format_webp" name="convert_format" value="webp" <?php checked( $convert_format, 'webp' ); ?> />
                            <?php esc_html_e( 'WebP', 'coding-bunny-image-optimizer' ); ?>
                        </label>
                        <label class="check-label <?php echo ( ! $licence_active ) ? 'disabled-label' : ''; ?>">
                            <input type="radio" id="convert_format_avif" name="convert_format" value="avif" <?php checked( $convert_format, 'avif' ); ?> <?php echo ( ! $licence_active ) ? 'disabled' : ''; ?> />
                            <?php esc_html_e( 'AVIF', 'coding-bunny-image-optimizer' ); ?>
                        </label>
                    </td>
                </tr>
    <tr valign="top">
        <th scope="row"><label for="quality_webp"><?php esc_html_e( 'WEBP Quality', 'coding-bunny-image-optimizer' ); ?></label></th>
        <td>
            <input type="range" id="quality_webp" name="quality_webp" min="0" max="100" value="<?php echo esc_attr( get_option( 'quality_webp', 80 ) ); ?>" oninput="this.nextElementSibling.value = this.value">
            <output><?php echo esc_attr( get_option( 'quality_webp', 80 ) ); ?></output>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><label for="quality_avif"><?php esc_html_e( 'AVIF Quality', 'coding-bunny-image-optimizer' ); ?></label></th>
        <td>
            <input type="range" id="quality_avif" name="quality_avif" min="0" max="100" value="<?php echo esc_attr( get_option( 'quality_avif', 80 ) ); ?>" oninput="this.nextElementSibling.value = this.value">
            <output><?php echo esc_attr( get_option( 'quality_avif', 80 ) ); ?></output>
        </td>
    </tr>
</table>
            <hr>
            <h3><b><?php esc_html_e( 'Sets the maximum size of width and height of uploaded images.', 'coding-bunny-image-optimizer' ); ?></b></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="enable_resize"><?php esc_html_e( 'Enable resizing', 'coding-bunny-image-optimizer' ); ?></label></th>
                    <td>
                        <label class="toggle-label">
                            <input type="checkbox" class="toggle" id="enable_resize" name="enable_resize" value="1" <?php checked( $enable_resize, '1' ); ?> />
                            <span class="slider"></span>
                            <?php esc_html_e( 'Resizes images to the size below.', 'coding-bunny-image-optimizer' ); ?>
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="max_width"><?php esc_html_e( 'Max. width (px)', 'coding-bunny-image-optimizer' ); ?></label></th>
                    <td><input type="number" id="max_width" name="max_width" value="<?php echo esc_attr( $max_width ); ?>" min="0" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="max_height"><?php esc_html_e( 'Max. height (px)', 'coding-bunny-image-optimizer' ); ?></label></th>
                    <td><input type="number" id="max_height" name="max_height" value="<?php echo esc_attr( $max_height ); ?>" min="0" /></td>
                </tr>
            </table>
            <hr>
            <h3><b><?php esc_html_e( 'Select intermediate dimensions to be created.', 'coding-bunny-image-optimizer' ); ?></b></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Intermediate sizes', 'coding-bunny-image-optimizer' ); ?></th>
                    <td>
                        <?php
// Retrieve available intermediate image sizes with dimensions
foreach ( $intermediate_sizes as $size => $data ) {
    $width = isset( $data['width'] ) ? $data['width'] : '';
    $height = isset( $data['height'] ) ? $data['height'] : '';
    
    // Set the checkbox to be checked by default if the licence is inactive
    $checked = ( ! $licence_active ) ? 'checked' : checked( in_array( $size, $enabled_image_sizes, true ), true, false );
    ?>
    <label class="toggle-label <?php echo ( ! $licence_active ) ? 'disabled-label' : ''; ?>">
        <input type="checkbox" name="enabled_image_sizes[]" value="<?php echo esc_attr( $size ); ?>" <?php echo $checked; ?> class="toggle" <?php echo ( ! $licence_active ) ? 'disabled' : ''; ?> />
        <span class="slider"></span>
        <span class="toggle-text"><?php echo esc_html( $size ); ?> (<?php echo esc_html( $width . ' x ' . $height . ' px' ); ?>)</span>
    </label>
    <br>
    <?php
}
?>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
            <hr>
            <p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> - <?php esc_html_e( 'Powered by CodingBunny', 'coding-bunny-image-optimizer' ); ?></p>
        </form>
    </div>
    <?php
}

/**
 * Resize images on upload
 */
add_filter( 'wp_handle_upload', 'coding_bunny_image_optimizer_resize_image' );
function coding_bunny_image_optimizer_resize_image( $file ) {
    // Check if resizing is enabled
    $enable_resize = get_option( 'enable_resize', '1' );
    if ( $enable_resize !== '1' ) {
        return $file;
    }

    // Get maximum dimensions from settings
    $max_width = get_option( 'max_width', 1000 );
    $max_height = get_option( 'max_height', 1000 );

    // Check if the file is an image
    $mime_type = mime_content_type( $file['file'] );
    if ( strpos( $mime_type, 'image' ) === false ) {
        return $file;
    }

    // Get image dimensions
    $image_size = getimagesize( $file['file'] );
    if ( ! $image_size ) {
        /* translators: %s: file URL */
        error_log( sprintf( __( 'Error: Unable to get image dimensions for file %s', 'coding-bunny-image-optimizer' ), esc_url( $file['file'] ) ) );
        return $file;
    }

    // Check if the image is smaller than the specified dimensions
    if ( $image_size[0] <= $max_width && $image_size[1] <= $max_height ) {
        return $file;
    }

    // Resize image while maintaining aspect ratio
    $image_editor = wp_get_image_editor( $file['file'] );
    if ( is_wp_error( $image_editor ) ) {
        /* translators: %s: file URL */
        error_log( sprintf( __( 'Error: Unable to create image editor for file %s', 'coding-bunny-image-optimizer' ), esc_url( $file['file'] ) ) );
        return $file;
    }
    $result = $image_editor->resize( $max_width, $max_height, false );
    if ( is_wp_error( $result ) ) {
        /* translators: %s: file URL */
        error_log( sprintf( __( 'Error: Unable to get image dimensions for file %s', 'coding-bunny-image-optimizer' ), esc_url( $file['file'] ) ) );
        return $file;
    }
    $saved = $image_editor->save( $file['file'] );
    if ( is_wp_error( $saved ) ) {
        /* translators: %s: file URL */
        error_log( sprintf( __( 'Error: Unable to save resized image for file %s', 'coding-bunny-image-optimizer' ), esc_url( $file['file'] ) ) );
    }

    return $file;
}

/**
 * Convert images to WEBP or AVIF format on upload
 */
add_filter( 'wp_handle_upload', 'coding_bunny_image_optimizer_convert_image' );
function coding_bunny_image_optimizer_convert_image( $upload ) {
    // Check if conversion is enabled
    $enable_conversion = get_option( 'enable_conversion', '1' );
    if ( $enable_conversion !== '1' ) {
        return $upload;
    }

    // Check the selected conversion format
    $convert_format = get_option( 'convert_format', 'webp' );

    if ( $convert_format && isset( $upload['type'] ) && in_array( $upload['type'], array( 'image/jpeg', 'image/png', 'image/gif' ) ) ) {
        $file_path = $upload['file'];

        // Check if ImageMagick or GD is available
        if ( extension_loaded( 'imagick' ) || extension_loaded( 'gd' ) ) {
            $image_editor = wp_get_image_editor( $file_path );
            if ( ! is_wp_error( $image_editor ) ) {
                $file_info = pathinfo( $file_path );
                $dirname   = $file_info['dirname'];
                $filename  = $file_info['filename'];

                // Create a new path for the converted image
                $new_file_path = $dirname . '/' . $filename . '.' . $convert_format;

                // Attempt to save the image in the selected format
                $saved_image = $image_editor->save( $new_file_path, 'image/' . $convert_format );
                if ( ! is_wp_error( $saved_image ) && file_exists( $saved_image['path'] ) ) {
                    // Success: replace the uploaded image with the converted image
                    $upload['file'] = $saved_image['path'];
                    $upload['url']  = str_replace( basename( $upload['url'] ), basename( $saved_image['path'] ), $upload['url'] );
                    $upload['type'] = 'image/' . $convert_format;

                    // Optionally remove the original image
                    wp_delete_file( $file_path );
                }
            }
        }
    }

    return $upload;
}

// Disable intermediate image sizes

add_filter( 'intermediate_image_sizes_advanced', 'coding_bunny_image_optimizer_disable_intermediate_image_sizes' );
function coding_bunny_image_optimizer_disable_intermediate_image_sizes( $sizes ) {
    
	// Check licence status
    $licence_active = io_is_licence_active();
    
    // If the licence is not active, generate all intermediate images
    if ( ! $licence_active ) {
        return $sizes; // Restituisce tutte le dimensioni intermedie
    }
    
    // If the licence is active, respect user settings
    $enabled_image_sizes = get_option( 'enabled_image_sizes', array() );
    if ( empty( $enabled_image_sizes ) ) {
        return array(); // Non creare miniature se nessuna è selezionata
    }
    
    // Only keep dimensions enabled
    foreach ( $sizes as $size => $data ) {
        if ( ! in_array( $size, $enabled_image_sizes ) ) {
            unset( $sizes[ $size ] );
        }
    }

    return $sizes;
}