<?php


class IDER_Callbacks
{

    static $tokens;
    static $options;
    static $discovery;


    static function getAuthorizationParams()
    {

        return [
            'client_id' => self::$options['client_id'],
            'response_type' => 'code',
            'scope' => 'openid ' . (self::_getOverridingScope() ?: self::$options['extra_scopes']),
            'redirect_uri' => site_url(IDER_Server::$endpoints['callback'])
        ];
    }


    // Authenticate Check and Redirect
    static function generate_authorization_url()
    {
        self::$options = get_option('wposso_options');

        $params = array_merge(
            self::getAuthorizationParams(),
            [
                'state' => md5(self::$options['client_id'] . self::$options['client_secret'] . time())
            ]
        );


        $url = IDER_Server::$endpoints['url'] . IDER_Server::$endpoints['auth'] . '?' . http_build_query($params);
        $nonce = md5($url);
        $url_nonced = $url . '&nonce=' . $nonce;


        $crypted = self::_sslEncrypt($params['state'] . '.' . $nonce);
        setcookie('_redi', $crypted, time() + 500);
        IDER_Helpers::logRotate('Cookie crypt: ' . $crypted, 'ider-auth');


        IDER_Helpers::logRotate(str_repeat('=-', 64), 'ider-auth');
        IDER_Helpers::logRotate('Call URL: ' . $url_nonced, 'ider-auth');

        wp_redirect($url_nonced);
        exit;
    }


    static function validate_authorization_response($state)
    {
        // state must match
        if ($state != $_GET['state']) {
            IDER_Helpers::logRotate('State NOT valid. Halt.', 'ider-auth');
            return false;
        } else {
            setcookie('_redi');
            IDER_Helpers::logRotate('State valid. Cookie unset.', 'ider-auth');
        }

        // all good
        return true;
    }


    static function callbackHandler()
    {
        self::$options = get_option('wposso_options');

        self::discovery();

        self::get_token();
        self::validateToken();
        self::getUserinfo();
    }


    // Handle the callback from the server if there is one.
    static function get_token()
    {
        IDER_Helpers::logRotate('Called callback URL: ' . site_url($_SERVER['REQUEST_URI']), 'ider-auth');
        IDER_Helpers::logRotate('Redeem Auth code' . str_repeat(' -', 64), 'ider-auth');

        $plain = self::_sslDecrypt($_COOKIE['_redi']);
        list($state, $nonce) = explode(".", $plain);

        //IDER_Helpers::logRotate('Cookie raw: ' . $_COOKIE['_redi'], 'ider-auth');
        //IDER_Helpers::logRotate('Cookie decrypt: ' . $plain, 'ider-auth');

        if (!self::validate_authorization_response($state)) {
            self::access_denied('State not valid');
        }

        $code = sanitize_text_field($_GET['code']);
        $server_url = IDER_Server::$endpoints['url'] . IDER_Server::$endpoints['token'];
        $request = [
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'Authorization' => 'basic ' . base64_encode(self::$options['client_id'] . ":" . self::$options['client_secret'])
            ],
            'body' => array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => site_url(IDER_Server::$endpoints['callback'])
            ),
            'cookies' => array(),
            'sslverify' => false
        ];

        IDER_Helpers::logRotate('Call curl URL: ' . $server_url, 'ider-auth');
        IDER_Helpers::logRotate('Request: ' . print_r($request, 1), 'ider-auth');

        $response = wp_remote_post($server_url, $request);

        unset($response['http_response']);
        IDER_Helpers::logRotate('Response: ' . print_r($response, 1), 'ider-auth');

        // var_dump($server_url);
        // var_dump($request);
        // var_dump($response['body']);


        if ($response['response']['code'] != 200) {
            IDER_Helpers::logRotate('Error: ' . $response['response']['code'] . '-' . $response['response']['message'], 'ider-auth');
            self::access_denied($response['response']['code'] . '-' . $response['response']['message']);
        }


        if (is_wp_error($response)) {
            IDER_Helpers::logRotate('Error: ' . $response->error, 'ider-auth');
            self::access_denied($response->error);
        }

        self::$tokens = json_decode($response['body']);


    }

    static function discovery()
    {
        // verify access_token
        $server_url = IDER_Server::$endpoints['url'] . IDER_Server::$endpoints['discovery'];

        IDER_Helpers::logRotate('Discovery' . str_repeat(' -', 64), 'ider-auth');

        IDER_Helpers::logRotate('Call curl URL: ' . $server_url, 'ider-auth');

        $response = wp_remote_get($server_url);

        if ($response['response']['code'] != 200) {
            IDER_Helpers::logRotate('Error: ' . $response['response']['code'] . '-' . $response['response']['message'], 'ider-auth');
            self::access_denied($response['response']['code'] . '-' . $response['response']['message']);
        }

        self::$discovery = json_decode($response['body']);

        unset($response['http_response']);
        IDER_Helpers::logRotate('Discovery: ' . print_r(self::$discovery, 1), 'ider-auth');
    }


    static function validateToken()
    {
        // verify access_token
        $server_url =
            IDER_Server::$endpoints['url'] . IDER_Server::$endpoints['validateaccesstoken'] .
            '?token=' . self::$tokens->access_token;

        IDER_Helpers::logRotate('Validate access token' . str_repeat(' -', 64), 'ider-auth');

        IDER_Helpers::logRotate('Call curl URL: ' . $server_url, 'ider-auth');

        $response = wp_remote_get($server_url);

        if ($response['response']['code'] != 200) {
            IDER_Helpers::logRotate('Error: ' . $response['response']['code'] . '-' . $response['response']['message'], 'ider-auth');
            self::access_denied($response['response']['code'] . '-' . $response['response']['message']);
        }


        unset($response['http_response']);
        IDER_Helpers::logRotate('Access token data: ' . print_r(json_decode($response['body']), 1), 'ider-auth');



        // verify id token

        $server_url =
            IDER_Server::$endpoints['url'] . IDER_Server::$endpoints['validateidtoken'] .
            '?token=' . self::$tokens->id_token .
            '&client_id=' . self::$options['client_id'];

        IDER_Helpers::logRotate('Validate id token' . str_repeat(' -', 64), 'ider-auth');

        IDER_Helpers::logRotate('Call curl URL: ' . $server_url, 'ider-auth');

        $response = wp_remote_get($server_url);

        unset($response['http_response']);
        IDER_Helpers::logRotate('Id token data: ' . print_r(json_decode($response['body']), 1), 'ider-auth');
        // TODO: check nonce + altre verifiche


    }


    static function getUserinfo()
    {

        $welcome_page = self::$options['redirect_to_dashboard'] == '1' ? self::$options['welcome_page'] : site_url();


        $server_url = IDER_Server::$endpoints['url'] . IDER_Server::$endpoints['user'];
        $request = [
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . self::$tokens->access_token
            ],
            'sslverify' => false
        ];


        IDER_Helpers::logRotate('Retrieve user infos' . str_repeat(' -', 64), 'ider-auth');


        IDER_Helpers::logRotate('Call curl URL: ' . $server_url, 'ider-auth');
        IDER_Helpers::logRotate('Request: ' . print_r($request, 1), 'ider-auth');

        $response = wp_remote_get($server_url, $request);

        unset($response['http_response']);
        IDER_Helpers::logRotate('Response: ' . print_r($response, 1), 'ider-auth');


        if ($response['response']['code'] != 200) {
            IDER_Helpers::logRotate('Error: ' . $response['response']['code'] . '-' . $response['response']['message'], 'ider-auth');
            self::access_denied($response['response']['code'] . '-' . $response['response']['message']);
        }


        if (is_wp_error($response)) {
            IDER_Helpers::logRotate('Error: ' . $response->error, 'ider-auth');
            self::access_denied($response->error);
        }


        $user_info = json_decode($response['body']);

        IDER_Helpers::logRotate('Ider returned user data: ' . print_r($user_info, 1), 'ider-auth');

        $user_info = (object)self::_fieldsMap((array)$user_info);

        // check if user exists
        $users = get_users(array('meta_key' => 'ider_sub', 'meta_value' => $user_info->sub));
        $user = $users[0];

        // if new, register first
        if (!$user->ID) {
            $user_id = self::_do_register($user_info);

            $user = get_user_by('id', $user_id);
        }

        update_user_meta($user->ID, 'ider_token', self::$tokens);

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
        update_user_meta($user_id, 'billing_address_1', 'Via Roma 10');
        update_user_meta($user_id, 'billing_postcode', '10100');
        update_user_meta($user_id, 'billing_city', 'Torino');
        update_user_meta($user_id, 'billing_state', 'TO');
    }


    static function access_denied($errormsg)
    {
        wp_enqueue_style('ider-css', IDER_PLUGIN_URL . 'assets/css/general.css', false, IDER_CLIENT_VERSION, 'all');

        $error_msg = sanitize_text_field($errormsg);
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


    private static function _getOverridingScope()
    {
        global $wp_query;

        if (!empty($wp_query->get('scope'))) {
            return $wp_query->get('scope');
        }

        return false;
    }


    private static function _sslEncrypt($plain)
    {
        $key = substr(sha1(self::$options['client_secret'], true), 0, 16);
        $iv = openssl_random_pseudo_bytes(16);

        $crypted = openssl_encrypt($plain, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $crypted);
    }


    private static function _sslDecrypt($crypted)
    {
        $crypted = base64_decode($crypted);

        $key = substr(sha1(self::$options['client_secret'], true), 0, 16);
        $iv = substr($crypted, 0, 16);

        $plain = openssl_decrypt(substr($crypted, strlen($iv)), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $plain;
    }


}