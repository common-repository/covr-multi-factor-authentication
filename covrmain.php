<?php

/*
  Plugin Name: Covr Multi Factor Authentication
  Description: Covr two-factor authentication is the best way to securely log into your wordpress blog.
  Version: 1.4
  Author: COVR
  Author URI: https://www.covrsecurity.com/
 */

/*
  Copyright 2018  COVR  (email: info@covrsecurity.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//Define covr constants
defined('COVR_REST_DOMAIN') || define('COVR_REST_DOMAIN', 'https://publicapi.covrsecurity.com/api/');
defined('COVR_DOMAIN') || define('COVR_DOMAIN', 'https://publicapi.covrsecurity.com/');
defined('COVR_ABS_PATH') || define('COVR_ABS_PATH', plugin_dir_path(__FILE__));
defined('COVR_ASSETS_PATH') || define('COVR_ASSETS_PATH', plugin_dir_path(__FILE__) . 'assets/');
defined('COVR_IMG_PATH') || define('COVR_IMG_PATH', plugin_dir_path(__FILE__) . 'assets/img/');
defined('COVR_POST_TIMEOUT') || define('COVR_POST_TIMEOUT', 60);

# Admin interfaces
if (is_admin()) {
    require_once(COVR_ABS_PATH . 'includes/covr.admin.php');

    require_once(COVR_ABS_PATH . 'includes/covr.update.phone.php');
    covr_update_phone::init();

    require_once(COVR_ABS_PATH . 'includes/covr.update.actions.php');
    covr_update_actions::init();

    require_once(COVR_ABS_PATH . 'includes/covr.register.merchant.php');
    covr_register_merchant::init();

    require_once(COVR_ABS_PATH . 'includes/covr.specify.seed.php');
    covr_specify_seed::init();
}

require_once(COVR_ABS_PATH . 'includes/covr.code.verification.php');
covr_code_verification::init();
require_once(COVR_ABS_PATH . 'includes/covr.phone.verification.php');
covr_phone_verification::init();

require_once(COVR_ABS_PATH . 'includes/covr.button.shortcode.php');
covr_button_shortcode::init();

require_once(COVR_ABS_PATH . 'includes/covr.login.request.php');
covr_login_request::init();

require_once(COVR_ABS_PATH . 'includes/covr.login.status.php');
covr_login_status::init();

require_once(COVR_ABS_PATH . 'includes/covr.phone.status.php');
covr_phone_status::init();

register_uninstall_hook(__FILE__, 'covr_uninstall');
register_activation_hook(__FILE__, 'covr_install_fields');

/**
 * Function to create api url
 *
 * @param $path
 *
 * @return string
 */
function covr_create_api_url($path) {
	if (preg_match('/^\//', $path)) {
		$path = preg_replace('/^\//', '', $path);
	}

	return COVR_REST_DOMAIN . $path;
}

function covr_install_fields() {
    if (!get_option('covr_seed')) {
        update_option('covr_success', '');
        update_option('covr_url_logo', '');
        update_option('covr_login_button', '');
        update_option('covr_force_login', '');
        update_option('covr_action', '');
        update_option('covr_system_info', '');
        update_option('covr_actions_list', '');
        update_option('covr_seed', '');
        update_option('covr_right_seed', '');
    }
    if(!get_option('covr_saved_seed')) {
        update_option('covr_saved_seed', '');
    }
}

function covr_uninstall() {
    delete_option('covr_success');
    delete_option('covr_url_logo');
    delete_option('covr_login_button');
    delete_option('covr_force_login');
    delete_option('covr_action');
    delete_option('covr_system_info');
    delete_option('covr_actions_list');
    delete_option('covr_seed');
    delete_option('covr_right_seed');
}

?>