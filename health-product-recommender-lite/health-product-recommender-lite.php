<?php
/*
Plugin Name: Health Product Recommender Lite
Plugin URI: https://beohosting.com/plugins/health-product-recommender-lite
Description: Lagani, responzivni WordPress plugin koji generiše preporuke proizvoda na osnovu zdravstvenog upitnika, potpuno kompatibilan sa Woodmart temom i Elementorom.
Version: 1.3.5
Author: BeoHosting
Author URI: https://beohosting.com
License: GPL2+
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'HPRL_DIR', plugin_dir_path( __FILE__ ) );
define( 'HPRL_URL', plugin_dir_url( __FILE__ ) );
define( 'HPRL_VERSION', '1.3.5' );
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
    }
}

register_activation_hook( __FILE__, 'hprl_activate' );
function hprl_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS " . HPRL_TABLE . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(200) NOT NULL,
        email varchar(200) NOT NULL,
        phone varchar(100) NOT NULL,
        birth_year int(4) NOT NULL,
        location varchar(200) DEFAULT '',
        answers text NOT NULL,
        product_id bigint(20) NOT NULL,
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
require_once HPRL_DIR . 'includes/excel.php';
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
