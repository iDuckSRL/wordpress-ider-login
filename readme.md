=== IDer Login for Wordpress ===
Tags: IDer, login, sso, qrcode, openid, client, oauth2
Requires at least: 4.6
Tested up to: 6.5.3
Stable tag: 2.1
License: Apache License, Version 2.0 or later
License URI: http://directory.fsf.org/wiki/License:Apache2.0

This plugin provides functionality to register and connect to your WordPress via IDer Service.


== Description ==
With this plugin you can provide login and registration process using the IDer Service.
An additional "Login with IDer" button will appears along the regular one.

How it works?
1. First of all you need to create a profile in the [IDer](http://ider.com/ "IDer website") website
2. Clicking the button a redirect to the IDer server will display a QR Code
3. To scan it you need to download the free IDer App from the App Store or Google Play depending which cell phone you own.
   Or just scan the QR code with any QR Code reader and you will be redirect to the download page.  
   Provide few infos and you are ready to scan the QR code 
4. After that the IDer App will prompt you for the missing data required to complete the login/registration process.
   Confirming the data your browser will automatically log you in and show you which info the website saved.


== Installation ==


1. Upload the plugin files to the `/wp-content/plugins/ider-login` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to the just appeared top level menu called "IDer Login"
4. Configure the plugin as descripted. You need to create a profile in the [IDer](http://ider.com/ "IDer website")
5. With the data collected during the profile creation process fill in the Client ID, Client Secret and Campagin ID fields
6. Change the other settings if you want


== Frequently Asked Questions ==

= Where can I download the App? =

You can find the IDer app in your App Store or Google Play. Search for "ider".

= Can I hack/improve/fork/edit this plugin? =

Mmmm. Even though we encourage the developing and extension of new plugins it doesn't sound like a good idea in this case.
This plugin has been very tested and stressed and it should be use as it is.
But if you got a brilliant idea or suggestion we would love to hear from you.


== Screenshots ==

1. Plugin settings
2. Login button
3. QR Code authentication
4. IDer welcome page after login

== Changelog ==

= 1.4.1 =
* First fully working release

= 1.5 =
* Added IDER_SERVER parameter for testing
* Missing image
* Minor in getProviderConfigValue
* Fixed wrong paths for admin assets
* Fields mapping as plugin setting in admin panel
* Additional css for IDer button

= 1.6 =
* Updated OID Client
* Fixed wrong CSS path

= 1.6.1 =
* Added flag "Allow Login only to Administrator Users"
* Update ider-log path.

= 2.0 =
* Removed deprecations.
* Added support to newer Wordpress versions.
* Improve UI assets.

= 2.1 =
* Improve address resolution.
