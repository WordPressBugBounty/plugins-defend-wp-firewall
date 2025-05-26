function defend_wp_firewall_parse_response_from_raw_data(raw_response) {

    return raw_response.split('<DEFEND_WP_FIREWALL_START>').pop().split('<DEFEND_WP_FIREWALL_END>').shift();
}

function defend_wp_firewall_save_settings(isAlert) {
    jQuery('.save_settings_dwp').val('Saving...');

    var ls_settings = {};
    ls_settings['enabled'] = 1;

    ls_settings['htaccess_directory_browsing'] = jQuery('#htaccess_directory_browsing:checked').val() || 'no';
    ls_settings['htaccess_uploads_php'] = jQuery('#htaccess_uploads_php:checked').val() || 'no';
    ls_settings['htaccess_plugins_php'] = jQuery('#htaccess_plugins_php:checked').val() || 'no';
    ls_settings['htaccess_themes_php'] = jQuery('#htaccess_themes_php:checked').val() || 'no';
    ls_settings['htaccess_protect_files'] = jQuery('#htaccess_protect_files:checked').val() || 'no';
    ls_settings['enable_auto_update'] = jQuery('#enable_auto_update:checked').val() || 'no';
    ls_settings['enable_dfwp_firewall'] = jQuery('#enable_dfwp_firewall:checked').val() || 'no';
    ls_settings['disable_xml_rpc_request'] = jQuery('#disable_xml_rpc_request:checked').val() || 'no';
    ls_settings['disable_xml_rpc_request'] = jQuery('#disable_xml_rpc_request:checked').val() || 'no';
    ls_settings['enable_sanitize_request'] = jQuery('#enable_sanitize_request:checked').val() || 'no';
    ls_settings['enable_defendwp_nonce'] = jQuery('#enable_defendwp_nonce:checked').val() || 'no';

    let customEvent = jQuery.Event('dfwp_save_settings');
    jQuery(document.body).trigger(customEvent, ls_settings);

    if (customEvent.return) {
        ls_settings = customEvent.return;
    }

    console.log(ls_settings);

    var data = {
        'action': 'save_settings_dwp',
        'security': defend_wp_firewall_admin_obj.security,
        'data': {
            'settings': ls_settings,
        }
    };

    jQuery.post(ajaxurl, data, function (response) {
        try {
            jQuery('.save_settings_dwp').val('Save Changes');

            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);

            if (isAlert) {
                alert('Success');
            }
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}


function defend_wp_firewall_whitelist_ip_from_log(jQObj) {
    var data = {
        'action': 'whitelist_ip_from_log_dfwp',
        'security': defend_wp_firewall_admin_obj.security,
        'log_id': jQObj.closest('tr').attr('log_id'),
    };

    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);

            alert('Success');
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_remove_single_whitelist(jQObj) {
    var data = {
        'action': 'remove_single_whitelist_dfwp',
        'security': defend_wp_firewall_admin_obj.security,
        'this_id': jQObj.attr('this_id'),
        'type': jQObj.attr('this_type'),
    };

    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);
            if (response && response.update_ui) {
                jQuery(response.update_ui.id).html(response.update_ui.html);
            }

        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_remove_single_blocklist(jQObj) {
    var data = {
        'action': 'remove_single_blocklist_dfwp',
        'security': defend_wp_firewall_admin_obj.security,
        'this_id': jQObj.attr('this_id'),
        'type': jQObj.attr('this_type'),
    };

    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);
            if (response && response.update_ui) {
                jQuery(response.update_ui.id).html(response.update_ui.html);
            }
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_whitelist_ip_from_settings(jQObj) {
    var value = jQuery('.whitelist_ip_dfwp_settings_val').val();

    if (!value) {
        return false;
    }
    var data = {
        'action': 'whitelist_ip_from_settings_dfwp',
        'security': defend_wp_firewall_admin_obj.security,
        'IP': value,
        'type': jQObj.attr('this_type'),
    };

    jQObj.text('Adding...');
    jQObj.attr('disabled', true);

    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);

            if (response && response.update_ui) {
                jQuery(response.update_ui.id).html(response.update_ui.html);
            }
            jQObj.text('Add');
            jQObj.removeAttr('disabled', true);
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_block_ip_from_settings(jQObj) {
    var value = jQuery('.block_ip_dfwp_settings_val').val();

    if (!value) {
        return false;
    }
    var data = {
        'action': 'block_ip_from_settings_dfwp',
        'security': defend_wp_firewall_admin_obj.security,
        'IP': value,
        'type': jQObj.attr('this_type'),
    };

    jQObj.text('Adding...');
    jQObj.attr('disabled', true);

    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);
            if (response && response.update_ui) {
                jQuery(response.update_ui.id).html(response.update_ui.html);
            }
            jQObj.text('Add');
            jQObj.removeAttr('disabled', true);
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_whitelist_pr_from_settings(jQObj) {
    var value = jQuery('.whitelist_pr_from_settings_dfwp_val').val();

    if (!value) {
        return false;
    }
    var data = {
        'action': 'whitelist_pr_from_settings_dfwp',
        'security': defend_wp_firewall_admin_obj.security,
        'pr_val': value,
        'type': jQObj.attr('this_type'),
    };
    jQObj.text('Adding...');
    jQObj.attr('disabled', true);
    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);
            if (response && response.update_ui) {
                jQuery(response.update_ui.id).html(response.update_ui.html);
            }
            jQObj.text('Add');
            jQObj.removeAttr('disabled', true);
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_whitelist_gr_from_settings(jQObj) {
    var value = jQuery('.whitelist_gr_from_settings_dfwp_val').val();

    if (!value) {
        return false;
    }
    var data = {
        'action': 'whitelist_gr_from_settings_dfwp',
        'security': defend_wp_firewall_admin_obj.security,
        'gr_val': value,
        'type': jQObj.attr('this_type'),
    };
    jQObj.text('Adding...');
    jQObj.attr('disabled', true);
    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);
            if (response && response.update_ui) {
                jQuery(response.update_ui.id).html(response.update_ui.html);
            }
            jQObj.text('Add');
            jQObj.removeAttr('disabled', true);
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_whitelist_post_req_from_log(jQObj) {
    var data = {
        'action': 'whitelist_post_req_from_log_dfwp',
        'security': defend_wp_firewall_admin_obj.security,
        'log_id': jQObj.closest('tr').attr('log_id'),
        'this_key': jQObj.closest('.group').attr('this_key'),
        'with_ip': jQObj.attr('with_ip'),
    };

    jQObj.remove();

    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);

            alert('Success');
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_clear_all_logs(jQObj) {
    var data = {
        'action': 'clear_all_logs_dwp',
        'security': defend_wp_firewall_admin_obj.security,
    };

    if (jQObj.text() == 'Clear all logs...') {

        return false;
    }
    jQObj.text('Clear all logs...');


    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);

            if (typeof response.error !== 'undefined') {
                alert(response.error);
            }
            if (typeof response.success !== 'undefined') {
                window.location.reload();
            }
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_load_more_logs(jQObj) {
    var data = {
        'action': 'load_more_logs_dwp',
        'security': defend_wp_firewall_admin_obj.security,
        'block_type': jQuery('.select_log_type_value_dfwp').val(),
        'last_log_id': jQObj.attr('last_log_id'),
    };

    if (jQObj.text() == 'Loading...') {

        return false;
    }
    jQObj.text('Loading...');


    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);

            if (typeof response.error !== 'undefined') {
                alert(response.error);
            }
            if (typeof response.success !== 'undefined' && typeof response.html !== 'undefined') {
                jQuery('.more_tr_logs_dwp').remove();
                jQuery('.tbody_logs_dwp').append(response.html);
            }
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}


function defend_wp_firewall_show_variables_log(jQObj) {
    var thisParent = jQObj.closest('tr');
    jQuery('.collapsed_vars_header_dwp', thisParent).remove();
    jQuery('.collapsed_vars_dwp', thisParent).show();
    jQObj.hide();
}

function defend_wp_firewall_whitelist_get_req_from_log(jQObj) {
    var data = {
        'action': 'whitelist_get_req_from_log_dfwp',
        'security': defend_wp_firewall_admin_obj.security,
        'log_id': jQObj.closest('tr').attr('log_id'),
        'this_key': jQObj.closest('.group').attr('this_key'),
        'with_ip': jQObj.attr('with_ip'),
    };

    jQObj.remove();

    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);

            alert('Success');
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_nav_setting_menu(jQObj) {
    var navAttr = jQObj.data('navid');
    jQuery('.dfwp-nav-dec').hide();
    jQuery('.dfwp-nav-item').removeClass('bg-lime-200');
    jQObj.addClass('bg-lime-200');
    jQuery('#' + navAttr).show();
}

function defend_wp_firewall_dismiss_cache_admin_notice(jQObj) {
    var data = {
        'action': 'dfwp_dismiss_cache_admin_notice',
        'security': defend_wp_firewall_admin_obj.security,
    };
    jQuery.post(ajaxurl, data, function (response) {
        try {
            jQuery('.defendwp-notice').remove();
        } catch (err) {
            console.log('json parse error', response);
        }
    });
}

function defend_wp_firewall_refresh_page() {
    window.location.reload();
}
function defend_wp_firewall_init_setup() {
    var data = {
        'action': 'dfwp_firewall_init_setup',
        'security': defend_wp_firewall_admin_obj.security,
    };


    jQuery.post(ajaxurl, data, function (response) {
        try {
            response = defend_wp_firewall_parse_response_from_raw_data(response);

            response = JSON.parse(response);
            console.log(response.success);
            if (response.success) {
                jQuery('.dfwp-loading').hide();
                jQuery('.dfwp-success').show();
                if (response.is_pro_activated) {
                    jQuery('.dfwp-mail-wrapper').hide();
                    defend_wp_firewall_refresh_page();
                }
            } else {
                jQuery('.dfwp-loading').hide();
                jQuery('.dfwp-error').show();
                if (response.result && response.result.error_msg) {
                    jQuery('#dfwp-error-msg').text(response.result.error_msg);
                }
                if (response.result && response.result.res_desc) {
                    jQuery('#dfwp-error-res').text(response.result.res_desc);
                }
            }
        } catch (err) {
            jQuery('.dfwp-loading').hide();
            jQuery('.dfwp-error').show();
            jQuery('#dfwp-error-res').text(err.message);
            jQuery('#dfwp-error-msg').text(JSON.stringify(response));
            console.log('json parse error', response);
        }
    });
}

jQuery(document).ready(function ($) {
    // 'use strict';

    jQuery('body').on('click', '.save_settings_dwp', function () {
        defend_wp_firewall_save_settings();
    });


    jQuery('body').on('click', '.show_variables_dwp_log', function () {
        defend_wp_firewall_show_variables_log(jQuery(this));
    });

    jQuery('body').on('click', '.load_more_logs_dwp', function () {
        defend_wp_firewall_load_more_logs(jQuery(this));
    });

    jQuery('body').on('click', '.dfwp_whitelist_ip_from_log', function () {
        if (confirm("Are you sure?")) {
            defend_wp_firewall_whitelist_ip_from_log(jQuery(this));
        }
    });

    jQuery('body').on('click', '.dfwp_whitelist_pr_from_log', function () {
        if (confirm("Are you sure?")) {
            defend_wp_firewall_whitelist_post_req_from_log(jQuery(this));
        }
    });

    jQuery('body').on('click', '.dfwp_whitelist_gr_from_log', function () {
        if (confirm("Are you sure?")) {
            defend_wp_firewall_whitelist_get_req_from_log(jQuery(this));
        }
    });

    jQuery('body').on('click', '.whitelist_ip_from_settings_dfwp', function () {
        defend_wp_firewall_whitelist_ip_from_settings(jQuery(this));
    });

    jQuery('body').on('click', '.block_ip_from_settings_dfwp', function () {
        defend_wp_firewall_block_ip_from_settings(jQuery(this));
    });

    jQuery('body').on('click', '.whitelist_pr_from_settings_dfwp', function () {
        defend_wp_firewall_whitelist_pr_from_settings(jQuery(this));
    });

    jQuery('body').on('click', '.whitelist_gr_from_settings_dfwp', function () {
        defend_wp_firewall_whitelist_gr_from_settings(jQuery(this));
    });

    jQuery('body').on('click', '.remove_single_whitelist_dfwp', function () {
        if (confirm("Are you sure want to remove?")) {
            defend_wp_firewall_remove_single_whitelist(jQuery(this));
        }
    });

    jQuery('body').on('click', '.clear_all_logs_dfwp', function () {
        if (confirm("Are you sure?")) {
            defend_wp_firewall_clear_all_logs(jQuery(this));
        }
    });

    jQuery('body').on('click', '.select_log_type_btn_dfwp', function () {
        jQuery('.select_log_type_cnt_dfwp').toggle();
        jQuery('.select_log_type_value_dfwp').val(jQuery(this).attr('block_type'));
    });

    jQuery('body').on('click', '.select_log_type_cnt_dfwp a', function () {
        jQuery('.select_log_type_cnt_dfwp').toggle();
        jQuery('.select_log_type_value_dfwp').val(jQuery(this).attr('block_type'));
        jQuery('.select_log_type_btn_dfwp button span').text(jQuery(this).text());

        window.location = window.location.href + '&block_type_dfwp=' + jQuery(this).attr('block_type');

    });

    jQuery('body').on('click', '.dfwp_first_flap_close', function () {
        jQuery('.dfwp_first_flap').hide();
    });

    jQuery('body').on('click', '.remove_single_blocklist_dfwp', function () {
        if (confirm("Are you sure want to remove?")) {
            defend_wp_firewall_remove_single_blocklist(jQuery(this));
        }
    });

    jQuery('body').on('click', '.dfwp-nav-item', function () {
        defend_wp_firewall_nav_setting_menu(jQuery(this));
    });

    jQuery('body').on('click', '.expand_log_row_dfwp', function () {
        var parent_prev_id = jQuery(this).attr('parent_prev_id');
        if (typeof parent_prev_id == 'undefined' || !parent_prev_id || parent_prev_id == '') {

            return false;
        }

        jQuery('.log_row_hidden_dfwp[parent_prev_id="' + parent_prev_id + '"').show();

        jQuery(this).hide();
    });

    jQuery(document.body).on('click', '.defendwp-notice .notice-dismiss', () => {
        defend_wp_firewall_dismiss_cache_admin_notice(jQuery(this));
    });

    $('.sync_firewall_dfwp').on('click', function (e) {
        e.preventDefault();
        $(this).addClass('opacity-50 cursor-not-allowed');
        $(this).text('Syncing firewall...');
        var data = {
            'action': 'dfwp_firewall_sync_firewall',
            'security': defend_wp_firewall_admin_obj.security,
        };

        jQuery.post(ajaxurl, data, function (response) {
            try {
                defend_wp_firewall_refresh_page();
            } catch (err) {

            }
        });
    });

    $('.revoke_connect_firewall_dfwp').on('click', function (e) {
        e.preventDefault();
        $(this).addClass('opacity-50 cursor-not-allowed');
        $(this).text('Revoking firewall...');
        var data = {
            'action': 'dfwp_firewall_revoke_connect_firewall',
            'security': defend_wp_firewall_admin_obj.security,
        };

        jQuery.post(ajaxurl, data, function (response) {
            try {
                defend_wp_firewall_refresh_page();
            } catch (err) {

            }
        });
    });

    $('#dfwp_join').on('click', function (e) {
        e.preventDefault();
        $(this).addClass('opacity-50 cursor-not-allowed');
        var data = {
            'action': 'dfwp_firewall_join_email',
            'security': defend_wp_firewall_admin_obj.security,
            'email': $('#dfwp_join_email').val()
        };


        jQuery('.dfwp-join-res').empty();
        jQuery('.dfwp-join-error').empty();
        jQuery.post(ajaxurl, data, function (response) {
            try {
                response = defend_wp_firewall_parse_response_from_raw_data(response);
                response = JSON.parse(response);
                console.log(response.success);
                if (response.success) {
                    defend_wp_firewall_refresh_page();
                } else {
                    $('#dfwp_join').removeClass('opacity-50 cursor-not-allowed');
                    if (response.result && response.result.error_msg) {
                        jQuery('.dfwp-join-error').text(response.result.error_msg);
                    }
                    if (response.result && response.result.res_desc) {
                        jQuery('.dfwp-join-res').text(response.result.res_desc);
                    }
                }
            } catch (err) {
                $('#dfwp_join').removeClass('opacity-50 cursor-not-allowed');
                jQuery('.dfwp-join-error').text(err.message);
                jQuery('.dfwp-join-res').text(JSON.stringify(response));
                console.log('json parse error', response);
            }
        });
    });

    if ($('#dfwp-init-setup-wrapper')) {
        if (defend_wp_firewall_admin_obj.is_connected == '0') {
            defend_wp_firewall_init_setup();
        } else {
            $('.dfwp-loading').hide();
            $('.dfwp-success').show();

        }
    }

});
