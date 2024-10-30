<?php

class covr_update_actions {

    static function init() {
        add_action('wp_ajax_covr_actions_update', array(__CLASS__, 'refresh_actions'));
    }

    static function refresh_actions() {
        if (get_option('covr_seed')) {
            $json_headers = array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json;charset=utf-8',
            );

            $covr_api_request = COVR_REST_DOMAIN . 'merchant/' . get_option('covr_seed') . '/actions';
            $api_response = wp_remote_get(
                    $covr_api_request, array(
                    'headers' => $json_headers,
                    'timeout' => COVR_POST_TIMEOUT)
            );

            if (wp_remote_retrieve_response_code($api_response) == 200) {
                $covr_actions_list = $api_response['body'];
                $decoded_list = json_decode($covr_actions_list, true);

                if ($covr_actions_list && count($decoded_list) > 0) {
                    update_option('covr_actions_list', $decoded_list);
                    update_option('covr_action', strval($decoded_list[0]['id']));
                    echo json_encode($decoded_list);
                } else {
                    update_option('covr_action', '');
                    update_option('covr_actions_list', 'No actions');
                    update_option('covr_success', 0);
                    echo '[]';
                }
            }
        } else {
            echo '{ "problem": "Problem with getting actions" }';
        }
        wp_die();
    }

}
