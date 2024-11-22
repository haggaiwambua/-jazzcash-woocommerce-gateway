=== WooCommerce JazzCash Gateway Plugin ===
Contributors: Haggai Wambua
Tags: woocommerce, jazzcash
Requires at least: 4.4
Tested up to: 4.9.8
Stable tag: 2.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Allows WooCommerce to accept payments using JazzCash payment gateway.

== Description ==

> **Support policy**
>
> * Should you need assistance, please open a support request in the **[Support section, above](http://wordpress.org/support/plugin/woocommerce-jazzcash-gateway)**, and we will look into it as soon as possible (usually within a couple of days).
> * If you need support urgently, or you require a customisation, you can avail of our paid support and consultancy services. To do so, please contact us [using our dedicated portal](https://www.jazzcash.com.pk/), specifying that you are using our WooCommerce JazzCash plugin. You will receive direct assistance from our team, who will troubleshoot your site and help you to make it work smoothly. We can also help you with installation, customisation and development of new features.

The plugin will add a new payment gateway called JazzCash, which will add support for the JazzCash payment gateway. Upon checkout, your customers will be redirected to a secure portal where they can complete their payment. Both standard and Quick Checkout modes are supported. - See more at: http://www.jazzcash.com.pk/digital-payments/online-payments/

= IMPORTANT =
**Make sure that you read and understand the plugin requirements and the FAQ before installing this plugin**. Almost all support requests we receive are related to missing requirements, and incomplete reading of the message that is displayed when such requirements are not met.

= Included localisations =
* English (GB)

= Requirements =
* A JazzCash Merchant account. The plugin was not tested with personal accounts and might not work correctly with them.
* WordPress 4.4 upto 4.9.8
* PHP 7 or greater
* WooCommerce 3.5.1

= Current limitations =
* Plugin does not yet support pre-authorisation or subscriptions.

= Notes =
* This plugin is provided as a **free** alternative to the many commercial plugins that add the JazzCash payment gateway to WooCommerce, and it's not automatically covered by free support. See FAQ for more details.

== Installation ==

1. Extract the zip file and drop the contents in the ```wp-content/plugins/``` directory of your WordPress installation.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to ```WooCommerce > Settings > Payment Gateways > JazzCash``` to configure the plugin.

For more information about installation and management of plugins, please refer to [WordPress documentation](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

= Setup =
On the settings page, the following settings are required:

* **MercahntID**: this is the ID associated to your JazzCash merchant account.
* **Password**: this is the password of your JazzCash account.
* **ActionUrl**: this is the action url provided by JazzCash support.

If you wish to get more details about JazzCash, please refer to [JazzCash website](https://www.jazzcash.com.pk/).

== Changelog ==

= 2.0 =
* First official release
