<?php

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Admin stuff to configurate plugin.
 *
 * @package     WordPress
 * @subpackage  Ider
 * @author      Davide Lattanzio <plugins@jlm.srl>
 * @since       1.0
 *
 */

class IDER_Admin
{

    protected $option_name = 'wposso_options';


    public static function init()
    {
        add_action("wp_loaded", array(new self, 'register_admin_assets'));
        add_action('admin_init', array(new self, 'admin_init'));
        add_action('admin_menu', array(new self, 'add_page'));
    }


    /**
     * [register_admin_assets description]
     * @return void
     */
    public function register_admin_assets()
    {
        wp_register_style('wposso_admin', plugins_url('../assets/css/admin.css', __FILE__));
        wp_register_script('wposso_admin', plugins_url('../assets/js/admin.js', __FILE__));
    }

    /**
     * [admin_init description]
     * @return [type] [description]
     */
    public function admin_init()
    {
        register_setting('wposso_options', $this->option_name, array($this, 'validate'));
    }

    /**
     * [add_page description]
     */
    public function add_page()
    {
        add_menu_page('IDer Login', 'IDer Login', 'manage_options', 'wposso_settings', array(
            $this,
            'options_do_page'
        ), IDER_PLUGIN_URL . 'assets/images/logo_ider.png');
    }

    /**
     * loads the plugin styles and scripts into scope
     * @return void
     */
    public function admin_head()
    {
        wp_enqueue_style('wposso_admin');
        wp_enqueue_script('wposso_admin');
    }

    /**
     * [options_do_page description]
     * @return [type] [description]
     */
    public function options_do_page()
    {
        $options = get_option($this->option_name);
        $this->admin_head();
        ?>
        <div class="wrap">
            <h2>IDer Single Sign On Configuration</h2>
            <p>This plugin is meant to be used with <a href="https://www.ider.com/">IDer Connect System</a>.
            </p>
            <div>
                <strong>Setting up IDer Client Account</strong>
                <ol>
                    <li>Create a new client and set the Redirect URI (aka callback URL) to:
                        <strong><?php echo site_url(\IDERConnect\IDEROpenIDClient::$IDERRedirectURL); ?></strong></li>
                    <li>Copy the Client ID and Client Secret in the text fields below.</li>
                    <li>Set the campaign id to retrieve the user data you chose.</li>
                    <li>If you open a custom campaign and want your customer to land on a specific page, please configure it in the advanced setting "Campaigns Landing pages" using the format <strong>&lt;Campaign id&gt;=&lt;Landing Page&gt;</strong>
                    <li>You can place the IDer button everywhere using widget, the classic form or the shortcode [ider_login_button]</li>
                    </li>
                </ol>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields('wposso_options'); ?>
                <hr/>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Client ID</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name ?>[client_id]" min="10"
                                   value="<?php echo $options["client_id"]; ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Client Secret</th>
                        <td>
                            <input type="password" name="<?php echo $this->option_name ?>[client_secret]" min="10"
                                   value="<?php echo $options["client_secret"]; ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Scope Name</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name ?>[extra_scopes]" min="10"
                                   value="<?php echo $options["extra_scopes"]; ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">

                        <th scope="row">Add IDer button in the classic WP login form</th>
                        <td>
                            <input type="checkbox" name="<?php echo $this->option_name ?>[login_form_button]"
                                   value="1" <?php echo $options['login_form_button'] == 1 ? 'checked="checked"' : ''; ?> />
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Welcome page</th>
                        <td>
                            <?php echo site_url(); ?>/<input type="text"
                                                             name="<?php echo $this->option_name ?>[welcome_page]"
                                                             min="10"
                                                             value="<?php echo preg_replace('/^(\/)*/i','',str_replace(site_url(), '' ,$options["welcome_page"])); ?>"/>
                        </td>
                    </tr>

                </table>

                <h4><a href="#" id="advancedbtn">Advanced Options &raquo;</a></h4>

                <table class="form-table" id="advancedopts">

                    <tr valign="top">
                        <th scope="row">Field Mapping</th>
                        <td>
                            <textarea
                                name="<?php echo $this->option_name ?>[fields_mapping]"><?php echo $options["fields_mapping"]; ?></textarea>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Campaigns Landing pages</th>
                        <td>
                            <textarea name="<?php echo $this->option_name ?>[landing_pages]"><?php echo $options["landing_pages"]; ?></textarea>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">IDer Button additional css</th>
                        <td>
                            <textarea
                                name="<?php echo $this->option_name ?>[button_css]"><?php echo $options["button_css"]; ?></textarea>

                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Settings Validation
     *
     * @param  [type] $input [description]
     *
     * @return [type]        [description]
     */
    public function validate($input)
    {

        $input['client_id'] = trim($input['client_id']);
        $input['client_secret'] = trim($input['client_secret']);
        $input['extra_scopes'] = trim($input['extra_scopes']);
        $input['welcome_page'] = site_url(trim($input['welcome_page']));

        return $input;
    }
}

