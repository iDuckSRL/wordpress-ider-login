<?php

/**
 * Callback handler
 *
 * @package     WordPress
 * @subpackage  Ider
 * @author      Davide Lattanzio <plugins@jlm.srl>
 * @since       1.0
 *
 */

class IDER_Callback
{

    static function handler($user_info)
    {
        $user_info = IDER_UserInfoManager::normalize($user_info);

        $handled = false;
        // pass the controll to user defined functions
        $handled = apply_filters('before_callback_handler', $user_info, self::_scopes());

        // if user function hadn't been exclusive let's resume the standard flow
        if (!$handled) {
            IDER_Callback::defaultHandler($user_info);
        }
    }

    // register or authenticate user
    static function defaultHandler($user_info)
    {
        // check if user exists by email
        // ps: if user uses same email on a new IDer profile the sub will be updated on the old profie
        $email = isset($user_info->email) ? sanitize_email($user_info->email) : '';
        $sub = isset($user_info->sub) ? (string) $user_info->sub : '';

        if ($email === '' || $sub === '') {
            IDER_Callback::access_denied("Missing user data from IDer.");
        }

        $user = get_user_by('email', $email);

        // check if user exists by sub
        if (!$user || !$user->ID) {
            $users = get_users(['meta_key' => 'ider_sub', 'meta_value' => $sub, 'number' => 1]);
            $user = $users ? $users[0] : false;

            // TODO: leverage future endpoint to check which side changed the email: local->no access and error msg, remote->update email
        }

        // Store the user roles to check for admins later
        $user_roles = array();

        if ($user && !empty($user->roles)) {
            $user_roles = (array) $user->roles;
        }

        if((IDER_Server::get_option('ider_admin_mode') == 1) && !in_array('administrator', $user_roles)) {
            IDER_Callback::access_denied("Only administrator can log-in via IDer.");
        }
        // --

        // if new, register first
        if (!$user || !$user->ID) {
            $user_id = self::_do_register($user_info);

            if (is_wp_error($user_id)) {
                IDER_Callback::access_denied($user_id->get_error_message());
            }

            $user = get_user_by('id', $user_id);
        }

        // Log the User In
        self::_login($user);
        do_action('wp_login', $user->user_login, $user);

        // update user data
        self::_update_usermeta($user->ID, $user_info);

        if (is_user_logged_in()) {
            // pass the controll to user defined functions and landing pages
            apply_filters('after_callback_handler', $user_info, self::_scopes());

            wp_redirect(IDER_Server::get_option('welcome_page') ?: home_url());

            exit;
        }

        IDER_Callback::access_denied("User unable to login.");
    }


    static function access_denied($errormsg, $mainmsg = null)
    {
        if (is_null($mainmsg)) {
            $mainmsg = "Error authenticating user";
        }

        wp_enqueue_style('ider-css', IDER_PLUGIN_URL . 'assets/css/general.css', false, IDER_CLIENT_VERSION, 'all');

        $error_msg = sanitize_text_field($errormsg);
        $mainmsg = esc_html($mainmsg);
        get_header();
        echo '<div class="container">';
        echo '<div class="row">';
        echo '<div class="col-md-12 col-sm-18">';
        echo '<header class="page-header">';
        echo '<h1 class="page-title">' . $mainmsg . '</h1>';
        echo '</header>';
        echo '<div class="errordiv">';
        echo '<p>Please try later.</p>';
        echo '<small>' . esc_html(ucwords(str_replace('_', ' ', $error_msg))) . ' </small>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        get_footer();
        die();

    }

    private static function _update_usermeta($user_id, $userdata)
    {
        if(!$user_id && !$userdata) {
            return;
        }

        $updated = [];

        // Never let remote data overwrite WordPress internals (roles, capabilities, sessions...).
        global $wpdb;
        $protected = array(
            $wpdb->get_blog_prefix() . 'capabilities',
            $wpdb->get_blog_prefix() . 'user_level',
            'session_tokens',
            'last_updated_fields',
        );

        foreach ($userdata as $key => $data) {
            $key = (string) $key;
            if ($key === '' || in_array($key, $protected, true) || strpos($key, $wpdb->base_prefix) === 0) {
                continue;
            }

            // TMP: override wrong format
            // if (in_array($key, array('billing_country', 'shipping_country'))) $data = 'IT';

            $metadata = get_user_meta($user_id, $key, true);

            if ($metadata != $data) {
                update_user_meta($user_id, $key, $data);

                // mask as updated
                $updated[] = $key;
            }
        }
        update_user_meta($user_id, 'last_updated_fields', $updated);

        // TMP: filling missing fields
        /*
        update_user_meta($user_id, 'billing_address_1', 'Via Roma 10');
        update_user_meta($user_id, 'billing_postcode', '10100');
        update_user_meta($user_id, 'billing_city', 'Torino');
        update_user_meta($user_id, 'billing_state', 'TO');
        */

        return $updated;
    }

    // Scopes requested in this login (set by the OpenID client in the session).
    private static function _scopes()
    {
        return isset($_SESSION['openid_connect_scope']) ? (array) $_SESSION['openid_connect_scope'] : array();
    }

    private static function _do_register($user_info)
    {
        // Does not have an account. Register and then log the user in
        $random_password = wp_generate_password(24, true);
        $email = sanitize_email($user_info->email);
        $user_id = wp_create_user($email, $random_password, $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        update_user_meta($user_id, 'ider_sub', $user_info->sub);

        return $user_id;
    }

    private static function _login($user)
    {
        // User ID 1 is not allowed
        if ('1' === $user->ID) {
            wp_die('For security reasons, admin cannot login via IDer.');
        }

        if (!is_user_logged_in()) {

            wp_clear_auth_cookie();
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID);
        }
    }
}
