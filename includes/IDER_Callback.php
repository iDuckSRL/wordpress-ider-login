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
        $handled = apply_filters('before_callback_handler', $user_info, $_SESSION['openid_connect_scope']);

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
        $user = get_user_by('email', $user_info->email);

        // check if user exists by sub
        if (!$user->ID) {
            $user = get_users(['meta_key' => 'ider_sub', 'meta_value' => $user_info->sub]);
            $user = @$user[0];

            // TODO: leverage future endpoint to check which side changed the email: local->no access and error msg, remote->update email
        }

        // if new, register first
        if (!$user->ID) {
            $user_id = self::_do_register($user_info);

            $user = get_user_by('id', $user_id);
        }

        // Log the User In
        self::_login($user);
        do_action('wp_login', $user_info->email);

        // update user data
        self::_update_usermeta($user->ID, $user_info);

        if (is_user_logged_in()) {
            // pass the controll to user defined functions and landing pages
            apply_filters('after_callback_handler', $user_info, $_SESSION['openid_connect_scope']);

            wp_redirect(IDER_Server::get_option('welcome_page'));

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
        get_header();
        echo '<div class="container">';
        echo '<div class="row">';
        echo '<div class="col-md-12 col-sm-18">';
        echo '<header class="page-header">';
        echo '<h1 class="page-title">' . $mainmsg . '</h1>';
        echo '</header>';
        echo '<div class="errordiv">';
        echo '<p>Please try later.</p>';
        echo '<small>' . ucwords(str_replace('_', ' ', $error_msg)) . ' </small>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        get_footer();
        die();

    }


    private static function _update_usermeta($user_id, $userdata)
    {
        $updated = [];

        foreach ($userdata as $key => $data) {
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


    private static function _do_register($user_info)
    {

        // Does not have an account. Register and then log the user in
        $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
        $user_id = wp_create_user($user_info->email, $random_password, $user_info->email);
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