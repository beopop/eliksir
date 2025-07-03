<?php
if( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'health_quiz_results' );

delete_option( 'hprl_questions' );
delete_option( 'hprl_products' );
delete_option( 'hprl_universal_package' );
delete_option( 'hprl_github_token' );
delete_option( 'hprl_status_texts' );
