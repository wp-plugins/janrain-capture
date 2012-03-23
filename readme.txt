=== Janrain Capture ===
Contributors: bhamrick, rwright
Tags: capture, janrain, sso
Requires at least: 3.0
Tested up to: 3.1
License: APL

Janrain Capture is a cloud-hosted user management platform

== Description ==

Janrain Capture provides a cloud-hosted user management and authentication platform for collecting rich sets of user data and providing an interface for SSO through Janrain Federate.

[Janrain](http://www.janrain.com/)
[Product Page](http://www.janrain.com/products/capture)
[API Documentation](http://docs.janraincapture.com/)

== Installation ==
Install through the Administration Panel or extract plugin archive in your plugin directory.

Once installed, visit the Janrain Capture menu item in the Administration Panel to enter your Janrain Capture configuration details. At a minimum, you will need to enter an Application Domain, API Client ID, and API Client Secret.

To insert Capture links in posts or pages you can use the shortcode: [janrain_capture]

By default, [janrain_capture] will result in a link with the text "Sign in / Register" that will launch a modal window pointing to your Capture signin screen. You can customize the text, action, and starting width/height of the modal window by passing in additional attributes. The following is an example of a link to the legacy_register screen with a height of 800px and a width of 600px:

[janrain_capture text="Register" action="legacy_register" width="600" height="800"]

You can prevent the construction of the link and simply return the URL to the screen by adding the attribute href_only="true" to the shortcode.

To insert links in your theme templates you can use the [do_shortcode](http://codex.wordpress.org/Function_Reference/do_shortcode) WordPress function.
