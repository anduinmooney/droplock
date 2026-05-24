# DropLock (Free) for WooCommerce

This is the **free / wordpress.org variant** of DropLock. The Pro version (sold at https://droplockwp.com) is a separate plugin folder at `../droplock-for-woocommerce/`.

## What's different from Pro

| | Free (this repo) | Pro |
|---|---|---|
| Lifetime per-customer limit | ✓ | ✓ |
| Add-to-cart + cart + checkout validation | ✓ | ✓ |
| Guest checkout via billing email | ✓ | ✓ |
| Variations roll up to parent | ✓ | ✓ |
| HPOS + Blocks compatibility | ✓ | ✓ |
| Admin bypass | ✓ | ✓ |
| Product page badge | ✓ | ✓ |
| Custom limit message + variables | ✓ | ✓ |
| Blocked-attempt log | Last 50, auto-pruned | Unlimited |
| Configurable counted statuses | (defaults only) | Per product |
| Clear log button | — | ✓ |
| Per-variation limits | — | ✓ (1.1) |
| Category / tag rules | — | ✓ (1.2) |
| Launch window + countdown | — | ✓ (1.4) |
| Waitlist email capture | — | ✓ (1.4) |
| CSV import/export | — | ✓ (1.3) |
| Priority support | — | ✓ |

When **both** plugins are installed, the Free version detects `DROPLOCK_PRO_VERSION` and stands down (shows a notice). Per-product settings live in the same postmeta keys, so customers can upgrade with no data loss.

## Submission to WordPress.org

See `../droplock-launch/RUNBOOK.md` step 11 and `../droplock-launch/wp-org-assets/README.md` for the asset prep.

Quick prep:
```powershell
# Build the submission zip
$src = "C:\Users\andui\WordpressPlugins\droplock"
$zip = "C:\Users\andui\WordpressPlugins\droplock-launch\dist\droplock.zip"
if (Test-Path $zip) { Remove-Item $zip -Force }
Compress-Archive -Path $src -DestinationPath $zip -CompressionLevel Optimal
```

Then submit at https://wordpress.org/plugins/developers/add/.
