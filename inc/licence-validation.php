<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CODING_BUNNY_IMAGE_OPTIMIZER_VERSION', '1.3.0' );

function coding_bunny_image_optimizer_submenu() {
	add_submenu_page(
	'coding-bunny-image-optimizer',
	esc_html__( "Manage Licence", 'coding-bunny-image-optimizer' ),
	esc_html__( "Manage Licence", 'coding-bunny-image-optimizer' ),
	'manage_options',
	'coding-bunny-image-optimizer-licence',
	'coding_bunny_image_optimizer_licence_page'
);

$licence_data = get_option( 'coding_bunny_image_optimizer_licence_data', [ 'key' => '', 'email' => '' ] );
$licence_key = sanitize_text_field( $licence_data['key'] );
$licence_email = sanitize_email( $licence_data['email'] );
$licence_active = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );

if ( !$licence_active['success'] ) {
	add_submenu_page(
	'coding-bunny-image-optimizer', // Parent slug
	esc_html__( "Go Pro", 'coding-bunny-image-optimizer' ),
	esc_html__( "Go Pro", 'coding-bunny-image-optimizer' ),
	'manage_options',
	'coding-bunny-image-optimizer-pro',
	'coding_bunny_image_optimizer_pro_redirect'
);
}
}
add_action( 'admin_menu', 'coding_bunny_image_optimizer_submenu' );

// Function to handle redirection to external URL
function coding_bunny_image_optimizer_pro_redirect() {
wp_redirect( esc_url_raw( 'https://www.coding-bunny.com/image-optimizer/' ) ); // External URL
exit;
}

// Function to add custom CSS to highlight the "Passa a Pro" menu item
function coding_bunny_image_optimizer_admin_styles() {
?>
<style>
#toplevel_page_coding-bunny-image-optimizer .wp-submenu li a[href*='coding-bunny-image-optimizer-pro'] {
	background-color: #00a22a;
	color: #fff;
	font-weight: bold;
}
#toplevel_page_coding-bunny-image-optimizer .wp-submenu li a[href*='coding-bunny-image-optimizer-pro']:hover {
	background-color: #00a22a;
	color: #fff;
}
</style>
<?php
}
add_action( 'admin_head', 'coding_bunny_image_optimizer_admin_styles' );

// Function to display the licence validation page content
function coding_bunny_image_optimizer_licence_page() {
$licence_data = get_option( 'coding_bunny_image_optimizer_licence_data', [ 'key' => '', 'email' => '' ] );
$licence_key = sanitize_text_field( $licence_data['key'] );
$licence_email = sanitize_email( $licence_data['email'] );
$licence_active = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );

if ( isset( $_POST['validate_licence'] ) ) {
	check_admin_referer('coding_bunny_licence_validation');

	$licence_key = sanitize_text_field( $_POST['licence_key'] );
	$licence_email = sanitize_email( $_POST['licence_email'] );
	$response = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );

	if ( $response['success'] ) {
		update_option( 'coding_bunny_image_optimizer_licence_data', [ 'key' => $licence_key, 'email' => $licence_email ] );
		echo '<div class="notice notice-success"><p>' . esc_html__( "Licence successfully validated!", 'coding-bunny-image-optimizer' ) . '</p></div>';
		echo '<script>setTimeout(function(){ location.reload(); }, 1000);</script>'; // Reload the page after 1 second
	} else {
		echo '<div class="notice notice-error"><p>' . esc_html__( "Incorrect licence key or email: ", 'coding-bunny-image-optimizer' ) . esc_html( $response['error'] ) . '</p></div>';
	}
}

if ( isset( $_POST['deactivate_licence'] ) ) {
	check_admin_referer('coding_bunny_licence_deactivation'); // Verify nonce
	delete_option( 'coding_bunny_image_optimizer_licence_data' );
	$licence_key = '';
	$licence_email = '';
	echo '<div class="notice notice-success"><p>' . esc_html__( "Licence successfully deactivated!", 'coding-bunny-image-optimizer' ) . '</p></div>';
	echo '<script>setTimeout(function(){ location.reload(); }, 1000);</script>'; // Reload the page after 1 second
}

?>
<div class="wrap coding-bunny-image-optimizer-wrap">
	<h1><?php esc_html_e( 'CodingBunny Image Optimizer', 'coding-bunny-image-optimizer' ); ?> 
		<span style="font-size: 10px;">v<?php echo esc_html( CODING_BUNNY_IMAGE_OPTIMIZER_VERSION ); ?></span></h1>
		<h3>
			<span class="dashicons dashicons-admin-network"></span>
			<?php esc_html_e( "Manage Licence", 'coding-bunny-image-optimizer' ); ?>
		</h3>
		<form method="post" action="">
			<?php wp_nonce_field('coding_bunny_licence_validation'); ?>
			<div class="coding-bunny-flex-container">
				<div class="coding-bunny-flex-item">
					<label for="licence_email"><?php esc_html_e( "Email account:", 'coding-bunny-image-optimizer' ); ?></label>
				</div>
				<div class="coding-bunny-flex-item">
					<input type="email" id="licence_email" name="licence_email" value="<?php echo esc_attr( $licence_email ); ?>" required />
				</div>
				<div class="coding-bunny-flex-item">
					<label for="licence_key"><?php esc_html_e( "Licence Key:", 'coding-bunny-image-optimizer' ); ?></label>
				</div>
				<div class="coding-bunny-flex-item">
					<input type="text" id="licence_key" name="licence_key" 
					value="<?php echo $licence_active['success'] ? str_repeat('*', strlen( $licence_key )) : esc_attr( $licence_key ); ?>" 
					required />   
				</div>
				<div class="coding-bunny-flex-item">
					<?php if ( $licence_active['success'] ) : ?>
						<?php wp_nonce_field('coding_bunny_licence_deactivation'); ?>
						<button type="submit" name="deactivate_licence" class="button button-primary">
							<?php esc_html_e( "Deactivate licence", 'coding-bunny-image-optimizer' ); ?>
						</button>
					<?php else : ?>
						<button type="submit" name="validate_licence" class="button button-primary">
							<?php esc_html_e( "Activate licence", 'coding-bunny-image-optimizer' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $licence_active['success'] ) : ?>
				<div style="margin-top: 20px;">
					<div style="margin-top: 20px; font-weight: bold;">
						<span style="color: green;">&#x25CF;</span> <?php esc_html_e( "Active licence", 'coding-bunny-image-optimizer' ); ?>
					</div><br>
					<?php esc_html_e( "Your licence expires on:", 'coding-bunny-image-optimizer' ); ?>
					<span style="font-weight: bold;">
						<?php 
						// Format the expiration date
						$expiration_date = DateTime::createFromFormat( 'Y-m-d', $licence_active['expiration'] );
						echo esc_html( $expiration_date->format( 'd-m-Y' ) ); 
						?>
					</span>
				</div>
			<?php endif; ?>
		</form>
		<p>
			<?php esc_html_e( "Having problems with your licence? Contact our support: ", 'coding-bunny-image-optimizer' ); ?>
			<a href="mailto:support@coding-bunny.com">support@coding-bunny.com</a>
		</p>
		<hr>
		<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> - <?php esc_html_e( 'Powered by CodingBunny', 'coding-bunny-image-optimizer' ); ?></p>
	</div>
	<?php
}

// Function to validate the licence key
function coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email ) {
	$url = esc_url_raw( 'https://www.coding-bunny.com/plugins-licence/io-active-licence.php' );

	$response = wp_remote_post( $url, [
		'body' => wp_json_encode( [ 'licence_key' => sanitize_text_field( $licence_key ), 'email' => sanitize_email( $licence_email ) ] ),
		'headers' => [
		'Content-Type' => 'application/json',
	],
	'timeout' => 15,
	'sslverify' => true,
	]);

	if ( is_wp_error( $response ) ) {
		return [ 'success' => false, 'error' => $response->get_error_message() ];
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( isset( $body['success'] ) && $body['success'] ) {
		return [ 'success' => true, 'expiration' => sanitize_text_field( $body['expiration'] ) ];
	} else {
		return [ 'success' => false, 'error' => isset( $body['error'] ) ? sanitize_text_field( $body['error'] ) : esc_html__( "Incorrect licence key or email", 'coding-bunny-image-optimizer' ) ];
	}
}

// Function to show the warning notice on the dashboard
function coding_bunny_image_optimizer_licence_expiration_notice() {
	$licence_data = get_option( 'coding_bunny_image_optimizer_licence_data', [ 'key' => '', 'email' => '' ] );
	$licence_key = sanitize_text_field( $licence_data['key'] );
	$licence_email = sanitize_email( $licence_data['email'] );
	$licence_active = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );

	if ( $licence_active['success'] ) {
		$expiration_date = DateTime::createFromFormat( 'Y-m-d', $licence_active['expiration'] );
		$current_date = new DateTime();
		$days_until_expiration = $expiration_date->diff( $current_date )->days;

		if ( $days_until_expiration <= 30 && $days_until_expiration > 0 ) {
			add_action( 'admin_notices', function() use ( $days_until_expiration ) {
				echo '<div class="notice notice-warning is-dismissible"><p>' . 
					sprintf( 
				/* translators: update message */
				__( 'Your <b>CodingBunny Image Optimizer</b> licence expires in <b>%1$d days</b>! <a href="%2$s">Renew now.</a>', 'coding-bunny-image-optimizer' ), 
				$days_until_expiration, 
				esc_url( 'mailto:support@coding-bunny.com' ) 
					) . 
						'</p></div>';
			});
		}
	}
}
add_action( 'admin_init', 'coding_bunny_image_optimizer_licence_expiration_notice' );

// Function to add the licence expiration badge to the menu
function coding_bunny_image_optimizer_licence_menu_badge() {
	global $submenu;

	if ( isset( $submenu['coding-bunny-image-optimizer'] ) ) {
		$licence_data = get_option( 'coding_bunny_image_optimizer_licence_data', [ 'key' => '', 'email' => '' ] );
		$licence_key = sanitize_text_field( $licence_data['key'] );
		$licence_email = sanitize_email( $licence_data['email'] );
		$licence_active = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );

		if ( $licence_active['success'] ) {
			$expiration_date = DateTime::createFromFormat( 'Y-m-d', $licence_active['expiration'] );
			$current_date = new DateTime();
			$days_until_expiration = $expiration_date->diff( $current_date )->days;

			if ( $days_until_expiration <= 30 && $days_until_expiration > 0 ) {
				foreach ( $submenu['coding-bunny-image-optimizer'] as &$item ) {
					if ( $item[2] === 'coding-bunny-image-optimizer-licence' ) {
						$item[0] .= ' <span class="update-plugins count-1"><span class="plugin-count">!</span></span>';
					}
				}
			}
		}
	}
}
add_action( 'admin_menu', 'coding_bunny_image_optimizer_licence_menu_badge', 100 );