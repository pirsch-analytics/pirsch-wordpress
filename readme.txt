=== Pirsch Analytics ===
Contributors: m5blum
Tags: pirsch, analytics, sdk, client, api
Requires at least: 5.1
Tested up to: 6.0
Requires PHP: 7.4
Stable tag: 1.4.0
License: MIT
License URI: https://github.com/pirsch-analytics/pirsch-wordpress/blob/master/LICENSE

The official Wordpress Plugin for Pirsch Analytics.

== Description ==

Pirsch is a simple, privacy-friendly, open-source web analytics tool. It's lightweight, cookie-free and easily integrates into your WordPress installation, without using JavaScript. It does not collect data that can be uniquely assigned to a user, but instead shows aggregated statistics. Find out more [here](https://docs.pirsch.io/privacy/) on how that works.

== Installation ==

To use this plugin, you will have to create an account at https://pirsch.io/ and set up a website. Create a client ID and secret from the dashboards settings page and set them in WordPress on the plugin page (Tools > Pirsch Analytics). It won't analyze your website traffic until you have provided the client details.

For more information, please read our [documentation](https://docs.pirsch.io/).

== Terms and Conditions, Privacy Policy ==

Please refer to our website for our [terms and conditions](https://pirsch.io/terms) and [privacy policy](https://pirsch.io/privacy). Responsible for this plugin is [Emvi Software GmbH](https://pirsch.io/legal).

== Screenshots ==
 
1. The Pirsch Analytics dashboard.
2. Manage as many websites as you want and get detailed insights into your statistics.

== Changelog ==

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
