<?php

class covr_register_merchant {

    static function init() {
        add_action('wp_ajax_covr_register_merchant', array(__CLASS__, 'register_merchant'));
    }

    static function register_merchant() {
        check_ajax_referer('covr_register_merchant', 'security');
        
        $json_headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json;charset=utf-8',
        );

        $covr_server_params = array();
        $covr_server_params['email'] = sanitize_email($_POST['covr_email']);
        $covr_server_params['firstname'] = sanitize_text_field($_POST['covr_firstname']);
        $covr_server_params['lastname'] = sanitize_text_field($_POST['covr_lastname']);
        $covr_server_params['name'] = sanitize_text_field($_POST['covr_name']);
        $covr_server_params['fullName'] = sanitize_text_field($_POST['covr_fullname']);
        $covr_server_params['websiteName'] = sanitize_text_field(get_bloginfo('wpurl'));
        $covr_server_params['websiteUrl'] = sanitize_text_field(get_bloginfo('url'));
        $user_id = get_current_user_id();
        $covr_server_params['phone'] = get_user_meta($user_id, 'covr_phone', true);
        $json = json_encode($covr_server_params);

        $covr_api_request = COVR_REST_DOMAIN . 'merchant/register';
        $api_response = wp_remote_post(
                $covr_api_request, array(
                'headers' => $json_headers,
                'timeout' => COVR_POST_TIMEOUT,
                'body' => $json)
        );

        if (wp_remote_retrieve_response_code($api_response) == 200) {
            $covr_response_body = $api_response['body'];
            $decoded = json_decode($covr_response_body, true);

            if ($decoded['seed']) {
                $covr_seed = $decoded['seed'];
                update_option('covr_seed', $covr_seed);
                update_option('covr_saved_seed', $covr_seed);
                update_option('covr_right_seed', 1);
                update_option('covr_success', 1);

                $covr_actions = $decoded['actions'];
                update_option('covr_actions_list', $covr_actions);
                if (count($covr_actions) > 0) {
                    update_option('covr_action', strval($covr_actions[0]['id']));
                }

                echo '{ "covr_seed": "' . $covr_seed . '" } ';
            } else {
                echo '{ "problem": "Sorry! Merchant was not registered." } ';
            }
        } else if (is_wp_error($api_response)) {
            $error_string = $api_response->get_error_message();
            echo '{ "problem": "Sorry! Merchant was not registered: ' . $error_string . '" } ';
        } else {
            $covr_response_body = $api_response['body'];
            $decoded = json_decode($covr_response_body, true);

            if ($decoded['message']) {
                if (strpos($decoded['message'], 'duplicate key value violates unique constraint "merchants_name_key"') !== false) {
                    echo '{ "problem": "Sorry! Merchant with specified name exists. Please change name or contact with admins." } ';
                } else {
                    $covr_saved_seed = get_option('covr_saved_seed');
                    if($covr_saved_seed) {
                        $link = 'Click <a style=\"cursor: pointer\" id=\"registerNewMerchant\" onclick=\"Covr.showMerchantSpecificationForm(\'covr_step2_merchant\', \'' . wp_create_nonce("specify_seed") .'\' ,\'' . $covr_saved_seed . '\' );\">here</a> to reactivate your account.';
                    } else {
                        $link = 'Please submit a ticket at <a style=\"cursor: pointer\" href=\"https://support.covrsecurity.com/hc/en-us\" target=\"_blank\">Covr Support</a> to help you with your registration.';
                    }
                    echo '{ "problem": "Sorry! Merchant was not registered. Website Name is already in use. ' . $link . '" } ';
                }
            } else {
                echo '{ "problem": "Sorry! Merchant was not registered." } ';
            }
        }
        wp_die();
    }

}
