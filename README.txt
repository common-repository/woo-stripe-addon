=== Addon for Stripe and WooCommerce ===
Contributors: The One Technologies
Tags: woocommerce, stripe, payment gateway,credit card, ecommerce, e-commerce, commerce, cart, checkout,stripe addon,refund,credit cards payment stripe and woocommerce,stripe for woocommerce,stripe payment gateway for woocommerce,stripe payment in wordpress,stripe payment refunds,stripe plugin for woocommerce,stripe woocommerce addon,free stripe woocommerce plugin,woocommerce credit cards payment with stripe,woocommerce plugin stripe
Requires at least: 4.0 & WooCommerce 2.3+
Tested up to: 5.3.2
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A Woo Stripe Addon plugin to accept credit card payments using stripe payment gateway for Woocommerce

== Description ==
Stripe Payment Gateway is used for taking credit card payments on your site.This plugin  shows you that how you can use Stripe to take credit card payments in their WooCommerce store without writing code. All you have to do is add stripe API key to a settings page and you're done.


= Why our plugin is better than other Stripe Plugins? =
1. Better Validation for Credit Card On checkout page.
2. Simple coding to accept the Credit card payments via Stripe.
3. No Technical Skills needed.
4. Can Customize the Credit Card Title and Display Credit card type icons as per your choice.
5. Accept the type of credit card you like.
6. Display the credit card type icons of your choice.
7. Manage Stock ,Restore stock for order status which get cancelled and refunded

= Features =
1. Simple Code to accept Credit cards via Stripe payment gateway in woocommerce
2. jQuery validations for Credit Cards.
3. Display the credit card type icons of your choice.
4. This plugin Supports Restoring stock if order status is changed to Cancelled or Refunded.
5. No technical skills required.
6. Visualized on screen shots.
7. Adds Charge Id and Charge time to Order Note.
8. Adds Refund Id and Refund time to Order Note.
9. Add Stock details for products to Order Note if the order status is Cancelled or Refunded.
10. This plugin accept the of credit card you like.
11. This plugin does not store Credit Card Details.
12. This plugin Uses Token method to charge Credit Cards rather sending sensitive card details to stripe directly as prescribed by Stripe.
13. This plugin requires SSL on merchant site as described <a href="https://stripe.com/help/ssl">here</a>.    
14. This plugin Support refunds (Only in Cents) in woocommerce.
15. This plugin Supports many currencies ,please check <a href="https://support.stripe.com/questions/which-currencies-does-stripe-support">here</a> which currencies are supported by this plugin for stripe.
16. This plugin uses the latest api of stripe.

= Support =

* Neither Woocommerce nor Stripe provides support for this plugin.
* If you think you've found a bug or you're not sure if you need to contact support, feel free to [contact us](http://estatic-infotech.com/).


== Installation ==
= Minimum Requirements =

* WooCommerce 2.2.0 or later
* Wordpress 3.8 or later

= Automatic installation =
In the search field type Woo Stripe addon and click Search Plugins. Once you've found our plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking Install Now button.

= Manual installation =

Steps to install and setup this plugin are:
1.Download the plugin
2.Copy paste the folder to wp-content/plugins folder
3.Activate the plugin and click on settings
4.Add stripe API key(secret key) , for more please check this link (https://dashboard.stripe.com/account/apikeys)
5.Set the Currency in Woocommerce General settings

== What after installation? ==
After installing and activation of plugin, first check if it displays any Notices at the top, if yes resolve that issues and then deactivate plugin and activate plugin.

Then start testing for the Test/Sandbox account by setting mode as Sandbox in Settings.
Once you are ready to take live payments, make sure the mode is set as live. You'll also need to force SSL on checkout in the WooCommerce settings and have an SSL certificate. As long as the Live API Keys are saved, your store will be ready to process credit card payments.

= Updating =

The plugin should automatically update with new features, but you could always download the new version of the plugin and manually update the same way you would manually install.

= want to remove stripe description message ? =

If you want to remove stripe description message within the stripe form then you can use 'wc_stripe_description' action for that.please find below code for reference purpose.
Note:Put this code into your function file for stripe description message modification OR leave it blank to remove description message. 

add_filter('wc_stripe_description', 'your_custom_stripe_description');
    
    function your_custom_stripe_description($fields){
        $fields = 'Stripe allows you to accept payments on your Woocommerce store';
        return $fields;
    }

== Screenshots ==

1. Settings Page.
2. How to get the Stripe Api Keys.
3. The standard credit card form on the checkout page with javascript validation.
4. Woocommerce Order with different order Note.
5. Refund of amount in stripe Merchant account.
6. Detail page of refund Amount.


== Frequently Asked Questions ==

= Does I need to have an SSL Certificate? =

Yes you do. For any transaction involving sensitive information, you should take security seriously, and credit card information is incredibly sensitive.You can read [Stripe's reasaoning for using SSL here](https://stripe.com/help/ssl).


== Changelog ==

= 1.0.1 =
* Fix - Plugin will now get activated if ssl is not installed
= 1.0.2 =
* Fix - Better Refund functionality
* Fix - Japanese currency refund issue
= 1.0.3 =
* Fix - email receipt for ssl
= 1.0.4 =
* Fix - Woocommerce Credit Card Form Compitable
= 1.0.5 =
* Fix - Payment method reduce issue
= 1.0.6 =
* Fix - Product restock issue
= 1.0.7 =
* Fix - Wordpress Version Update
= 1.0.8 =
* Fix - Wordpress Version Update / Woocommerce Version Update
= 1.0.9 =
* Fix - Solved Javascript issue,secure encryption of key,Show-Hide Secret key
= 1.0.10 =
* Fix - Wordpress Version and Woocommerce Version Update
= 1.0.11 =
* Fix - Tested upto latest version of Woocommerce 3.4.5 and Wordpress 4.9.8
= 1.0.12 =
* Fix - Tested upto latest version of Woocommerce 3.8.1 and Wordpress 5.3
= 2.0.1 =
* Fix - Update stipe Library.
* Fix - Remove deprecated funcations.
= 2.0.2 =
* Fix - Change Name of plugin.
= 2.0.3 =
* Add - PaymentIntents Method
= 2.0.4 =
* Fix - Resolved some miscellaneous bugs.
== Upgrade Notice ==
