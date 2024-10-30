<?php

class covr_specify_seed {

    static function init() {
        add_action('wp_ajax_specify_seed', array(__CLASS__, 'specify_seed'));
    }

    static function specify_seed() {
        check_ajax_referer('specify_seed', 'security');
        
        $json_headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json;charset=utf-8',
        );

        $covr_server_params = array();
        $covr_server_params['SERVER'] = $_SERVER["SERVER_SOFTWARE"];
        $covr_server_params['OS'] = php_uname('s');
        $covr_server_params['IP'] = $_SERVER['SERVER_ADDR'];
        $covr_server_params['TYPE'] = 'WORDPRESS';
        $json = json_encode($covr_server_params);

        $covr_seed = sanitize_user($_POST['covr_seed']);
        $covr_api_request = COVR_REST_DOMAIN . 'merchant/' . $covr_seed . '/parameters';
        $api_response = wp_remote_post(
                $covr_api_request, array(
                'headers' => $json_headers,
                'timeout' => COVR_POST_TIMEOUT,
                'body' => $json)
        );
        if (wp_remote_retrieve_response_code($api_response) !== 200) {
            update_option('covr_action', '');
            update_option('covr_actions_list', 'No actions');
            update_option('covr_success', 0);
            update_option('covr_right_seed', 0);

            echo '{ "problem": "Wrong COVR seed! Authorization via COVR will not work!" } ';
        } else {
            $covr_response_body = $api_response['body'];
            $decoded = json_decode($covr_response_body, true);

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
        }

        wp_die();
    }

}
