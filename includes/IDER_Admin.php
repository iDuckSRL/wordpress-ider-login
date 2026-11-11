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
     * @return void
     */
    public function register_admin_assets()
    {
        wp_register_style('wposso_admin', plugins_url('../assets/css/admin.css', __FILE__));
        wp_register_script('wposso_admin', plugins_url('../assets/js/admin.js', __FILE__), array('jquery'));

        $this->register_dynamic_css();
        $this->admin_head();
    }

    /**
     * Add dynamic CSS for new wp-menu-image system.
     * 
     * @return void
     */
    public function register_dynamic_css() {
        $css = '.toplevel_page_wposso_settings > .wp-menu-image {';
        $css .= 'background-image: url("' . IDER_PLUGIN_URL . 'assets/images/ider_logo_white_128.png");';
        $css .= '}';

        wp_add_inline_style('wposso_admin', $css);
    }

    /**
     * @return void
     */
    public function admin_init()
    {
        register_setting('wposso_options', $this->option_name, array($this, 'validate'));
    }

    /**
     * @return void
     */
    public function add_page()
    {
        add_menu_page('IDer Login', 'IDer Login', 'manage_options', 'wposso_settings', array(
            $this,
            'options_do_page'
        ), 'none');
    }

    /**
     * loads the plugin styles and scripts into scope
     * 
     * @return void
     */
    public function admin_head()
    {
        wp_enqueue_style('wposso_admin');
        wp_enqueue_script('wposso_admin');
    }

    /**
     * Print the options page.
     * 
     * @return void
     */
    public function options_do_page()
    {
        $options = wp_parse_args((array) get_option($this->option_name, array()), array(
            'client_id' => '', 'client_secret' => '', 'extra_scopes' => '', 'login_form_button' => false,
            'welcome_page' => '', 'ider_admin_mode' => false, 'fields_mapping' => '', 'landing_pages' => '', 'button_css' => '',
        ));
        $name = esc_attr($this->option_name);
        ?>
        <div class="wrap">
            <h2>IDer Single Sign On Configuration</h2>
            <p>This plugin is meant to be used with <a href="https://www.ider.com/">IDer Connect System</a>.
            </p>
            <div>
                <strong>Setting up IDer Client Account</strong>
                <ol>
                    <li>Create a new client and set the Redirect URI (aka callback URL) to:
                        <strong><?php echo esc_html(site_url(\IDERConnect\IDEROpenIDClient::$IDERRedirectURL)); ?></strong>
                    </li>
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
                            <input type="text" name="<?php echo $name ?>[client_id]" min="10"
                                   value="<?php echo esc_attr($options["client_id"]); ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Client Secret</th>
                        <td>
                            <input type="password" name="<?php echo $name ?>[client_secret]" min="10"
                                   value="<?php echo esc_attr($options["client_secret"]); ?>" autocomplete="new-password"/>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Scope Name</th>
                        <td>
                            <input type="text" name="<?php echo $name ?>[extra_scopes]" min="10"
                                   value="<?php echo esc_attr($options["extra_scopes"]); ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">

                        <th scope="row">Add IDer button in the classic WP login form</th>
                        <td>
                            <input type="checkbox" name="<?php echo $name ?>[login_form_button]"
                                   value="1" <?php echo $options['login_form_button'] == 1 ? 'checked="checked"' : ''; ?> />
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Welcome page</th>
                        <td>
                            <?php echo esc_html(site_url()); ?>/<input type="text"
                                                             name="<?php echo $name ?>[welcome_page]"
                                                             min="10"
                                                             value="<?php echo esc_attr(preg_replace('/^(\/)*/i','',str_replace(site_url(), '' ,$options["welcome_page"]))); ?>"/>
                        </td>
                    </tr>

                    <tr valign="top">

                        <th scope="row">Allow Login only to Administrator Users</th>
                        <td>
                            <input type="checkbox" name="<?php echo $name ?>[ider_admin_mode]"
                                   value="1" <?php echo $options['ider_admin_mode'] == 1 ? 'checked="checked"' : ''; ?> />
                        </td>
                    </tr>

                </table>

                <h4><a href="#" id="advancedbtn">Advanced Options &raquo;</a></h4>

                <table class="form-table" id="advancedopts">

                    <tr valign="top">
                        <th scope="row">Field Mapping</th>
                        <td>
                            <textarea
                                name="<?php echo $name ?>[fields_mapping]"><?php echo esc_textarea($options["fields_mapping"]); ?></textarea>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Campaigns Landing pages</th>
                        <td>
                            <textarea name="<?php echo $name ?>[landing_pages]"><?php echo esc_textarea($options["landing_pages"]); ?></textarea>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">IDer Button additional css</th>
                        <td>
                            <textarea
                                name="<?php echo $name ?>[button_css]"><?php echo esc_textarea($options["button_css"]); ?></textarea>

                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>"/>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Settings Validation
     *
     * @param array $input 
     * @return array
     */
    public function validate($input)
    {
        $input = (array) $input;
        $text = function ($key) use ($input) {
            return isset($input[$key]) ? sanitize_text_field((string) $input[$key]) : '';
        };
        $area = function ($key) use ($input) {
            return isset($input[$key]) ? sanitize_textarea_field((string) $input[$key]) : '';
        };

        return array(
            'client_id' => $text('client_id'),
            // secrets may contain characters that sanitize_text_field() would alter
            'client_secret' => isset($input['client_secret']) ? trim(wp_strip_all_tags((string) $input['client_secret'])) : '',
            'extra_scopes' => $text('extra_scopes'),
            'welcome_page' => esc_url_raw(site_url(ltrim($text('welcome_page'), '/'))),
            // fix null being set in case of booleans
            'login_form_button' => !empty($input['login_form_button']),
            'ider_admin_mode' => !empty($input['ider_admin_mode']),
            'fields_mapping' => $area('fields_mapping'),
            'landing_pages' => $area('landing_pages'),
            'button_css' => wp_strip_all_tags($area('button_css')),
        );
    }
}

