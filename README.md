# DropLock — Limit WooCommerce purchases to one per customer

[![Website](https://img.shields.io/badge/site-droplockwp.com-1a1a1a)](https://droplockwp.com)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)](https://www.gnu.org/licenses/gpl-2.0.html)

**Limit a WooCommerce product to one (or N) per customer across every order — not just one cart.**

WooCommerce's built-in *Sold individually* setting only caps a single cart: a customer can simply check out again and buy more. **DropLock** enforces a true **lifetime per-customer purchase limit**, blocking duplicate purchases across all of a customer's orders. It matches logged-in customers by user ID and guests by billing email, and validates at add-to-cart, cart update, and checkout — *before* the order is created.

Built for limited drops, collectibles, chase variants, memberships, event-exclusive merch, preorders, and one-per-customer promotions.

> This repository is the **free edition** (the one published on WordPress.org). The paid **DropLock Pro** lives at **[droplockwp.com](https://droplockwp.com)**.

## What the free edition does

- Protects **one product** with a lifetime per-customer limit
- Logged-in customers matched by **user ID**; guests matched by **billing email** at checkout
- Validation at **add-to-cart, cart-update, and checkout** (before the order exists)
- Default limit message + product-page badge
- Admin / shop-manager bypass (you can still place manual orders)
- Variations roll up to the parent product
- **HPOS** (High-Performance Order Storage) compatible
- WooCommerce **Cart & Checkout Blocks** compatible
- Blocked-attempt count + the 5 most recent entries

## Free vs Pro

| Capability | Free (this repo) | [Pro](https://droplockwp.com) |
| --- | --- | --- |
| Products you can protect | 1 | Unlimited |
| Lifetime per-customer limit + validation | ✓ | ✓ |
| Guest + logged-in matching, HPOS, Blocks, admin bypass | ✓ | ✓ |
| Custom limit message | Default | Editable |
| Custom product badge | Default | Editable |
| Counted order statuses | Fixed | Per product |
| Blocked-attempt log | Count + last 5 | Full + CSV export |
| Per-variation limits | — | ✓ |
| Category limits | — | ✓ |
| Sale-only limits (limit only while discounted) | — | ✓ |
| Disposable / burner-email blocking | — | ✓ |
| Priority email support | — | ✓ |

[See DropLock Pro →](https://droplockwp.com)

## Installation

1. Download the [latest release](https://github.com/anduinmooney/droplock/releases) zip (or get it from the WordPress.org plugin directory).
2. In WordPress admin: **Plugins → Add New → Upload Plugin** → choose the zip → **Install Now** → **Activate**.
3. Edit any product → **General** tab → **DropLock** section.
4. Enable DropLock and set the maximum quantity per customer. Save.

## How customer matching works

DropLock counts a customer's previous purchases by two signals — **user ID** for logged-in accounts and **billing email** for guest checkouts. When both are available the lookups are merged and deduplicated by order ID, so one order is never counted twice. Counted statuses in the free edition are Completed, Processing, and On-hold (Pro makes this configurable per product).

## Honest limitations

DropLock reliably stops repeat purchases through the same account or billing email — which covers most accidental and casual duplicate orders. It **cannot** stop a determined buyer who uses a clean email, a new account, and a different card; no email-based check can. For adversarial/bot traffic, pair DropLock with Cloudflare Bot Management or hCaptcha.

## Links

- 🌐 Website & Pro: <https://droplockwp.com>
- 📘 Docs: <https://droplockwp.com/docs/>
- ✍️ Guides: ["one per order" vs "one per customer"](https://droplockwp.com/blog/woocommerce-one-per-order-vs-one-per-customer.html) · [the right way to limit one per customer](https://droplockwp.com/blog/woocommerce-limit-product-one-per-customer.html)

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
