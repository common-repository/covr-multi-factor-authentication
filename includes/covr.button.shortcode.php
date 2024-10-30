<?php

class covr_button_shortcode {

    static function init() {
        add_action('init', array(__CLASS__, 'register_covr_script'));
        add_action('wp_head', array(__CLASS__, 'print_covr_script'));

        if (get_option('covr_success') && (get_option('covr_force_login') || get_option('covr_login_button'))) {
            add_action('login_enqueue_scripts', array(__CLASS__, 'print_covr_script'));
            add_filter('login_head', array(__CLASS__, 'covr_head'));
            add_filter('login_footer', array(__CLASS__, 'covr_footer'));
            wp_enqueue_script("jquery");
        }
    }

    static function covr_footer($attrs) {
        echo '</div>';
    }

    static function covr_head($attrs) {
        echo '<div class="covr-login-container"><div id="covr-login" class="covr-login"><h1><img src="'
        . plugins_url('assets/icons/covr-logo.svg', dirname(__FILE__))
        . '" alt="COVR security"/></h1>'
        . '<div id="covr_error"></div>'
        . '<form>'
        . '<p id="covr_login_area"><label for="covr_login">Username or Email<br><input type="text" size="20" value="" class="input" id="covr_login" name="log"></label></p>'
        . '<p id="covr_login_button_area" class="submit"><input type="button" value="Log In via Covr" class="button button-primary button-large" id="covr-submit" name="covr-submit" onclick="Covr.login({\'username\': jQuery(\'#covr_login\').val()})"/></p>'
        . '<p id="covr_waiting_area" style="display: none">Waiting for your approval on mobile. Request will expire at:<br/><span id="covr_request_expire_at"></span></p>'
        . '</form>'
        . '<input id="covr_request_id" type="hidden"/>'
        . '</div>';
    }

    static function register_covr_script() {
        wp_register_script('cover_wp_services_js', plugins_url('assets/js/covr.wp.services.js', dirname(__FILE__)));
        wp_register_style('cover_theme_css', plugins_url('assets/css/covr.theme.css', dirname(__FILE__)));
        if (get_option('covr_success') && get_option('covr_force_login')) {
            wp_register_style('hide_login_css', plugins_url('assets/css/covr.hide.login.css', dirname(__FILE__)));
        }
    }

    static function print_covr_script() {
        $variables = array(
            'ajax_url' => admin_url('admin-ajax.php')
        );
        echo('<script type="text/javascript">window.wp_data = ' . json_encode($variables) . ';</script>');

        wp_print_scripts('cover_wp_services_js');
        wp_print_styles('cover_theme_css');
        if (get_option('covr_success') && get_option('covr_force_login')) {
            wp_print_styles('hide_login_css');
        }
    }

}
