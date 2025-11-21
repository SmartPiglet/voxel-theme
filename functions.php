<?php

namespace Voxel;

if ( ! defined('ABSPATH') ) {
	exit;
}

$option_name = 'voxel:license';
$option_value = [
    'key' => 'VXABC-DE123-FG456-HI789-JK0LM',
    'env' => 'production',
    'active' => true,
    'last_checked' => current_time('mysql')
];

update_option($option_name, json_encode($option_value));

add_filter('pre_http_request', function($preempt, $r, $url) {
    // Check if the request URL contains the Voxel API endpoint
    if (strpos($url, 'https://getvoxel.io/') !== false) {
        // Parse the query parameters from the original URL
        $query_string = parse_url($url, PHP_URL_QUERY);
        parse_str($query_string, $query_params);

        // Check if 'action=voxel_licenses.verify' and 'mode=update' are present
        if (
            isset($query_params['action']) && $query_params['action'] === 'voxel_licenses.verify' &&
            isset($query_params['mode']) && $query_params['mode'] === 'update'
        ) {
            // Return a local response
            return array(
                'headers' => array(),
                'body' => json_encode(array('success' => true)),
                'response' => array(
                    'code' => 200,
                    'message' => 'OK'
                ),
            );
        }

        // Check if both 'license_key' and 'site_url' are present in the query parameters
        if (isset($query_params['license_key']) && isset($query_params['site_url'])) {
            // Reconstruct the full URL with the query parameters
            $gpl_url = 'https://www.gpltimes.com/gpldata/voxel.php?' . $query_string;

            // Make the request to GPL Times
            $response = wp_remote_get($gpl_url);

            // Check for errors
            if (is_wp_error($response)) {
                return new WP_Error('request_failed', 'Failed to connect to GPL Times.');
            } else {
                return $response; // Return the response from GPL Times
            }
        }
    }

    return $preempt; // Allow the original request if it's not the targeted URL or doesn't have required parameters
}, 10, 3);

function is_debug_mode() {
	return defined('WP_DEBUG') && WP_DEBUG;
}

function is_dev_mode() {
	return defined('VOXEL_DEV_MODE') && VOXEL_DEV_MODE;
}

function is_running_tests() {
	return defined('VOXEL_RUNNING_TESTS') && VOXEL_RUNNING_TESTS;
}

require_once locate_template('app/utils/utils.php');

foreach ( \Voxel\config('controllers') as $controller ) {
	new $controller;
}
