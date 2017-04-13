<?php

/**
 * Plugin Routing
 *
 * @package     WordPress
 * @subpackage  Ider
 * @author      Davide Lattanzio <plugins@jlm.srl>
 * @since       1.0
 *
 */

defined('ABSPATH') or die('No script kiddies please!');


use IDERConnect\IDEROpenIDClient;

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


    static function create_rewrite_rules($rules)
    {
        global $wp_rewrite;
        $newRule1 = array('auth/(.+)' => 'index.php?auth=' . $wp_rewrite->preg_index(1));
        $newRules = $newRule1 + $rules;

        return $newRules;
    }


    static function add_query_vars($qvars)
    {
        $qvars[] = 'auth';
        $qvars[] = 'scope';
        return $qvars;
    }


    static function flush_rewrite_rules()
    {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }


    /**
     *
     */
    static function template_redirect_intercept()
    {
        global $wp_query;

        // echo $wp_query->get('auth'); exit;


        // if auth code or callback: pass the control to library
        if ('ider' == $wp_query->get('auth') or $wp_query->get('name') == IDEROpenIDClient::$IDERRedirectURL or $wp_query->get('name') == IDEROpenIDClient::$IDEButtonURL) {
           IDER_Server::IDerOpenIdClientHandler();
        }


    }
}
