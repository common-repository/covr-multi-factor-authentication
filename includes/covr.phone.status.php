<?php

class covr_phone_status {

    static function init() {
        add_action('wp_ajax_phone_status', array(__CLASS__, 'phone_status'));
        add_action('wp_ajax_nopriv_phone_status', array(__CLASS__, 'phone_status'));
    }

    static function phone_status() {
        
        $covr_consumer_id = $_POST['covr_consumer_id'];
        if (!$covr_consumer_id) {
            echo '{ "problem": "Consumer id is not specified."}';
            wp_die();
            return;
        }

        $json_headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json;charset=utf-8',
        );

        $covr_api_request = COVR_REST_DOMAIN . '/merchant/consumers/' . $covr_consumer_id;
        $api_response = wp_remote_get(
            $covr_api_request, array(
                'timeout' => COVR_POST_TIMEOUT,
                'headers' => $json_headers)
        );
        if (wp_remote_retrieve_response_code($api_response) !== 200) {
            echo '{ "problem": "Problem with creation request via COVR."}';
            wp_die();
            return;
        }
        $covr_response_body = $api_response['body'];
        $decoded = json_decode($covr_response_body, true);

        if (strcmp($decoded['status'], 'ACTIVE') == 0) {
            $user_id = get_current_user_id();
            $covr_seed = get_option('covr_seed');
            update_user_meta($user_id, "covr_is_phone_valid_for_seed", $covr_seed);
            update_user_meta($user_id, 'covr_phone_status', "Phone was accepted by consumer.");

            echo '{ '
                . '"request" : ' . $decoded['id'] . ', '
                . '"status": "' . $decoded['status'] . '" '
                . '}';
            wp_die();
            return;
        }

        if (strcmp($decoded['status'], 'INACTIVE') == 0) {
            echo '{ '
                . '"request" : ' . $decoded['id'] . ', '
                . '"status": "' . $decoded['status'] . '", '
                . '"problem": "Your request was rejected."'
                . '}';
            wp_die();
            return;
        }

        if (strcmp($decoded['status'], 'EXPIRED') == 0) {
            echo '{ '
                . '"request" : ' . $decoded['id'] . ', '
                . '"status": "' . $decoded['status'] . '", '
                . '"problem": "Your request was expired."'
                . '}';
            wp_die();
            return;
        }

        echo '{ '
            . '"request" : ' . $decoded['id'] . ', '
            . '"status": "' . $decoded['status'] . '" '
            . '}';
        wp_die();
    }

}
