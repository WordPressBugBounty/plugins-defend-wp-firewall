=== DefendWP Firewall ===

Contributors: dark-prince, rajkuppus, amritanandh, Revmakx
Tags: security, vulnerability, malware, performance
Requires at least: 6.2.0
Tested up to: 6.7.2
Stable tag: 1.1.5
Requires PHP: 8.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Get instant protection against vulnerabilities disclosed by security companies.

== Description ==

### Instant protection against disclosed vulnerabilities ###

[DefendWP.org](https://defendwp.org/) is a WordPress plugin that protects your website from hackers exploiting vulnerable code on your website. Security research companies discover vulnerabilities and notify plugin developers to patch them. After some time, they disclose the vulnerability to the public, allowing you to update your plugins. However, this system has flaws. Once vulnerabilities are publicly disclosed, hackers rush to exploit the sites in which you haven't yet applied the patch.


= A Better Approach: Immediate Protection for All Users =
To solve this, our plugin pushes firewall rules and patches as soon as vulnerabilities are disclosed, ensuring websites are protected without waiting for an official patch. This protection is silent and automatic, ensuring that you are not affected even if you don't take any immediate action.

1. Immediate Patches Upon Disclosure: When vulnerabilities are disclosed, our plugin pushes patches or firewall rules that prevent exploitation.
2. Silent Protection: We operate in the background, allowing plugin developers to roll out patches at their own pace without compromising user security.
3. Free and Accessible: Security should not be a privilege. Our plugin is free and accessible and ensures that all WordPress users are protected from newly disclosed vulnerabilities.

### Protecting Everyone, Not Just the Privileged Few ###

Security should not be reserved for those who can afford premium services. The spirit of WordPress is inclusivity, and this should extend to security as well. When vulnerabilities are disclosed, they pose a risk to every website, regardless of its owner's resources. Every WordPress user should have access to immediate protection.

Security researchers play a vital role in identifying vulnerabilities, but the current system leaves too many users exposed. Our approach aims to create a safer WordPress ecosystem for all, by closing the gap between vulnerability disclosure and patching.

This isn't about taking credit—it's about prioritizing the safety of small business owners, bloggers, and entrepreneurs who rely on WordPress. By silently closing the vulnerability gap, we aim for a future where WordPress security is accessible to everyone.

Let's build a safer WordPress ecosystem together—one that protects all users, not just the privileged few.

= For plugin authors: Report a Vulnerability =
Do you have an active vulnerability in your plugin you want to safeguard users from? Report it [here](https://defendwp.org/submit-a-vulnerability/)

= Support =
Need help with your website's security? Just send us an email at [help@defendwp.org](mailto:help@defendwp.org).

= Note =
This plugin utilizes the [Ipify.org](https://api.ipify.org?format=json) to provide enhanced functionality. The API allows the plugin to retrieve the exact IP of the current user, which will be used to determine whether the user can access the WordPress site.[Privacy policy](https://ipify.org)

Vulnerabilities, IPs, Plugins and Themes data will be sent between [DefendWP.org](https://defendwp.org) and the WP site to instantly patch from vulnerabilities.

== Installation ==

This section describes how to install the plugin and get it working.

### INSTALL THE PLUGIN FROM WITHIN WORDPRESS
1. Visit the Plugins page within your dashboard and select ‘Add New’.
1. Search for ‘DefendWP’ and in the 'DefendWP Firewall' plugin, click on the 'Install Now' button and once installed, click Active button.

### INSTALL THE PLUGIN MANUALLY
1. Upload the ‘defendwp-firewall’ folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the ‘Plugins’ menu in WordPress

== Frequently Asked Questions ==

= How does the current Vulnerability Disclosure process work? =

Currently, security research companies identify vulnerabilities in WordPress plugins or themes and notify the developers. The developers are given time to create a patch. During this time, premium users of the security companies are offered protection against the vulnerability. After the patch is made, the vulnerability is disclosed publicly. Unfortunately, once disclosed, hackers can target websites that haven’t yet applied the patch, leaving many users vulnerable.

= Why is this process problematic for many WordPress users? =

While the current process allows developers time to patch vulnerabilities, it unintentionally leaves many users—especially those without premium protection—exposed once the vulnerability is disclosed. Hackers actively target disclosed vulnerabilities, creating a window in which users who haven’t yet updated their plugins are vulnerable to attacks. The system also prevents plugin developers from informing their own users of vulnerabilities before public disclosure, limiting early protection.

= What is your solution to this problem? =

We offer a free plugin that immediately pushes patches or firewall rules to protect websites when vulnerabilities are disclosed. This ensures all users are protected, regardless of whether they have premium protection. Our plugin works silently, pushing protection as soon as a vulnerability is disclosed, even if a patch from the developer hasn’t been applied yet.

= How does your plugin work when a vulnerability is disclosed? =

Once a vulnerability is disclosed, our plugin pushes a silent update that either applies a patch or implements firewall rules to prevent exploitation. This immediate protection means that users are safeguarded against attacks, even if they haven’t yet applied the official patch provided by the plugin developer.

= Do you take credit for patching vulnerabilities? =

No, our goal is to protect users, not to take credit for patching or disclosures. Security research companies deserve recognition for their work in identifying vulnerabilities, and we respect that. We focus on providing immediate protection to all WordPress users, without seeking credit or publicity for doing so.

= Can plugin developers work with you to push patches before public disclosure? =

Yes, plugin developers can contact us to push patches through our platform before public disclosure. This ensures that their users are protected without violating early disclosure agreements with security companies.

= Is your plugin free? =

Yes, our plugin is completely free and accessible to all WordPress users. We believe that security should not be a luxury and aim to protect every WordPress site, regardless of financial resources.

= Why focus on protecting everyone instead of just premium users? =

The WordPress ecosystem is built on open-source principles: inclusivity and accessibility. Unfortunately, the current vulnerability disclosure process leaves many users—particularly those using free plugins—vulnerable to attacks. By offering free, immediate protection, we aim to ensure that all WordPress users, regardless of financial status, are safeguarded from potential threats.

= How does your solution differ from other security companies? =

Unlike other security companies that offer early protection only to their premium users, our solution is free and available to everyone. We push patches and firewall rules silently upon vulnerability disclosure, ensuring that all users are protected, not just those with paid services.

= Does your plugin interfere with official patches from plugin developers? =

No, our plugin works alongside official patches from developers. It provides temporary protection through firewall rules or patches until the official update is applied, ensuring that users are not left vulnerable during the critical window between disclosure and patch adoption.

== Changelog ==

= 1.1.5 =
*Release Date - 5 May 2025*

Improvement: Support for IWP addon.

= 1.1.4 =
*Release Date - 14 Mar 2025*

Fix: Firewall service error.

= 1.1.3 =
*Release Date - 03 Mar 2025*

Fix: IP fetching failed in a few cases.

= 1.1.2 =
*Release Date - 03 Mar 2025*

Fix: Firewall JSON input error in a few cases.
Improvement: Admin dashboard DefendWP settings page improvements.

= 1.1.1 =
*Release Date - 28 Feb 2025*

Fix: Broken Access Control fixed.

= 1.1.0 =
*Release Date - 11 Feb 2025*

Improvement: Support for DefendWP Pro v.2.0.0 plugin.

= 1.0.0 =
*Release Date - 30 Sep 2024*

Improvement: First Release.
