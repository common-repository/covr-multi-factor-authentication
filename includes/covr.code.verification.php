<?php

class covr_code_verification {

    static function init() {
        add_action('wp_ajax_nopriv_code_verification', array(__CLASS__, 'code_verification'));
        add_action('wp_ajax_code_verification', array(__CLASS__, 'code_verification'));
    }

    static function code_verification() {
        $verification_code = $_GET['verification_code'];
        $username = $_GET['username'];

        $user = get_user_by('login', $username);
        if (is_wp_error($user)) {
            // There was an error, probably that user doesn't exist.
            echo '{ "verified": false }';
            wp_die();
            return;
        }
        
        $user_code = get_user_meta($user->ID, "covr_verification_code", TRUE);
        if (strcmp($user_code, $verification_code) == 0) {
            $new_phone = get_user_meta($user->ID, 'covr_new_phone', true);
            delete_user_meta($user->ID, 'covr_new_phone');
            update_user_meta($user->ID, 'covr_phone', $new_phone);
            
            echo '{ "verified": true }';
            wp_die();
            return;
        }
        
        delete_user_meta($user_id, 'covr_new_phone');
        echo '{ "verified": false }';
        wp_die();
    }

}
