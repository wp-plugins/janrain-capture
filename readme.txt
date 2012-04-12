=== Janrain Capture ===
Contributors: bhamrick, rwright
Tags: capture, janrain, sso
Requires at least: 3.0
Tested up to: 3.1
License: APL

Janrain Capture is a cloud-hosted user management platform

== Description ==

This module accelerates the time to implement Janrain Capture into your WordPress web sites, helping to improve your registration conversion rates by allowing your customers to register and sign-in through their Social Network of choice.

Janrain Capture is a hosted registration and authentication system that allows site owners to provide a central repository for user information, that can be deployed on one or more web sites. Registration and authentication can be done through connection to a social network identity provider such as Facebook, Google, Yahoo!, OpenID, LinkedIn, eBay, Twitter and many others or through traditional form field methods.

Key features:
* Social and form based registration
* Social and form based login and authentication
* Central data store for one or more sites
* Single Sign On to federate identity across multiple domains

[Existing Janrain Engage module for WordPress](http://wordpress.org/extend/plugins/rpx/)
For more information about the Janrain Capture product please checkout [http://www.janrain.com/products/capture/](http://www.janrain.com/products/capture/)
For technical documentation please refer to [http://developers.janrain.com/documentation/capture/](http://developers.janrain.com/documentation/capture/)

Follow us on [Twitter](http://twitter.com/janrain) and on [Facebook](http://janrain.com/facebook) to keep up with the latest updates.

== Installation ==
Install through the Administration Panel or extract plugin archive in your plugin directory.

Once installed, visit the Janrain Capture menu item in the Administration Panel to enter your Janrain Capture configuration details. At a minimum, you will need to enter an Application Domain, API Client ID, and API Client Secret.

To insert Capture links in posts or pages you can use the shortcode: [janrain_capture]

By default, [janrain_capture] will result in a link with the text "Sign in / Register" that will launch a modal window pointing to your Capture signin screen. You can customize the text, action, and starting width/height of the modal window by passing in additional attributes. The following is an example of a link to the legacy_register screen with a height of 800px and a width of 600px:

[janrain_capture text="Register" action="legacy_register" width="600" height="800"]

You can prevent the construction of the link and simply return the URL to the screen by adding the attribute href_only="true" to the shortcode.

To insert links in your theme templates you can use the [do_shortcode](http://codex.wordpress.org/Function_Reference/do_shortcode) WordPress function.
