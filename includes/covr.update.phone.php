<?php

class covr_update_phone {

    static function init() {
        add_action('wp_ajax_update_phone', array(__CLASS__, 'update_phone'));
    }

    static function update_phone() {
        check_ajax_referer('update_phone', 'security');

        $covr_phone = sanitize_text_field($_POST['covr_phone']);
        $user_id = get_current_user_id();
        $covr_verification_code = uniqid();

        delete_user_meta($user_id, 'covr_verification_code');
        update_user_meta($user_id, 'covr_verification_code', $covr_verification_code);

        if (is_wp_error($user_id)) {
            // There was an error, probably that user doesn't exist.
            echo '{ "problem": "' . $covr_phone . '" } ';
            wp_die();
            return;
        }

        $covr_seed = get_option('covr_seed');
        if ($covr_seed) {
            update_user_meta($user_id, 'covr_phone_status', "Phone is NOT stored.");
            update_user_meta($user_id, 'covr_new_phone', $covr_phone);

            $json_headers = array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json;charset=utf-8',
            );

            $current_user = wp_get_current_user();
            $covr_params = array();
            $covr_params['seed'] = $covr_seed;
            $covr_params['phone'] = $covr_phone;
            $covr_params['username'] = $current_user->user_login;
            $covr_params['email'] = $current_user->user_email;
            $covr_params['verificationCode'] = $covr_verification_code;
            $json = json_encode($covr_params);

            $covr_api_request = COVR_REST_DOMAIN . '/merchant/consumers';

            $api_response = wp_remote_request(
                $covr_api_request, array(
                    'headers' => $json_headers,
                    'method' => 'PUT',
                    'timeout' => COVR_POST_TIMEOUT,
                    'body' => $json)
            );

            $covr_response_body = $api_response['body'];
            $decoded_body = json_decode($covr_response_body, true);

            if (wp_remote_retrieve_response_code($api_response) !== 200) {
                $decoded = json_decode($covr_response_body, true);
                if ($decoded['message'] && strpos($decoded['message'], 'We sent request on change phone number to') !== false) {
                    update_user_meta($user_id, 'covr_phone_status', "Phone is NOT stored. Waiting validation from previous consumer.");
                    echo '{ "problem": "' . $decoded['message'] . '", "shouldChangePhone": false } ';
                } else {
                    update_user_meta($user_id, 'covr_phone_status', "Phone is NOT stored. Problem: " + $decoded['message']);
                    echo '{ "problem": "' . $decoded['message'] . '" } ';
                }
                wp_die();
                return;
            } else {
                update_user_meta($user_id, 'covr_phone', $covr_phone);
                update_user_meta($user_id, 'covr_phone_status', "Phone is stored, consumer-merchant is waiting for approval.");
            }
        } else {
            update_user_meta($user_id, 'covr_phone', $covr_phone);
            update_user_meta($user_id, 'covr_phone_status', "Phone is stored, but merchant is not specified by admin.");
        }

        echo '{ "covr_phone": "' . $covr_phone . '", "covr_verification_code": "' . $covr_verification_code . '", "covr_consumer_id": "' . $decoded_body['id'] . '" }';
        wp_die();
    }

}
