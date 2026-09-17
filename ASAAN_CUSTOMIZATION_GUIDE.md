# ASAAN — How to Customize the Website and the Dashboard

A practical guide. Official Aimeos docs are listed at the end; this file explains
how customization actually works in THIS project and where to put your changes.

## Rule of thumb

Never edit files inside `vendor/`. Aimeos publishes all its templates, stylesheets
and config from `vendor/`, so any change there is silently overwritten on the next
deploy. Everything you change goes into one of two project-owned places:

1. **`ext/asaan/`** — the project Aimeos extension (looks, templates, translations)
2. **`resources/views/vendor/shop/`** — the Laravel page shell (header, footer, layout)
3. **`config/shop.php`** — behaviour switches (currencies, countries, features)

---

## 1. What's already customized here

| Piece | Where |
|---|---|
| Storefront shell (navbar, footer, favicon, PWA) | `resources/views/vendor/shop/base.blade.php`, `resources/views/vendor/shop/` |
| Language dropdown shows native names | `ext/asaan/client/html/templates/locale/select/language-body.php` |
| Afghan dates on history/subscription/stock | `ext/asaan/client/html/templates/account/...`, `catalog/stock/body.php` |
| Admin navbar, settings form, dashboard | `ext/asaan/admin/jqadm/templates/...`, `ext/asaan/lib/custom/src/Admin/JQAdm/...` |
| Brand logo, favicon, PWA icons | `ext/asaan/media/brand/` (seeded into `public/assets/` on every deploy) |
| Languages, currencies, dates, demo catalog | `app/Console/Commands/AsaanSetup.php` (`asaan:setup`) |
| Behavior switches | `config/shop.php` |

Why it's organised this way: the file inside `ext/asaan/...` has the **exact same
relative path** as the original file in `vendor/aimeos/*`, and Aimeos prefers the
extension copy automatically. That's the whole trick of template overrides.

---

## 2. Customize the website look (frontend)

### a) Colors and simple style changes (no code)

The fastest lever. Run the shop, log into the admin panel, open the **Settings**
panel, and in the **Theme** tab you get the CSS variables (primary color etc.)
controls. Save, and the front page recovers. All of that leaks into the page as
CSS variables via `base.blade.php`.

### b) Your own stylesheet / javascript

Create your own CSS (or JS) in a project directory and include it **after** the
theme files in `resources/views/vendor/shop/base.blade.php`, inside the `<head>`
(after the `app.css` / `aimeos.css` lines for CSS) and near the bottom for JS.

The official approach from the Aimeos docs "Adapt themes":
- Put changes in `public/shop/custom` → your own `aimeos.css` / `aimeos.js`
- Overwrite any stock style by using the same CSS selector with different values,
  or replace/`decorate` a stock Javascript method, e.g.

```js
AimeosCatalogDetail.setupBlockPriceSlider = function() {} // disable
```

### c) Change a template (e.g. product page, basket)

1. Find the original template file under `vendor/aimeos/ai-client-html/templates/client/html/<component>/<name>/{body,header}-*.php`
2. Copy it to `ext/asaan/client/html/templates/<same path>` (the relative path after `templates/` must match exactly)
3. Edit your copy. The `z-` prefix in `ext/asaan/manifest.php` guarantees your file wins.
4. Commit + push → the deploy picks it up automatically.

The stock templates live mainly in:
- `vendor/aimeos/ai-client-html/templates/client/html/` (website components)

A template override example already in this repo:
`ext/asaan/client/html/templates/locale/select/language-body.php`

Templates are plain PHP with `$this->get(...)` data access and `$this->translate(...)`
helpers. You can also use Blade by naming the file `.blade.php` (see the docs).

---

## 3. Customize the dashboard (admin panel, /admin)

"Dashboard" in the Aimeos world means the **admin panel itself** (the thing you
log into at `/admin`). Customizing it works exactly like the frontend, just with
a different path prefix:

1. Find the original under `vendor/aimeos/ai-admin-jqadm/templates/admin/jqadm/<panel>/...`
2. Copy it to `ext/asaan/admin/jqadm/templates/<same path>` (again: matching relative path)
3. Edit. Examples already here: `ext/asaan/admin/jqadm/templates/page.php` (the admin shell), `.../settings/item.php`, `.../order/list.php`, `.../dashboard/item-order-latest.php`.

### Change the admin menu (which panels appear)

Menu order and entries are config, not templates:
- `ext/asaan/config/admin/jqadm/navbar.php` — panel order and grouping
- `ext/asaan/config/admin/jqadm/resource.php` — which user groups may open each panel

### Change panel behaviour via PHP (decorators)

Behaviour changes (not just HTML) go into `ext/asaan/lib/custom/src/Admin/JQAdm/...`
and are activated through `ext/asaan/config/admin/jqadm/common/decorators/default.php`.
Examples already in the project:
- `Settings/Asaan.php` — heals the site-settings save
- `Common/Decorator/Asaan.php` — restricts admin UI languages to en/fa/ps
- `Dashboard/Order/Asaan.php` — removes a dashboard sub-widget

### Change what the admin shows for products, orders etc.

Every panel = templates (colours/layout) + PHP classes (handling). For common
tasks like "show an extra column in the order list", override the list template.
For "process data differently when saving", use a decorator or a new subpart.

---

## 4. Customize languages, currency, dates, site

All the shop data setup lives in `app/Console/Commands/AsaanSetup.php`. It runs on
every deploy (`docker/entrypoint.sh` → `php artisan asaan:setup`) and is
**idempotent** (safe to re-run). Fiddle there for:
- which languages / currencies the shop offers
- the default currency / language / country
- the demo catalog content and translations
- Afghan date handling (`app/Support/AsaanDate.php`)

Important deploy reminder: any image you upload through the dashboard into
`public/assets/1.d/` disappears on the next deploy (Render's disk is wiped and
that folder is git-ignored). The safe pattern, already used here, is to put brand
files in `ext/asaan/media/` and let `AsaanSetup` copy them into place on each boot.

---

## 5. Change translations

- Small, few strings (a handful): `config/shop.php` → `'i18n' => [...]` (see docs).
- Whole language packs: keep them in `ext/asaan/i18n/<domain>/<lang>.po` and rebuild
  the `.mo` files (the process used for the Dari/Pashto packs).

---

## 6. Workflow

1. Edit files under `ext/asaan/`, `resources/views/vendor/shop/`, `config/shop.php`
2. Commit + push to `main` → Render builds + deploys automatically
3. Check the live site. Clear browser cache / hard-reload if the frontend does not
   update (the mini-basket is cached in the session; add a product to refresh it).

---

## Official Aimeos documentation

- Laravel customization (config, translations, blade, multiple shops): https://aimeos.org/docs/latest/laravel/customize
- Adapt themes (CSS/JS overrides): https://aimeos.org/docs/latest/frontend/html/adapt-themes
- Overwrite frontend templates: https://aimeos.org/docs/latest/frontend/html/overwrite-templates
- Overwrite admin templates: https://aimeos.org/docs/latest/admin/jqadm/overwrite-templates
- Implement / extend admin panels: https://aimeos.org/docs/latest/admin/jqadm/implement-panels and `/extend-panels`
- Create extensions (skeleton): https://aimeos.org/extensions
- All config keys reference: https://aimeos.org/docs/latest/config

---

## 6. Change homepage banner text and button (no code needed)

The big headline + button on the covered image at the top of the homepage are
stored inside the homepage CMS page, not in code. Two ways to change them:

**Easy way (recommended):**

1. Log in at `https://assan-hmn5.onrender.com/admin`
2. Menu: Dashboard → Home (CMS page list)
3. Click the row labeled "Demo content: Home" → Edit
4. A visual drag-and-drop editor opens (GrapeJS). Click the text or button on
   the canvas and type your replacement, e.g. change the button label, or "Select
   your unique style".
5. Press the SAVE button (disk icon) — the change appears on the storefront right away.

**Careful:** for a button or text to be readable over the banner, don't hand-type a
color into the editor's "style" box. The site removes custom color tags on purpose
(for safety) when it shows the page. Instead, keep using the **class** `btn
btn-primary` (green button, white text) which is already styled by the site's
theme. To make any element a styled button, set its Class to `btn btn-primary`.
If you want a different colour, tell me and I'll add a matching class once
(Dashboard icon, H1, accent → each can get its own colour class).

**Alternative (data-level) way:** I changed "Take a look" → "Shop now" and gave
it the green background on 2026-09-15 in the live database. If you ever need it
restored, it's the homepage content row in the database (`mshop_text`, label
"Demo content: Home", type "content"). Re-running `php artisan asaan:setup` does
NOT overwrite this text/banner (it only re-seeds brand images and the demo
catalogue, not the CMS banner text).

---

## 7. Site appearance light switches (CSS classes, no layout change)

| Want | Use on the element | Result |
|---|---|---|
| Solid green button, white text | Class `btn btn-primary` | Site's themed button |
| Rounded corners | `--ai-radius: 50` already set for buttons | rounded |
| Solid dark/contrast on any light banner | Class `btn btn-dark` | dark button, white text |