# Pretty Girls Ladies Salon — System Design

**Date:** 2026-08-05  
**Status:** Approved for implementation planning  
**Brand:** Pretty Girls Ladies Salon  
**Build credit:** Art Developer and Market As pot  
**Platform:** Standalone (not Shopify)

---

## 1. Summary

A single-salon management product with:

- Public website (services, packages, marketing)
- Customer accounts and online **appointment booking** (no online payment)
- Staff **POS** (services, packages, simple retail products)
- Owner **admin** (catalog, staff, reports, gateway settings)
- Nepal payment methods at POS: **cash, eSewa, Khalti, Fonepay**
- Local-first on a salon PC/LAN; same codebase later on a cheap VPS

---

## 2. Goals and constraints

| Goal | Decision |
|------|----------|
| Day-one scope | Full MVP: site + booking + POS + admin |
| Roles | Owner, staff, customer |
| Booking payment | Pay at salon only |
| POS catalog | Services + packages + simple product stock |
| Hosting phase 1 | Salon laptop/PC, LAN access |
| Hosting phase 2 | Cheap VPS (~$5–6/mo) + MySQL when ready |
| Cost | Lowest practical ops + reuse familiar stack |
| Not in scope | Shopify, multi-branch, SMS, full warehouse/suppliers |

---

## 3. Architecture

### 3.1 Stack

| Layer | Choice |
|-------|--------|
| Backend | Laravel 11 |
| Frontend | React via **Inertia.js** |
| Styling | Tailwind CSS |
| Auth | Laravel Breeze (Inertia React) |
| Local DB | SQLite |
| Production DB (later) | MySQL |
| Repository base | `jsdecena/baserepo` |
| Log UI | `arcanedev/log-viewer` (owner-only) |
| Payments | Custom service classes (eSewa, Khalti, Fonepay) |

**Why Inertia (not separate React SPA):** one process, session auth, simpler LAN deploy (`php artisan serve` + built assets).  
**Why not Shopify:** standalone ownership, Nepal POS focus, zero Shopify fees.

### 3.2 Application surfaces (one repo)

```
Public (/)          Customer (/account)     Staff (/pos, /staff)     Owner (/admin)
─────────────       ───────────────────     ────────────────────     ──────────────
Home                My appointments         POS checkout             Services/CRUD
Services            Profile                 Today's appointments     Packages/CRUD
Packages            Cancel/reschedule*      Customer lookup          Products/stock
Book appointment                            Complete appointment     Staff users
Login / Register                                                     Sales reports
                                                                     Payment settings
                                                                     Log Viewer
```

\*Reschedule only if rules allow and slot free; cancel sets status `cancelled`.

### 3.3 Request path

```
React page (Inertia)
  → Laravel controller / form request
    → Domain repository (extends jsdecena BaseRepository)
      → Eloquent model
        → SQLite/MySQL
```

Payment gateways are not called from React for “paid=true”; only server-side verify callbacks mark sales paid.

### 3.4 Packages usage

#### `jsdecena/baserepo`

- All domain data access goes through repositories that extend the package base:
  - `UserRepository`, `ServiceRepository`, `PackageRepository`, `ProductRepository`
  - `AppointmentRepository`, `SaleRepository`, `PaymentRepository`, `StockMovementRepository`
- Controllers stay thin; business rules live in repositories or small action classes.
- **Compatibility note:** package last published major is older than Laravel 11. Install target is latest compatible release. If Composer blocks Laravel 11, implement a thin internal `BaseRepository` with the same create/list/find/update/delete patterns and keep repository interfaces identical so the design does not change.

#### `arcanedev/log-viewer`

- Registered out of the box for Laravel 11-compatible version.
- Route middleware: `auth` + `role:owner` only.
- Used for salon-PC debugging and later production incident review.

---

## 4. Roles and authorization

| Capability | Customer | Staff | Owner |
|------------|----------|-------|-------|
| View public site/packages | ✓ | ✓ | ✓ |
| Book appointment | ✓ | ✓ (for walk-in) | ✓ |
| POS sell / take payment | | ✓ | ✓ |
| Manage catalog / stock | | | ✓ |
| Manage staff users | | | ✓ |
| Sales reports | | limited (own day) optional | ✓ full |
| Payment gateway settings | | | ✓ |
| Log Viewer | | | ✓ |

Middleware: `role:customer|staff|owner` (or dual check staff+owner for POS routes).

---

## 5. Data model (MVP)

### 5.1 Entities

**users**  
- id, name, email, phone, password, role (`owner`|`staff`|`customer`), timestamps  

**services**  
- id, name, description, duration_minutes, price, is_active, timestamps  

**packages**  
- id, name, description, price, is_active, timestamps  
- package_service (package_id, service_id) — catalog composition only  

**products**  
- id, name, sku (nullable), price, stock_qty, low_stock_threshold, is_active, timestamps  

**appointments**  
- id, customer_id, staff_id (nullable), bookable_type (`service`|`package`), bookable_id  
- starts_at, ends_at  
- status: `pending` | `confirmed` | `completed` | `cancelled`  
- notes (nullable), timestamps  
- Indexes: starts_at, staff_id + starts_at, customer_id  

**sales**  
- id, sale_number, customer_id (nullable), staff_id, appointment_id (nullable)  
- subtotal, discount, total, status (`draft`|`pending_payment`|`paid`|`void`)  
- timestamps  

**sale_items**  
- id, sale_id, item_type (`service`|`package`|`product`), item_id, name_snapshot, qty, unit_price, line_total  

**payments**  
- id, sale_id, method (`cash`|`esewa`|`khalti`|`fonepay`)  
- amount, status (`pending`|`completed`|`failed`)  
- gateway_reference, gateway_payload (json nullable), idempotency_key, timestamps  

**stock_movements**  
- id, product_id, delta, reason (`sale`|`manual_adjust`|`void_restore`), sale_id (nullable), user_id, timestamps  

**settings** (optional key-value)  
- salon hours, slot length, auto-confirm booking flag, branding extras  
- Secrets for gateways prefer `.env`; non-secrets may live in settings  

### 5.2 Package behavior (MVP)

Packages are **priced sellable bundles** (one POS line / one bookable unit).  

**Out of MVP:** multi-visit package wallets, remaining session counts, shared family packages.

### 5.3 Stock behavior (MVP)

- On successful **paid** sale: for each product line, decrement `stock_qty` and write `stock_movements`.  
- On void of paid sale: restore stock.  
- Owner manual adjust: positive/negative delta with reason.  
- Low stock: `stock_qty <= low_stock_threshold` flag in product UI.  
- No suppliers, purchase orders, or multi-location inventory.

---

## 6. Feature flows

### 6.1 Online booking (no payment)

1. Customer authenticates.  
2. Chooses service or package.  
3. Picks date/time within salon hours; slot length = service duration or configured package duration default.  
4. Optional preferred staff.  
5. Validate no overlapping appointment for that staff (if staff chosen) / capacity rule if no staff (simple: max concurrent appointments per slot config, default 1 per staff or N salon chairs).  
6. Create appointment `pending` or auto-`confirmed` based on settings.  
7. Visible in customer “My appointments” and staff “Today”.

### 6.2 POS sale

1. Staff selects/creates customer (phone search).  
2. Adds services, packages, products with qty.  
3. Optional discount (amount or percent; cap rules simple).  
4. Confirm total → choose payment method.  
5. **Cash:** create sale `paid` + payment completed + stock deduct.  
6. **eSewa / Khalti / Fonepay:** create sale `pending_payment` + payment `pending` → start gateway flow → success callback verifies signature/amount → `paid` + stock deduct; failure → keep pending or void after timeout.  
7. Optional: link to open appointment → set appointment `completed`.

### 6.3 LAN gateway constraint

Gateways need a public return/callback URL.

| Environment | Digital wallets |
|-------------|-----------------|
| LAN-only (no tunnel) | Cash always works. Staff action: “Confirm paid after customer shows success on phone” (records method + optional ref) as offline fallback. |
| LAN + tunnel (ngrok/Cloudflare) | Full sandbox verify against test merchants. |
| Public VPS | Full live verify. |

Sandbox credentials always used until production deploy.

### 6.4 Public website pages

- Home (brand-first salon identity)  
- Services list  
- Packages list/detail  
- Book  
- Auth  
- Footer / about credit: Art Developer and Market As pot  

---

## 7. Payments module

### 7.1 Interface

```
PaymentGatewayInterface
  initiate(Sale $sale, Payment $payment): GatewayRedirect|QrPayload
  verify(Request $request): VerifyResult
  methodName(): string
```

Implementations: `EsewaGateway`, `KhaltiGateway`, `FonepayGateway`.  
Cash handled without gateway class.

### 7.2 Rules

- Amount and sale_id fixed before initiate; never re-trust client amount.  
- Idempotency key prevents double-complete.  
- All gateway responses logged (Laravel log; owner reads via Log Viewer).  
- Credentials from env: `ESEWA_*`, `KHALTI_*`, `FONEPAY_*`.

---

## 8. Local run and migration path

### 8.1 Salon PC

```bash
composer install
npm install
cp .env.example .env   # DB_CONNECTION=sqlite, APP_URL=http://<lan-ip>:8000
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build            # or npm run dev during development
php artisan serve --host=0.0.0.0 --port=8000
```

- Devices on same Wi‑Fi use `http://<pc-lan-ip>:8000`.  
- Daily backup: copy `database/database.sqlite`.  
- Optional: Windows/macOS start script or simple systemd/launchd later.

### 8.2 Later VPS

1. Deploy same repo (git pull / rsync).  
2. Switch DB to MySQL; migrate; import if needed.  
3. Set `APP_URL` to real domain, HTTPS.  
4. Set live gateway keys and callback URLs.  
5. `php artisan config:cache`, queue if added later (MVP can run sync).

---

## 9. Error handling and security

- Form request validation for all writes.  
- Role middleware on staff/admin/log-viewer.  
- CSRF on Inertia/Laravel forms.  
- Payment verify only on server; HTTP signature checks per provider docs.  
- Soft conflict errors on booking overlap (user-friendly Inertia flash).  
- Product stock insufficient blocks adding/completing paid sale.  
- No secrets in React props beyond non-sensitive public config.

---

## 10. Seeding and defaults

- One seed **owner** user (change password on first use).  
- Sample services, one package, few products.  
- Default salon hours (e.g. 10:00–19:00), slot 30 minutes configurable.  
- No real gateway keys in repo; `.env.example` documents placeholders.

---

## 11. Out of MVP (explicit)

- Online payment for bookings  
- Multi-visit package balance / redeem  
- SMS / WhatsApp / email marketing  
- Multi-branch / multi-device offline sync  
- Suppliers, PO, accounting exports  
- Commission payroll for stylists  
- Mobile native apps  

---

## 12. Testing strategy (implementation phase)

- Feature tests: booking conflict, POS cash sale stock, role gates, payment verify success/fail (mocked gateways).  
- Manual: LAN smoke for public book → staff complete → cash POS.  
- Optional browser test for critical Inertia pages if tooling is light.

---

## 13. Success criteria

MVP is done when:

1. Public site shows services/packages and salon branding.  
2. Customer can register and book without paying online.  
3. Staff can sell services/packages/products and take **cash**.  
4. Digital methods wired with verify path + LAN staff-confirm fallback.  
5. Owner can manage catalog, stock adjust, staff, see sales, open Log Viewer.  
6. App runs on salon PC with SQLite and one clear start command/script.  
7. No Shopify dependency.

---

## 14. Project placement

Suggested app root when scaffolding:

`/Users/saurahwa/Desktop/Shopify/pretty-girls-ladies-salon/`

(Design living at `docs/superpowers/specs/` under workspace until app repo is initialized.)
