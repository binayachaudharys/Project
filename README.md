# Pretty Girls Ladies Salon

Local-first booking + POS for **Pretty Girls Ladies Salon** (Nepal).  
Laravel 13 · Breeze (Inertia + React) · SQLite on salon PC · MySQL on VPS later.

**Build credit:** [Art Developer](https://artdeveloper.com.np) and Market As pot

## Stack notes

| Piece | Choice |
|-------|--------|
| Auth / UI | Laravel Breeze + Inertia React + Tailwind |
| Repos | [`jsdecena/baserepo`](https://github.com/jsdecena/baserepo) |
| Log UI | [`opcodesio/log-viewer`](https://github.com/opcodesio/log-viewer) (owner-only). Design mentioned `arcanedev/log-viewer`, which does not support Laravel 13. |
| Timezone | `APP_TIMEZONE=Asia/Kathmandu` |
| DB phase 1 | SQLite (`database/database.sqlite`) |
| DB phase 2 | MySQL on a cheap VPS |

## Quick start (salon PC / LAN)

PHP 8.4 (Homebrew example):

```bash
export PATH="/opt/homebrew/opt/php@8.4/bin:/opt/homebrew/opt/php@8.4/sbin:$PATH"
```

First-time setup:

```bash
composer install
npm install
cp .env.example .env
# Set APP_URL=http://<this-pc-lan-ip>:8000  (phones/tablets on Wi‑Fi need the real LAN IP)
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
```

Daily / one-command serve (same Wi‑Fi phones open `http://<lan-ip>:8000`):

```bash
./bin/start-salon.sh
```

The script ensures SQLite exists, runs migrations, rebuilds front-end assets, then:

`php artisan serve --host=0.0.0.0 --port=8000`

Development with hot reload: `npm run dev` in another terminal instead of relying on the build step alone.

### Seed logins (change passwords after first use)

After `php artisan migrate --seed`:

| Role     | Email                     | Password            |
|----------|---------------------------|---------------------|
| Owner    | owner@prettygirls.local  | ChangeMeOwner1!     |
| Staff    | staff@prettygirls.local   | ChangeMeStaff1!     |
| Customer | customer@prettygirls.local| ChangeMeCustomer1!  |

- Owner: catalog, staff users, stock, sales reports, **Log Viewer** at `/log-viewer`
- Staff: today’s appointments, POS
- Customer: book services/packages (no online pay on booking), My appointments

### Backup (SQLite)

Daily (or before risky changes), copy the database file:

```bash
cp database/database.sqlite "backups/database-$(date +%Y%m%d).sqlite"
```

Store backups off the salon PC when possible. Restoring is copy-back + restart.

## Payments on LAN (§6.3)

Gateways need public return/callback URLs.

| Environment | Digital wallets (eSewa / Khalti / Fonepay) |
|-------------|--------------------------------------------|
| **LAN-only** (no tunnel) | Cash always works. Staff **Confirm paid** after customer shows success on phone (method + optional ref). |
| **LAN + tunnel** (ngrok / Cloudflare Tunnel) | Full sandbox verify against test merchants. |
| **Public VPS** | Live keys + real callback URLs. |

- Fill `ESEWA_*`, `KHALTI_*`, `FONEPAY_*` in `.env` only on the machine (see `.env.example`; secrets empty in git).
- `SALON_PAYMENT_SANDBOX_AUTO` controls optional sandbox helpers; keep sandbox until production.

## VPS / MySQL later (§8.2)

1. Deploy same repo (`git pull` / rsync).
2. Point `.env` to MySQL (`DB_CONNECTION=mysql`, host, database, user, password).
3. `php artisan migrate` (import/export from SQLite if you need historical data).
4. Set `APP_URL` to HTTPS domain; live gateway keys and callbacks.
5. `php artisan config:cache` (queue optional for MVP).

## Roles at a glance

- **Public:** home, services, packages, book, auth  
- **Customer:** appointments  
- **Staff (+ owner):** POS cash/digital, today board, staff-confirm payment on LAN  
- **Owner:** admin catalog/stock/staff/reports + Log Viewer  

## Useful commands

```bash
php artisan test          # feature suite
npm run build             # production assets
php artisan migrate --seed
./bin/start-salon.sh
```

## Security highlights

- CSRF on Inertia/Laravel forms  
- Role middleware on staff/admin/log-viewer  
- Payment amount/signature verified server-side  
- Stock applied when sale becomes `paid` (idempotent)  

## License / credit

Application built for Pretty Girls Ladies Salon.  
**Art Developer** and **Market As pot**.
