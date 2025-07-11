<?php
/*
Plugin Name: Health Product Recommender Lite
Plugin URI: https://beohosting.com/plugins/health-product-recommender-lite
Description: Lagani, responzivni WordPress plugin koji generiše preporuke proizvoda na osnovu zdravstvenog upitnika, potpuno kompatibilan sa Woodmart temom i Elementorom.
Version: 1.5.2
Author: BeoHosting
Author URI: https://beohosting.com
License: GPL2+
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'HPRL_DIR', plugin_dir_path( __FILE__ ) );
define( 'HPRL_URL', plugin_dir_url( __FILE__ ) );
define( 'HPRL_VERSION', '1.5.2' );
define( 'HPRL_UPDATE_REPO', 'beopop/eliksir' );
define( 'HPRL_UPDATE_ASSET', 'health-product-recommender-lite.zip' );
if ( ! defined( 'HPRL_GITHUB_TOKEN' ) ) {
    define( 'HPRL_GITHUB_TOKEN', '' );
}

define( 'HPRL_TABLE', $GLOBALS['wpdb']->prefix . 'health_quiz_results' );

add_action( 'plugins_loaded', 'hprl_maybe_create_table' );
add_action( 'init', 'hprl_maybe_create_table' );
function hprl_maybe_create_table() {
    global $wpdb;
    $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", HPRL_TABLE ) );
    if ( $table_exists != HPRL_TABLE ) {
        hprl_activate();
    } else {
        $column = $wpdb->get_results( "SHOW COLUMNS FROM `" . HPRL_TABLE . "` LIKE 'product_id'" );
        if ( empty( $column ) ) {
            $wpdb->query( "ALTER TABLE `" . HPRL_TABLE . "` ADD `product_id` bigint(20) NOT NULL DEFAULT 0 AFTER `answers`" );
        }
        $column = $wpdb->get_results( "SHOW COLUMNS FROM `" . HPRL_TABLE . "` LIKE 'order_id'" );
        if ( empty( $column ) ) {
            $wpdb->query( "ALTER TABLE `" . HPRL_TABLE . "` ADD `order_id` bigint(20) NOT NULL DEFAULT 0 AFTER `product_id`" );
        }
        $column = $wpdb->get_results( "SHOW COLUMNS FROM `" . HPRL_TABLE . "` LIKE 'first_name'" );
        if ( empty( $column ) ) {
            $wpdb->query( "ALTER TABLE `" . HPRL_TABLE . "` ADD `first_name` varchar(200) NOT NULL AFTER `id`" );
        }
        $column = $wpdb->get_results( "SHOW COLUMNS FROM `" . HPRL_TABLE . "` LIKE 'last_name'" );
        if ( empty( $column ) ) {
            $wpdb->query( "ALTER TABLE `" . HPRL_TABLE . "` ADD `last_name` varchar(200) NOT NULL AFTER `first_name`" );
        }
    }
}

register_activation_hook( __FILE__, 'hprl_activate' );
function hprl_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS " . HPRL_TABLE . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        first_name varchar(200) NOT NULL,
        last_name varchar(200) NOT NULL,
        email varchar(200) NOT NULL,
        phone varchar(100) NOT NULL,
        birth_year int(4) NOT NULL,
        location varchar(200) DEFAULT '',
        answers text NOT NULL,
        product_id bigint(20) NOT NULL,
        order_id bigint(20) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

register_uninstall_hook( __FILE__, 'hprl_uninstall' );
function hprl_uninstall() {
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS " . HPRL_TABLE );
    delete_option( 'hprl_questions' );
    delete_option( 'hprl_products' );
    delete_option( 'hprl_combos' );
}

// Includes
require_once HPRL_DIR . 'includes/utils.php';
require_once HPRL_DIR . 'includes/data-handler.php';
require_once HPRL_DIR . 'includes/shortcodes.php';
add_action( 'wp_enqueue_scripts', 'hprl_enqueue_checkout_fill_script' );
function hprl_enqueue_checkout_fill_script() {
    if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
        wp_enqueue_script( 'hprl-checkout-fill', HPRL_URL . 'assets/js/checkout-fill.js', array( 'jquery' ), HPRL_VERSION, true );
    }
}
if ( is_admin() ) {
    require_once HPRL_DIR . 'includes/admin-panel.php';
    require_once HPRL_DIR . 'includes/updater.php';
}

add_filter( 'auto_update_plugin', 'hprl_force_auto_update', 10, 2 );
function hprl_force_auto_update( $update, $item ) {
    if ( $item->plugin === plugin_basename( __FILE__ ) ) {
        return true;
    }
    return $update;
}

// Redirects /zavrsena-anketa back to the quiz page so that refreshing the last
// step doesn't result in a 404 page.
add_action( 'template_redirect', 'hprl_handle_quiz_refresh' );
function hprl_handle_quiz_refresh() {
    if ( is_404() && ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'zavrsena-anketa' ) !== false ) {
        $base = preg_replace( '#zavrsena-anketa/?$#', '', $_SERVER['REQUEST_URI'] );
        wp_safe_redirect( home_url( $base ) );
        exit;
    }
}

add_action( 'woocommerce_checkout_order_processed', 'hprl_save_order_to_result', 10, 3 );
function hprl_save_order_to_result( $order_id, $posted_data, $order ) {
    if ( empty( $_COOKIE['hprl_result_id'] ) ) {
        return;
    }
    $result_id = intval( $_COOKIE['hprl_result_id'] );
    if ( $result_id > 0 ) {
        global $wpdb;
        $wpdb->update( HPRL_TABLE, array( 'order_id' => $order_id ), array( 'id' => $result_id ) );
        setcookie( 'hprl_result_id', '', time() - DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
    }
}
