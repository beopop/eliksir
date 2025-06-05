<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'pre_set_site_transient_update_plugins', 'hprl_check_plugin_update' );
add_filter( 'plugins_api', 'hprl_plugin_update_info', 20, 3 );

function hprl_get_github_release() {
    $response = wp_remote_get( 'https://api.github.com/repos/' . HPRL_UPDATE_REPO . '/releases/latest', array(
        'headers' => array(
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
        ),
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
        $transient->response[ $plugin ] = (object) array(
            'slug'        => 'health-product-recommender-lite',
            'plugin'      => $plugin,
            'new_version' => $new_version,
            'url'         => $release->html_url,
            'package'     => $release->asset_url ? $release->asset_url : $release->zipball_url,
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
    $res->download_link = $release->asset_url ? $release->asset_url : $release->zipball_url;
    $res->sections      = array( 'description' => $release->body );

    return $res;
}

