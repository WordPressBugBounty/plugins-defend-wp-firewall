async function defend_wp_firewall_get_org_IP() {
    if (typeof defend_wp_firewall_common_blocklist_obj != 'undefined' && defend_wp_firewall_common_blocklist_obj.ipify_ip) {

        return true;
    }
    try {
        const ORG_IP_DWP = await fetch("https://api.ipify.org?format=json");
        let thisIP = await ORG_IP_DWP.json();
        if (typeof thisIP != 'undefined' && thisIP && thisIP.ip) {
            defend_wp_firewall_save_original_ip_got_from_ipify(thisIP.ip);
        }
    } catch (error) {
        console.error('ipify Error:', error); // Handle errors
    }

}

function defend_wp_firewall_save_original_ip_got_from_ipify(thisIP) {
    var data = new URLSearchParams({
        'action': 'save_ipify_ip_dfwp',
        'security': defend_wp_firewall_common_blocklist_obj.security,
        'ip': thisIP,
    });

    fetch(defend_wp_firewall_common_blocklist_obj.ajaxurl, {
        method: 'POST',
        body: data
    })
}

defend_wp_firewall_get_org_IP();