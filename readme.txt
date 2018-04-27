=== WooCommerce Request Network Payment Gateway ===
Contributors: Adam Dowson
Tags: cryptocurrency, cryptocurrencies, ethereum, request network, woocommerce, automattic
Requires at least: 4.4
Tested up to: 4.9
Requires PHP: 5.6
Stable tag: 4.1.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Request for WooCommerce is a simple way to accept cryptocurrency payments on your WooCommerce store, with no setup fees, no monthly fees and no hidden costs.

= Request Network for WooCommerce =

![Request Network for WooCommerce](https://camo.githubusercontent.com/7f9e1b1c9166f2b5833c5e150f7c99dd9fc71814/68747470733a2f2f63646e2d696d616765732d312e6d656469756d2e636f6d2f6d61782f3830302f302a5455504f4e464f695331325f586d62352e)

== Description

The Request Network for WooCommerce plugin extends WooCommerce allowing you to take cryptocurrency payments directly on your store powered by the Request Network. The plugin has no setup fees, no monthly fees and no hidden costs.

== Installation

=== Automatic Installation

Automatic installation coming soon. Pending approval from Wordpress.

=== Manual Installation

For help setting up and configuring, please refer to our [installation guide](https://wooreq.com/getting-started/)

The manual installation method involves downloading the plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

== Features

* Allow customers to pay with Metamask or their Ledger Nano S hardware wallet.
* Accept payments in ETH. ERC20 + Bitcoin support will be added shortly.
* 100% free, no setup fees, no monthly fees and no hidden costs. 
* Quick and easy installation. 
* Real-time currency exchange rates with support for multiple currencies. 
* Allows for testing on test-net (Rinkeby).

== Supported Currencies

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
* [and more](https://www.cryptocompare.com/)

== Dependencies

[BCMath](http://php.net/manual/en/book.bc.php) is the only dependency for the plugin - almost every setup has BCMath installed as default. If it's not installed 

Check if you already have BCMath installed
```
php -m
```
Install the BCMath extension.
```
sudo apt install php7.0-bcmath
```
Restart Apache / Nginx  
```
service apache2 restart or service nginx restart
```

== Frequently Asked Questions ==

=== Will you be taking any profit for the plugin? ===

No, the plugin will be 100% free — no profit will be taken by me or the Request team.

=== Does this require an SSL certificate? ===

No, although it is HIGHLY recommended you use SSL when handling any sensitive customer data.

=== Will ERC20 tokens be accepted in the future? ===

Yes, any payment that is accepted by the Request Network will work with the WooCommerce plugin — the code is written in a very generic way which means adding new currencies is incredibly straight forward.

=== Does this support both mainnet and testnet for testing? ===

Yes it does - mainnet and testnet (Rinkeby) can be toggled on/off in the plugin settings.

=== Where can I find documentation? ===

For help setting up and configuring, please refer to our [installation guide](https://wooreq.com/getting-started/), if you are still having issues you can contact me [here](https://wooreq.com/contact/).

=== Where can I get support or talk to other users? ===

If you get stuck, you can contact me [here](https://wooreq.com/contact/).

== Powered by the Request Network ==


