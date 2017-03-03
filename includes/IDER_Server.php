<?php

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Main Class
 *
 * @author Justin Greer <justin@justin-greer.com>
 * @package WP Single Sign On Client
 */
class IDER_Server
{

    /** Server Instance */
    public static $_instance = null;

    /** Options */
    public static $options = null;

    /** Default Settings */
    protected static $default_settings = array(
        'client_id' => '',
        'client_secret' => '',
        'extra_scopes' => '',
        'redirect_to_dashboard' => true,
        'login_form_button' => true,
        'welcome_page' => 'my-account/ider-profile'
    );


    function __construct()
    {
        self::init();
    }


    static function init()
    {
        spl_autoload_register(array(__CLASS__, 'autoloader'));

        //add_action("init", array(__CLASS__, "includes"));

        // add IDER login button to WP login form
        if (self::get_option('login_form_button') == 1) {
            add_action('login_form', [IDER_Helpers, 'wp_sso_login_form_button']);
            add_action('woocommerce_login_form_end', [IDER_Helpers, 'wp_sso_login_form_button']);
        }

        self::register_activation_hooks();
        self::includes();

    }

    // Options lazy load
    static function get_option($option = null)
    {
        self::$options = get_option('wposso_options');;

        if ($option === null) {
            return self::$options;
        } else if (array_key_exists($option, self::$options)) {
            return self::$options[$option];
        } else {
            return null;
        }
    }

    /**
     *  IDEROpenIDClient Initializer
     */
    public static function getIDerOpenIdClientIstance()
    {
        \IDERConnect\IDEROpenIDClient::$IDERLogFile = IDER_PLUGIN_DIR . '/log/ider-connect.log';

        $options = get_option('wposso_options');

        if (is_null(\IDERConnect\IDEROpenIDClient::$_instance)) {
            \IDERConnect\IDEROpenIDClient::$_instance = new \IDERConnect\IDEROpenIDClient($options['client_id'], $options['client_secret'], $options['extra_scopes']);
        }

        return \IDERConnect\IDEROpenIDClient::$_instance;
    }


    public static function IDerOpenIdClientHandler()
    {
        global $wp_query;


        try {
            $iderconnect = IDER_Server::getIDerOpenIdClientIstance();

            if (!empty($wp_query->get('scope'))) {
                $iderconnect->setScope($wp_query->get('scope'));
            }

            $iderconnect->authenticate();

            $userInfo = $iderconnect->requestUserInfo();
            IDER_UserManager::userinfo_handler($userInfo);
            exit;

        } catch (Exception $e) {
            IDER_UserManager::access_denied($e->getMessage());
        } finally {
            exit;
        }
    }

    /**
     * Plugin Initializer
     */
    public static function register_activation_hooks()
    {
        register_activation_hook(IDER_PLUGIN_FILE, array(__CLASS__, 'setup'));
        register_activation_hook(IDER_PLUGIN_FILE, array(__CLASS__, 'upgrade'));
    }

    /**
     * populate the instance if the plugin for extendability
     * @return object plugin instance
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * plugin includes called during load of plugin
     * @return void
     */
    public static function includes()
    {
        IDER_Widget::init();
        IDER_Admin::init();
        IDER_Widget::init();
        IDER_Rewrites::init();
        IDER_Shortcodes::init();
        IDER_WooPage::init();

    }


    /**
     * Plugin Setup
     */
    public function setup()
    {
        $options = get_option("wposso_options");
        if (!isset($options["server_url"])) {
            update_option("wposso_options", self::$default_settings);
        }
    }


    private static function autoloader($class)
    {
        $path = IDER_PLUGIN_DIR;
        $paths = array();
        $exts = array('.php', '.class.php');

        $paths[] = $path;
        $paths[] = $path . 'includes/';

        foreach ($paths as $p)
            foreach ($exts as $ext) {
                if (file_exists($p . $class . $ext)) {
                    require_once($p . $class . $ext);
                    return true;
                }
            }

        return false;
    }

}

