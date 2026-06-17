=== DropLock — Limit Purchases Per Customer ===
Contributors: droplock
Tags: woocommerce, limit purchase, one per customer, limited edition, drops
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Limit one WooCommerce product to one (or N) per customer across all of their orders. Free protects one product; Pro protects unlimited.

== Description ==

DropLock enforces a lifetime per-customer purchase limit on WooCommerce products. Unlike the built-in "Sold individually" setting (which only limits one cart at a time), DropLock blocks duplicate purchases across every order a customer has ever placed.

**DropLock Free is the full enforcement engine, scoped to one product** so you can try it on a real drop. When you need to protect more products, customize the message and badge, or unlock per-variation and category rules, [DropLock Pro](https://droplockwp.com) does that.

= Built for =

* Limited edition products
* Collector drops, chase variants, die-cast model releases
* Membership products
* Event-exclusive merch
* Preorder windows
* Limited merch drops
* One-per-customer promotions

= What the Free version does =

* Protects **one product** with a lifetime per-customer limit
* Logged-in customers matched by user ID
* Guest customers matched by billing email at checkout
* Validation at add-to-cart, cart-update, and checkout (before the order is created)
* Default limit message and product badge
* Admin / shop manager bypass (you can still place manual orders)
* Variations roll up to the parent product
* HPOS (High-Performance Order Storage) compatible
* WooCommerce Cart & Checkout Blocks compatible
* Blocked-attempt count plus the 5 most recent entries

= Free vs Pro =

| Capability | Free | Pro |
| --- | --- | --- |
| Products you can protect | 1 | Unlimited |
| Lifetime per-customer limit + validation | Yes | Yes |
| Guest + logged-in matching, HPOS, Blocks, admin bypass | Yes | Yes |
| Custom limit message | Default | Editable |
| Custom product badge | Default | Editable |
| Counted order statuses | Fixed | Per product |
| Blocked-attempt log | Count + last 5 | Full + CSV export |
| Per-variation limits, category & tag rules, launch window | — | Yes / on the roadmap |
| Priority email support | — | Yes |

[See DropLock Pro &rarr;](https://droplockwp.com)

= How customer matching works =

DropLock counts a customer's previous purchases by two signals:

* **Logged-in user ID** — for customer accounts.
* **Billing email** — for guest checkouts (matched at checkout time).

When both are available the lookups are merged and deduplicated by order ID, so a single order is never counted twice.

= Counted order statuses =

Free version: Completed, Processing, On-hold (hard-coded).
Cancelled, Failed, Refunded, and Pending orders are not counted.

DropLock Pro lets you customize the counted statuses per product.

= Honest limitations =

DropLock reliably stops repeat purchases through the same customer account or the same billing email — which covers most accidental and casual duplicate orders. It cannot stop a determined attacker who uses a clean email, a new account, and a different card; no email-based check can. If you need bot/fraud protection on top of per-customer limits, pair DropLock with Cloudflare Bot Management or hCaptcha.

= DropLock Pro =

[DropLock Pro](https://droplockwp.com) adds:

* Per-variation limits (separate limit per Red, Blue, Green, etc.)
* Category-level and tag-level drop rules
* Configurable counted order statuses per product
* Launch date/time window + countdown badge
* Waitlist email capture
* Full blocked-attempts log with filters, search, and CSV export
* Bulk editor for DropLock settings
* Customer lookup panel
* Priority email support

== Installation ==

1. In WordPress admin go to **Plugins → Add New**.
2. Search for "DropLock".
3. Install, then activate.
4. Edit any product, scroll to the General tab → DropLock section.
5. Enable DropLock and set the maximum quantity per customer.
6. Save.

== Frequently Asked Questions ==

= Does it work with guest checkout? =

Yes. Guests are matched by their billing email at checkout, before the order is created.

= Does it support variable products? =

Yes — the parent product limit applies across all variations. A customer who buys "Red" cannot then buy "Blue" of the same product.

Per-variation limits (separate limits for each variation) are a Pro feature.

= Does it work with WooCommerce HPOS? =

Yes. DropLock declares HPOS compatibility and uses the modern `wc_get_orders()` API.

= Will it slow down my store? =

No. Lookups only run when a DropLock-enabled product is in the cart. Results cache for 60 seconds per customer.

= Can a customer just use a different email? =

A determined attacker using a fresh email and a new card can always bypass an email-based check — no plugin can stop that. DropLock stops casual duplicate purchases, not state-level adversaries.

= Will refunds reduce the count? =

By default no — refunded orders still have line items. To free a customer's allowance, change the order status to Cancelled.

= Where is data stored? =

Per-product settings live in postmeta. The blocked-attempt log lives in a custom table `{prefix}droplock_blocked_log`. No data is sent to any external service.

= Can I delete all the plugin's data? =

Yes. Click Delete (not Deactivate) on the Plugins screen. The `uninstall.php` script drops the log table. Per-product settings are intentionally preserved so a reinstall (or upgrade to DropLock Pro) doesn't lose configured limits.

== Screenshots ==

1. Product edit screen with DropLock section.
2. Single product page showing the "Limit per customer" badge.
3. Customer-facing block message when the limit is reached.
4. WooCommerce → DropLock admin dashboard with the blocked-attempt log.

== Changelog ==

= 2.0.0 =
* Free now protects one product (the active "free slot" is the first product you enable). Protecting more products is a Pro feature. Existing settings are preserved.
* Free now uses the default limit message and product badge; editable message and badge are Pro features.
* The dashboard log shows the 5 most recent blocks plus the total count; the full log with CSV export is a Pro feature.
* Clear in-product messaging about which product holds your free slot, and what Pro unlocks.

= 1.1.0 =
* Added a Free vs Pro comparison on the DropLock dashboard.
* Added a one-time, dismissible milestone notice after DropLock has blocked enough purchases to prove its value (WooCommerce screens only).
* Clearer, single-line "what Pro unlocks" hint on the product edit screen.
* Pricing and documentation links from the dashboard.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
