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

    /** Version */
    public $version = "0.1.0";

    /** Server Instance */
    public static $_instance = null;

    /** Default Settings */
    protected static $default_settings = array(
        'client_id' => '',
        'client_secret' => '',
        'extra_scopes' => '',
        'redirect_to_dashboard' => true,
        'login_form_button' => true,
        'welcome_page' => 'my-account/ider-profile'
    );

    public static $endpoints = array(
        'url' => 'https://oid.ider.com/core/connect/',
        'auth' => 'authorize',
        'token' => 'token',
        'user' => 'userinfo',
        'logout' => 'endsession',
        'callback' => 'Callback'
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
        $options = get_option('wposso_options');
        if ($options['login_form_button'] == 1) {
            add_action('login_form', [IDER_Helpers, 'wp_sso_login_form_button']);
        }

        self::register_activation_hooks();
        self::includes();

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

