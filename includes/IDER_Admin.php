<?php

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class IDER_Admin
 *
 * @author Justin Greer <justin@justin-greer.com
 * @package WP Single Sign On Client
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
        wp_register_style('wposso_admin', plugins_url('/assets/css/admin.css', __FILE__));
        wp_register_script('wposso_admin', plugins_url('/assets/js/admin.js', __FILE__));
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
            <p>This plugin is meant to be used with <a href="https://oid.ider.com/core">IDer Identity Server</a>.
            </p>
            <div>
                <strong>Setting up IDer Client Account</strong>
                <ol>
                    <li>Create a new client and set the Redirect URI (aka callback URL) to:
                        <strong><?php echo site_url('CallBack'); ?></strong></li>
                    <li>Copy the Client ID and Client Secret in the text fields below.</li>
                    <li>Set the campaign id to retrieve the user data you chosen.</li>
                    <li>If you need it set the offline QR campaign as <strong><?php echo site_url('?auth=ider&scope='); ?>&lt;campaign_id&gt;</strong> in IDer Parter admin page</li>
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
                            <input type="text" name="<?php echo $this->option_name ?>[client_secret]" min="10"
                                   value="<?php echo $options["client_secret"]; ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Campaign IDs</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name ?>[extra_scopes]" min="10"
                                   value="<?php echo $options["extra_scopes"]; ?>"/>
                            <p class="description">'openid' scope is always included</p>
                        </td>
                    </tr>

                    <tr valign="top">
                    <th scope="row">Sync user data with IDer server after each login</th>
                    <td>
                        <input type="checkbox" name="<?php echo $this->option_name ?>[keep_synced]"
                               value="1" <?php echo $options['keep_synced'] == 1 ? 'checked="checked"' : ''; ?> />
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
                        <th scope="row">Redirect to the IDer welcome page</th>
                        <td>
                            <input type="checkbox" name="<?php echo $this->option_name ?>[redirect_to_dashboard]"
                                   value="1" <?php echo $options['redirect_to_dashboard'] == 1 ? 'checked="checked"' : ''; ?> />
                        </td>
                    </tr>


                    <tr valign="top">
                        <th scope="row">Welcome page</th>
                        <td>
                            <?php echo site_url(); ?>/<input type="text" name="<?php echo $this->option_name ?>[welcome_page]" min="10"
                                   value="<?php echo $options["welcome_page"]; ?>"/>
                        </td>
                    </tr>

                </table>

                <!--
                <h3 class="seperator">Advanced Options</h3>
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">OAuth Server URL</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name ?>[server_url]" min="10"
                                   value="<?php echo $options["server_url"]; ?>"/>
                            <p class="description">Example: https://your-site.com</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Authorization Endpoint</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name ?>[server_auth_endpoint]" min="10"
                                   value="<?php echo $options["server_auth_endpoint"]; ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Server Token Endpoint</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name ?>[server_token_endpont]" min="10"
                                   value="<?php echo $options["server_token_endpont"]; ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">User Information Endpoint</th>
                        <td>
                            <input type="text" name="<?php echo $this->option_name ?>[user_info_endpoint]" min="10"
                                   value="<?php echo $options["user_info_endpoint"]; ?>"/>
                            <p class="description">Example: https://your-site.com</p>
                        </td>
                    </tr>
                </table>
                -->

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
        $input['redirect_to_dashboard'] = isset($input['redirect_to_dashboard']) ? $input['redirect_to_dashboard'] : 0;

        return $input;
    }
}

