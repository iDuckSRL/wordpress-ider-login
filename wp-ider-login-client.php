<?php

/**
 * Plugin Name: IDer Login
 * Plugin URI: https://www.ider.com
 * Version: 2.1
 * Description: Provides Single Sign On via IDer Identity Server
 * Author: iDuck SRL
 * Author URI: https://www.ider.com
 * License: GPL2
 *
 * This program is GPL but; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of.
 */

defined('ABSPATH') or die('No script kiddies please!');

if (!defined('IDER_PLUGIN_FILE')) {
    define('IDER_PLUGIN_FILE', __FILE__);
}

if (!defined('IDER_PLUGIN_DIR')) {
    define('IDER_PLUGIN_DIR', trailingslashit(plugin_dir_path(__FILE__)));
}

if (!defined('IDER_PLUGIN_URL')) {
    define('IDER_PLUGIN_URL', trailingslashit(plugin_dir_url(__FILE__)));
}

if (!defined('IDER_CLIENT_VERSION')) {
    define('IDER_CLIENT_VERSION', '2.0');
}

if (!defined('IDER_SITE_DOMAIN')) {
    define('IDER_SITE_DOMAIN', implode(".", array_reverse(array_slice(array_reverse(explode(".", $_SERVER['HTTP_HOST'])), 0, 2))));
}

// Enable shortcodes in text widgets
add_filter('widget_text', 'do_shortcode');

// require the main lib
require_once IDER_PLUGIN_DIR . '/vendor/autoload.php';
require_once IDER_PLUGIN_DIR . '/includes/IDER_Server.php';

// Check if we need different environment
if (defined('IDER_SERVER')) {
    \IDERConnect\IDEROpenIDClient::$IDERServer = IDER_SERVER;
}

// bootstrap the plugin
IDER_Server::instance();

/* If you need customization (ie: field map) you can write below */
add_filter('ider_fields_map', function ($fields) {
    $options = get_option("wposso_options");
    $fields_mapping = $options["fields_mapping"];

    preg_match_all('/^(?!#)(\w+)=([\w\.]+)/m', $fields_mapping, $matches);

    $fields = array_combine($matches[1], $matches[2]);

    return $fields;
});

// If you need custom data handler you can hook here.
add_filter('before_callback_handler', function ($user_info, $scopes) {
    $handled = false;
    if (in_array('yourscope', $scopes)) {
        // do something...

        // true will prevent further processing
        $handled = true;
    }
    return $handled;

}, 10, 2);

// If you need custom data handler you can hook here. $handler = true will prevent the default action
add_filter('after_callback_handler', function ($user_info, $scopes) {
    if (in_array('yourscope', $scopes)) {
        // do something...
    }

    $options = get_option("wposso_options");
    $landing_pages = $options["landing_pages"];

    preg_match_all('/^(?!#)([\w-]+)=(.+)/m', $landing_pages, $matches);

    $landing_pages_r = array_combine($matches[1], $matches[2]);

    foreach ($landing_pages_r as $scope => $landing_page) {
        if (in_array($scope, $scopes)) {

            wp_redirect($landing_page);
            exit;
        }
    }

}, 10, 2);
