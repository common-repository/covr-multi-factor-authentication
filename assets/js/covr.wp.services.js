(function () {
    var Covr = {};
    var ajax_action_url = window.wp_data.ajax_url;

    Covr.checkPhoneStatus = function (id, startDate) {

        var request_data = {
            'action': 'phone_status',
            'covr_consumer_id': id
        };

        jQuery.post(ajax_action_url, request_data, function (responseString) {
            var response = jQuery.parseJSON(responseString);
            if (response.status === 'REGISTERED') {
                jQuery('#covr_p_error').empty();
                jQuery('#covr_p_accept').show();
                jQuery('#phone_status_info').hide();
                if(startDate + 300000 > new Date()) {
                    window.setTimeout( function () {
                        Covr.checkPhoneStatus(response.request, startDate);
                    }, 2000);

                } else {
                    jQuery('#covr_p_error').empty();
                    var errorDiv = document.createElement('div');
                    errorDiv.innerHTML = "<div class='update-nag error-nag'>Your request was expired.</div>";
                    jQuery('#covr_p_error').append(errorDiv);
                    jQuery('#phone_status_info').show();
                    jQuery('.no-phone-input').show();
                    jQuery('#covr_p_accept').hide();
                    jQuery('#spinner_load').hide();
                    jQuery('#covr_phone_button').prop('disabled', false);
                    if (!response.shouldChangePhone) {
                        jQuery("#phoneReadOnly").show();
                        jQuery("#phoneUpdate").hide();
                    }
                }

            } else if (response.problem) {
                jQuery('#covr_p_error').empty();
                var errorDiv = document.createElement('div');
                errorDiv.innerHTML = "<div class='update-nag error-nag'>" + response.problem + "</div>";
                jQuery('#covr_p_error').append(errorDiv);
                jQuery('#phone_status_info').show();
                jQuery('#covr_p_accept').hide();
                jQuery('#spinner_load').hide();
            }else if (response.status = 'ACTIVE') {
                location.reload();
            } else {
                alert("UNKNOWN");
                jQuery('#covr_p_error').empty();
                var errorDiv = document.createElement('div');
                errorDiv.innerHTML = "<div class='update-nag error-nag'>" + response.problem + "</div>";
                jQuery('#covr_p_error').append(errorDiv);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            jQuery('#covr_p_error').append(Covr.buildErrorDiv(textStatus, errorThrown));
        });
    };

    Covr.updatePhone = function (data, nonce) {
        jQuery('#covr_p_error').empty();

        // var pattern = "^[0-9-()+]{3,20}$";
        var regex = new RegExp('^[0-9-()+]{3,20}$');
        if (!regex.test(data.phone)) {
            var errorDiv = document.createElement('div');
            errorDiv.innerHTML = "<div class='update-nag error-nag'>Phone number is not valid.</div>";
            jQuery('#covr_p_error').append(errorDiv);
            return;
        }
        if(data.phone.indexOf('+') == -1) {
            data.phone  = '+' + data.phone;
        }

        var request_data = {
            'action': 'update_phone',
            'covr_phone': data.phone || '',
            'security': nonce
        };
        
        // jQuery('#covr_phone').prop('disabled', true);
        jQuery('#spinner_load').show();
        jQuery('#covr_phone_input').hide();
        jQuery('#covr_phone_button').prop('disabled', true);
        jQuery.post(ajax_action_url, request_data, function (responseString) {
            var response = jQuery.parseJSON(responseString);
            if (response.problem) {
                var errorDiv = document.createElement('div');
                errorDiv.innerHTML = "<div class='update-nag error-nag'>" + response.problem + "</div>";
                jQuery('#covr_p_error').append(errorDiv);
                jQuery('#spinner_load').hide();
                jQuery('#covr_phone_input').show();
                jQuery('#covr_phone_button').prop('disabled', false);

                if (!response.shouldChangePhone) {
                    jQuery("#phoneReadOnly").show();
                    jQuery("#phoneUpdate").hide();
                }
            } else {
                // location.reload();
                var startDate = new Date().getTime();
                if(response.message) {
                    var errorDiv = document.createElement('div');
                    errorDiv.innerHTML = "<div class='update-nag error-nag'>" + response.message + "</div>";
                    jQuery('#covr_p_error').append(errorDiv);
                }
                Covr.checkPhoneStatus(response.covr_consumer_id, startDate);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            jQuery('#covr_p_error').append(Covr.buildErrorDiv(textStatus, errorThrown));
            jQuery("#phoneReadOnly").show();
            jQuery("#phoneUpdate").hide();
            jQuery('#spinner_load').hide();
        }).always(function() {
            // jQuery('#covr_phone').prop('disabled', false);

            // jQuery('#spinner_load').hide();
            // jQuery('#covr_phone_input').show();
            // jQuery('#covr_phone_button').prop('disabled', false);
        });
    };

    Covr.changePhone = function () {
        jQuery("#phoneReadOnly").hide();
        jQuery("#phoneUpdate").show();
        jQuery('#covr_phone_input').show();
        jQuery('#covr_phone_button').prop('disabled', false);
    };

    Covr.resendPhone = function (phone, nonce) {
        Covr.updatePhone({'phone': phone}, nonce);
    };

    Covr.updateActions = function () {
        var request_data = {
            'action': 'covr_actions_update'
        };
        jQuery.post(ajax_action_url, request_data, function (responseString) {
            console.log(responseString);

            var response = jQuery.parseJSON(responseString);
            if (response.problem) {
                var errorDiv = document.createElement('div');
                errorDiv.innerHTML = "<div class='update-nag error-nag'>" + response.problem + "</div>";
                jQuery('#covr_error').append(errorDiv);
            } else {
                window.location.reload();
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            jQuery('#covr_error').append(Covr.buildErrorDiv(textStatus, errorThrown));
        });
    };

    Covr.showMerchantRegistrationForm = function (elementId, email, firstname, lastname, name, description, nonce) {
        var div = document.createElement('div');
        div.innerHTML = "<h4>Register your website with Covr Security</h4>" +
                "<div id='covr_m_error'></div>" +
                "<table id='merchantRegistrationTable' class='form-table'>" +
                "<tr valign='top'><th scope='row'>Website Name</th><td>" +
                "<input class='regular-text' type='text' id='covr_name' name='covr_name' value='" + name + "' maxlength='150' /></td></tr>" +
                "<tr valign='top'><th scope='row'>Site description</th><td>" +
                "<input class='regular-text' type='text' id='covr_fullname' name='covr_fullname' value='" + description + "' maxlength='150' /></td></tr>" +
                "<tr valign='top'><th scope='row'>Email</th><td>" +
                "<input class='regular-text' type='email' id='covr_email' name='covr_email' value='" + email + "' maxlength='70' /></td></tr>" +
                "<tr valign='top'><th scope='row'>First name</th><td>" +
                "<input class='regular-text' type='text' id='covr_firstname' name='covr_firstname' value='" + firstname + "' maxlength='50' /></td></tr>" +
                "<tr valign='top'><th scope='row'>Last name</th><td>" +
                "<input class='regular-text' type='text' id='covr_lastname' name='covr_lastname' value='" + lastname + "' maxlength='50' /></td></tr>" +
                "</table>" +
                "<p id='merchantRegistrationLoading' style='display:none' class='covr-wp-spinner spinner is-active'>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspCreating account on Covr Security AB...</p>" +
                "<input id='merchantRegistrationButton' type='button' class='button-primary' value='Register' onclick='Covr.registerMerchant({" +
                "\"name\": jQuery(\"#covr_name\").val()," +
                "\"fullname\": jQuery(\"#covr_fullname\").val()," +
                "\"email\": jQuery(\"#covr_email\").val()," +
                "\"firstname\": jQuery(\"#covr_firstname\").val()," +
                "\"lastname\": jQuery(\"#covr_lastname\").val()" +
                "}, \"" + nonce + "\")' />";
        jQuery('#' + elementId).empty();
        jQuery('#' + elementId).append(div);
    };

    Covr.buildErrorDiv = function (textStatus, errorThrown) {
        var errorDiv = document.createElement('div');
        errorDiv.innerHTML = "<div class='update-nag error-nag'>" +
                "Problem with merchant registration: " + textStatus + " " +
                "<a id='errorDetailsLink' href='#' onclick=\"jQuery('#errorDetails').show(); jQuery('#errorDetailsLink').hide();\">more</a>" +
                "<p id='errorDetails' style='display: none'> " + errorThrown + "</p>"
        "</div>";
        return errorDiv;
    };

    Covr.registerMerchant = function (data, nonce) {
        jQuery("#merchantRegistrationTable").hide();
        jQuery("#merchantRegistrationLoading").show();

        jQuery('#merchantRegistrationButton').prop('disabled', true);
        jQuery('#covr_m_error').empty();
        var request_data = {
            'action': 'covr_register_merchant',
            'covr_name': data.name || '',
            'covr_fullname': data.fullname || '',
            'covr_email': data.email || '',
            'covr_firstname': data.firstname || '',
            'covr_lastname': data.lastname || '',
            'security': nonce
        };
        jQuery.post(ajax_action_url, request_data, function (responseString) {
            jQuery('#merchantRegistrationButton').prop('disabled', false);
            var response = jQuery.parseJSON(responseString);
            if (response.covr_seed) {
                location.reload();
            } else {
                var errorDiv = document.createElement('div');
                if (response.problem.indexOf('error 28') !== -1) {
                    response.problem = response.problem + '. It looks like there is a problem with maximum execution time. Please copy this error and contact your host provider.';
                }
                errorDiv.innerHTML = "<div class='update-nag error-nag'>" + response.problem + "</div>";
                jQuery('#covr_m_error').append(errorDiv);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            jQuery('#covr_m_error').append(Covr.buildErrorDiv(textStatus, errorThrown));
        }).always(function () {
            jQuery("#merchantRegistrationTable").show();
            jQuery("#merchantRegistrationLoading").hide();
            jQuery('#merchantRegistrationButton').prop('disabled', false);
        });
    };
    // <p class='covr-wp-spinner spinner is-active'></p>
    Covr.showMerchantSpecificationForm = function (elementId, nonce, covr_saved_seed) {
        var div = document.createElement('div');
        var disabled = covr_saved_seed ? 'disabled' : '';
        var seed = covr_saved_seed || '';
        div.innerHTML = "<h4>Set your Covr Token</h4>" +
                "<div id='covr_error'></div>" +
                "<table id='merchantSpecificationTable' class='form-table'><tr valign='top'>" +
                "<th scope='row'>Token</th><td>" +
                "<input class='regular-text' type='text' id='covr_seed' name='covr_seed' value='" + seed + "' " + disabled  + " />" +
                "</td></tr></table>" +
                "<p id='merchantSpecificationLoading' style='display:none' class='covr-wp-spinner spinner is-active'>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspLinking your WordPress with account on Covr Security AB...</p>" +
                "<br>" +
                "<input id='merchantSpecificationButton' type='button' class='button-primary' value='Apply Token' onclick='Covr.specifyToken({" +
                "\"token\": \"\" + jQuery(\"#covr_seed\").val()" +
                "}, \"" + nonce + "\")' />";
        jQuery('#' + elementId).empty();
        jQuery('#' + elementId).append(div);
    };

    Covr.specifyToken = function (data, nonce) {
        jQuery('#merchantSpecificationButton').prop('disabled', true);
        jQuery('#merchantSpecificationTable').hide();
        jQuery('#merchantSpecificationLoading').show();

        jQuery('#covr_error').empty();
        var request_data = {
            'action': 'specify_seed',
            'covr_seed': data.token || '',
            'security': nonce
        };
        jQuery.post(ajax_action_url, request_data, function (responseString) {
            jQuery('#merchantSpecificationButton').prop('disabled', false);
            var response = jQuery.parseJSON(responseString);
            if (response.covr_seed) {
                location.reload();
            } else {
                var errorDiv = document.createElement('div');
                errorDiv.innerHTML = "<div class='update-nag error-nag'>" + response.problem + "</div>";
                jQuery('#covr_error').append(errorDiv);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            jQuery('#covr_error').append(Covr.buildErrorDiv(textStatus, errorThrown));
        }).always(function () {
            jQuery('#merchantSpecificationButton').prop('disabled', false);
            jQuery('#merchantSpecificationTable').show();
            jQuery('#merchantSpecificationLoading').hide();
        });
    };

    Covr.checkStatus = function () {
        var requestId = jQuery("#covr_request_id").val();
        if (!requestId) {
            return;
        }

        var request_data = {
            'action': 'covr_login_status',
            'covr_request': requestId
        };

        jQuery.post(ajax_action_url, request_data, function (responseString) {
            var response = jQuery.parseJSON(responseString);
            if (response.status === 'ACTIVE') {
                window.setTimeout(function () {
                        Covr.checkStatus();
                    }, 2000);
            } else if (response.problem) {
                var errorDiv = document.createElement('div');
                errorDiv.innerHTML = "<div class='update-nag error-nag'>" + response.problem + "</div>";
                jQuery('#covr_error').append(errorDiv);
                jQuery('#covr_login_area').show();
                jQuery('#covr_login_button_area').show();
                jQuery('#covr_waiting_area').hide();
            } else if (response.status === 'ACCEPTED') {
                if (response.url) {
                    window.location.href = response.url;
                } else {
                    window.location.href = '/';
                }
            } else if (response.status === 'REJECTED') {
                alert("REJECTED");
            } else {
                alert("UNKNOWN");

                var errorDiv = document.createElement('div');
                errorDiv.innerHTML = "<div class='update-nag error-nag'>Unknown response: [" + responseString + "] </div>";
                jQuery('#covr_error').append(errorDiv);
                jQuery('#covr_login_area').show();
                jQuery('#covr_login_button_area').show();
                jQuery('#covr_waiting_area').hide();
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            jQuery('#covr_error').append(Covr.buildErrorDiv(textStatus, errorThrown));
        });
    };

    Covr.login = function (data) {
        jQuery('#covr_error').empty();

        var request_data = {
            'action': 'covr_login',
            'covr_username': data.username || ''
        };

        jQuery('#covr_login_area').hide();
        jQuery('#covr_login_button_area').hide();
        jQuery('#covr_waiting_area').show();
        jQuery.post(ajax_action_url, request_data, function (responseString) {
            var response = jQuery.parseJSON(responseString);
            if (response.problem) {
                jQuery('#covr_error').empty();
                var errorDiv = document.createElement('div');
                errorDiv.innerHTML = "<div class='update-nag error-nag'>" + response.problem + "</div>";
                jQuery('#covr_error').append(errorDiv);
                jQuery('#covr_login_area').show();
                jQuery('#covr_login_button_area').show();
                jQuery('#covr_waiting_area').hide();

                if (response.wp_login) {
                    jQuery('#covr-login').addClass("covr-login-to-left");
                    jQuery('#login').show();
                }
            } else if (response.status === "ACCEPTED") {
                if (response.url) {
                    window.location.href = response.url;
                } else {
                    window.location.href = '/';
                }
            } else {
                jQuery('#covr_waiting_area').show();

                jQuery('#covr_request_expire_at').html("" + new Date(response.expiryAt));

                jQuery("#covr_request_id").val(response.request);
                window.setTimeout(function () {
                    Covr.checkStatus();
                }, 2000);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            jQuery('#covr_error').append(Covr.buildErrorDiv(textStatus, errorThrown));
        });
    };
    Covr.checkPhoneValidity = function (event) {
        event = (event) ? event : window.event;
        var charCode = (event.which) ? event.which : event.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
        return true;
    };

    window.Covr = Covr;
})();