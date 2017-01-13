<?php
/**
 * Plugin Name: WP Local Dev
 * Description: Rewrites all links to the current host, if WP_LOCAL_DEV is defined as true.
 * Version: 1.1
 * Author: piDennis
 * Author URI: https://github.com/pidennis/
 * Plugin URI: https://github.com/pidennis/wp-local-dev
 *
 * Copy this file to wp-content/mu-plugins/ or install via composer.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ) {
    $fileName = WPMU_PLUGIN_DIR . '/wp-local-dev/wp-local-dev.php';
    if ( file_exists( $fileName ) ) {
        require $fileName;
        ( new WPLocalDev() )->init();
    }
}