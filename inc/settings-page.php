<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Function to check if the licence is valid
function io_is_licence_active() {
	$licence_data = get_option( 'coding_bunny_image_optimizer_licence_data', ['key' => '', 'email' => ''] );
	$licence_key = sanitize_text_field( $licence_data['key'] );
	$licence_email = sanitize_email( $licence_data['email'] );

	if ( empty( $licence_key ) || empty( $licence_email ) ) {
		return false;
	}

	$response = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );
	return isset( $response['success'] ) ? $response['success'] : false;
}

// Retrieve actual dimensions of intermediate image sizes.
function coding_bunny_get_intermediate_image_sizes_with_dimensions() {
	$sizes = [];
	$image_sizes = get_intermediate_image_sizes();

	foreach ( $image_sizes as $size ) {
		$data = wp_get_additional_image_sizes();
		if ( isset( $data[ $size ] ) ) {
			$sizes[ $size ] = $data[ $size ];
		} else {
			$sizes[ $size ] = [
				'width' => get_option( $size . '_size_w' ),
				'height' => get_option( $size . '_size_h' ),
			];
		}
	}

	return $sizes;
}

// Function to toggle unused images
function toggle_unused_images() {
	if ( isset( $_POST['toggle_unused_images'] ) ) {
		$disable_unused_images = sanitize_text_field( $_POST['toggle_unused_images'] ) === '1' ? '1' : '0';
		update_option( 'disable_unused_images', $disable_unused_images );
	}

	return get_option( 'disable_unused_images', '0' );
}
add_action( 'admin_post_toggle_unused_images', 'toggle_unused_images' );

// Add toggle switch to settings page
function coding_bunny_image_optimizer_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$licence_active = io_is_licence_active();
	$disable_unused_images = get_option('disable_unused_images', '0');

	if ( isset( $_POST['submit'] ) ) {
		check_admin_referer( 'coding_bunny_image_settings_update' );

		$max_width = isset( $_POST['max_width'] ) ? absint( $_POST['max_width'] ) : 1000;
		$max_height = isset( $_POST['max_height'] ) ? absint( $_POST['max_height'] ) : 1000;
		$convert_format = isset( $_POST['convert_format'] ) ? sanitize_text_field( wp_unslash( $_POST['convert_format'] ) ) : 'webp';
		$enable_resize = isset( $_POST['enable_resize'] ) ? '1' : '0';
		$enable_conversion = isset( $_POST['enable_conversion'] ) ? '1' : '0';
		$quality_webp = isset( $_POST['quality_webp'] ) ? absint( $_POST['quality_webp'] ) : 80;
		$quality_avif = isset( $_POST['quality_avif'] ) ? absint( $_POST['quality_avif'] ) : 80;
		$enabled_image_sizes = isset( $_POST['enabled_image_sizes'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['enabled_image_sizes'] ) ) : [];
		$disable_unused_images = isset( $_POST['toggle_unused_images'] ) ? '1' : '0';

		update_option( 'max_width', $max_width );
		update_option( 'max_height', $max_height );
		update_option( 'convert_format', $convert_format );
		update_option( 'enable_resize', $enable_resize );
		update_option( 'enable_conversion', $enable_conversion );
		update_option( 'enabled_image_sizes', $enabled_image_sizes );
		update_option( 'quality_webp', $quality_webp );
		update_option( 'quality_avif', $quality_avif );
		update_option( 'disable_unused_images', $disable_unused_images );
	}

	$max_width = get_option( 'max_width', 1000 );
	$max_height = get_option( 'max_height', 1000 );
	$convert_format = get_option( 'convert_format', 'webp' );
	$enable_resize = get_option( 'enable_resize', '1' );
	$enable_conversion = get_option( 'enable_conversion', '1' );
	$enabled_image_sizes = get_option( 'enabled_image_sizes', array() );
	$intermediate_sizes = coding_bunny_get_intermediate_image_sizes_with_dimensions();

	$using_gd = extension_loaded( 'gd' );
	$using_imagick = extension_loaded( 'imagick' );

	if ( $using_gd && $using_imagick ) {
		$message = sprintf( __( 'Your site is using the libraries %s', 'coding-bunny-image-optimizer' ), esc_html( 'GD e Imagick' ) );
		$icon = '<span style="color: green;">●</span>';
	} elseif ( $using_imagick ) {
		$message = sprintf( __( 'Your site is using the libraries %s', 'coding-bunny-image-optimizer' ), esc_html( 'Imagick' ) );
		$icon = '<span style="color: green;">●</span>';
	} elseif ( $using_gd ) {
		$message = sprintf( __( 'Your site is using the libraries %s', 'coding-bunny-image-optimizer' ), esc_html( 'GD' ) );
		$icon = '<span style="color: green;">●</span>';
	} else {
		$message = __( 'Your site does not use the libraries required for the correct functioning of the plugin.', 'coding-bunny-image-optimizer' );
		$icon = '<span style="color: red;">●</span>';
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'CodingBunny Image Optimizer', 'coding-bunny-image-optimizer' ); ?> 
			<span style="font-size: 10px;">v<?php echo esc_html( CODING_BUNNY_IMAGE_OPTIMIZER_VERSION ); ?></span></h1>
			<p><?php echo wp_kses_post( $icon ) . ' ' . esc_html( $message ); ?></p>
			<form method="post" action="">
				<?php wp_nonce_field( 'coding_bunny_image_settings_update' ); ?>			
				<h3>
					<span class="dashicons dashicons-share-alt2"></span>
					<?php esc_html_e( 'Convert', 'coding-bunny-image-optimizer' ); ?>
				</h3>
				<p>
					<?php esc_html_e( 'Convert JPEG, PNG and GIF images to WEBP or AVIF format.', 'coding-bunny-image-optimizer' ); ?>
				</p>
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
							<input type="range" id="quality_webp" name="quality_webp" min="0" max="100" 
							value="<?php echo esc_attr( get_option( 'quality_webp', 80 ) ); ?>" 
							<?php echo ( ! $licence_active ) ? 'disabled' : ''; ?> 
							oninput="this.nextElementSibling.value = this.value">
							<output class="check-label <?php echo ( ! $licence_active ) ? 'disabled-label' : ''; ?>"><?php echo esc_attr( get_option( 'quality_webp', 80 ) ); ?></output>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="quality_avif"><?php esc_html_e( 'AVIF Quality', 'coding-bunny-image-optimizer' ); ?></label></th>
						<td>
							<input type="range" id="quality_avif" name="quality_avif" min="0" max="100" 
							value="<?php echo esc_attr( get_option( 'quality_avif', 80 ) ); ?>" 
							<?php echo ( ! $licence_active ) ? 'disabled' : ''; ?> 
							oninput="this.nextElementSibling.value = this.value">
							<output class="check-label <?php echo ( ! $licence_active ) ? 'disabled-label' : ''; ?>"><?php echo esc_attr( get_option( 'quality_avif', 80 ) ); ?></output>
						</td>
					</tr>
				</table>
				<hr>
				<h3>
					<span class="dashicons dashicons-editor-expand"></span>
					<?php esc_html_e( 'Resize', 'coding-bunny-image-optimizer' ); ?>
				</h3>
				<p>
					<?php esc_html_e( 'Sets the maximum size of width and height of uploaded images.', 'coding-bunny-image-optimizer' ); ?>
				</p>
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
				<h3>
					<span class="dashicons dashicons-images-alt"></span>
					<?php esc_html_e( 'Default Image Sizes', 'coding-bunny-image-optimizer' ); ?>
				</h3>
				<p>
					<?php esc_html_e( 'Choose which image formats WordPress should automatically create.', 'coding-bunny-image-optimizer' ); ?>
				</p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Image size', 'coding-bunny-image-optimizer' ); ?></th>
						<td>
							<?php

							foreach ( $intermediate_sizes as $size => $data ) {
								$width = isset( $data['width'] ) ? $data['width'] : '';
								$height = isset( $data['height'] ) ? $data['height'] : '';

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
				<hr>
				<h3>
					<span class="dashicons dashicons-dismiss"></span>
					<?php esc_html_e( 'Unused Image Finder', 'coding-bunny-image-optimizer' ); ?>
					<span style="font-size: 10px; color:red;"><?php echo esc_html( 'BETA', 'coding-bunny-image-optimizer' ); ?></span>
				</h3>
				<p>
					<?php esc_html_e( 'Detect and mark unused images on your site.', 'coding-bunny-image-optimizer' ); ?>
				</p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="toggle-unused-images"><?php esc_html_e( 'Enable Toolbar', 'coding-bunny-image-optimizer' ); ?></label></th>
						<td>
							<label class="toggle-label <?php echo esc_attr( $is_disabled ? 'disabled-label' : '' ); ?>">
								<input type="checkbox" class="toggle" id="toggle-unused-images" name="toggle_unused_images" value="0" <?php checked( $disable_unused_images, '1' ); ?><?php echo ( ! $licence_active ) ? 'disabled' : ''; ?> />
								<span class="slider"></span>
								<?php esc_html_e( 'Enables or disables the toolbar in the media library.', 'coding-bunny-image-optimizer' ); ?>
							</label>
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

	// Resize images on upload
	function coding_bunny_image_optimizer_resize_image( $file ) {

		$enable_resize = get_option( 'enable_resize', '1' );
		if ( $enable_resize !== '1' ) {
			return $file;
		}

		$max_width = get_option( 'max_width', 1000 );
		$max_height = get_option( 'max_height', 1000 );

		$mime_type = mime_content_type( $file['file'] );
		if ( strpos( $mime_type, 'image' ) === false ) {
			return $file;
		}

		$image_size = getimagesize( $file['file'] );
		if ( ! $image_size ) {
			error_log( sprintf( __( 'Error: Unable to get image dimensions for file %s', 'coding-bunny-image-optimizer' ), esc_url( $file['file'] ) ) );
			return $file;
		}

		if ( $image_size[0] <= $max_width && $image_size[1] <= $max_height ) {
			return $file;
		}

		$image_editor = wp_get_image_editor( $file['file'] );
		if ( is_wp_error( $image_editor ) ) {
			error_log( sprintf( __( 'Error: Unable to create image editor for file %s', 'coding-bunny-image-optimizer' ), esc_url( $file['file'] ) ) );
			return $file;
		}
		$result = $image_editor->resize( $max_width, $max_height, false );
		if ( is_wp_error( $result ) ) {
			error_log( sprintf( __( 'Error: Unable to get image dimensions for file %s', 'coding-bunny-image-optimizer' ), esc_url( $file['file'] ) ) );
			return $file;
		}
		$saved = $image_editor->save( $file['file'] );
		if ( is_wp_error( $saved ) ) {
			error_log( sprintf( __( 'Error: Unable to save resized image for file %s', 'coding-bunny-image-optimizer' ), esc_url( $file['file'] ) ) );
		}

		return $file;
	}
	add_filter( 'wp_handle_upload', 'coding_bunny_image_optimizer_resize_image' );

	// Convert images to WEBP or AVIF format on upload
	function coding_bunny_image_optimizer_convert_image($upload) {
		// Check if conversion is enabled
		$enable_conversion = get_option('enable_conversion', '1');
		if ($enable_conversion !== '1') {
			return $upload;
		}

		$convert_format = get_option('convert_format', 'webp');

		if ($convert_format && isset($upload['type']) && in_array($upload['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
			$file_path = $upload['file'];

			if (extension_loaded('imagick') || extension_loaded('gd')) {
				$output_path = pathinfo($file_path, PATHINFO_DIRNAME) . '/' . pathinfo($file_path, PATHINFO_FILENAME) . '.' . $convert_format;

				if (convert_image($file_path, $output_path, $convert_format)) {
					$upload['file'] = $output_path;
					$upload['url'] = str_replace(basename($upload['url']), basename($output_path), $upload['url']);
					$upload['type'] = 'image/' . $convert_format;

					wp_delete_file($file_path);
				}
			}
		}

		return $upload;
	}
	add_filter('wp_handle_upload', 'coding_bunny_image_optimizer_convert_image');

	// Disable intermediate image sizes
	function coding_bunny_image_optimizer_disable_intermediate_image_sizes( $sizes ) {

		$licence_active = io_is_licence_active();

		if ( ! $licence_active ) {
			return $sizes;
		}

		$enabled_image_sizes = get_option( 'enabled_image_sizes', array() );
		if ( empty( $enabled_image_sizes ) ) {
			return array();
		}

		foreach ( $sizes as $size => $data ) {
			if ( ! in_array( $size, $enabled_image_sizes ) ) {
				unset( $sizes[ $size ] );
			}
		}

		return $sizes;
	}
	add_filter( 'intermediate_image_sizes_advanced', 'coding_bunny_image_optimizer_disable_intermediate_image_sizes' );