# Classybazar — local development stack

The full site running on your own machine: a Laravel API and a Next.js
storefront, wired together, with no MySQL server, no Docker and no live
hosting anywhere in the loop.

```
classybazar/
├── setup.sh / setup.ps1    install + configure everything (run once)
├── start.sh / start.ps1    run both servers
├── stop.sh  / stop.ps1     free the ports if something is stuck
├── api/                    Laravel 12 REST API   → http://127.0.0.1:8000/api
├── client/                 Next.js 16 storefront → http://localhost:3000
└── logs/                   api.log, web.log, build.log
```

**On Windows, read [WINDOWS.md](WINDOWS.md) instead** — use the `.ps1`
scripts, and note that PowerShell 5.1 does not accept `&&` between commands.

---

## First run (macOS / Linux)

```bash
cd classybazar
./setup.sh
./start.sh
```

Then open **http://localhost:3000**.

`setup.sh` checks your PHP/Node versions before touching anything, installs
both dependency trees, creates the two env files, builds the SQLite database
and seeds it with 47 products across 11 categories, two demo orders and two
accounts.

`start.sh` boots both servers, waits until each one actually answers a
request, and prints the URLs. Ctrl+C stops both together.

### Sign in with

| Role | Email | Password |
|---|---|---|
| Admin | `admin@classybazar.com` | `password` |
| Customer | `customer@classybazar.com` | `password` |

The seeder prints two demo order numbers at the end — use those on
`/order/ordertrack` to see the tracking timeline with real data. Tracking asks
for the phone number too: `01812345678`.

---

## Admin panel

**http://127.0.0.1:8000/admin** — sign in with `admin@classybazar.com` / `password`.

Built with Filament. Only accounts with `is_admin = true` can get in; every
customer who registers through the storefront lands in the same `users` table,
so that check is what keeps them out.

| Section | What you can do |
|---|---|
| Dashboard | Revenue this month vs last, orders awaiting confirmation, stock alerts, latest 8 orders |
| Orders | Tabs for New / Processing / Shipped / Delivered / Cancelled. Open one to see items, totals, address and the full status timeline. "Update status" sets the courier and consignment ID and writes a tracking entry the customer sees. |
| Products | Search, filter by category or stock level, sort. Create/edit with drag-drop image upload and a reorderable gallery. Bulk publish/unpublish. |
| Categories | Full CRUD with image and sort order |
| Coupons | Percent or fixed, minimum spend, usage cap, expiry |
| Contact messages | Everything submitted through the contact form |

Orders cannot be created by hand in the panel — they only come from a real
checkout, so that pricing and stock are never bypassed.

### Prices are typed in taka

The database stores integer poisha, but every price field in the panel shows
and accepts taka. Type `1499.50` and `149950` is stored. There is a test
pinning this both ways, because a broken conversion here would silently move
every price in the shop by a factor of 100.

### Saving refreshes the storefront immediately

Next.js caches catalogue pages, so a freshly saved product used to take up to a
minute to appear — long enough to look like the save had failed. Saving a
product or category now pings the storefront's `/api/revalidate` endpoint,
which drops the matching cache tags at once. If the storefront is not running,
the ping fails quietly and the save still goes through.

The shared secret lives in `REVALIDATE_SECRET` on both sides; they must match.

### Live preview

Product rows have a **View live** action, and the edit page has **View on
storefront** in the header. Both open the real product page in a new tab. They
are hidden for unpublished products, which 404 on the storefront by design.

Saving also shows a "View live" button in the success notification.

### Changing status restores stock

Setting an order to Cancelled or Returned puts its items back into stock. That
runs through the same `OrderService` the API uses, so the panel and the
storefront can never disagree about inventory.

---

## Requirements

| Tool | Minimum | Install on macOS |
|---|---|---|
| PHP | 8.2 | [Laravel Herd](https://herd.laravel.com) (easiest) or `brew install php` |
| Composer | 2.x | `brew install composer` |
| Node.js | 20 | `brew install node` |

`setup.sh` verifies all of these and tells you exactly what is missing rather
than failing halfway through.

**Herd users:** nothing special to do. Herd ships PHP with `pdo_sqlite`
already enabled, which is the one extension this project needs and the one
people most often lack. You can keep using `./start.sh` — you do not have to
park the project in `~/Herd`.

---

## Everyday commands

```bash
./start.sh              # dev mode, hot reload on the storefront
./start.sh --prod       # production build, served locally
./stop.sh               # kill anything left on ports 8000 / 3000
./setup.sh --fresh      # wipe the database and reseed
```

Ports are configurable when something else is already using them:

```bash
API_PORT=8001 WEB_PORT=3001 ./start.sh
```

If you change `API_PORT`, update `client/.env.local` to match:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8001/api
```

---

## How the two halves talk

The storefront reads `NEXT_PUBLIC_API_URL` from `client/.env.local` and calls
the API through `client/src/lib/api/`. Nothing else in the app knows the API
exists, so changing hosts later is a one-line edit.

The API only accepts browser requests from origins listed in
`api/.env`:

```env
FRONTEND_URLS=http://localhost:3000,http://127.0.0.1:3000
```

Both `localhost` and `127.0.0.1` are whitelisted because browsers treat them
as different origins — visiting the site by one name while the config lists
only the other produces a CORS error that looks like the API is down.

### What changed in the storefront

All nine files that previously called `dummyjson.com` now call the local API:

| File | Now uses |
|---|---|
| `app/products/page.jsx` | `getProducts()` — filter, sort and pagination in SQL |
| `app/products/[id]/page.jsx` | `getProduct()`, `getRelatedProducts()` |
| `components/FlashSales.jsx` | `getFlashSaleProducts()` |
| `components/BestSelling.jsx` | `getBestSellingProducts()` |
| `components/NewArrivalsProducts.jsx` | `getNewArrivals()` |
| `components/OurProduct.jsx` | `getFeaturedProducts()` |
| `components/RadioExprience.jsx` | `getFlashSaleProducts(1)` |
| `components/Navbar.jsx` | `searchProducts()` |
| `components/MenuBar.jsx` | `getCategories()` |

The products page used to download the entire catalogue and filter, sort and
paginate it in JavaScript. That work now happens in the database, so the page
stays fast as the catalogue grows.

---

## Troubleshooting

**`Port 8000 is already in use`**
Run `./stop.sh`, or start on different ports as shown above. On macOS, port
5000 and friends are often taken by AirPlay — 8000 and 3000 are usually free.

**`PHP is missing the pdo_sqlite extension`**
With Homebrew PHP: `brew reinstall php`. With Herd it is already there — check
you are not accidentally using a different `php` binary with `which php`.

**Storefront loads but every product section is empty**
The API is not reachable. Check `logs/api.log`, confirm
`curl http://127.0.0.1:8000/api/settings` returns JSON, and make sure
`client/.env.local` points at the right port.

**Browser console shows a CORS error**
The origin you are visiting is not in `FRONTEND_URLS` in `api/.env`. Add it,
then restart the API.

**Images do not load**
`next.config.mjs` whitelists `picsum.photos` (the seeded demo images) and
`127.0.0.1:8000` (images you upload through the admin API). If you seed images
from another host, add it there.

**Changes to `api/.env` seem to be ignored**
Laravel caches config. Run `php artisan optimize:clear` in `api/`.

---

## Going further

- The checkout form has no submit handler yet — wire it to `placeOrder()`
  from `client/src/lib/api/orders.js`.
- Login and signup pages are still UI only — wire them to `login()` and
  `register()` from `client/src/lib/api/auth.js`.
- Payment is cash-on-delivery only. The database columns for an online
  gateway already exist.

See `api/README.md` for the full endpoint reference and
`api/frontend-integration/MIGRATION.md` for the details of each wiring change.
