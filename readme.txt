=== Teleporter ===
Contributors: majick
Donate link: https://wordquest.org/contribute/?plugin=teleporter
Tags: transition, page transition, ajax page load
Requires at least: 4.0.0
Tested up to: 5.6.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Seamless fading page loading transitions via the Browser History API.

== Description ==

Teleporter brings smooth SPA-like (Single Page Application) transitions to your WordPress site. New pages are faded in magically when they are loaded internally, and pages already loaded are instantly faded in.

Teleporter uses fullpage iframe swapping, rather than AJAX. So unlike AJAX Page Loaders, this means that all the javascript in any pages will continue to run as it normally would, giving visitors a seamless experience while navigating your site.





== Installation ==

1. Upload plugin .zip file to the `/wp-content/plugins/` directory and unzip.
2. Activate the plugin through the 'Plugins' menu in the WordPress Admin

== Frequently Asked Questions ==

= How do I get started?

Once you have activated the plugin, any internal links on your site will automatically be loaded via page transitions. 

= Which links are affected? =

Any standard `<a>` links on the page that:

1. Do not have a target attribute set.
2. Do not have an onclick attribute already set.
3. Do not have a class of `no-transition` or `no-teleporter`.

As such, it is recommended you add a `target=_blank` or `target=_self` to any external links.

= How does it work? =

Teleporter loads new content in iframes within the existing window, then uses the Browser History API to keep track of the content that is loaded. It then monitors the `onpopstate` event so that browser forward and back buttons continue to load the desired content, with the added ability to fade between them.


== Screenshots ==

== Changelog ==

= 0.9.5 =
* Initial Working Version

== Upgrade Notice ==
