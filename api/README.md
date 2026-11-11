# Classybazar API

Laravel 12 REST backend for the Classybazar Next.js storefront.

Responses are deliberately shaped to match the DummyJSON payloads the frontend
was prototyped against, so switching over is mostly a base-URL change rather
than a component rewrite.

---

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

API is now at `http://127.0.0.1:8000/api`.

### Demo accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@classybazar.com` | `password` |
| Customer | `customer@classybazar.com` | `password` |

The seeder also creates two demo orders (one in transit, one delivered) so the
order-tracking page has real data to render. Their order numbers are printed
at the end of `migrate --seed`.

### Switching to MySQL

SQLite is the default so the project runs with zero setup. For MySQL, replace
the `DB_*` block in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=classybazar
DB_USERNAME=root
DB_PASSWORD=
```

Then `php artisan migrate:fresh --seed`. No migration changes are required —
nothing in the schema is SQLite-specific.

---

## Two design decisions worth knowing

**1. All money is stored in poisha (integers), never floats.**

`price`, `subtotal`, `shipping_charge`, `discount` and `total` are
`unsignedBigInteger` columns holding poisha (1 BDT = 100 poisha). Conversion
to taka happens only at the API edge via `App\Support\Money`. This means no
arithmetic anywhere in the application can drift by a rounding error, which
matters once discounts, coupons and percentage maths start compounding.

Admins type prices in taka; the controller converts on the way in.

**2. The client never sends prices.**

Checkout accepts only `product_id` and `quantity`. The server re-reads every
price from the database, re-applies discounts, re-validates the coupon and
computes the total itself. A tampered `localStorage` cart cannot buy a phone
for ৳1 — verified by test.

Stock is decremented with a conditional update
(`where('stock', '>=', $qty)->decrement(...)`), so if two customers race for
the last unit, the second gets 0 affected rows and the whole transaction rolls
back. Verified with 6 concurrent checkouts against 1 unit of stock: exactly one
succeeded, stock landed on 0, never negative.

---

## Endpoints

Base URL: `/api`. Authenticated routes use a Sanctum bearer token:
`Authorization: Bearer <token>`.

### Storefront (public)

| Method | Path | Notes |
|---|---|---|
| GET | `/settings` | Shipping charges, currency, support contacts |
| GET | `/products` | Filter, sort, paginate — see params below |
| GET | `/products/categories` | All active categories with product counts |
| GET | `/products/search?q=` | Same envelope as `/products` |
| GET | `/products/category/{slug}` | Products in one category |
| GET | `/products/{id}` | Single product + up to 20 reviews |
| GET | `/products/{id}/related` | Same-category products |
| GET | `/products/{id}/reviews` | Paginated reviews |
| POST | `/products/{id}/reviews` | One review per customer per product |
| POST | `/cart/validate` | Re-price a cart against live data |
| POST | `/orders` | Place an order (guest or logged in) |
| POST | `/orders/track` | Requires `order_number` **and** `phone` |
| POST | `/contact` | Contact form |

**`GET /products` query params**

`limit` (1–100, default 30), `skip` or `page`, `category` (slug or `all`),
`q`, `sort` (`default` | `low-high` | `high-low` | `popularity` |
`latest` | `best-selling`), `minPrice`, `maxPrice` (in taka),
`featured`, `flashSale`.

Price filters and sorting run on the **discounted** price — the number
actually shown on the product card — not the pre-discount price.

**Response envelope**

```json
{ "products": [ ... ], "total": 47, "skip": 0, "limit": 30 }
```

Each product:

```json
{
  "id": 3,
  "title": "Realme C75 (6/128GB)",
  "slug": "realme-c75-6-128gb",
  "description": "...",
  "category": "mobile-gadgets",
  "categoryName": "Mobile & Gadgets",
  "brand": "Realme",
  "sku": "CB-00003",
  "price": 16711.2,
  "originalPrice": 18990,
  "discountPercentage": 12,
  "rating": 4.2,
  "reviewsCount": 5,
  "stock": 55,
  "availabilityStatus": "In Stock",
  "thumbnail": "https://...",
  "images": ["https://...", "..."],
  "isFeatured": false,
  "isFlashSale": true,
  "flashSaleEndsAt": "2026-07-30T07:20:00+00:00"
}
```

`price` is what the customer pays (discount already applied). `originalPrice`
is the strike-through price.

### Auth

| Method | Path | Notes |
|---|---|---|
| POST | `/auth/register` | Returns `{ user, token }` |
| POST | `/auth/login` | `email` field accepts email **or** phone |
| GET | `/auth/me` | Auth required |
| PATCH | `/auth/profile` | Auth required |
| POST | `/auth/change-password` | Revokes all other device tokens |
| POST | `/auth/logout` | Revokes the current token |

Login is throttled to 5 failed attempts per minute per identifier + IP, and
returns an identical error for "no such user" and "wrong password" so the
endpoint can't be used to discover which emails are registered.

### Customer area (auth required)

| Method | Path |
|---|---|
| GET | `/orders` |
| GET | `/orders/{orderNumber}` |
| POST | `/orders/{orderNumber}/cancel` |
| GET | `/wishlist` |
| POST | `/wishlist/toggle` |
| POST | `/wishlist/sync` |
| DELETE | `/wishlist` |

`/wishlist/sync` merges a guest's localStorage wishlist into their account
after login — call it once on successful sign-in.

Orders can only be cancelled while `pending` or `confirmed`. Cancelling
returns the stock to the catalogue.

### Admin (auth + `is_admin`)

| Method | Path |
|---|---|
| GET | `/admin/dashboard` |
| GET/POST | `/admin/products` |
| GET/PATCH/DELETE | `/admin/products/{id}` |
| POST | `/admin/products/images` |
| GET/POST | `/admin/categories` |
| PATCH/DELETE | `/admin/categories/{id}` |
| GET | `/admin/orders` |
| GET | `/admin/orders/{orderNumber}` |
| PATCH | `/admin/orders/{orderNumber}/status` |

Deleting a product **archives** it (`is_active = false`) rather than removing
the row, because order history references it.

`GET /admin/products?status=` accepts `active`, `inactive`, `out_of_stock`,
`low_stock`.

---

## Order status flow

```
pending -> confirmed -> processing -> in_transit -> out_for_delivery -> delivered
                                              \-> cancelled / returned
```

Every transition writes an `order_events` row. `POST /orders/track` replays
those events into the exact `steps[]` shape the tracking page renders:

```json
{
  "id": "CB-260727-XV1Z",
  "status": "In Transit",
  "date": "July 27, 2026",
  "eta": "July 29, 2026",
  "carrier": "Steadfast Courier",
  "steps": [
    { "title": "Order Placed", "desc": "...", "time": "07:29 AM",
      "completed": true, "current": false },
    { "title": "In Transit", "desc": "...", "time": "07:36 AM",
      "completed": true, "current": true }
  ]
}
```

Tracking requires the phone number used on the order. Without it, anyone could
enumerate order numbers and read strangers' delivery addresses.

---

## Frontend wiring

Set in the Next.js project's `.env.local`:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api
```

And in `.env` here, whitelist the storefront origin for CORS:

```env
FRONTEND_URLS=http://localhost:3000,http://127.0.0.1:3000
```

Never set `allowed_origins` to `*` in production — a wildcard would let any
site read authenticated responses.

The seeded demo images come from `picsum.photos`, so add it to
`next.config.mjs` or `next/image` will block them:

```js
images: {
  remotePatterns: [{ protocol: 'https', hostname: 'picsum.photos' }],
}
```

Once real product images are uploaded through `/admin/products/images`, they
are served from this app's `storage/` disk and that entry can be swapped for
the API host.

---

## Coupons

Two are seeded: `CLASSY10` (10% off, min ৳1000, capped at ৳500) and
`FREESHIP` (flat ৳70 off, min ৳2000). Pass `coupon_code` to `/cart/validate`
or `/orders`. An invalid or expired code returns a 422 with a validation
error on `coupon_code`.

---

## Admin panel

A Filament panel lives at `/admin`, gated on `User::canAccessPanel()` which
checks `is_admin`. See the root `README.md` for what it covers.

The JSON admin endpoints under `/api/admin/*` are still there and still
tested - they are what a future custom dashboard or mobile app would use.

## Tests

```bash
php artisan test
```

The feature suite covers the catalogue envelope, the price-tampering
defence, stock clamping, phone-verified tracking and the admin guard.

---

## What is not built yet

- **Online payment.** Only cash on delivery is wired up. The schema has
  `payment_method`, `payment_status` and `transaction_id` columns ready, so
  adding SSLCommerz or bKash means a controller plus a webhook route, not a
  migration.
- **Courier API integration.** `carrier` and `tracking_code` are set manually
  by an admin. Pushing consignments to Steadfast/Pathao/RedX is a service
  class away.
- **Email/SMS notifications** on order placement and status change.
- **Payment gateway UI in the admin panel.** Order payment status can be
  set by hand, but there is no gateway reconciliation screen.
