<?php

/**
 * Description of covr
 *
 * @author sergeizenevich
 */
class covr_login_status {

    static function init() {
        add_action('wp_ajax_nopriv_covr_login_status', array(__CLASS__, 'login_status'));
    }

    static function login_status() {
        $covr_request = intval($_POST['covr_request']);
        if (!$covr_request) {
            echo '{ "problem": "Request is not specified."}';
            wp_die();
            return;
        }

        $json_headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json;charset=utf-8',
        );

        $covr_api_request = COVR_REST_DOMAIN . '/merchant/requests/' . $covr_request;
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

        if (strcmp($decoded['status'], 'ACCEPTED') == 0) {
            $wp_user = get_user_by('login', $decoded['username']);
            if (!$wp_user) {
                echo '{ "problem": "Username was not found."}';
                wp_die();
                return;
            }

            wp_set_current_user($wp_user->id, $wp_user->user_login);
            wp_set_auth_cookie($wp_user->id);
            do_action('wp_login', $wp_user->id->user_login);
            if(in_array("administrator", $wp_user->roles)) {
                $redirect_url = admin_url( 'index.php' );
            } else {
                $redirect_url = admin_url( 'profile.php' );
            }

            echo '{ '
            . '"request" : ' . $decoded['id'] . ', '
            . '"expiryAt": ' . $decoded['expiryAt'] . ', '
            . '"status": "' . $decoded['status'] . '", '
            . '"url": "' . $redirect_url .'" '
            . '}';
            wp_die();
            return;
        }

        if (strcmp($decoded['status'], 'REJECTED') == 0) {
            echo '{ '
            . '"request" : ' . $decoded['id'] . ', '
            . '"expiryAt": ' . $decoded['expiryAt'] . ', '
            . '"status": "' . $decoded['status'] . '", '
            . '"problem": "Your request was rejected."'
            . '}';
            wp_die();
            return;
        }

        if (strcmp($decoded['status'], 'EXPIRED') == 0) {
            echo '{ '
            . '"request" : ' . $decoded['id'] . ', '
            . '"expiryAt": ' . $decoded['expiryAt'] . ', '
            . '"status": "' . $decoded['status'] . '", '
            . '"problem": "Your request was expired."'
            . '}';
            wp_die();
            return;
        }

        echo '{ '
        . '"request" : ' . $decoded['id'] . ', '
        . '"expiryAt": ' . $decoded['expiryAt'] . ', '
        . '"status": "' . $decoded['status'] . '" '
        . '}';
        wp_die();
    }

}
