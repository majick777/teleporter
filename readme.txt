=== Teleporter ===
Contributors: majick
Donate link: https://wordquest.org/contribute/?plugin=teleporter
Tags: transition, page transition, single page application, ajax page load
Requires at least: 4.0.0
Tested up to: 5.7.2
Stable tag: 0.9.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Seamless fading page loading transitions via the Browser History API.

== Description ==

Teleporter brings smooth SPA-like (Single Page Application) transitions to your WordPress site. External links work as normal, but new pages on the same site are faded in magically when they are loaded and pages that have already been visited are instantly faded back in without needing to reload.

Teleporter uses fullpage iframe swapping, rather than AJAX. So unlike AJAX Page Loaders, this means that all the javascript in any pages loaded will continue to run as it normally would, giving visitors a seamless experience while navigating your site.


== Installation ==

1. Upload plugin .zip file to the `/wp-content/plugins/` directory and unzip.
2. Activate the plugin through the 'Plugins' menu in the WordPress Admin

== Frequently Asked Questions ==

= How do I get started?

Once you have activated the plugin, any internal links on your site will automatically be loaded via page transitions. See the next question for what links are affected.

= Which links are affected? =

Any standard `<a>` link on the page that:

1. Does not have a target attribute set.
2. Dose not have an onclick attribute already set.
3. Dose not have a class of `no-transition` or `no-teleporter`.
4. Does not have an URL starting with `#` or `?`
5. Does not have an URL starting with Site URL.
6. Does not have an URL with a hostname matching the current page.

This is a comprehensive attempt to match and transition between internal links only. (If you think there is something missing here please open a Github issue.)

In future, `a` link elements with an `onclick` event assigned will also be detected and ignored.

= How does it work? =

Teleporter loads new content in iframes within the existing window, then uses the Browser History API to keep track of the content that is loaded. It then monitors the `onpopstate` event so that browser forward and back buttons continue to load the desired content, with the added ability to fade between them.

= Will it break other scripts? =

No. Unlike similar plugins or libraries that use AJAX to retrieve new content and swap it on the current page, Teleporter uses iframes. This may seem a little counter-intuitive since iframes have been around forever and AJAX would seem to be the modern tool for the job. However, loading page content in an iframe means that any scripts loaded within that iframe are correctly loaded by the browser without fail. Using AJAX, there is a risk that the scripts in the current page and the new page are different, which could cause breakage as the new page's scripts are not initialized along with the content, and AJAX page transitioning does not (and cannot) address this issue.

= How do I conflict test this? =

If the page transitions are not working at all, it is likely you have another plugin causing a javascript error. This would prevent Teleporter from loading. Check you javascript console by right-clicking and selecting "Inspect" or "Inspect Element" then choose the "Console" tab from within the developer box. Javascript errors will be shown in red. You can try deactivating the plugin causing the error to see if this resolves the issue and if so report it to the plugin author. If the error is from Teleporter itself, please report it in the [Plugin Support forum](https://wordpress.org/support/plugins/teleporter/)

= How do I debug the script? =

You can run Teleporter in debug mode by appending `?teleporter-debug=1` to any URL on your site. This will load the unminified version of the script and output extra messages to the browser javascript debug console (see previous question.) If you make changes to the development script `teleporter.dev.js` for testing purposes, you can reprocess that file into minified and unminified versions and debug simultaneously with `?teleporter-minify=1&teleporter-debug=1`


== Screenshots ==


== Changelog ==

= 0.9.8 =
* Fixed: check for external links by prefix, host and site URL
* Fixed: scrollbars on backward and forward history clicks

= 0.9.7 = 
* Initial Release Version
* Fixed: added missing escape wrappers to plugin output
* Fixed: use unminified script for debug mode via querystring

= 0.9.6 =
* Initial Submission Version
* Fixed: ignore WordPress Admin Bar links
* Improved: added script minification

= 0.9.5 =
* Initial Working Version

== Upgrade Notice ==
