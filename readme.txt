=== Ease Pay – Pay with USDC or Card on Base ===
Contributors: nexflow
Donate link: https://easepay.xyz
Tags: usdc, base, coinbase, crypto, payments, stablecoin, card, woocommerce, cryptocurrency, blockchain
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
WC requires at least: 7.0
WC tested up to: 9.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept instant USDC payments on Base. Customers can pay with crypto wallet or debit/credit card. Zero custody, 1-2% fees.

== Description ==

**Ease Pay** is the simplest way to accept USDC payments on Base for your WooCommerce store.

= Why Ease Pay? =

* **Zero Custody** – Funds go directly to your wallet. We never hold your money.
* **Dual Payment Options** – Customers can pay with their crypto wallet OR debit/credit card (via Coinbase Onramp).
* **Instant Settlement** – Payments confirm in seconds on Base.
* **Low Fees** – Only 1-2% per transaction. No monthly fees.
* **Non-Custodial** – You control your funds at all times.

= How It Works =

1. Customer selects "Pay with USDC or Card" at checkout
2. They're redirected to the Ease Pay secure payment page
3. Customer pays with wallet (MetaMask, Coinbase Wallet, etc.) or card
4. USDC is sent directly to your Base wallet
5. Order is automatically marked as paid

= Features =

* **WooCommerce Integration** – Seamless checkout experience
* **Multiple Wallets Supported** – MetaMask, Coinbase Wallet, Rainbow, and more
* **Card Payments** – Accept Visa/Mastercard via Coinbase Onramp
* **Real-time Webhooks** – Instant order status updates
* **Debug Logging** – Easy troubleshooting
* **HPOS Compatible** – Works with WooCommerce High-Performance Order Storage

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 7.0 or higher
* PHP 7.4 or higher
* A Base-compatible wallet address (starts with 0x)

== Installation ==

1. Upload the `ease-pay-woocommerce` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce → Settings → Payments
4. Click "Manage" next to Ease Pay
5. Enter your Base wallet address
6. Enable the gateway and save

= Configuration =

* **Merchant Wallet Address** – Your Base wallet where USDC will be sent (required)
* **Title** – Payment method name shown to customers
* **Description** – Description shown at checkout
* **Debug Mode** – Enable logging for troubleshooting

== Frequently Asked Questions ==

= What is Base? =

Base is a secure, low-cost Ethereum L2 built by Coinbase. It offers fast transactions with fees under $0.01.

= What is USDC? =

USDC is a stablecoin pegged 1:1 to the US Dollar. It's issued by Circle and fully backed by cash and short-term US treasuries.

= Do I need a Coinbase account? =

No! You just need any Ethereum-compatible wallet address. We recommend Coinbase Wallet for the best experience.

= How do customers pay with a card? =

Customers without crypto can use Coinbase Onramp to buy USDC with their debit/credit card and pay in one flow.

= What are the fees? =

Ease Pay charges 1-2% per transaction. There are no monthly fees, setup fees, or hidden costs.

= How do I receive my funds? =

USDC is sent directly to your wallet address. You can hold it, swap to other tokens, or off-ramp to your bank.

= Is this secure? =

Yes! Ease Pay is non-custodial – we never have access to your funds. All transactions are secured by the Base blockchain.

= Can I issue refunds? =

Refunds must be processed manually from your wallet. The plugin will log refund requests for your reference.

== Screenshots ==

1. Checkout page with Ease Pay payment option
2. Ease Pay secure payment page
3. WooCommerce settings page
4. Order confirmation with transaction details

== Changelog ==

= 1.0.0 =
* Initial release
* WooCommerce payment gateway integration
* Wallet and card payment support
* Webhook handling for order updates
* HPOS compatibility
* Debug logging

== Upgrade Notice ==

= 1.0.0 =
Initial release of Ease Pay for WooCommerce.

== Additional Info ==

= Support =

For support, please visit [easepay.xyz](https://easepay.xyz) or email support@easepay.xyz.

= Documentation =

Full documentation is available at [easepay.xyz/docs](https://easepay.xyz/docs).

= GitHub =

This plugin is open source. Contribute at [github.com/moejeaux/ease-pay-woocommerce](https://github.com/moejeaux/ease-pay-woocommerce).
