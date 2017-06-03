<?php

/**
 * Shortcodes
 *
 * @package     WordPress
 * @subpackage  Ider
 * @author      Davide Lattanzio <plugins@jlm.srl>
 * @since       1.0
 *
 */

class IDER_Shortcodes
{

    static function init()
    {
        add_shortcode('ider_login_button', [__CLASS__, 'ider_login_button']);
        add_shortcode('ider_profile_summary', [__CLASS__, 'ider_profile_summary']);
    }



    static function ider_login_button_render($atts = []){

        echo self::ider_login_button($atts);

    }

    static function ider_login_button($atts = [])
    {
        wp_enqueue_style('ider-css', IDER_PLUGIN_URL . 'assets/css/general.css', false, IDER_CLIENT_VERSION, 'all');

        $options = get_option("wposso_options");
        wp_add_inline_style('ider-css', $options['button_css']);

        $a = shortcode_atts(array(
            'title' => 'Login using Single Sign On',
            'class' => 'button button-primary button-large ider-login',
            'target' => '',
            'text' => 'Login with IDer',
            'loginonly' => ''
        ), $atts);

        if (!is_user_logged_in()) {
            return '<a class="' . $a['class'] . '" href="' . site_url('/iderbutton') . '" title="' . $a['title'] . '" target="' . $a['target'] . '">
                    <img src="' . IDER_PLUGIN_URL . 'assets/images/ider_logo_white_32.png">' .
                    $a['text'] .
                    '</a>';
        } else {
            if (!$a['loginonly']) {
                return '<a class="' . $a['class'] . '" href="' . wp_logout_url('/') . '" title="' . $a['title'] . '" target="' . $a['target'] . '">
                        <img src="' . IDER_PLUGIN_URL . 'assets/images/logo_ider.png"> Logout</a>';
            }
        }
    }


    static function ider_profile_summary($atts = [])
    {

        $a = shortcode_atts(array(
            'title' => 'Ider profile Summary ',
            'class' => 'button button-primary button-large',
            'target' => '',
            'text' => 'Login with IDer',
            'loginonly' => ''
        ), $atts);


        $user = get_user_by('id', get_current_user_id());

        $usermetas = get_user_meta(get_current_user_id());

        $updated_fields = get_user_meta(get_current_user_id(), 'last_updated_fields', true);

        //print_r($user->user_email);

        $fields = [];
        $fields = array_keys(apply_filters('ider_fields_map', $fields));

        //print_r($usermetas);

        $tbody = '';
        foreach ($fields as $localfield) {

            // skip shipping fields
            if (preg_match("/^shipping_(.*)/i", $localfield)) continue;

            $tbody .= '<tr class="' . (in_array($localfield, $updated_fields) ? 'warning' : '') . '"><th class="textright">' . ucfirst(str_replace(['-', '_'], ' ', $localfield)) . '</th><td>';
            if ($usermetas[$localfield]) {
                $tbody .= $usermetas[$localfield][0];
            } else {
                $tbody .= '--';
            }
            $tbody .= '</td></tr>';
        }

        $email_mismatch = '<div class="alert alert-warning">
                           <strong>Warning!</strong> Your local email (' . $user->user_email . ') is different than your IDer email (' . ($usermetas['email'][0] ?: 'none') . ').
                           </div>';

        $table = '<h3>Welcome ' . $usermetas['first_name'][0] . ' ' . $usermetas['last_name'][0] . '</h3>';
        $table .= '<h4>You have been authenticated via IDer<sup>&copy;</sup> system.</h4>';
        $table .= $usermetas['email'][0] == $user->user_email ? '' : $email_mismatch;
        $table .= '<table class="table table-condensed">';
        $table .= '<tbody>' . $tbody . '</tbody>';
        $table .= '</table>';

        return $table;
    }
}

