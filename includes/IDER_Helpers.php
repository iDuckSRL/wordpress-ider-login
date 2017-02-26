<?php


class IDER_Helpers
{

    static function wp_sso_login_form_button()
    {

        ?>
        <a style="color:#FFF; width:100%; text-align:center; margin-bottom:1em;"
           class="button button-primary button-large"
           href="<?php echo site_url('?auth=ider'); ?>">
            <img src="<?php echo IDER_PLUGIN_URL ?>assets/images/logo_ider.png"
                 style="display: inline; vertical-align: sub;margin-right: 5px">
            Login with IDer</a>
        <div style="clear:both;"></div>
        <?php
    }


    static function logRotate($text, $filename, $ext = 'log')
    {
        $text = "[" .strftime("%Y-%m-%d %H:%M:%S") . "] " . $text . "\n";

        // add basepath
        $filename = IDER_PLUGIN_DIR . 'logs/' . $filename;

        // add the point
        $ext = '.' . $ext;

        if (!file_exists($filename . $ext)) {
            touch($filename . $ext);
            chmod($filename . $ext, 0755);
        }

        // 2 mb
        if (filesize($filename . $ext) > 5 * 1024 * 1024) {

            // search for available filename
            $n = 1;
            while (file_exists($filename . '.' . $n . $ext)) {
                $n++;
            }

            rename($filename . $ext, $filename . '.' . $n . $ext);

            touch($filename . $ext);
            chmod($filename . $ext, 0755);
        }


        if (!is_writable($filename . $ext)) {
            error_log("Cannot open log file ($filename$ext)");
        }

        if (!$handle = fopen($filename . $ext, 'a')) {
            echo "Cannot open file ($filename$ext)";
        }

        if (fwrite($handle, $text) === FALSE) {
            echo "Cannot write to file ($filename$ext)";
        }

        fclose($handle);
    }
}