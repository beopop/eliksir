<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_hprl_save_quiz', 'hprl_save_quiz' );
add_action( 'wp_ajax_nopriv_hprl_save_quiz', 'hprl_save_quiz' );
function hprl_save_quiz() {
    check_ajax_referer( 'hprl_nonce', 'nonce' );
    global $wpdb;
    $name = sanitize_text_field( $_POST['name'] );
    $email = sanitize_email( $_POST['email'] );
    $phone = sanitize_text_field( $_POST['phone'] );
    $birth_year = intval( $_POST['birth_year'] );
    $location = sanitize_text_field( $_POST['location'] );
    $answers = isset( $_POST['answers'] ) ? array_map( 'sanitize_text_field', $_POST['answers'] ) : array();
    $product_id = intval( $_POST['product'] );

    $wpdb->insert( HPRL_TABLE, [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'birth_year' => $birth_year,
        'location' => $location,
        'answers' => maybe_serialize( $answers ),
        'product_id' => $product_id,
        'created_at' => current_time( 'mysql' )
    ] );

    wp_send_json_success();
}
