<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define the plugin version
define( 'CODING_BUNNY_IMAGE_OPTIMIZER_VERSION', '1.2.0' );

// Function to add a submenu item for licence validation
function coding_bunny_image_optimizer_submenu() {
    add_submenu_page(
        'coding-bunny-image-optimizer', // Parent slug
        __( "Manage Licence", 'coding-bunny-image-optimizer' ), // Page title
        __( "Manage Licence", 'coding-bunny-image-optimizer' ), // Menu title
        'manage_options', // Capability required to access this menu
        'coding-bunny-image-optimizer-licence', // Menu slug
        'coding_bunny_image_optimizer_licence_page' // Function to display the page content
    );

    // Check if the licence is inactive
    $licence_data = get_option( 'coding_bunny_image_optimizer_licence_data', [ 'key' => '', 'email' => '' ] );
    $licence_key = esc_attr( $licence_data['key'] );
    $licence_email = esc_attr( $licence_data['email'] );
    $licence_active = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );

    // Add "Go Pro" menu item if the licence is inactive
    if ( !$licence_active['success'] ) {
        add_submenu_page(
            'coding-bunny-image-optimizer', // Parent slug
            __( "Go Pro", 'coding-bunny-image-optimizer' ), // Page title
            __( "Go Pro", 'coding-bunny-image-optimizer' ), // Menu title
            'manage_options', // Capability required to access this menu
            'coding-bunny-image-optimizer-pro', // Menu slug
            'coding_bunny_image_optimizer_pro_redirect' // Function to handle redirection
        );
    }
}

// Hook the coding_bunny_image_optimizer_submenu function into the admin_menu action
add_action( 'admin_menu', 'coding_bunny_image_optimizer_submenu' );

// Function to handle redirection to external URL
function coding_bunny_image_optimizer_pro_redirect() {
    // Redirect to the external site
    wp_redirect( 'https://www.coding-bunny.com/image-optimizer/' ); // External URL
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

// Hook to add custom styles in admin
add_action( 'admin_head', 'coding_bunny_image_optimizer_admin_styles' );

// Function to display the licence validation page content
function coding_bunny_image_optimizer_licence_page() {
    $licence_data = get_option( 'coding_bunny_image_optimizer_licence_data', [ 'key' => '', 'email' => '' ] );
    $licence_key = esc_attr( $licence_data['key'] );
    $licence_email = esc_attr( $licence_data['email'] );
    $licence_active = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );

    // Handle the licence validation
    if ( isset( $_POST['validate_licence'] ) ) {
        check_admin_referer('coding_bunny_licence_validation'); // Verify nonce

        $licence_key = sanitize_text_field( $_POST['licence_key'] );
        $licence_email = sanitize_email( $_POST['licence_email'] );
        $response = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );

        if ( $response['success'] ) {
            // Save the valid licence key and email in the database
            update_option( 'coding_bunny_image_optimizer_licence_data', [ 'key' => $licence_key, 'email' => $licence_email ] );
            echo '<div class="notice notice-success"><p>' . __( "Licence successfully validated!", 'coding-bunny-image-optimizer' ) . '</p></div>';
            echo '<script>setTimeout(function(){ location.reload(); }, 1000);</script>'; // Reload the page after 1 second
        } else {
            echo '<div class="notice notice-error"><p>' . __( "Incorrect licence key or email: ", 'coding-bunny-image-optimizer' ) . esc_html( $response['error'] ) . '</p></div>';
        }
    }

    // Handle the licence deactivation
    if ( isset( $_POST['deactivate_licence'] ) ) {
        check_admin_referer('coding_bunny_licence_deactivation'); // Verify nonce
        delete_option( 'coding_bunny_image_optimizer_licence_data' );
        $licence_key = '';
        $licence_email = '';
        echo '<div class="notice notice-success"><p>' . __( "Licence successfully deactivated!", 'coding-bunny-image-optimizer' ) . '</p></div>';
        echo '<script>setTimeout(function(){ location.reload(); }, 1000);</script>'; // Reload the page after 1 second
    }

    ?>
    <div class="wrap coding-bunny-image-optimizer-wrap">
         <h1><?php esc_html_e( 'CodingBunny Image Optimizer', 'coding-bunny-image-optimizer' ); ?> 
            <span style="font-size: 10px;">v<?php echo CODING_BUNNY_IMAGE_OPTIMIZER_VERSION; ?></span></h1>
        <h3><?php esc_html_e( "Manage Licence", 'coding-bunny-image-optimizer' ); ?></h3>
        <form method="post" action="">
            <?php wp_nonce_field('coding_bunny_licence_validation'); // Add nonce for licence validation ?>
            <div class="coding-bunny-flex-container">
                <div class="coding-bunny-flex-item">
                    <label for="licence_email"><?php _e( "Email account:", 'coding-bunny-image-optimizer' ); ?></label>
                </div>
                <div class="coding-bunny-flex-item">
                    <input type="email" id="licence_email" name="licence_email" value="<?php echo esc_attr( $licence_email ); ?>" required />
                </div>
                <div class="coding-bunny-flex-item">
                    <label for="licence_key"><?php _e( "Licence Key:", 'coding-bunny-image-optimizer' ); ?></label>
                </div>
                <div class="coding-bunny-flex-item">
                    <input type="text" id="licence_key" name="licence_key" 
                        value="<?php echo $licence_active['success'] ? str_repeat('*', strlen( $licence_key )) : esc_attr( $licence_key ); ?>" 
                        required />   
                </div>
                <div class="coding-bunny-flex-item">
                    <?php if ( $licence_active['success'] ) : ?>
                        <?php wp_nonce_field('coding_bunny_licence_deactivation'); // Add nonce for licence deactivation ?>
                        <button type="submit" name="deactivate_licence" class="button button-primary">
                            <?php _e( "Deactivate licence", 'coding-bunny-image-optimizer' ); ?>
                        </button>
                    <?php else : ?>
                        <button type="submit" name="validate_licence" class="button button-primary">
                            <?php _e( "Activate licence", 'coding-bunny-image-optimizer' ); ?>
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
    $url = 'https://www.coding-bunny.com/plugins-licence/io-active-licence.php';

    $response = wp_remote_post( $url, [
        'body' => json_encode( [ 'licence_key' => $licence_key, 'email' => $licence_email ] ),
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
        return [ 'success' => true, 'expiration' => $body['expiration'] ]; // Get expiration date from server response
    } else {
        return [ 'success' => false, 'error' => isset( $body['error'] ) ? $body['error'] : __( "Incorrect licence key or email", 'coding-bunny-image-optimizer' ) ];
    }
}

// Function to show the warning notice on the dashboard
function coding_bunny_image_optimizer_licence_expiration_notice() {
    $licence_data = get_option( 'coding_bunny_image_optimizer_licence_data', [ 'key' => '', 'email' => '' ] );
    $licence_key = esc_attr( $licence_data['key'] );
    $licence_email = esc_attr( $licence_data['email'] );
    $licence_active = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );

    if ( $licence_active['success'] ) {
        $expiration_date = DateTime::createFromFormat( 'Y-m-d', $licence_active['expiration'] );
        $current_date = new DateTime();
        $days_until_expiration = $expiration_date->diff( $current_date )->days;

        if ( $days_until_expiration <= 30 && $days_until_expiration > 0 ) {
            add_action( 'admin_notices', function() use ( $days_until_expiration ) {
                echo '<div class="notice notice-warning is-dismissible"><p>' . 
                    sprintf( 
                        __( 'Your <b>CodingBunny Image Optimizer</b> licence expires in <b>%d days</b>! <a href="%s">Renew now.</a>', 'coding-bunny-image-optimizer' ), 
                        $days_until_expiration, 
                        esc_url( 'mailto:support@coding-bunny.com' ) 
                    ) . 
                '</p></div>';
            });
        }
    }
}

// Hook the coding_bunny_image_optimizer_licence_expiration_notice function into the admin_init action
add_action( 'admin_init', 'coding_bunny_image_optimizer_licence_expiration_notice' );

// Function to add the licence expiration badge to the menu
function coding_bunny_image_optimizer_licence_menu_badge() {
    global $submenu;

    // Check if the submenu for "coding-bunny-image-optimizer" exists
    if ( isset( $submenu['coding-bunny-image-optimizer'] ) ) {
        $licence_data = get_option( 'coding_bunny_image_optimizer_licence_data', [ 'key' => '', 'email' => '' ] );
        $licence_key = esc_attr( $licence_data['key'] );
        $licence_email = esc_attr( $licence_data['email'] );
        $licence_active = coding_bunny_image_optimizer_validate_licence( $licence_key, $licence_email );

        if ( $licence_active['success'] ) {
            $expiration_date = DateTime::createFromFormat( 'Y-m-d', $licence_active['expiration'] );
            $current_date = new DateTime();
            $days_until_expiration = $expiration_date->diff( $current_date )->days;

            // Show the badge only if the licence expires within 30 days
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

// Hook to modify the menu when it is loaded
add_action( 'admin_menu', 'coding_bunny_image_optimizer_licence_menu_badge', 100 );