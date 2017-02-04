<?php


class IDER_Callbacks
{


// Authenticate Check and Redirect
    static function generate_authorization_url()
    {
        $options = get_option('wposso_options');

        $params = array(
            'client_id' => $options['client_id'],
            'response_type' => 'code',
            'scope' => 'openid ' . (self::getOverridingScope() ?: $options['extra_scopes']),
            'redirect_uri' => site_url('/CallBack'),
            'state' => md5($options['client_id'] . $options['client_secret'] . time()),
        );

        $params = http_build_query($params);

        $url = IDER_Server::$endpoints['url'] . IDER_Server::$endpoints['auth'] . '?' . $params;
        $url_nonced = $url . '&nonce=' . md5(wp_create_nonce($url));
        IDER_Helpers::logRotate('Call URL: ' . $url_nonced, 'ider-auth');

        wp_redirect($url_nonced);
        exit;
    }

//  http://www.xgox.net/CallBack?code=6c3d246717f68d526c848376763a343c&state=000303044efcf512f1834342d9298702&session_state=DCGlUH8NDpBqIsCWcO_aGvG0rFhQTmYrnAtwXiyWlF0.e35feb5b595a552aec968487cccde1f7


// Handle the callback from the server is there is one.
    static function redeem_authorization_code()
    {
        $options = get_option('wposso_options');

        // Grab a copy of the options and set the redirect location.
        $welcome_page = $options['redirect_to_dashboard'] == '1' ? $options['welcome_page'] : site_url();


        $code = sanitize_text_field($_GET['code']);
        $server_url = IDER_Server::$endpoints['url'] . IDER_Server::$endpoints['token'];
        $request = ['method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'Authorization' => 'basic ' . base64_encode($options['client_id'] . ":" . $options['client_secret'])
            ],
            'body' => array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                //'client_id' => $options['client_id'],
                //'client_secret' => $options['client_secret'],
                'redirect_uri' => site_url('/CallBack')
            ),
            'cookies' => array(),
            'sslverify' => false
        ];

        IDER_Helpers::logRotate(str_repeat('-', 64));
        IDER_Helpers::logRotate('Call URL: ' . $server_url, 'ider-auth');
        IDER_Helpers::logRotate('-- Request: ' . print_r($request, 1), 'ider-auth');

        $response = wp_remote_post($server_url, $request);

        IDER_Helpers::logRotate('-- Response: ' . print_r($response, 1), 'ider-auth');

        // var_dump($server_url);
        // var_dump($request);
        // var_dump($response['body']);

        $tokens = json_decode($response['body']);


        if (is_wp_error($response)) {
            wp_die($response->error);
        }

        $server_url = IDER_Server::$endpoints['url'] . IDER_Server::$endpoints['user'];
        $request = ['timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . $tokens->access_token
            ],
            'sslverify' => false
        ];

        IDER_Helpers::logRotate('Call URL: ' . $server_url, 'ider-auth');
        IDER_Helpers::logRotate('-- Request: ' . print_r($request, 1), 'ider-auth');

        $response = wp_remote_get($server_url, $request);

        IDER_Helpers::logRotate('-- Response: ' . print_r($response, 1), 'ider-auth');


        if (is_wp_error($response)) {
            IDER_Helpers::logRotate('-- Error: ' . $response->error, 'ider-auth');
            wp_die($response->error);
        }


        $user_info = json_decode($response['body']);

        // var_dump($user_info);exit;

        $user_info = (object)self::fieldsMap((array)$user_info);

        // check if user exists
        $users = get_users(array('meta_key' => 'ider_sub', 'meta_value' => $user_info->sub));
        $user = $users[0];

        // if new, register first
        if (!$user->ID) {
            $user_id = self::_do_register($user_info);

            $user = get_user_by('id', $user_id);

            $newuser = true;
        }

        update_user_meta($user->ID, 'ider_token', $tokens);

        // Log the User In
        self::_login($user);
        do_action('wp_login', $user_info->email);

        // update user data
        self::_update_usermeta($user->ID, $user_info);


        if (is_user_logged_in()) {
            wp_redirect($welcome_page);
            exit;
        }
    }


    private static function _update_usermeta($user_id, $userdata)
    {
        foreach ($userdata as $key => $data) {
            // TMP: override wrong format
            if (in_array($key, array('billing_country', 'shipping_country'))) $data = 'IT';

            update_user_meta($user_id, $key, $data);
        }


        // TMP: filling missing fields
        update_user_meta($user_id, 'billing_city', 'Torino');
        update_user_meta($user_id, 'billing_state', 'TO');
        update_user_meta($user_id, 'shipping_city', 'Torino');
        update_user_meta($user_id, 'shipping_state', 'TO');
        update_user_meta($user_id, 'billing_phone', '347-8585743');
        update_user_meta($user_id, 'shipping_phone', '349-9730953');
    }


    static function access_denied()
    {
        wp_enqueue_style('ider-css', IDER_PLUGIN_URL . 'assets/css/general.css', false, IDER_CLIENT_VERSION, 'all');

        $error_msg = sanitize_text_field($_REQUEST['error']);
        get_header();
        echo '<div class="errordiv">';
        echo '<header class="page-header">';
        echo '<h1 class="page-title">Error authenticating user</h1>';
        echo '</header>';
        echo '<p>Please try later.</p>';
        echo '<small>Err: ' . ucwords(str_replace('_', ' ', $error_msg)) . ' </small>';
        echo '</div>';
        get_footer();
        die();

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


    public static function fieldsMap($userdata)
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


    public static function getOverridingScope()
    {
        global $wp_query;

        if (!empty($wp_query->get('scope'))) {
            return $wp_query->get('scope');
        }

        return false;
    }


    public static function welcomePage()
    {
        wp_enqueue_style('jlm-loginbox-css', JLM_PLUGIN_URL . 'assets/css/loginbox.css', false, JLM_CLIENT_VERSION, 'all');

        get_header();
        ?>

        <div id="primary" class="content-area">
            <main id="main" class="site-main" role="main">
                    <?php echo IDER_Shortcodes::ider_profile_summary(); ?>
            </main><!-- #main -->
        </div><!-- #primary -->

        <?php
        get_footer();
        die();

    }


}