<?php
/**
 * Plugin Name: IDer Single Sign On Client
 * Plugin URI: https://oid.ider.com/core
 * Version: 0.1.0
 * Description: Provides Single Sign On integration with IDer Identity Server using OAuth2 specs.
 * Author: Davide Lattanzio
 * Author URI: https://oid.ider.com/core
 * License: GPL2
 *
 * This program is GLP but; you can redistribute it and/or modify
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
    define('IDER_CLIENT_VERSION', '0.2.0');
}

if (!defined('IDER_SITE_DOMAIN')) {
    define('IDER_SITE_DOMAIN', implode(".", array_reverse(array_slice(array_reverse(explode(".", $_SERVER['HTTP_HOST'])), 0, 2))));
}

// Enable shortcodes in text widgets
add_filter('widget_text','do_shortcode');

// require the main lib
require_once IDER_PLUGIN_DIR . '/includes/IDER_Server.php';

// bootstrap the plugin
IDER_Server::instance();


/* If you need customization (ie: field map) you can write below */
add_filter('ider_fields_map', function($fields){

    $fields['ider_sub'] = 'sub';
    $fields['first_name'] = 'given_name';
    $fields['last_name'] = 'family_name';
    $fields['email'] = 'email';
    $fields['url'] = 'given_name';
    $fields['description'] = 'given_name';

    $fields['billing_first_name'] = 'given_name';
    $fields['billing_last_name'] = 'family_name';
    $fields['billing_company'] = '';
    $fields['billing_address_1'] = 'residential_address';
    $fields['billing_address_2'] = '';
    $fields['billing_city'] = '';
    $fields['billing_postcode'] = 'residential_zipcode';
    $fields['billing_country'] = 'address_country';
    $fields['billing_state'] = '';
    $fields['billing_phone'] = '';
    $fields['billing_email'] = 'email';

    $fields['shipping_first_name'] = 'given_name';
    $fields['shipping_last_name'] = 'family_name';
    $fields['shipping_company'] = '';
    $fields['shipping_address_1'] = 'delivery_address';
    $fields['shipping_address_2'] = '';
    $fields['shipping_city'] = '';
    $fields['shipping_postcode'] = 'delivery_zipcode';
    $fields['shipping_country'] = 'address_country';
    $fields['shipping_state'] = '';
    $fields['shipping_phone'] = '';
    $fields['shipping_email'] = 'email';

    return $fields;
});





