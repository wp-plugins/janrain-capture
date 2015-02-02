=== Janrain Registration ===
Contributors: byron-janrain, rebekahgolden
Tags: capture, janrain, sso
Requires at least: 3.5
Tested up to: 4.1
License: APL
Stable tag: 0.2.7

Janrain Registration and Customer Profile Data Storage

== Description ==

Janrain makes it easy to know your customers and personalize every interaction. Our Customer Profile Management Platform helps companies build a unified view of their customers across all devices by collecting accurate customer profile data to power personalized marketing. The platform encompasses social login, registration, customer profile data storage, customer insights, single sign-on, and user-generated content.

This plugin accelerates the deployment of Janrain Registration on your WordPress site. This plugin requires a Janrain Registration account.

Janrain Registration is a customizable registration and profile system that offers  default screens and workflows to optimize the user experience around sign-up and improve data quality. Registration includes in-line field verification, email confirmation, password reset, and other advanced registration features.

Janrain Registration was previously named Janrain Capture. The plugin UI references this legacy name.

[About Janrain Registration](http://developers.janrain.com/overview/registration/registration-overview/)

#### Customizable Registration & Data Collection Forms
Easily add registration and data collection forms that can be customized and styled to match the look and feel of your site, and implemented across a range of customer touch points, from promotions to campaigns and subscriptions.

#### Mobile Optimized Registration Screens
Leverage our registration SDK's for your mobile app and automatic device detection and screen optimization for the mobile web.

#### Real-Time Field Validation
Use real-time validation on form fields, bad word filters, and terms of service and privacy policy acceptance.

#### Conditional Workflows
Dynamically trigger form fields or distinct user flows based on inputs to previous fields or existing data already stored about a customer.

#### Email Confirmation & Password Reset Flows
Deliver account confirmation and password reset emails straight to your customer’s inbox, and let Janrain securely handle password management and reset flows on your behalf.

#### Account Linking
Let your customers link social identities to their existing account on your website.

#### Profile Pages
Offer profile pages that include name, email, demographics, photos, subscription preferences and more.

#### Data Mapping
Map Janrain customer profile fields to Drupal user fields.


== Installation ==

For detailed installation documentation, refer to [http://developers.janrain.com/how-to/registration/implementation-steps/](http://developers.janrain.com/how-to/registration/implementation-steps/)

1. Install through the Administration Panel or extract plugin archive in your plugin directory.

2. Copy the default screens folder from /wp-content/janrain-capture/janrain-capture-screens to /wp-content/plugins/janrain-capture-screens. Your Janrain technical lead will provide you with your customized screens files.

3. Select the Janrain Capture menu item from the Administration menu to enter your Janrain Registration configuration details. Individual settings will be provided by your Janrain technical lead.

    * Engage Application URL
    * Enable Social Sharing (if desired)
    * Override Native Links
    * UI Type

4. Select Janrain Capture > Capture Settings from the Administration menu to enter your Janrain Registration configuration details. Individual settings will be provided by your Janrain technical lead.

    * Application URL
    * Application ID
    * API Client ID
    * API Client Secret

5. If applicable, configure Federate and Backplane settings. Individual settings will be provided by your Janrain technical lead.

6. Select Janrain Capture > Interface Settings from the Administration menu to enter your Janrain Registration configuration details. Individual settings will be provided by your Janrain technical lead.

    * URL for load.js file
    * Screens Folder

== Short Codes ==
To insert Janrain Registration links in posts or pages use the shortcode: [janrain_capture]

By default, [janrain_capture] will result in a link with the text "Sign in / Register" that will launch a modal window pointing to your Registration signin screen. You can customize the text, action, and starting width/height of the modal window by passing in additional attributes. The following is an example of a link to the legacy_register screen with a height of 800px and a width of 600px:

[janrain_capture text="Register" action="legacy_register" width="600" height="800"]

You can prevent the construction of the link and simply return the URL to the screen by adding the attribute href_only="true" to the shortcode.

To insert links in your theme templates you can use the [do_shortcode](http://codex.wordpress.org/Function_Reference/do_shortcode) WordPress function.

This plugin supports Janrain Social Sharing v2 via the Social Login (Engage) application configured for your account. To use this feature, ensure 'Enable Social Sharing' is checked in the UI Options administration page and use the [janrain_share] shortcode. If the $post object is available the title, description, URL, and the most recent attached image URL will automatically be determined for sharing. These variables, as well as the button text, can be overridden with the following shortcode attributes:

* title
* description
* url
* img
* text

Example:
[janrain_share title="Janrain Engage Share Widget" description="Instructions for how to configure the Janrain Engage Share Widget" url="wordpress.org/extend/plugins/janrain-capture/" text="Tell a friend"]

== Multisite Installation ==
This plugin now fully supports WordPress Multisite. To install proceed as above, however you must Network Enable this plugin. The Janrain Capture administration menu will appear on the Network Admin dashboard.

Individual blogs can be updated with separate UI settings and a different API Client ID through the Janrain Capture administration menu in each blog's dashboard. If no changes are made they will default to the network admin settings.
