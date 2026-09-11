# ASAAN Customization Audit (Phase 1)

Date: 2026-09-12
Project: Laravel + Aimeos ecommerce, Docker (Render deploy, PostgreSQL 16 on Render).
Objective per `ASAAN_OpenCode_Customization_Instructions`: convert the stock shop into **ASAAN** — single-shop, 3 languages (Dari/Pashto/English), 2 currencies (AFN/USD), Afghanistan-focus, dual date system (Gregorian + Afghan Solar Hijri). This document is the required baseline (`01_AUDIT_AND_BASELINE.md`).

---

## 1. Architecture summary

- **Runtime**: PHP 8.3, Laravel `dev-master`-based, Aimeos `dev-master` (2025.x series) installed as Composer packages: `aimeos/aimeos-laravel`, `aimeos/aimeos-core`, `aimeos/aimeos-base`, `aimeos/ai-client-html` (storefront), `aimeos/ai-admin-jqadm` (admin panel), `aimeos/ai-controller-jobs` (order emails/PDF), `aimeos/ai-admin-jsonadm` + `graphql` admin APIs.
- **Deployment**: Docker (Render, `render.yaml`), auto-deploy on commit to `main`. `docker/entrypoint.sh` runs DB migrations + `php artisan aimeos:setup` (now gated to seed demo data once, see prior fix commit `0c51eaf`) + admin account creation.
- **Stack details**: PostgreSQL 16, sessions/cache on file, queue sync, logs stderr. Config lives in `config/shop.php` (project override). DB connection via Laravel `config/database.php` from `DATABASE_URL`.
- **No project templates/views yet**: the repo has zero custom Aimeos templates; everything renders from `vendor/`. Blade views exist only for auth/pagination/errors/mail (Laravel Breeze defaults).

## 2. Shop/site architecture

- Engine is **multi-site capable**: `mshop_locale_site` table, `{site}` route segment for admin, site relationships required by the commerce engine.
- The app runs **one site**: code `default`, label "Default" (`mshop_locale_site`), theme `default`. No `SHOP_MULTISHOP`/`SHOP_MULTISHOP`/`SHOP_MULTIROUTE`/`SHOP_MULTILOCALE` env set → single-domain URLs without `{site}`/`{locale}` prefix.
- Site name shown in <title> fallback = `mshop_locale_site.label` (`contextSiteLabel`, set in `vendor/aimeos/ai-client-html/src/Client/Html/Common/Decorator/Context.php`; used with fallback `'Aimeos'` in ~12 storefront header templates, e.g. `templates/client/html/catalog/lists/header.php:36`).

## 3. Admin navigation

Menu defined in `vendor/aimeos/ai-admin-jqadm/config/admin/jqadm/navbar.php`:

| Key | Items |
|---|---|
| 0  | dashboard |
| 10 | order, subscription, basket (Sales) |
| 20 | product, catalog, attribute, supplier (Goods) |
| 30 | customer, group (Users) |
| 40 | coupon, rule, review (Marketing) |
| 50 | settings (single panel) |
| 60 | service, plugin (Setup) |
| 70 | locale, locale/site, locale/language, locale/currency |
| 80 | type |
| 90 | log |

Access groups per resource: `admin/jqadm/resource/*/groups` in `.../config/admin/jqadm/resource.php`. Key ones: `site` → admin/super; `locale/site`, `locale/language`, `locale/currency` → **super only**; `locale`, `settings`, `service`, `plugin`, `type`, `log`, `group`, `setup` → admin/super; everything else → admin/editor/super.
Enforcement chokepoint: `vendor/aimeos/ai-admin-jqadm/src/Admin/JQAdm.php:46-48` throws 403 if the user's groups miss the resource; plus Laravel Gate `admin` in `JqadmController` actions. Sidebar auto-hides items the user cannot access (`.../templates/admin/jqadm/page.php:99,188`).

## 4. Routes for unwanted management (needed for blocking)

- Admin routes: `vendor/aimeos/aimeos-laravel/routes/aimeos.php`. JQAdm group: `admin/{site}/jqadm/{action}/{resource}` with `web, auth` middleware (config `shop.routes.jqadm`, `config/shop.php:76`). Actions: file, batch, copy, create, delete, export, get, import, save, search.
- Same "resource groups" concept also covers JSON/GraphQL admin APIs (`admin/jsonadm/resource/*`, `ai-admin-jsonadm`).
- Site switcher widget: sidebar `page.php:141-170` (gated by `resource/site/groups` = admin/super).
- Settings panel: `admin/jqadm/search/settings` serverside; subparts `theme`, `ai` (`config/admin/jqadm.php`).
- Site editor: `admin/jqadm/search/locale/site` (super only).

## 5. Current locales (languages)

- Enabled in a fresh DB: `en` and `de` only (`setup/default/data/locale.php`, `MShopAddLocaleLangCurData.php` seeds full ISO list with `status=0`). `fa` exists as a language row but **disabled**; `ps`, `ur` disabled; `prs`/`dar` don't exist in seed data.
- Storefront selector builds its list from `mshop_locale` rows filtered by site, ordered by `pos` (`vendor/aimeos/ai-controller-frontend/src/Controller/Frontend/Locale/Standard.php:217-277`).
- Translation catalogs present (gettext `.po`/`.php` under each package `i18n/code/`):
  - **Storefront** `ai-client-html/i18n/code/`: 46 languages incl. `fa`, `en`, `de`. **No `ps`, `prs`, `dar`.**
  - **Admin** `ai-admin-jqadm/i18n/code/`: 44 languages incl. `fa`. No `ps`/`prs`/`dar`.
  - **Core domains** (`aimeos-core/i18n/mshop/`): `fa.po` present.
  - **Name lists** `aimeos-core/i18n/{country,currency,language}/`: 34–35 languages only — **no `fa`, `ps`, `ur`**. So `fa` covers UI strings but not country/currency/language *name* catalogs.
- **Conclusion**: the technically valid language id for Dari in the installed translation system is `fa` (Aimeos uses 2-letter codes; no `prs` support in catalogs). Pashto = `ps` (valid ISO code; no catalog → falls back to English strings unless we ship one).

## 6. Current currencies

- Enabled in fresh DB: `USD` (pos 0), `EUR` (pos 1). Wide ISO seed otherwise disabled.
- Formatting: `\NumberFormatter` (intl, `num_formatter => 'Locale'`, `config/shop.php:66`; `aimeos-base/src/View/Helper/Number/Locale.php`).
- Currency **symbol/name**: rendered by translating the currency code through the `currency` catalog (`common/summary/detail.php:111` `translate('currency', 'EUR')`). No per-currency symbol config. EUR→"€", USD→"US$" in `aimeos-core/i18n/currency/en.po`. **AFN has no entry in the currency catalogs** (only 34-ish languages incl. `en`/`de`, none has AFN). Need `currency` overrides for AFN (+ name).

## 7. Current countries

- Country list is config-driven: `mshop` `common/countries` (`vendor/aimeos/aimeos-core/config/common.php:4-37`).
- Used in checkout address forms (`common/partials/address.php`), account profile, admin order/customer editors.
- Shipping restrictions by country via service decorator `Country.php`. Country names come from `i18n/country/*` catalogs (no `fa`/`ps`).
- Afghanistan is NOT currently the default country.

## 8. Current date handling

- **Timezone**: `config/app.php:128` → `'timezone' => 'UTC'` (hard-coded; no env var).
- **Storage**: naive `Y-m-d H:i:s` UTC strings (`Aimeos\MShop\Context::datetime()`; order `datepayment`; Laravel timestamps bound from the same context). Zero timezone-conversion code anywhere.
- **Storefront displays** (all `date_create(...)->format( $this->translate('client','Y-m-d') )`):
  - `account/history/body.php` (order date :90-92,153, payment :165-167, delivery :178-180)
  - `account/subscription/body.php` (:93,129,155-156,167-168)
  - `catalog/stock/body.php` (:44,84,89 "back on")
  - HTML5 date inputs in `checkout/standard/serviceattr-partial.php` and `common/partials/address.php` (birthday) — device-local inputs, no conversion.
- **Emails/PDF** (`ai-controller-jobs/templates/controller/jobs/order/email/`): `payment/text.php:17`, `payment/html.php:18`, `payment/pdf.php:169-170`, `delivery/text.php:17`, `delivery/html.php:18` — same `Y-m-d` pattern.
- **Admin**: raw DB strings in all list/item templates (`getTimeCreated()`, `getDatePayment()` etc.); `Datetime` view helper converts to `2026-09-12T10:30` for flatpickr edit inputs (`ai-admin-jqadm/src/Base/View/Helper/Datetime/Standard.php`); dashboard charts render client-side with moment.js UTC.
- **No Intl date formatting anywhere.**

## 9. Date libraries/packages

- Installed: nothing calendar-specific. `nesbot/carbon` present (transitive via Laravel). No `jalali`, `verta`, `jdf`, no `ext-intl` requirement (only polyfills for IDN).
- **No Solar Hijri implementation exists** → must be added. Swiss-army option: add a small, actively maintained package (candidate `hekmatinasser/verta` / `morilog/jalali`) OR a self-contained converter class in `app/`. Numeric Afghan dates (`1405/06/21`) are identical to Iranian Solar Hijri numerically — the Iran/Afghanistan difference is **only month names**, which the spec prefers to avoid by using numeric dates.

## 10. Date storage/database behavior

- All date/time preserved as-is in DB (UTC naive strings). Sorting/querying/aggregates (`cdate`, `cmonth`, etc.) operate on those strings.
- **Rule**: convert only at presentation layer; never store formatted Afghan strings. Machine-readable API/JSON dates must stay stable (convert in frontend JS at display time only).

## 11. All user-facing Aimeos references

- **Storefront**: pages `<title>` fallback `... | Aimeos` (site label empty); `<meta name="application-name" content="Aimeos">` (e.g. `catalog/lists/header.php:74`, plus ~11 more header templates); stock logo `public/vendor/shop/themes/default/assets/logo.png` (used by `vendor/aimeos/aimeos-laravel/views/base.blade.php:39,106` when site logo unset).
- **Admin**: sidebar logo image pulled from `https://aimeos.org/check/...` + "update" link (`.../templates/admin/jqadm/page.php:135-136`); footer link to Aimeos GitHub issue tracker (`page.php:263`); login pages titled "Aimeos administration interface" (`vendor/aimeos/aimeos-laravel/views/admin/index.blade.php:7`, `jqadm/index.blade.php:13`); external CDN fonts/scripts in admin blades; admin page security list includes openai/deepl/openstreetmap (internal config only, not customer-facing).
- **Emails**: `X-MailGenerator: Aimeos` header; sender currently "Example" `<hello@example.com>` (`config/mail.php:94-95`).
- **Invoices/PDF**: placeholder "Example company / Example address / example.com" (`ai-controller-jobs/templates/controller/jobs/order/email/payment/pdf.php` ~50-90, voucher PDF too).
- **Blade**: `config('app.name')` fallback "Aimeos" (`.env.example`, `render.yaml` APP_NAME=Aimeos) appears in Laravel mail footer `© YYYY Aimeos` (`resources/views/vendor/mail/*/message.blade.php:24`).
- **Legal/attribution to KEEP**: package licenses (LGPLv3/MIT), copyright headers in vendor templates, Aimeos trademark/legal notices required by license.

## 12. External links

- Storefront: none actionable (social links are `href="#"` placeholders in `base.blade.php:109-112`).
- Admin: `https://aimeos.org/check/...`, Aimeos GitHub issue tracker, CDN resources (fonts.googleapis.com / gstatic / bootstrapcdn-ish), OpenStreetMap tiles for address maps (`customer/item-address.php:386`, `supplier/item-address.php:385`).
- `robots.txt` allows everything.

## 13. Emails/invoices

- Order mails: `controller/jobs/order/email/{payment,delivery}` text+html, subscription, voucher — embed site logo, use `translate('controller/jobs','Y-m-d')` for order date, sender from config `resource.email.from-*` (site config override possible via `locale.site.config`).
- Account/customer mails: `customer/email/account`, `customer/email/watch`.
- Password reset/email verification: Laravel mail blades (app name from `config('app.name')`).
- Invoice/PDF: `order/email/payment/pdf.php` (placeholder company data + order date `Y-m-d`), voucher PDF.
- No real sender configured; queue sync (`sync`).

## 14. Metadata / PWA

- No PWA: no web manifest, no theme-color, no apple-touch-icon. `public/build/manifest.json` is the Vite build map (unrelated), generated for `public/vendor/shop` theme assets? (Assets are served from `vendor/shop/themes/default/`; the repo owns these published copies.)
- Favicon: fallback `public/vendor/shop/themes/default/assets/icon.png` + stock `public/favicon.ico`.
- Site logo/icon uploaded via admin Settings panel → stored in `public/aimeos/` (fs-media) and referenced from `mshop_locale_site.logo/icon`; storefront `base.blade.php` and emails use it automatically.

## 15. Plugins/extensions

- **No custom Composer extensions** installed (`composer show --type aimeos-extension` empty apart from the standard ai-* packages). `ext/` directory does not exist, BUT looking it up: `Aimeos\Shop\Base\Aimeos::get()` scans `base_path('ext')` and loads any subdirectory containing a `manifest.php` — so **a project extension can be added without Composer changes** (templates, i18n, and config overrides load automatically; class autoloading still needs Composer-PSR-4 or a manual loader).
- Aimeos core modules (service providers, plugins listed in admin) are ecommerce core — must NOT be uninstalled (audit table in Final Report).
- Demo data: `DemoAddCatalogData`, `DemoAddProductData` and friends only created `en`/`de` text/media content.

## 16. Files that should remain untouched

- `vendor/` **unless technically unavoidable** (document each change; currently only a runtime Markdown-patch on one admin JS file already exists via `docker/entrypoint.sh`).
- Aimeos DB schema tables and relationships (site architecture stays intact).
- License/copyright headers in templates and packages.
- `composer.lock` semantics — will be updated only when adding a dependency.
- Laravel framework files, `public/vendor/shop/...` published theme (unless replacing brand assets, which are ours to override: `assets/logo.png`, `assets/icon.png`, favicon).

## 17. License / attribution requirements

- Keep Aimeos copyright/license notices (LGPLv3 for core packages; MIT for `aimeos-laravel`).
- Do not claim ASAAN removes upstream attribution from source files.
- Do not remove legal links/notices that are required by packaging.

## 18. Baseline tests (recorded)

Storefront/admin verified live (prior sessions, `frontend_check2.js` + manual):
- Homepage, all 13 category list pages, product detail, search, admin login + panel, CMS pages (`/p/{terms,privacy,cancel,contact,about}`), checkout page — all HTTP 200, no console errors, no ≥400 assets.
- Admin login works (role check OK), dashboard renders with 0 orders so far.
- Language selector lists English/German; currency USD/EUR (baseline to be replaced by fa/ps/en + AFN/USD).
- **Pre-existing failures now fixed**: catalog ID instability across boots (demo reseed) — fixed by `0c51eaf`; broken footer CMS pages `/p/{cancel,contact,about}` — fixed by creating pages.
- **Remaining baseline defects (not yet fixed)**: email sender "Example" placeholder; invoice/PDF placeholder company text; no ASAAN logo; `app.name` "Aimeos"; site label empty (titles fall back to `| Aimeos`); PWA absent; social links dead.

## 19. Implementation plan (ordered)

1. **Foundation**: create `ext/asaan` project extension (`manifest.php`, custom template/i18n/config dirs); add `app/Support/AsaanDate.php` (Solar Hijri converter/formatter, Afghan month names, numeric format) registered + helper; add site-config reader for date mode.
2. **Date system** (`04`): admin site-config setting `Customer date format/calendar` (Afghan default, Gregorian) in existing Settings panel via template override; central `AsaanDate::fmt()`; apply to storefront order/subscription/stock dates, admin dashboard dual-format display `2026/09/12 | 1405/06/21`, emails + invoice PDFs; keep API/timestamps intact.
3. **Locale/currency/country** (`03`): DB locale matrix (fa,ps,en)×(AFN,USD), default fa+AFN; enable `fa` (presented **Dari/دری**) + `ps` (**Pashto/پښتو**) in selector; ship `fa`+`ps` UI language catalogs (override i18n) and `currency`/`country`/`language` name-list overrides incl. AFN; Afghanistan as relevant country (default address country, keep shipping country list); translate core demo texts or document fallback.
4. **Single-shop** (`02`): hide site switcher + locale/site|language|currency + generic locale management from admin UI (navbar override + resource groups tightened), hide locale/language/currency/country management controls; block direct JQAdm access to site/locale resources for non-super; keep engine multi-site (DB untouched). Test 403s directly.
5. **Branding** (`05`, `06`): site label → "ASAAN" (DB via admin), logo/icon upload via Settings panel using `ASAAN.af.png` + replace shipped default logo/icon/favicon; app name → ASAAN in `.env.example`/`render.yaml`/`config/mail.php` sender; titles use site label; storefront + admin templates overridden to drop Aimeos logos/links; admin login blades rebranded; emails/PDF rebranded (neutral + ASAAN), sender "ASAAN"; error pages neutral; metadata (application-name, og, PWA manifest) added.
6. **Admin hygiene**: plugin/module audit table; keep core, hide irrelevant; verify settings panel date setting; keep essential features.
7. **Verify** (`07`): live regression, language/currency switching, date modes, direct-route blocking, browser console, security of hidden actions.
8. **Final** (`08`): `ASAAN_CUSTOMIZATION_FINAL_REPORT.md`, final search for Iranian month names / stray "Aimeos" / external links.

## 20. Risks / unknowns

- **Dari content**: demo products/categories carry `en`/`de` texts only. In Dari/Pashto, product titles/descriptions will be empty unless translated (decide scope).
- **Pashto translations**: no upstream catalog; starter catalog from us is approximate; fallback to English for untranslated strings (Aimeos GetText falls back to en).
- **Locale code**: `fa` labeled "Dari" is the only technically valid choice in the installed system; declined Iranian month names are excluded from any date display anyway (numeric preferred).
- **AFN formatting/symbol**: no upstream catalog entry; we ship one. Choice of symbol (؋ vs "AFN") affects all price displays.
- **`num_formatter Locale`** for fa/ps locales may change digit grouping/separators; verify prices render sanely (intl may use Persian digits for `fa` in some ICU builds — must test and, if unexpected, pin separators).
- **Vendor-touched runtime patch** (`docker/entrypoint.sh` sed): must be preserved and re-verified after admin JS changes.
- **Frontend caching** (client/html cache) may mask changes during verification; force cache clear after deploys.
- **Composer changes**: adding a calendar dependency alters `composer.lock`; local PHP 8.3 + Composer 2.10 available → safe to regenerate.