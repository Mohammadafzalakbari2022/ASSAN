# ASAAN Delivery System — Plan

Status: **PLAN ONLY — nothing built yet.**
This document describes what we are going to build, how it will work, what it
needs, and how we will test it. No code has been written.

---

## 1. What this is

Today the ASAAN shop takes orders, but there is no way to hand an order to a
delivery person and no way to watch that delivery happen. This plan adds a
delivery system with two staff-facing parts:

1. A **Delivery section inside the admin dashboard** — for you to create
   delivery accounts, assign orders, and watch live locations on a map.
2. A **Delivery app for your delivery staff** — a separate login and a simple
   phone screen that shows their assigned orders, the customer address, and a
   "Delivered" button.

Nothing about the customer-facing shop changes. It keeps working exactly as it
does now.

---

## 2. Decisions already agreed

| Question | Answer |
|---|---|
| Who assigns orders to delivery staff? | **The admin. Only the admin.** |
| Who creates delivery staff accounts? | **The admin.** Staff never register themselves. |
| How do delivery staff log in? | **Their own separate link** (`/delivery`), same account system, separate door. |
| Where is the admin dashboard? | The **same admin we already have** (`/admin`) — no new dashboard. |
| Is the phone number stored? | **Yes** — the account table already has mobile/telephone fields. |
| Is there a delivery tab in admin? | **Yes, added by us** — a Delivery section holding all delivery info. |
| GPS tracking? | **Yes — free.** OpenStreetMap map + the phone's own GPS. |
| Must it work in Afghanistan? | **Yes.** OpenStreetMap covers Afghanistan; no Google, no paid service. |
| Cost? | **Zero.** No map fees, no per-request fees, no payment card. |

---

## 3. The three doors (logins)

There will be three separate ways in, so nobody lands in the wrong place:

1. **Customer door** — the existing "My account" login. Unchanged.
2. **Admin door** — the existing `/admin` dashboard. Unchanged.
3. **Delivery door** — **new**, at `/delivery`. For delivery staff only.

Guard rules (to be enforced on the server, not just hidden in the page):

- A customer can **not** open the admin or the delivery app.
- A delivery person can **not** open the admin or the customer account area.
- Only the admin can create or remove delivery accounts.

Right now the admin flag is the existing `superuser` field on the account.
Requirement: **add a ways to mark an account as "delivery"** and keep the
existing `superuser` as "admin". The plan is to add one small field, `role`, to
the account table with values `customer` / `delivery` / `admin`. Existing
accounts keep their current meaning (the one `superuser` account stays admin).

---

## 4. What already exists and will be reused

We are not starting from zero. Verified in the live database:

- **Accounts** already carry: admin flag (`superuser`), status, `mobile`,
  `telephone`, full address, `latitude`/`longitude`. So phone storage is done.
- **Orders** exist (42 of them) in `mshop_order`, each with `statusdelivery`
  (the delivery stage) already built in.
- **Order addresses** exist in `mshop_order_address` — the customer's delivery
  address and phone for each order.
- **Order products** exist in `mshop_order_product` — what is in each order.
- **Delivery stages** already exist in the shop (not started, in progress,
  dispatched, delivered, refused, returned). We will reuse these exact stages so
  the shop, the customer history, and the admin order panel all agree.

What we add is only the *connecting tissue*: who delivers which order, and where
that person is right now.

---

## 5. What we will build (the two pieces)

### 5.1 Admin — "Delivery" section

Reached from the admin dashboard through a clear **Delivery** entry. It contains
four screens:

**a) Delivery staff list**
- Shows every delivery person: name, phone, email, whether they are active, and
  when they were last seen on the map.
- **Add staff**: admin types name, phone, email, and a password. The account is
  created and marked as delivery.
- **Edit / disable / remove** staff.
- Staff cannot register themselves; this is the only way in.

**b) Assign orders**
- A list of orders that are paid but not yet delivered.
- Admin picks an order, picks a delivery person, and clicks **Assign**.
- The order immediately appears on that delivery person's phone.
- Admin can reassign an order to someone else, or unassign it, before delivery.

**c) Live map**
- A real street map showing every on-duty delivery person as a dot.
- Each dot shows: name, phone, last-seen time ("2 minutes ago"), and which order
  they are carrying.
- The map refreshes by itself every few seconds.
- If someone's dot has gone stale, it is shown greyed out so you know they are
  offline (phone locked, page closed, or no signal).

**d) Status board**
- Which assignments are waiting, started, delivered, or failed.
- Times for each change.
- Failed deliveries show the reason the staff entered.

### 5.2 Delivery app (phone)

A very simple, phone-first screen reached at `/delivery` after login:

**a) Today's deliveries**
- A list of orders assigned to this person today.
- Each row shows: short order reference, customer first/last name, area, the
  amount to collect (if cash on delivery), and the current stage.

**b) One order, opened**
- Full delivery address as text **and** on a small map.
- Customer phone with a **tap-to-call** button.
- What is in the order.
- Amount to collect, if any.
- Big buttons: **Delivered** and **Couldn't deliver**.
- "Couldn't deliver" opens a short reason box (customer not home, wrong address,
  refused, other).

**c) Tracking**
- While the delivery person is on the delivery screen, the phone quietly sends
  its GPS position every few seconds. This is what moves their dot on your map.
- A small, honest banner on the screen: *"Keep this screen open while you are
  out, so your location stays visible to the shop."*

---

## 6. How the tracking works, and its one real limit

**The free stack (all of it free, no account, no card):**

| Job | What we use | Cost |
|---|---|---|
| Draw the map | Leaflet (free map library) | Free |
| Map images | OpenStreetMap tiles | Free |
| Find the phone | The browser's built-in GPS (Geolocation) | Free |
| Store movements | Our own database | Already paid for |

**Why not Google Maps:** it needs a paid account and a payment card, and its
rules restrict storing/displaying tracking data. So we do not use it.

**The honest limit — read this:**

A website can read the phone's GPS **only while that page is open and the phone
is awake.** If the delivery person locks the screen or closes the page, tracking
stops until they open it again. This is true of every browser-based tracker,
including Google's. Apps like Uber only track in the background because they are
installed native apps with paid background services — not websites.

Practical result: the delivery app must stay open on the phone during a run. We
will make that obvious on screen, and the admin map will clearly show who is
currently offline and when they were last seen. A true always-on background
tracker would be a later, separate, likely paid step (an installed app). This
plan does not do that.

**Other honest notes:**
- OpenStreetMap street detail is good in big cities (Kabul, Herat, Kandahar,
  Mazar-i-Sharif) and thinner in rural areas.
- GPS needs a data connection; in weak-signal areas the dot may move slowly or
  stop. We will show accuracy and last-seen so you are never misled.
- Constant GPS uses battery. The app will only send while the delivery screen is
  open, not all day.

---

## 7. Data we will add (plain English)

Three small additions. Names are shown so the build is precise.

**1. A staff marker on accounts** — one field, `role`, on the existing account
table: `customer` (default), `delivery`, or `admin`.

**2. A delivery list** (`delivery_assignments`) — one row per handed-out order:
- which order, which delivery person, which admin assigned it
- stage: waiting / started / delivered / failed
- the failure reason (if any)
- the times: assigned, started, finished

**3. A location log** (`delivery_locations`) — one row per position received:
- which delivery person, latitude, longitude, accuracy, and the exact time

The latest row per person is their dot on the map; older rows are their trail.
The trail can be trimmed automatically after a set number of days so the
database does not grow forever.

Nothing existing is deleted or changed in meaning. All additions are new tables
plus one new field, so the risk to the working shop is very low.

---

## 8. End-to-end flow (how an order actually travels)

1. Customer orders in the shop (as today).
2. Order appears in the admin's **Assign orders** list.
3. Admin assigns it to a delivery person.
4. It appears in that person's **Today's deliveries**.
5. Staff taps **Start** (optional) and sets off, keeping the app open.
6. The admin map shows the moving dot.
7. Staff taps **Delivered**. The order's delivery stage in the shop becomes
   **Delivered**, and the assignment closes with a time stamp.
8. If it fails, staff enters a reason; the assignment closes as **Failed** and
   the order shows the matching stage (e.g. refused/returned).
9. Customer history and the admin order panel both reflect the real stage,
   because we use the shop's own delivery stages.

---

## 9. Screens, in plain terms

### Admin
- **Delivery → Staff**: table of staff, Add/Edit/Disable/Remove buttons.
- **Delivery → Assign orders**: two lists side by side — unassigned orders, and
  staff. Select, assign, done.
- **Delivery → Map**: the live map with dots and a side panel of on-duty staff.
- **Delivery → Status board**: a sortable list of assignments and their stages.

### Delivery person (phone)
- **Login** (`/delivery`): email + password.
- **Today**: the assigned list.
- **Order**: address, map, phone, items, amount, Delivered / Couldn't deliver.

---

## 10. Required app behaviour (not optional)

These are standing rules for any app with screens and forms, and the delivery
app must follow them:

**Back / leaving with unsaved typing**
- The "Couldn't deliver" reason box counts as unsaved typing. Pressing back,
  Escape, or the phone's back gesture must ask before discarding it.
- If nothing has been typed, leaving must be immediate — no annoying prompt.
- A dismissed dialog means *stay*, never *discard*.
- After a successful Delivered/Failed save, the form is no longer unsaved, so the
  next back press must not nag.

**Mobile vs desktop**
- The delivery app is phone-first: **no on-screen back/forward buttons** — the
  phone's own back is used.
- The admin is desktop-first: it **gets** back/forward buttons, greyed out when
  there is nowhere to go, with the forward list remembered by us.
- Any UI meant for desktop only is gated by the **operating system**, never by
  window width — a tablet or landscape phone must not be handed desktop controls.

**Desktop keyboard**
- Tab/Shift+Tab move between fields and buttons in visual order.
- Enter submits (except in the multi-line reason box, where it is a newline).
- Escape goes back / closes, through the unsaved-changes check.
- Focus is always clearly visible.

**Errors**
- Any error (wrong login, missing reason, order already delivered by someone
  else) is shown **next to the field or button it concerns**, not in a banner far
  away. All problems are shown at once, and focus moves to the first one.
- No button ever silently does nothing.

**Language and direction**
- The app will be usable in Dari/Pashto as well as English. Nothing left/right is
  hard-coded: layout, padding, alignment, and icons are direction-aware so RTL
  mirrors correctly.

**Rules on both sides**
- Every rule (login required, correct role, order belongs to this person, reason
  required, no double-delivering) is enforced **on the server as well as in the
  screen**. The server is the path nobody can bypass; the screen just makes it
  friendly. Both sides show the same message.

---

## 11. Security

- Delivery staff see **only** their own assigned orders and that customer's
  delivery details. No browsing other orders, no prices/accounts admin.
- The delivery login and the admin login are checked by role on the server.
- Passwords are stored the same secure way the shop already stores them.
- Disabling a staff account immediately stops their login and their tracking.
- Live location is visible only to the admin.
- The temporary map tiles and GPS are public, free services that receive no
  private customer data — only map squares and coordinates are exchanged.

---

## 12. What stays unchanged

- The customer shop, checkout, product pages, and "My account".
- The existing `/admin` dashboard and its order panel.
- The existing admin account.
- Nothing already in the database is deleted or renamed; we only add.

---

## 13. Build phases

Each phase is finished and checked before the next begins. Nothing is deployed
to the live shop until all phases pass their tests.

- **Phase 0 — Setup.** Add the staff marker and the two new tables to the local
  copy. Confirm the live shop is untouched.
- **Phase 1 — Admin staff management.** Create/edit/disable/remove delivery
  accounts with phone. Admin-only.
- **Phase 2 — Assign orders.** List undelivered orders, assign to staff, store
  the assignment.
- **Phase 3 — Delivery app.** Separate login, Today list, order screen with
  address, phone, items, Delivered / Couldn't deliver.
- **Phase 4 — Status sync.** (DONE, verified) Delivered/Failed update the shop's
  own order delivery stage, and the admin order panel and customer history agree.
- **Phase 5 — Tracking.** (DONE, verified) Phone sends position; admin map shows
  dots, last-seen, and a basic trail. Auto-refresh.
- **Phase 6 — Polish and rules.** Back-button protection, keyboard, RTL, error
  placement, offline/stale states, mobile/desktop gating.
- **Phase 7 — Deploy.** Only after hand-testing everything on a copy.

---

## 14. How we will test it (and prove it actually works)

For every important behaviour we check it works, and then we deliberately break
it and watch the test fail, to prove the test is real. In plain terms:

- Create a delivery account in admin → log in at `/delivery` → it works.
- Log in as a customer and try to open `/delivery` and `/admin` → both refused.
- Assign an order → it appears on that staff phone and no one else's.
- Mark Delivered → the shop's order shows Delivered and the customer history
  agrees. **Then break the link on purpose and confirm the order does NOT update
  — proving the update was real, not accidental.**
- Type a failure reason, press back → asked before discarding.
- Leave with nothing typed → no prompt.
- Two staff on two phones at once → two distinct moving dots.
- Turn one phone's location off → that dot greys out with a last-seen time.
- Open the app on a tablet → desktop-only controls do not appear.
- RTL: switch to Dari → the layout mirrors and nothing is stuck to the left.

---

## 15. Risks, stated plainly

| Risk | Honest impact | Handling |
|---|---|---|
| Browser GPS stops when the page closes | Tracking gap | Clear on-screen warning; map shows "last seen"; later native app if truly needed |
| Weak mobile data | Slow or stale dots | Show last-seen and accuracy; do not pretend it is live |
| Rural map detail | Thin street data | Address text + map together, never map alone |
| Battery drain | Phone runs down | Only send while delivery screen open |
| Selling data tile limits (free map) | Could throttle at huge traffic | Keep tile use light; can self-host later if needed |
| Double-delivering an order | Two staff claim one order | Server blocks it and shows a clear message |

---

## 16. Open points I will default unless you say otherwise

1. **Cash on delivery**: recorded simply as an amount shown to staff and marked
   collected on Delivered. No cash reconciliation totals in v1.
2. **Availability**: staff are considered on-duty when their delivery screen is
   open; there is no separate shift/rota screen in v1.
3. **Assigning**: one delivery person per order at a time (reassignable before
   delivery).
4. **History**: location trails auto-trim after 30 days.

---

## 17. Plain-English glossary

- **Admin** — you; the person who runs the shop. Uses `/admin`.
- **Delivery person / staff** — the rider who takes the order to the customer.
  Uses `/delivery`.
- **Assignment** — the link between one order and one delivery person.
- **Dot** — a delivery person's live position shown on the map.
- **Delivery stage** — how far an order is: not started, in progress,
  dispatched, delivered, refused, returned.
- **Leaflet / OpenStreetMap** — the free map library and free world map.
- **GPS** — the phone's built-in location, read by the browser (free).

---

## 18. Verification of facts used in this plan

- Read `routes/web.php`, `routes/auth.php`, `app/Models/User.php`, the users
  migration.
- Queried the live database (read-only): account table columns; order table
  columns; address table name; product/status tables; counts (3 accounts, 42
  orders).
- Confirmed the shop already carries delivery stages and per-order addresses.
- Confirmed the free map/GPS stack and Afghanistan coverage via public sources.

**Next step: on your go-ahead, start Phase 0. No code will run on the live shop
until every phase is tested on a copy.**

---

## 19. Progress log

### Phase 0 — Setup (DONE, verified)

- Added `users.role` (`customer` default; the existing superuser account was
  marked `admin`), plus `users.phone` and `users.active`.
- Added tables `delivery_assignments` and `delivery_locations`.
- Migrations roll back and re-apply cleanly on the local test database.
- **Live shop confirmed untouched:** no new column, no new table.
- Files: `database/migrations/2026_09_17_00000{1,2,3}_*.php`,
  `app/Models/User.php`.

### Phase 1 — Admin staff management (DONE, verified)

- Admin-only section at `/admin/delivery` to list, add, edit, enable/disable and
  remove delivery accounts (name, phone, email, password).
- Reachable from a **Delivery** link in the admin panel.
- Role gate (`role:admin`) blocks customers and delivery staff on the server.
- 13 tests pass. The guard was proven real by removing it, watching the
  "customers cannot open" test fail, then restoring it.
- Files: `app/Http/Middleware/EnsureUserRole.php`,
  `app/Http/Controllers/Admin/DeliveryStaffController.php`,
  `app/Models/DeliveryAssignment.php`, `app/Models/DeliveryLocation.php`,
  `resources/views/delivery/admin/*`, `routes/web.php`, `app/Http/Kernel.php`.

### Known, unrelated test noise

The stock Breeze tests (homepage, profile, auth redirects) fail **before and
after** this work because the PHPUnit database has no Aimeos shop tables and the
Breeze `/profile` routes are not used by this Aimeos-based app. Out of scope.

### Phase 2 — Assign orders (DONE, verified)

- Admin delivery board at `/admin/delivery/orders` lists every order still out
  for delivery, newest first, with customer name, phone, address, total, and the
  shop's own delivery status. Finished orders (delivered, refused, returned,
  lost, deleted) are hidden.
- Admin picks a delivery person from a dropdown and assigns the order. Assigning
  again changes the person; the order can be unassigned while it has not started.
- Server-side rules: only active delivery accounts can be chosen, finished or
  missing orders are refused, and a database rule allows only one active
  delivery person per order at a time.
- 12 tests pass. The finished-order rule was deliberately disabled, the matching
  test failed, and the rule was put back.
- Files: `app/Support/ShopOrders.php`,
  `app/Http/Controllers/Admin/DeliveryOrderController.php`,
  `resources/views/delivery/admin/orders/index.blade.php`,
  `database/migrations/2026_09_17_000004_*.php`, `routes/web.php`,
  `tests/Feature/DeliveryAssignmentTest.php`,
  `tests/Concerns/CreatesShopOrderTables.php`.

### Phase 3 — Delivery app (DONE, verified)

- Separate, phone-first area at `/delivery` with its own sign-in page
  (`/delivery/login`). Only active accounts with the delivery role can enter;
  customers, admins, and disabled accounts are refused with the message shown
  next to the email field.
- "My deliveries" list shows only that person's assigned and in-progress orders,
  with customer name and address.
- Order screen shows the customer name, tap-to-call phone, address, an "Open on
  map" link (free OpenStreetMap), the items, and the order total, with
  **Start delivery**, **Delivered**, and **Couldn't deliver** (reason required).
- A disabled account is signed out of a live session immediately. Staff can
  never open or act on another person's delivery.
- 15 tests pass. The "only your own delivery" check and the "disabled account"
  check were each disabled on purpose, their tests failed, and the checks were
  put back.
- Files: `app/Http/Controllers/Delivery/AuthController.php`,
  `app/Http/Controllers/Delivery/OrderController.php`,
  `app/Http/Middleware/EnsureUserActive.php`,
  `resources/views/delivery/{auth,app}/**`, `routes/delivery.php`,
  `database/migrations/2026_09_17_000005_*.php`,
  `tests/Feature/DeliveryAppTest.php`.

### Phase 4 — Status sync (DONE, verified)

- When a delivery person marks an order **Delivered**, the shop's own order
  delivery stage becomes "Delivered"; **Couldn't deliver** becomes "Refused"
  (the shop's closest wording for a failed attempt). The admin order panel and
  the customer's order history therefore agree with the delivery app.
- Each change is also written into the shop's status history with the note
  `status-delivery` and the staff member's id, the same way the shop records its
  own changes.
- An order that is already finished (delivered, refused, returned, lost or
  deleted) is never overwritten.
- The assignment and the shop-order update happen together; if one part fails,
  neither is kept.
- 19 tests pass. The status write was disabled on purpose, the three tests that
  depend on it failed, and it was put back.
- Files: `app/Support/ShopOrders.php` (`setDeliveryStatus`),
  `app/Http/Controllers/Delivery/OrderController.php`,
  `tests/Concerns/CreatesShopOrderTables.php`, `tests/Feature/DeliveryAppTest.php`.

### Phase 5 — Tracking (DONE, verified)

- A delivery phone sends its position while the delivery page is open. A bar at
  the bottom of the app offers **Share my location** (the phone asks for
  permission the first time) and then shows when it last sent. If permission is
  refused, it says so plainly instead of pretending.
- The admin **Map** page (Delivery → Map) draws every on-duty delivery person on
  a free OpenStreetMap map using Leaflet, which is served from our own site
  (`public/leaflet/`) so it does not depend on anyone else's server.
- A dot is green **live** if seen in the last 2 minutes, amber **stale** up to
  15 minutes, grey **offline** beyond that or if never seen. Each dot has a short
  dotted trail, a popup with name, phone, accuracy and active order count, and a
  side list you can click to jump to a person. The page refreshes itself every
  10 seconds.
- Position is stored with the server's own clock and tied to the person's own
  current order; the phone cannot claim someone else's order. Points older than
  7 days are trimmed automatically. Only admins can read positions.
- 12 tests pass. The live/stale/offline rule and the "delivery only" server rule
  were each disabled on purpose, the matching tests failed, and both were put
  back.
- Files: `public/leaflet/**`, `app/Support/DeliveryTracking.php`,
  `app/Http/Controllers/Delivery/LocationController.php`,
  `app/Http/Controllers/Admin/DeliveryMapController.php`,
  `resources/views/delivery/admin/map.blade.php`,
  `resources/views/delivery/app/partials/tracker.blade.php`,
  `routes/delivery.php`, `routes/web.php`, `tests/Feature/DeliveryTrackingTest.php`.

### Next up

Phase 6 — polish and rules: back-button protection, keyboard, RTL, error
placement, offline/stale states, mobile/desktop gating.
