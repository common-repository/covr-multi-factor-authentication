<?php
add_action('admin_menu', 'covr_add_menu_pages');
add_action('update_option_covr_seed', 'covr_save_system_info');
add_action('admin_enqueue_scripts', 'admin_covr_script');
add_action('admin_enqueue_scripts', 'admin_covr_style');

function covr_add_menu_pages() {
    add_menu_page('COVR', 'COVR', 'read', 'covr_menu_page', 'covr_menu_page_function', plugins_url('assets/icons/covr-wp-16x16.png', dirname(__FILE__)));
    add_action('admin_init', 'register_covr_settings');
}

function admin_covr_script() {
    $variables = array(
        'ajax_url' => admin_url('admin-ajax.php')
    );
    echo('<script type="text/javascript">window.wp_data = ' . json_encode($variables) . ';</script>');
    
    wp_register_script('cover_wp_services_js', plugins_url('assets/js/covr.wp.services.js', dirname(__FILE__)));
    wp_print_scripts('cover_wp_services_js');
}

function admin_covr_style() {
    wp_enqueue_style('cover_covr_theme', plugins_url('assets/css/covr.theme.css', dirname(__FILE__)));

    wp_enqueue_style('wpb-fa', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css');
    add_action('wp_enqueue_scripts', 'wpb_load_fa');
}

function register_covr_settings() {
    register_setting('covr-settings-group', 'covr_url_logo');
    register_setting('covr-settings-group', 'covr_login_button');
    register_setting('covr-settings-group', 'covr_force_login');
    register_setting('covr-settings-group', 'covr_action');
    register_setting('covr-settings-group', 'covr_seed');
}

function covr_save_system_info() {
    $covr_seed = get_option('covr_seed');
    if ($covr_seed) {
        $json_headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json;charset=utf-8',
        );

        $covr_api_request = COVR_REST_DOMAIN . 'merchant/' . get_option('covr_seed') . '/parameters';
        $covr_server_params = array();
        $covr_server_params['SERVER'] = $_SERVER["SERVER_SOFTWARE"];
        $covr_server_params['OS'] = php_uname('s');
        $covr_server_params['SERVER_ADDR'] = $_SERVER['SERVER_ADDR'];
        $covr_server_params['SERVER_PORT'] = $_SERVER['SERVER_PORT'];
        $covr_server_params['CALLBACK_URL'] = admin_url('admin-ajax.php');
        if (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") {
            $covr_server_params['SERVER_PROTOCOL'] = "HTTPS";
        } else {
            $covr_server_params['SERVER_PROTOCOL'] = "HTTP";
        }
        $covr_server_params['TYPE'] = 'WORDPRESS';
        $json = json_encode($covr_server_params);

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
            $covr_system_info = 'Wrong COVR seed! Authorization via COVR will not work!';
        } else {
            $covr_merchant = $api_response['body'];
            $decoded = json_decode($covr_merchant, true);

            update_option('covr_right_seed', 1);
            update_option('covr_success', 1);

            $covr_actions = $decoded['actions'];
            update_option('covr_actions_list', $covr_actions);
            if (count($covr_actions) > 0) {
                update_option('covr_action', strval($covr_actions[0]['id']));
            }

            $covr_system_info = $decoded['parameters'];
        }
        update_option('covr_system_info', $covr_system_info);
    } else {
        update_option('covr_success', 0);
    }
}

function covr_sign_up_menu() {
    $current_user = wp_get_current_user();
    $return_val = '
    <div class="wrap">
        <h2>Sign Up with COVR</h2>
        <div class="covr-sign-up-container">
        <button type="button" id="uSafeLoginBtn" class="covr-button-green">Sign Up</button>
            <script>
                USafe.init({
                    "addButtonId" : "uSafeAddBtn",
                    "userName": "' . $current_user->user_login . '",
                    "loginButtonId" : "uSafeLoginBtn",
                    "merchantSeed" : "' . get_option('covr_seed') . '",
                    "action" : "' . get_option('covr_action') . '",
                    "timeout" : "3",
                    "siteDemoUrl": "' . get_option('covr_url_logo') . '"
                });
            </script></div>
        </div>';
    echo $return_val;
}

function covr_menu_page_function() {
    ?>
    <div class="wrap">
        <h1>Covr Security for Wordpress</h1>
        <p>Thank you for installing Covr Security. There are only three simple steps you need to complete for enabling two factor authentication on your wordpress site.</p>
        <ol>
            <li>You will need to download and install the Covr Security App from the <a href="https://itunes.apple.com/us/app/covr-security/id1116921713" target="_blank">AppStore</a> or <a href="https://play.google.com/store/apps/details?id=com.covrsecurity.covr" target="_blank">Google Play</a></li>
            <?php
//            phpinfo();
            if (current_user_can("edit_plugins")) {
                ?>
                <li>Create a Covr account for your website</li>
                <?php
            }
            ?>
            <li>Register your mobile phone number with our service</li>
        </ol>
        <div id="message" class="updated notice is-dismissible" style="display: none">
        </div>
        <?php
        $user_id = get_current_user_id();
        $covr_phone = get_user_meta($user_id, 'covr_phone', true);
        $covr_is_phone_valid_for_seed = get_user_meta($user_id, 'covr_is_phone_valid_for_seed', true);
        $covr_seed = get_option('covr_seed');
        $covr_saved_seed = get_option('covr_saved_seed');

        $covr_phone_valid = FALSE;
        if (empty($covr_seed)) {
            update_user_meta($user_id, 'covr_phone_status', "Merchant is not specified by admin.");
        } else if (strcmp($covr_is_phone_valid_for_seed, $covr_seed) == 0) {
            $covr_phone_valid = TRUE;
            update_user_meta($user_id, 'covr_phone_status', "Phone is stored and verified.");
        } else {
            update_user_meta($user_id, 'covr_phone_status', "Phone is not valid.");
        }

        $covr_phone_status = get_user_meta($user_id, 'covr_phone_status', true);

        if (!$covr_seed) {
            ?>
            <!--<div class="update-nag">Please specify merchant below.</div>-->
            <?php
        } else {
            $update_phone_nonce = wp_create_nonce("update_phone");
            if (!$covr_phone) {
                ?>
                <br/>
                <h2>Phone settings</h2>
                <div>To continue, you will need to specify your phone number.</div>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="covr_phone">Phone</label>
                        </th>
                        <td>
                            <div id='covr_p_error'></div>

                            <div id="covr_phone_input" class="no-phone-input">
                                +<input class="regular-text" placeholder="123456789123" type="text" name="covr_phone" id="covr_phone" onkeypress="return Covr.checkPhoneValidity(event)" value="<?php echo ltrim($covr_phone,'+'); ?>" pattern="/^\+?\d{10,12}$/"/>
                            </div>
                            <div id="spinner_load" class="covr-wp-spinner spinner is-active" style="display: none"></div>
                            <p id="covr_p_accept" class="description" style="display: none">A request has been sent to your Covr app, please accept it on your mobile phone or Add a new connection if you have received an SMS.</p>
                            <p id="phone_status_info" class="description">Enter your mobile number with country code to securely link the COVR mobile application with your WordPress site.</p>
                        </td>
                    </tr>
                </table>
                <input type="button" class="button-primary" id="covr_phone_button"  value="Update phone" onclick="Covr.updatePhone({'phone': jQuery('#covr_phone').val()}, '<?php echo $update_phone_nonce ?>');"/>
                <?php
            } else {
                ?> 
                <br/>
                <h2>Phone settings</h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="covr_phone">Phone</label>
                        </th>
                        <td>
                            <div id='covr_p_error'></div>
                            <p id="phoneReadOnly">
                                <b><?php echo $covr_phone; ?></b>
                            </p>

                            <div id="covr_phone_input">
                                <p id="phoneUpdate" style="display: none">
                                    +<input class="regular-text" placeholder="123456789123" type="text" name="covr_phone" id="covr_phone" onkeypress="return Covr.checkPhoneValidity(event)" value="<?php echo ltrim($covr_phone,'+'); ?>" pattern=""/^\+?\d{10,12}$/""/>
                                           <input type="button" class="button-primary" id="covr_phone_button" value="Update phone" onclick="Covr.updatePhone({'phone': jQuery('#covr_phone').val()}, '<?php echo $update_phone_nonce ?>');"/>
                                </p>
                            </div>
                            <div id="spinner_load" class="covr-wp-spinner spinner is-active" style="display: none"></div>
                            <p id="covr_p_accept" class="description" style="display: none">A request has been sent to your Covr app, please accept it on your mobile phone or Add a new connection if you have received an SMS.</p>
                            <?php
                            if ($covr_phone_valid) {
                                ?>
                                <p id="phone_status_info" class="description">The mobile number is associated with a user's account for the current merchant. If your mobile number is changed you can associate a new mobile number with your account <a href="#" onclick="Covr.changePhone()">here</a>.</p>
                                
                                <?php
                            } else {
                                ?>
                                <p id="phone_status_info" class="description">Your mobile number is not associated with a user's account for the current merchant. Click <a href="#" onclick="Covr.resendPhone('<?php echo $covr_phone; ?>', '<?php echo $update_phone_nonce ?>')">here</a> to resend a validation request and approve it in your Covr mobile app or <a href="#" onclick="Covr.changePhone()">change your mobile number</a>.</p>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                <?php
            }
        }
        ?>

        <?php
        if (current_user_can("edit_plugins")) {
            ?>
            <h2 class="covr-account-settings">Account Settings</h2>
            <?php
            if (!$covr_seed) {
                $current_user = wp_get_current_user();
                $user_email = $current_user->user_email;
                $user_firstname = $current_user->user_firstname;
                $user_lastname = $current_user->user_lastname;
                $wp_name = get_bloginfo('name');
                $wp_description = get_bloginfo('description');
                ?>
                <div>Please specify your Covr account token <a style="cursor: pointer" id="registerNewMerchant" onclick="Covr.showMerchantSpecificationForm('covr_step2_merchant', '<?php echo wp_create_nonce("specify_seed") ?>', '<?php echo $covr_saved_seed; ?>');">here</a> or create a new Covr account below.</div>
                <br>
                <input type="button" class="button-primary" onclick="Covr.showMerchantRegistrationForm('covr_step2_merchant', '<?php echo $user_email; ?>', '<?php echo $user_firstname; ?>', '<?php echo $user_lastname; ?>', '<?php echo $wp_name ?>', '<?php echo $wp_description ?>', '<?php echo wp_create_nonce("covr_register_merchant") ?>');" value="Create Account"/>
                <input type="button" class="button-primary" onclick="Covr.showMerchantSpecificationForm('covr_step2_merchant', '<?php echo wp_create_nonce("specify_seed") ?>', '<?php echo $covr_saved_seed; ?>');" value="Existing Account"/>
                <br>
                <br>
                <div id="covr_step2_merchant"></div>
                <?php
            } else {
                ?>
                <div id='covr_error'></div>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('covr-settings-group');
                    ?>
                    <table class = "form-table">
                        <tr valign = "top">
                            <th scope = "row">Token</th>
                            <td>
                                <?php
                                if (!$covr_seed || !get_option('covr_right_seed')) {
                                    ?>
                                    <input class="regular-text" type="text" name="covr_seed" value="<?php echo $covr_seed; ?>" />
                                    <?php
                                } else {
                                    ?>
                                    <div>
                                        <b><?php echo $covr_seed; ?></b>
                                        <input class="regular-text" type="hidden" name="covr_seed" value="<?php echo $covr_seed; ?>" />
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">COVR Actions</th>
                            <td>
                                <?php
                                $selected_action = get_option('covr_action');
                                $actions_list = get_option('covr_actions_list');
                                ?>
                                <select name="covr_action" class="regular-text">
                                    <?php
                                    if ($actions_list && is_array($actions_list)) {
                                        foreach ($actions_list as $action) {
                                            ?>
                                            <option value="<?php echo $action['id']; ?>" <?php if ($action['id'] == $selected_action) echo 'selected="selected"'; ?>><?php echo $action['message']; ?></option>
                                            <?php
                                        }
                                    }
                                    ?>
                                </select>
                                <!--<p class="description">To refresh list of actions click <a href="#" style="margin-top: 10px;" onclick="Covr.updateActions();">here</a>.</p>-->
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Enable COVR</th>
                            <td><input type="checkbox" name="covr_login_button" value="1" <?php checked('1', get_option('covr_login_button')); ?> /></td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">Force users to login with COVR</th>
                            <td><input type="checkbox" name="covr_force_login" value="1" <?php checked('1', get_option('covr_force_login')); ?> /></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                    </p>
                </form>
                <?php
            }
        }
        ?>
    </div>
<?php } ?>