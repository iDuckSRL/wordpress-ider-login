=== Single Sign On Client ===
Contributors: justingreerbbi
Donate link: http://wp-oauth.com
Tags: oauth2, SSO, Single Sign On
Requires at least: 4.5
Tested up to: 4.6
Stable tag: 1.1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin is a OAuth2 Client plugin that provides Single Sign On capabilities with another WordPress install using
WP Oauth Server.

== Description ==

This plugin is designed and developed for use with WP OAuth Server (https://wordpress.org/plugins/oauth2-provider/).

Once Single Sign On Client is installed, it will provide WordPress users SSO abilities using another WordPress install.

Use Case;

Site A is your main WordPress site but you need to launch another WordPress website or service (Site B).
Instead of having all your users create a new account on the new website, you can simply use Sign Sign on from Site A.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/single-sign-on-client` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Single Sign On screen to configure the client

== Frequently Asked Questions ==

= Does this plugin work with other OAuth2 Providers? =

No. This plugin is designed for WP OAuth Server only.

= Single Sign On Error =

In certain cases the user will be presented with a message "Single Sign On Error". This is because the use either has a
username already and the emails do not match or visa versa. The solution is to completely remove the associated user from
the client site.

== Changelog ==

= 1.0.0 =
* Init repo push