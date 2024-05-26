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
        'login_form_button' => true,
        'ider_admin_mode' => false,
        'welcome_page' => 'my-account/ider-profile',
        'landing_pages' => '',
        'button_css' => '',
        'fields_mapping' => '
ider_sub=sub
first_name=given_name
last_name=family_name
# nickname=nickname
email=email
display_name=preferred_user_name
url=website
description=note

# billing
billing_address_1=address.street_address
billing_address_2=
billing_city=address.locality
billing_state=address.region
billing_postcode=address.postal_code
billing_country=address.country
billing_first_name=given_name
billing_last_name=family_name
billing_company=
billing_phone=phone_number
billing_email=email

# shipping
shipping_address_1=address.street_address
shipping_address_2=
shipping_city=address.locality
shipping_state=address.region
shipping_postcode=address.postal_code
shipping_country=address.country
shipping_first_name=given_name
shipping_last_name=family_name
shipping_company=
shipping_phone=phone_number
shipping_email=email

# miscellaneous examples
preferred_short_size=shirt_size
preferred_shoes_size=shoe_size
');

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
            add_action('login_form', [IDER_Shortcodes::class, 'ider_login_button_render']);
            add_action('woocommerce_login_form_end', [IDER_Shortcodes::class, 'ider_login_button_render']);
        }

        self::register_activation_hooks();
        self::includes();
    }

    // Options lazy load
    static function get_option($option = null)
    {
        self::$options = get_option('wposso_options', array());

        if ($option === null) {
            return self::$options;
        } else if (@array_key_exists($option, self::$options)) {
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

        // Log file placed into uploads folder.
        $wpUploads = wp_upload_dir();

        $filename =  $wpUploads['basedir'] . '/ider-logs/log/ider-connect.log';

        if (!file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0777, true);
        }

        \IDERConnect\IDEROpenIDClient::$IDERLogFile = $filename;

        // Override the base URL with the WP one.
        \IDERConnect\IDEROpenIDClient::$BaseUrl = get_site_url();

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

            IDER_Callback::handler($userInfo);

            exit;

        } catch (Exception $e) {
            IDER_Callback::access_denied($e->getMessage());
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
        //  This should be used only when composer autoload fails to include classes
        //  self::loadPackage(IDER_PLUGIN_DIR.'vendor/phpseclib/phpseclib');
        //  self::loadPackage(IDER_PLUGIN_DIR.'vendor/jlmsrl/ider-openid-client-php');

        IDER_Widget::init();
        IDER_Shortcodes::init();
        IDER_Admin::init();
        IDER_Widget::init();
        IDER_Rewrites::init();
        IDER_WooPage::init();
    }

    /**
     * Plugin Setup
     */
    public static function setup()
    {
        $options = self::$default_settings;

        // if woocommerce then set the welcome page to user profile
        if (class_exists('WooCommerce')) {
            $options['welcome_page'] = site_url('my-account/ider-profile');
        } else {
            $options['welcome_page'] = home_url();
        }

        update_option("wposso_options", $options);
    }

    /**
     * Plugin Setup
     */
    public static function upgrade() {}

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

    private static function loadPackage($dir)
    {
        $composer = json_decode(file_get_contents("$dir/composer.json"), 1);
        $namespaces = $composer['autoload']['psr-4'];

        // Foreach namespace specified in the composer, load the given classes
        foreach ($namespaces as $namespace => $classpath) {
            spl_autoload_register(function ($classname) use ($namespace, $classpath, $dir) {
                // Check if the namespace matches the class we are looking for
                if (preg_match("#^" . preg_quote($namespace) . "#", $classname)) {
                    // Remove the namespace from the file path since it's psr4
                    $classname = str_replace($namespace, "", $classname);
                    $filename = preg_replace("#\\\\#", "/", $classname) . ".php";
                    include_once $dir . "/" . $classpath . "/$filename";
                }
            });
        }
    }
}
