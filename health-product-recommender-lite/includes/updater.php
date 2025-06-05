<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'http_request_args', 'hprl_github_auth_header', 10, 2 );

function hprl_get_github_token() {
    if ( defined( 'HPRL_GITHUB_TOKEN' ) && HPRL_GITHUB_TOKEN ) {
        return HPRL_GITHUB_TOKEN;
    }
    $token = get_option( 'hprl_github_token', '' );
    return trim( $token );
}

function hprl_github_auth_header( $args, $url ) {
    if ( strpos( $url, 'github.com' ) !== false || strpos( $url, 'api.github.com' ) !== false || strpos( $url, 'objects.githubusercontent.com' ) !== false ) {
        $token = hprl_get_github_token();
        if ( $token ) {
            if ( empty( $args['headers'] ) ) {
                $args['headers'] = array();
            }
            if ( empty( $args['headers']['Authorization'] ) ) {
                $args['headers']['Authorization'] = 'token ' . $token;
            }
        }
    }
    return $args;
}

function hprl_add_github_token_to_url( $url ) {
    // Token no longer appended to the URL. Authentication is handled via
    // hprl_github_auth_header which sets the Authorization header.
    return $url;
}

add_filter( 'pre_set_site_transient_update_plugins', 'hprl_check_plugin_update' );
add_filter( 'plugins_api', 'hprl_plugin_update_info', 20, 3 );

function hprl_get_github_release() {
    $headers = array(
        'Accept'     => 'application/vnd.github.v3+json',
        'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
    );
    $token = hprl_get_github_token();
    if ( $token ) {
        $headers['Authorization'] = 'token ' . $token;
    }

    $response = wp_remote_get( 'https://api.github.com/repos/' . HPRL_UPDATE_REPO . '/releases/latest', array(
        'headers' => $headers,
        'timeout' => 15,
    ) );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ) );
    if ( empty( $data->tag_name ) ) {
        return false;
    }

    $data->asset_url = '';
    if ( ! empty( $data->assets ) ) {
        foreach ( $data->assets as $asset ) {
            if ( isset( $asset->name ) && $asset->name === HPRL_UPDATE_ASSET && ! empty( $asset->browser_download_url ) ) {
                $data->asset_url = $asset->browser_download_url;
                break;
            }
        }
    }

    return $data;
}

function hprl_check_plugin_update( $transient ) {
    if ( empty( $transient->checked ) ) {
        return $transient;
    }

    $release = hprl_get_github_release();
    if ( ! $release ) {
        return $transient;
    }

    $new_version = ltrim( $release->tag_name, 'v' );
    if ( version_compare( $new_version, HPRL_VERSION, '>' ) ) {
        $plugin = plugin_basename( HPRL_DIR . 'health-product-recommender-lite.php' );
        $package = $release->asset_url ? $release->asset_url : $release->zipball_url;
        $transient->response[ $plugin ] = (object) array(
            'slug'        => 'health-product-recommender-lite',
            'plugin'      => $plugin,
            'new_version' => $new_version,
            'url'         => $release->html_url,
            'package'     => $package,
        );
    }

    return $transient;
}

function hprl_plugin_update_info( $res, $action, $args ) {
    if ( 'plugin_information' !== $action || 'health-product-recommender-lite' !== $args->slug ) {
        return $res;
    }

    $release = hprl_get_github_release();
    if ( ! $release ) {
        return $res;
    }

    $res = new stdClass();
    $res->name          = 'Health Product Recommender Lite';
    $res->slug          = 'health-product-recommender-lite';
    $res->version       = ltrim( $release->tag_name, 'v' );
    $res->author        = '<a href="https://beohosting.com">BeoHosting</a>';
    $res->homepage      = $release->html_url;
    $download = $release->asset_url ? $release->asset_url : $release->zipball_url;
    $res->download_link = $download;
    $res->sections      = array( 'description' => $release->body );

    return $res;
}

