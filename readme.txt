=== Pirsch Analytics ===
Contributors: m5blum
Tags: pirsch, analytics, server-side, web
Requires at least: 5.9
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 2.0.0
License: MIT
License URI: https://github.com/pirsch-analytics/pirsch-wordpress/blob/master/LICENSE

The official Wordpress Plugin for Pirsch Analytics.

== Description ==

Pirsch is a simple, privacy-friendly, open-source web analytics tool. It's lightweight, cookie-free and easily integrates into your WordPress installation, without using JavaScript. It does not collect data that can be uniquely assigned to a user, but instead shows aggregated statistics. Find out more [here](https://docs.pirsch.io/privacy/) on how that works.

== Installation ==

To use this plugin, you will have to create an account at https://pirsch.io/ and set up a website. Create a client access key from the dashboards settings page and set them in WordPress on the plugin page (Tools > Pirsch Analytics). It won't analyze your website traffic until you have provided the client details.

For more information, please read our [documentation](https://docs.pirsch.io/).

== Terms and Conditions, Privacy Policy ==

Please refer to our website for our [terms and conditions](https://pirsch.io/terms) and [privacy policy](https://pirsch.io/privacy). Responsible for this plugin is [Emvi Software GmbH](https://pirsch.io/legal).

== Screenshots ==
 
1. The Pirsch Analytics dashboard.
2. Manage as many websites as you want and get detailed insights into your statistics.

== Changelog ==

= 2.0.0 =

- added embedded dashboard
- improved page path filter
- improved and simplified UI
- removed client ID + secret option in favor of access keys
- fixed running plugin with WPRocket (and potentially other caching plugins) installed
- fixed removing settings on uninstall instead of deactivation
- fixed X-Forwarded-For and Forwarded proxy headers
- updated dependencies

= 1.7.0 =

- upgraded to latest Wordpress version
- upgraded to Pirsch SDK version 2
- removed hostname field (no longer required)
- updated dependencies

= 1.6.4 =

- fixed minimum required PHP version

= 1.6.3 =

- added support for client hints
- removed base URL
- updated SDK

= 1.5.5 =

- fixed tracking page views when path filter is left empty

= 1.5.4 =

- added missing delimiter and modifier to path filter

= 1.5.3 =

- fixed path filter separator

= 1.5.2 =

- fixed missing settings for path filter

= 1.5.1 =

- fixed settings

= 1.5.0 =

- added the option to filter pages/files using regular expressions
- added link to settings page
- fixed parsing X-Forwarded-For

= 1.4.0 =

- added support for base URL
- updated SDK

= 1.3.0 =

- added support for single access tokens
- added new option to configure a header when behind a proxy or load balancer
- updated SDK
- updated screenshots

= 1.2.0 =

- updated SDK
- improved /wp-... site filter

= 1.1.2 =

- fixed plugin version number

= 1.1.1 =

- updated SDK
- fixed str_starts_with

= 1.1.0 =

- updated SDK
- ignore /wp-... pages

= 1.0.2 =

- updated SDK
- updated screenshots

= 1.0.2 =

- respect the DNT (do not track) header

= 1.0.0 =

Initial release.
