<?php

/**
 * Add custom endpoint that appears in My Account Page - WooCommerce 2.6
 * Ref - https://gist.github.com/claudiosmweb/a79f4e3992ae96cb821d3b357834a005#file-custom-my-account-endpoint-php
 *
 * @package     WordPress
 * @subpackage  Ider
 * @author      Davide Lattanzio <plugins@jlm.srl>
 * @since       1.0
 *
 */


class IDER_WooPage
{
    /**
     * Custom endpoint name.
     *
     * @var string
     */
    public static $endpoint = 'ider-profile';

    /**
     * Plugin actions.
     */
    public static function  init()
    {
        // Actions used to insert a new endpoint in the WordPress.
        add_action('init', array(__CLASS__, 'add_endpoints'));
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'), 0);
        // Change the My Accout page title.
        add_filter('the_title', array(__CLASS__, 'endpoint_title'));
        // Insering your new tab/page into the My Account page.
        add_filter('woocommerce_account_menu_items', array(__CLASS__, 'new_menu_items'));
        add_action('woocommerce_account_' . self::$endpoint . '_endpoint', array(__CLASS__, 'endpoint_content'));


        register_activation_hook(__FILE__, array('IDER_WooPage', 'install'));
    }

    /**
     * Register new endpoint to use inside My Account page.
     *
     * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
     */
    public static function  add_endpoints()
    {
        add_rewrite_endpoint(self::$endpoint, EP_ROOT | EP_PAGES);
    }

    /**
     * Add new query var.
     *
     * @param array $vars
     * @return array
     */
    public static function  add_query_vars($vars)
    {
        $vars[] = self::$endpoint;
        return $vars;
    }

    /**
     * Set endpoint title.
     *
     * @param string $title
     * @return string
     */
    public static function  endpoint_title($title)
    {
        global $wp_query;
        $is_endpoint = isset($wp_query->query_vars[self::$endpoint]);
        if ($is_endpoint && !is_admin() && is_main_query() && in_the_loop() && is_account_page()) {
            // New page title.
            $title = __('IDer Profile', 'woocommerce');
            remove_filter('the_title', array(__CLASS__, 'endpoint_title'));
        }
        return $title;
    }

    /**
     * Insert the new endpoint into the My Account menu.
     *
     * @param array $items
     * @return array
     */
    public static function  new_menu_items($items)
    {
        // Remove the logout menu item.
        $logout = $items['customer-logout'];
        unset($items['customer-logout']);
        // Insert your custom endpoint.
        $items[self::$endpoint] = __('IDer Profile', 'woocommerce');
        // Insert back the logout item.
        $items['customer-logout'] = $logout;
        return $items;
    }

    /**
     * Endpoint HTML content.
     */
    public static function  endpoint_content()
    {
         echo IDER_Shortcodes::ider_profile_summary();
    }

    /**
     * Plugin install action.
     * Flush rewrite rules to make our custom endpoint available.
     */
    public static function install()
    {
        flush_rewrite_rules();
    }

}


