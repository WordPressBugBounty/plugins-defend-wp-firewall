jQuery(document).ready(function ($) {
    setTimeout(function () {
        var data = {
            'action': 'firewall_sync_ptc',
            'security': defend_wp_firewall_sync_obj.security,
        };

        jQuery.post(defend_wp_firewall_sync_obj.ajaxurl, data, function (response) {
        });
    }, 1000);
}); 