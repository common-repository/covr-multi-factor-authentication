<?php

/**
 * Description of covr_phone_verification
 *
 * @author sergeizenevich
 */
class covr_phone_verification {

    static function init() {
        add_action('wp_ajax_nopriv_phone_verification', array(__CLASS__, 'phone_verification'));
        add_action('wp_ajax_phone_verification', array(__CLASS__, 'phone_verification'));
    }

    static function phone_verification() {
        $seed = $_GET['seed'];
        $username = $_GET['username'];

        $user = get_user_by('login', $username);
        if (is_wp_error($user)) {
            // There was an error, probably that user doesn't exist.
            echo '{ "verified": false }';
            wp_die();
            return;
        }

        update_user_meta($user->ID, "covr_is_phone_valid_for_seed", $seed);
        update_user_meta($user->ID, 'covr_phone_status', "Phone was accepted by consumer.");
        
        echo '{ "verified": true }';
        wp_die();
    }

}
