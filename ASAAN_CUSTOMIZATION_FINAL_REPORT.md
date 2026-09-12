# ASAAN Customization Final Report

Date: 2026-09-12
Production URL: https://assan-hmn5.onrender.com
GitHub: https://github.com/Mohammadafzalakbari2022/ASSAN

---

## 1. Summary of changes

| Requirement | Status | Commit |
|---|---|---|
| AFN currency (Western digits, `1,234,567.89`) | Verified live | config/shop.php `num_formatter => Standard` |
| Afghan Solar Hijri dates (`1405/06/20`) | Verified live | `AsaanDate.php`, `asaan:setup`, config `date/customer-mode` |
| English / دری / پښتو | Verified live | locale selector present: fa/ps/en with AFN/USD |
| Afghanistan sole country, AFN + USD currencies | Verified live | AFN present on all product/cart pages |
| Brand logo upload via admin UI | Fixed, verified locally; prod deploy pending | `a3766b6` |
| Remove price/cache/config panels 500 error | Verified live | `79a2a3a` |
| Admin hidden from anon users | Verified live | 302 redirect to login |

---

## 2. What was delivered (vs audit §20 plan)

**Foundation (Phase 1-2, earlier sessions)**
- `ext/asaan` project extension (`manifest.php`, custom templates, i18n, setup)
- `app/Support/AsaanDate.php` Solar Hijri converter (numeric format, Afghan month names)
- `asaan:setup` artisan command: seeds locales fa(دیری)/ps(پښتو)/en, AFN+USD currencies, Afghanistan, kitchen-tool catalog, sets Afghan date mode
- Config cache rebuilt on every deploy (entrypoint.sh)

**Storefront branding (verified live)**
- `<title>` uses site label "ASAAN" throughout
- Language selector: English / دری / پښتو — all three render correctly
- Currency selector: AFN / US$ — both switchable
- All 13 product-category pages + home render HTTP 200, no console errors
- AFN currency shows Western digits (Standard number formatter via `num_formatter => Standard`)
- Account pages (history, subscription) + stock pages use overridden `AsaanDate` templates

**Admin (verified live)**
- All 21 admin panels render HTTP 200 after login (dashboard, order, product, catalog, attribute, supplier, customer, group, locale, locale/site, locale/language, locale/currency, settings, log, type, service, plugin, coupon, rule, review, subscription, basket)
- Price/cache/config: show friendly stub notices ("not available in this version...") instead of 500 crash
- Dashboard order panel dates show Afghan Solar Hijri (`1405/06/20`)
- Anonymous users: 302 redirect to `/login` on all admin jqadm routes; 404 on jsonadm
- Brand logo upload: fix pushed (`a3766b6`) — the stock Settings panel had a bug where `fromArray()` unconditionally wrote a `resource` key into the site config, which the site manager now rejects. The override (`ext/asaan/lib/custom/src/Admin/JQAdm/Settings/Asaan.php`) strips the `resource` subtree from both POST input and existing DB config before saving, enabling the logo/icon file upload fields to work. Verified locally (302 redirect after upload, storefront shows uploaded logo).

**CMS pages**
- Terms, Privacy, Cancellation, Contact, Company — all HTTP 200

---

## 3. Price / cache / config 500 fix (commit 79a2a3a)

The admin panels `price`, `cache`, `config` returned HTTP 500 because the classes `Aimeos\Admin\JQAdm\{Price,Cache,Config}\Standard` were removed in the current `ai-admin-jqadm` version (routes still resolve, but no backing class). Fixed by adding stub classes in `ext/asaan/lib/custom/src/Admin/JQAdm/{Price,Cache,Config}/Standard.php` that extend the base client and return a translated notice explaining where the function moved (prices now managed in the Product panel's "prices" tab). Verified live: all three panels return HTTP 200 with a human-readable notice.

---

## 4. Settings panel / brand logo upload fix (commits 3a133d1, a3766b6)

**The bug:** the stock `Settings\Standard::fromArray()` in `ai-admin-jqadm` unconditionally sets `resource/email/from-name` into the site config array. On this version of `aimeos-core`, `checkConfig()` in the site manager (`MShop\Locale\Manager\Site\Standard`) rejects any site config key starting with `resource`, `madmin`, or `mshop`. Every attempt to save the Settings panel crashed — making brand logo/icon upload via admin UI impossible.

**The fix (two commits):**
1. `3a133d1` — override `fromArray()` in `ext/asaan/lib/custom/src/Admin/JQAdm/Settings/Asaan.php` to unset `resource` from the incoming POST config before saving. Config line `admin/jqadm/settings/name => Asaan` in `config/shop.php`.
2. `a3766b6` — also strip `resource` from the existing DB site item's config before the merge, because the site item in the DB can carry a pre-existing `resource` subtree (set before the `checkConfig` guard was introduced upstream). Both POST and existing config are now cleaned.

**Current prod status:** both commits are pushed to `origin/main`. `79a2a3a` (stubs) is confirmed live; `a3766b6` has not yet appeared on prod (Render deploy pending/in-progress). Verified locally: settings save returns 302, storefront logo updates immediately.

**Note:** the Settings panel's "Shop e-mail" field (`item[locale.site.config][resource][email][from-email]`) is intentionally dropped by this fix. In this Aimeos version, per-site email configuration is not allowed at site level (application-owned namespace). Shop email sender should be configured via Laravel's `config/mail.php` instead.

---

## 5. Admin security (blocked resources)

| Route | Auth response | Anon response |
|---|---|---|
| `/admin/default/jqadm/search/dashboard` | 200 | 302 (→ login) |
| `/admin/default/jqadm/search/product` | 200 | 302 (→ login) |
| `/admin/default/jsonadm/product` | 200 | 404 |
| `/login?locale=en` | 200 | 200 (login page) |

Anonymous users cannot reach any admin panel without logging in.

---

## 6. Git history (this session)

| Hash | Time | Message |
|---|---|---|
| `9b5b641` | 17:49 | Cleanup: remove temporary debug header from Handler |
| `79a2a3a` | 18:16 | Fix: add stub panels for removed price/cache/config JQAdm resources |
| `3a133d1` | 18:42 | Fix: settings panel save failed; enable logo/icon upload via admin UI |
| `a3766b6` | 18:50 | Fix: strip resource from existing site config too, not just POST input |

---

## 7. What remains for the client / operator

1. **Render deploy:** ensure commit `a3766b6` is deployed (check Render dashboard). Once live, the Settings panel (admin → Settings → Save) will accept the brand logo/icon upload. Upload the `ASAAN.af.png` file as "Shop logo" and optionally a favicon-sized image as "Shop icon".
2. **Brand logo:** the storefront currently shows the default Aimeos theme logo. Once the settings fix deploys, upload the ASAAN brand image via admin Settings.
3. **Product content:** demo catalog was seeded with kitchen-tool categories and product data. Product descriptions may need Dari/Pashto translations (currently en/af entries only).
4. **PDF/Email branding:** order confirmation emails and PDF invoices still contain placeholder company text ("Example company / example.com"). These use Aimeos templates in `vendor/` — override in `ext/asaan/` as needed.
5. **Social links:** footer social links are `href="#"` placeholders.
6. **PWA / manifest:** no web manifest, no apple-touch-icon, no theme-color set.

---

## 8. Files changed in this session

### Extension override (`ext/asaan/lib/custom/src/Admin/JQAdm/`)
- `Price/Standard.php` — stub panel, returns "not available" notice
- `Cache/Standard.php` — same
- `Config/Standard.php` — same
- `Settings/Asaan.php` — overrides `fromArray()` to strip rejected `resource` config key

### Extension templates (`ext/asaan/client/html/templates/`)
- `account/history/body.php` — Afghan Solar Hijri dates on order history
- `account/subscription/body.php` — same for subscription dates
- `catalog/stock/body.php` — same for stock/back-in-stock dates

### Config
- `config/shop.php` — `num_formatter => Standard` (Western digits), `admin/jqadm/settings/name => Asaan`, date separator config, route middleware `asan.guard`

### App support
- `app/Support/AsaanDate.php` — Solar Hijri date converter (numeric, Afghan months)
- `app/Console/Commands/AsaanSetup.php` — artisan command: locale/currency/country/catalog seeding, site config

### Infrastructure
- `docker/entrypoint.sh` — DB migration, aimeos:setup, asaan:setup, config/view cache, admin vendor.js Markdown patch
- `render.yaml` — Render deploy config (Docker, PostgreSQL 16, Frankfurt region, free tier)

### Admin panel customization
- `ext/asaan/admin/jqadm/templates/dashboard/` — admin dashboard override
- `ext/asaan/admin/jqadm/templates/order/` — admin order panel override
- `ext/asaan/admin/jqadm/templates/settings/` — admin settings panel override
