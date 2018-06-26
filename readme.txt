=== WooCommerce Request Network Payment Gateway ===
Contributors: adowson9
Tags: cryptocurrency, ethereum, bitcoin, request network, woocommerce
Requires at least: 4.4
Tested up to: 4.9
Requires PHP: 5.6
Stable tag: 0.1.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Request for WooCommerce is a simple way to accept cryptocurrency payments on your WooCommerce store, with no setup fees, no monthly fees and no hidden costs.

== Description ==

The Request Network for WooCommerce plugin extends WooCommerce allowing you to take cryptocurrency payments directly on your store powered by the Request Network. The plugin has no setup fees, no monthly fees and no hidden costs.

= Features =

* Allow customers to pay with Metamask or their Ledger Nano S hardware wallet.
* Accept payments in ETH and ERC20 tokens (REQ, DAI, KNC, OMG, DGX). Bitcoin support coming soon.
* 100% free, no setup fees, no monthly fees and no hidden costs. 
* Quick and easy installation. 
* Real-time currency exchange rates with support for multiple currencies. 
* Allows for testing on test-net (Rinkeby).

= Supported Currencies =

Request Network for WooCommerce currently supports:

* USD
* GBP
* EUR
* CNY
* JPY
* CAD
* RUB
* PLN
* AUD
* SGD
* HKD
* ZAR
* CHF
* INR
* NZD
* DKK
* KRW
* BRL
* IDR
* [and many more](https://www.cryptocompare.com/)

= Dependencies =

[BCMath](http://php.net/manual/en/book.bc.php) is the only dependency for the plugin - almost every setup has BCMath installed as default. If it's not installed 

Check if you already have BCMath installed
`
php -m
`

Install the BCMath extension.
`
sudo apt install php7.0-bcmath
`

Restart Apache / Nginx  
`
service apache2 restart or service nginx restart
`

== Installation ==

= Automatic Installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type WooCommerce Request Network Payment Gateway” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

= Manual Installation =

For help setting up and configuring, please refer to our [installation guide](https://wooreq.com/getting-started/)

The manual installation method involves downloading the plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

== Frequently Asked Questions ==

= Will you be taking any profit for the plugin? =

No, the plugin will be 100% free — no profit will be taken by me or the Request team.

= Does this require an SSL certificate? =

No, although it is HIGHLY recommended you use SSL when handling any sensitive customer data.

= Will more ERC20 tokens be added in the future? =

Yes, we will slowly be adding more ERC20 tokens in the future.

= Does this support both mainnet and testnet for testing? =

Yes it does - mainnet and testnet (Rinkeby) can be toggled on/off in the plugin settings.

= Where can I find documentation? =

For help setting up and configuring, please refer to our [installation guide](https://wooreq.com/getting-started/), if you are still having issues you can contact me [here](https://wooreq.com/contact/).

= Where can I get support or talk to other users? =

If you get stuck, you can contact me [here](https://wooreq.com/contact/).

To discuss the plugin feel free to join the Request Hub [here](https://request-slack.herokuapp.com/).

== Screenshots ==

1. Checkout using the Request Network for WooCommerce gateway..
2. The settings panel used to configure the gateway.
3. Example customer order with useful cryptocurrency information.

== Changelog ==

= 0.1.0 - 2018-05-01 =
* Initial Release

[See changelog for all versions](https://raw.githubusercontent.com/AdamDowson/RequestNetworkForWooCommerce/master/changelog.txt).

= Powered by the Request Network =

For more information about the Request Network click [here](https://request.network/)


