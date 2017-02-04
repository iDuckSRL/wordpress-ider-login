<?php
/**
 * File: rewrites.php
 *
 * @author Justin Greer <justin@justin-greer.com
 * @package WP Single Sign On Client
 */
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class WPOSSO_Rewrites
 *
 */
class IDER_Rewrites
{

    static function init()
    {

        add_filter('rewrite_rules_array', array(__CLASS__, 'create_rewrite_rules'));
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        add_filter('wp_loaded', array(__CLASS__, 'flush_rewrite_rules'));
        add_action('template_redirect', array(__CLASS__, 'template_redirect_intercept'));
    }


    function create_rewrite_rules($rules)
    {
        global $wp_rewrite;
        $newRule1 = array('auth/(.+)' => 'index.php?auth=' . $wp_rewrite->preg_index(1));
        $newRules = $newRule1 + $rules;

        return $newRules;
    }


    function add_query_vars($qvars)
    {
        $qvars[] = 'auth';
        $qvars[] = 'scope';
        return $qvars;
    }


    function flush_rewrite_rules()
    {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }


    function template_redirect_intercept()
    {

        global $wp_query;
        //var_dump($_GET['code']);exit;

        if (is_user_logged_in()) {
           // wp_redirect(home_url());
           // exit;
        }

        if ('ider' == $wp_query->get('auth')) {
            IDER_Callbacks::generate_authorization_url();
            exit;
        }

        if ('callback' == $wp_query->get('name') && !empty($_GET['code'])) {
            IDER_Callbacks::redeem_authorization_code();
            exit;
        }

        if ('callback' == $wp_query->get('name') && !empty($_REQUEST['error'])) {
            IDER_Callbacks::access_denied();
            exit;
        }

    }
}
