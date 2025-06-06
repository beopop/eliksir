<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_hprl_save_quiz', 'hprl_save_quiz' );
add_action( 'wp_ajax_nopriv_hprl_save_quiz', 'hprl_save_quiz' );
function hprl_save_quiz() {
    check_ajax_referer( 'hprl_nonce', 'nonce' );
    global $wpdb;
    $debug = intval( get_option( 'hprl_debug_log', 0 ) );
    $first_name = sanitize_text_field( $_POST['first_name'] );
    $last_name  = sanitize_text_field( $_POST['last_name'] );
    $email = sanitize_email( $_POST['email'] );
    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Neispravan email.' ) );
    }
    $phone = preg_replace( '/[^0-9]/', '', $_POST['phone'] );
    if ( $phone === '' ) {
        wp_send_json_error( array( 'message' => 'Neispravan telefon.' ) );
    }
    $birth_year = intval( $_POST['birth_year'] );
    $location = sanitize_text_field( $_POST['location'] );
    $answers = isset( $_POST['answers'] ) ? array_map( 'sanitize_text_field', $_POST['answers'] ) : array();
    $product_id = intval( $_POST['product'] );

    $inserted = $wpdb->insert( HPRL_TABLE, [
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
        'phone'      => $phone,
        'birth_year' => $birth_year,
        'location'   => $location,
        'answers'    => maybe_serialize( $answers ),
        'product_id' => $product_id,
        'created_at' => current_time( 'mysql' )
    ] );

    if ( false === $inserted ) {
        error_log( 'HPRL insert error: ' . $wpdb->last_error );
        $resp = array( 'message' => 'Greška pri snimanju.' );
        if ( $debug && $wpdb->last_error ) {
            $resp['log'] = $wpdb->last_error;
        }
        wp_send_json_error( $resp );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_hprl_save_answers', 'hprl_save_answers' );
add_action( 'wp_ajax_nopriv_hprl_save_answers', 'hprl_save_answers' );
function hprl_save_answers() {
    check_ajax_referer( 'hprl_nonce', 'nonce' );
    global $wpdb;
    $debug = intval( get_option( 'hprl_debug_log', 0 ) );
    $first_name = sanitize_text_field( $_POST['first_name'] );
    $last_name  = sanitize_text_field( $_POST['last_name'] );
    $email = sanitize_email( $_POST['email'] );
    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Neispravan email.' ) );
    }
    $phone = preg_replace( '/[^0-9]/', '', $_POST['phone'] );
    if ( $phone === '' ) {
        wp_send_json_error( array( 'message' => 'Neispravan telefon.' ) );
    }
    $birth_year = intval( $_POST['birth_year'] );
    $location = sanitize_text_field( $_POST['location'] );
    $answers = isset( $_POST['answers'] ) ? array_map( 'sanitize_text_field', $_POST['answers'] ) : array();

    $inserted = $wpdb->insert( HPRL_TABLE, [
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
        'phone'      => $phone,
        'birth_year' => $birth_year,
        'location'   => $location,
        'answers'    => maybe_serialize( $answers ),
        'product_id' => 0,
        'created_at' => current_time( 'mysql' )
    ] );

    if ( false === $inserted ) {
        error_log( 'HPRL insert error: ' . $wpdb->last_error );
        $resp = array( 'message' => 'Greška pri snimanju.' );
        if ( $debug && $wpdb->last_error ) {
            $resp['log'] = $wpdb->last_error;
        }
        wp_send_json_error( $resp );
    }

    wp_send_json_success( array( 'result_id' => $wpdb->insert_id ) );
}

add_action( 'wp_ajax_hprl_set_product', 'hprl_set_product' );
add_action( 'wp_ajax_nopriv_hprl_set_product', 'hprl_set_product' );
function hprl_set_product() {
    check_ajax_referer( 'hprl_nonce', 'nonce' );
    global $wpdb;
    $debug = intval( get_option( 'hprl_debug_log', 0 ) );
    $id = intval( $_POST['result_id'] );
    $product_id = intval( $_POST['product'] );
    if ( $id > 0 ) {
        $updated = $wpdb->update( HPRL_TABLE, [ 'product_id' => $product_id ], [ 'id' => $id ] );
        if ( false === $updated ) {
            error_log( 'HPRL update error: ' . $wpdb->last_error );
            $resp = array();
            if ( $debug && $wpdb->last_error ) {
                $resp['log'] = $wpdb->last_error;
            }
            wp_send_json_error( $resp );
        }
        wp_send_json_success();
    }
    wp_send_json_error();
}
