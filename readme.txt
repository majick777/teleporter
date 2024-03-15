=== Teleporter ===
Contributors: majick
Donate link: https://wordquest.org/contribute/?plugin=teleporter
Tags: transition, page transition, single page application, ajax page load
Requires at least: 4.0.0
Tested up to: 6.4.2
Stable tag: 1.0.9
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

Once you have activated the plugin, any internal links on your site will automatically be loaded via page transitions. Visit the plugin settings page to modify the default plugin behaviour.

= How does it work? =

Teleporter loads new content in iframes within the existing window, then uses the Browser History API to keep track of the content that is loaded. It then monitors the `onpopstate` event so that browser forward and back buttons continue to load the desired content, with the added ability to fade between them.

= Which links are affected? =

Any standard `<a>` link on the page that:

1. Does not have a link target attribute set.
2. Does not have an onclick attribute already explicitly set.
3. Does not have a class of `no-transition` or `no-teleporter` (or other specified classes.)
4. Does not have an URL starting with `javascript` or `#` or `?` or `mailto:` or `tel:`
5. Does not have an URL starting with the current Site URL.
6. Does not have an URL with a hostname matching the current page.
7. Does not have an URL containing `/wp-admin/` or `wp-login.php`.
8. Does not have an attribute of `no-teleporter="1"`.
9. Is not in the Admin Bar (any link within the `#wpadminbar` section.)

This is a comprehensive attempt to match and transition between internal links only. (If you think there is something missing here please open a Github issue.)

= How are dynamic links handled? =

As of 1.0.4, Teleporter will also handle dynamic link content. That is, links added to the page later. Simply specify the classes of these links on the plugin settings page, and they are then handled with click event delegation (instead of being directly adding to the `a` link.) So for example, if you have a mobile menu that creates links upon expanding it with a `.mobile-link` class, you can add `mobile-link` in the plugin settings. When the mobile menu link is clicked, Teleporter will transition the page as normal. This makes it possible to use Teleporter with frontend builder or frameworks that add their content with javascript.

Similarly, if there are links that you wish to force to not transition for some reason, you can use the setting for ignore link classes in the same way. And, if you need to use selectors other than classes for these links, you can use the filters `teleporter_dynamic_selectors` and `teleporter_ignore_selectors` to add those respectively also.

= Can I ensure fresh copies of certain pages are loaded? =

As of 1.0.8, Teleporter includes a setting where you can specify pages (by slug or ID) to always refresh when clicked they are clicked through to.

This means that if a page already has been loaded in a Teleporter page session, and is switched away from, when it is switched back to, it is reloaded instead of simply switched back to.

Intended for use with cart or checkout pages. For example, if a customer visits their cart, then navigates away and adds another product, then switches back to the cart, the cart should be refreshed to show the new contents.

Note if there are other non-page URLS (eg. archives) where you want to force refresh also you can set the `teleporter_refresh` filter to true for that condition.

= Will it break other scripts? =

No. Unlike similar plugins or libraries that use AJAX to retrieve new content and swap it on the current page, Teleporter uses iframes. This may seem a little counter-intuitive since iframes have been around forever and AJAX would seem to be the modern tool for the job. However, loading page content in an iframe means that any scripts loaded within that iframe are correctly loaded by the browser without fail. Using AJAX, there is a risk that the scripts in the current page and the new page are different, which could cause breakage as the new page's scripts are not initialized along with the content, and AJAX page transitioning does not (and cannot) address this issue.

= How do I conflict test this? =

If the page transitions are not working at all, it is likely you have another plugin causing a javascript error. This would prevent Teleporter from loading. Check you javascript console by right-clicking and selecting "Inspect" or "Inspect Element" then choose the "Console" tab from within the developer box. Javascript errors will be shown in red. You can try deactivating the plugin causing the error to see if this resolves the issue and if so report it to the plugin author. If the error is from Teleporter itself, please report it in the [Plugin Support forum](https://wordpress.org/support/plugins/teleporter/)

= How do I debug the script? =

You can run Teleporter in debug mode by appending `?teleporter-debug=1` to any URL on your site. This will load the unminified version of the script and output extra messages to the browser javascript debug console (see previous question.) If you make changes to the development script `teleporter.dev.js` for testing purposes, you can reprocess that file into minified and unminified versions and debug simultaneously with `?teleporter-minify=1&teleporter-debug=1`


== Screenshots ==


== Changelog ==

= 1.0.8 =
* Added: setting for pages to always refresh (plus filter)
* Fixed: history pop state mismatch after multiple clicks

= 1.0.7 =
* Updated: Plugin Panel (1.2.9)
* Updated: WordQuest Library (1.8.2)
* Fixed: add extra onclick function for iPhone event bubbling
* Improved: added checks to not load for admin/previews/editing modes

= 1.0.6 =
* Added: filter to allow for non-class ignore/dynamic selectors
* Fixed: dynamic link event bubbling on iPhone (via cursor:pointer)
* Fixed: method of adding of comment-reply-link ignore class

= 1.0.5 =
* Hotfix: automatically load dynamic link check

= 1.0.4 =
* Updated: Plugin Panel (1.2.8)
* Improved: add link event handlers instead of onclicks
* Added: dynamic link class click event handling option

= 1.0.3 =
* Updated: Plugin Panel (1.2.2)
* Fixed: (properly) not countable warning on ignore link classes
* Fixed: ignore comment reply link filter function name

= 1.0.2 =
* Fixed: not countable warning on ignore link classes
* Added: filter to ignore comment reply link classes

= 1.0.1 =
* Fixed: always ignore javascript href links
* Improved: also ignore mailto and tel links

= 1.0.0 =
* Added: Plugin Panel (1.2.0)
* Added: Teleporter Configuration Settings
* Added: Page Load Timeout to auto-transition
* Improved: Match timeout to loading bar animation
* Improved: Streamlined link checking function

= 0.9.9 =
* Improved: ignore links containing wp-login.php

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
