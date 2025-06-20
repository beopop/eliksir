<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Hold an update error message for displaying admin notices.
$GLOBALS['hprl_update_error_msg'] = '';

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
    $token = hprl_get_github_token();
    if ( ! $token ) {
        return $url;
    }

    // Skip if token already present.
    if ( false !== strpos( $url, 'access_token=' ) ) {
        return $url;
    }

    $sep = ( false === strpos( $url, '?' ) ) ? '?' : '&';
    return $url . $sep . 'access_token=' . rawurlencode( $token );
}

function hprl_show_update_error_notice() {
    if ( empty( $GLOBALS['hprl_update_error_msg'] ) ) {
        return;
    }
    echo '<div class="notice notice-error"><p>' . esc_html( $GLOBALS['hprl_update_error_msg'] ) . '</p></div>';
}

function hprl_log_update_error( $msg ) {
    if ( get_option( 'hprl_debug_log', 0 ) ) {
        error_log( 'HPRL update error: ' . $msg );
        // Also write update errors to a dedicated log file inside wp-content/uploads
        $upload_dir = wp_upload_dir();
        if ( ! empty( $upload_dir['basedir'] ) ) {
            $log_file = trailingslashit( $upload_dir['basedir'] ) . 'hprl-update.log';
            $entry = '[' . date( 'Y-m-d H:i:s' ) . '] ' . $msg . PHP_EOL;
            @file_put_contents( $log_file, $entry, FILE_APPEND );
        }
    }
    if ( current_user_can( 'manage_options' ) ) {
        $GLOBALS['hprl_update_error_msg'] = 'Health Product Recommender Lite update error: ' . $msg;
        add_action( 'admin_notices', 'hprl_show_update_error_notice' );
    }
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
        hprl_log_update_error( $response->get_error_message() );
        return false;
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code && 404 == intval( $code ) ) {
        hprl_log_update_error(
            'GitHub API returned 404 (release not found or repository inaccessible).'
        );
        return false;
    }
    if ( $code && 200 !== intval( $code ) ) {
        hprl_log_update_error( 'GitHub API returned HTTP ' . $code );
        return false;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ) );
    if ( empty( $data->tag_name ) ) {
        hprl_log_update_error( 'Invalid release information received.' );
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
        $package = hprl_add_github_token_to_url( $package );
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
    $res->download_link = hprl_add_github_token_to_url( $download );
    $res->sections      = array( 'description' => $release->body );

    return $res;
}

