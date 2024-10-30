<?php

class covr_login_request {

    static function init() {
        add_action('wp_ajax_nopriv_covr_login', array(__CLASS__, 'nopriv_login_request'));
        add_action('wp_ajax_covr_login', array(__CLASS__, 'login_request'));
    }

    static function login_request() {
        echo '{ '
        . '"status": "ACCEPTED" '
        . '}';
        wp_die();
    }

    static function nopriv_login_request() {
        $covr_username = sanitize_user($_POST['covr_username']);
        if (!$covr_username) {
            echo '{ "problem": "Username is not specified."}';
            wp_die();
            return;
        }

        $wp_user = get_user_by('login', $covr_username);
        if (!$wp_user) {
            $wp_user = get_user_by('email', $covr_username);
        }
        if (!$wp_user) {
            echo '{ "problem": "Username was not found."}';
            wp_die();
            return;
        }

        $json_headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json;charset=utf-8',
        );

        $covr_server_params = array();
        $covr_server_params['action'] = get_option('covr_action');
        $covr_server_params['username'] = $wp_user->user_login;
        $json = json_encode($covr_server_params);

        $covr_api_request = COVR_REST_DOMAIN . '/merchant/requests';
        $api_response = wp_remote_post(
                $covr_api_request, array(
                'headers' => $json_headers,
                'timeout' => COVR_POST_TIMEOUT,
                'body' => $json)
        );

        if (wp_remote_retrieve_response_code($api_response) !== 200) {
            echo '{ "problem": "Problem with creation request via COVR. It seems COVR is not configured properly. Please check it or use other user.", "wp_login": true, "debug": ' . $api_response['body'] . ' }';
            wp_die();
            return;
        }
        if(in_array("administrator", $wp_user->roles)) {
            $redirect_url = admin_url( 'index.php' );
        } else {
            $redirect_url = admin_url( 'profile.php' );
        }

        $covr_response_body = $api_response['body'];
        $decoded = json_decode($covr_response_body, true);

        echo '{ '
        . '"request" : ' . $decoded['id'] . ', '
        . '"expiryAt": ' . $decoded['expiryAt'] . ', '
        . '"status": "' . $decoded['status'] . '", '
        . '"url": "' . $redirect_url .'" '
        . '}';
        wp_die();
    }

}
