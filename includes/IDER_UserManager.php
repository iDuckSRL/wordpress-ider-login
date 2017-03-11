<?php


class IDER_UserManager
{


    static function userinfo_handler($user_info)
    {
        // TODO: leverage future endpoint to check which side changed the email: local->no access and error msg, remote->update email

        $user_info = (array)$user_info;

        // explode json packed claims
        $user_info = self::_checkJsonfields($user_info);

        // remap openID fields into local fields
        $user_info = self::_fieldsMap($user_info);

        $user_info = (object)$user_info;

        // check if user exists by email
        // ps: if user uses same email on a new IDer profile the sub will be updated on the old profie
        $user = get_user_by('email', $user_info->email);

        // check if user exists by sub
        if (!$user->ID) {
            $user = get_users(['meta_key' => 'ider_sub', 'meta_value' => $user_info->sub]);
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
            wp_redirect(IDER_Server::get_option('welcome_page'));
            exit;
        }

        IDER_UserManager::access_denied("User unable to login.");
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


    private static function _fieldsMap($userdata)
    {
        $fields = array();

        $fields = apply_filters('ider_fields_map', $fields);

        foreach ($fields as $localkey => $remotekey) {
            if (!empty($userdata[$remotekey])) {
                $userdata[$localkey] = $userdata[$remotekey];
                // unset($userdata[$remotekey]);
            }
        }

        return $userdata;
    }


    private static function _checkJsonfields($userdata)
    {

        foreach ($userdata as $key => $claim) {
            if (IDER_Helpers::isJSON($claim)) {
                $subclaims = json_decode($claim);

                // break down the claim
                foreach ($subclaims as $subkey => $subclaim) {
                    $userdata[$key . '_' . $subkey] = $subclaim;
                }

                // delete the original claim
                unset($userdata[$key]);
            }
        }

        return $userdata;
    }


}