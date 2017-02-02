<?php


class IDER_Shortcodes
{

    static function init()
    {
        add_shortcode('sso_button', [__CLASS__, 'single_sign_on_login_button_shortcode']);
    }


    static function single_sign_on_login_button_shortcode($atts)
    {
        $a = shortcode_atts(array(
            'type' => 'primary',
            'title' => 'Login using Single Sign On',
            'class' => 'button button-primary button-large',
            'target' => '',
            'text' => 'Login with IDer'
        ), $atts);
        if (!is_user_logged_in()) {
            return '<a class="' . $a['class'] . '" style="width: 100%; text-align: center" href="' . site_url('?auth=sso') . '" title="' . $a['title'] . '" target="' . $a['target'] . '"><img src="' . IDER_PLUGIN_URL . 'assets/images/logo_ider.png" style="display: inline; vertical-align: sub;margin-right: 5px">' . $a['text'] . '</a>';
        } else {
            return '<a class="' . $a['class'] . '" style="width: 100%; text-align: center" href="' . wp_logout_url('/') . '" title="' . $a['title'] . '" target="' . $a['target'] . '"><img src="' . IDER_PLUGIN_URL . 'assets/images/logo_ider.png" style="display: inline; vertical-align: sub;margin-right: 5px"> Logout</a>';
        }
    }
}

