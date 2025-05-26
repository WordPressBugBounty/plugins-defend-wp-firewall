(function ($) {
  "use strict";

  /**
   * All of the code for your public-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  $.ajaxPrefilter(function (settings, options, jqXHR) {
    if (settings.type.toLowerCase() == "post") {
      if (
        typeof defend_wp_firewall_nonce_obj !== "undefined" &&
        defend_wp_firewall_nonce_obj.defend_wp_firewall_nonce
      ) {
        var nonce = defend_wp_firewall_nonce_obj.defend_wp_firewall_nonce;
        if (
          typeof settings.data === "object" &&
          settings.data instanceof FormData
        ) {
          settings.data.append("defend_wp_firewall_nonce", nonce);
        } else if (typeof settings.data === "string") {
          var isObject = false;
          try {
            var obj = JSON.parse(settings.data);
            if (typeof obj === "object") {
              isObject = true;
            }
          } catch (error) {
            isObject = false;
          }
          if (isObject === false) {
            var nonceParam = "&defend_wp_firewall_nonce=" + nonce;
            settings.data += nonceParam;
          }
        } else if (typeof settings.data === "object") {
          var additionalData = {
            defend_wp_firewall_nonce: nonce,
          };
          settings.data = $.extend(settings.data, additionalData);
        }
      }
    } else if (settings.type.toLowerCase() == "get") {
      if (
        typeof defend_wp_firewall_nonce_obj !== "undefined" &&
        defend_wp_firewall_nonce_obj.defend_wp_firewall_nonce
      ) {
        var nonce = defend_wp_firewall_nonce_obj.defend_wp_firewall_nonce;
        var separator = settings.url.includes("?") ? "&" : "?";
        settings.url +=
          separator + "defend_wp_firewall_nonce=" + encodeURIComponent(nonce);
      }
    }
  });

  $(window).load(function () {
    if (
      typeof defend_wp_firewall_nonce_obj !== "undefined" &&
      defend_wp_firewall_nonce_obj.defend_wp_firewall_nonce
    ) {
      var nonce = defend_wp_firewall_nonce_obj.defend_wp_firewall_nonce;
      $("form").each(function () {
        if (!$(this).hasClass("defend_wp_firewall_nonce")) {
          $(this).append(
            '<input class="defend_wp_firewall_nonce" type="hidden" name="defend_wp_firewall_nonce" value="' +
              nonce +
              '">'
          );
        }
      });
    }
  });
})(jQuery);
