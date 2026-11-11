# Frontend migration guide

Copy `src/lib/api/` into `classybazar-client/src/lib/api/`, then work through
the table below. There are exactly 8 files with hard-coded `dummyjson.com`
calls.

## 1. Environment

`classybazar-client/.env.local`:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api
```

## 2. Allow the image host

`next.config.mjs` — the seeded demo images come from picsum:

```js
const nextConfig = {
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "picsum.photos" },
      { protocol: "http", hostname: "127.0.0.1", port: "8000" },
    ],
  },
};
```

The second entry is for real images once you upload them through
`/admin/products/images`.

## 3. Replace the fetch calls

| File | Was | Becomes |
|---|---|---|
| `app/products/page.jsx` | `fetch(".../products")` then filter/sort/paginate in JS | `getProducts({ category, sort, minPrice, maxPrice, limit, page })` |
| `app/products/[id]/page.jsx` | fetch all, then `.find()` | `getProduct(id)` |
| `app/products/[id]/page.jsx` | fetch by category for related | `getRelatedProducts(id)` |
| `components/FlashSales.jsx` | `fetch(".../products")` | `getFlashSaleProducts()` |
| `components/BestSelling.jsx` | `fetch(".../products")` | `getBestSellingProducts()` |
| `components/NewArrivalsProducts.jsx` | `fetch(".../products")` | `getNewArrivals()` |
| `components/OurProduct.jsx` | `fetch(".../products")` | `getFeaturedProducts()` |
| `components/RadioExprience.jsx` | `fetch(".../products")` | `getFeaturedProducts(4)` |
| `components/Navbar.jsx` | `fetch(".../products/search?q=")` | `searchProducts(query)` |
| `components/MenuBar.jsx` | `fetch(".../products/categories")` | `getCategories()` |

### The biggest win: `app/products/page.jsx`

Right now that page downloads every product and does filtering, price
filtering, sorting and pagination in JavaScript. That works at 47 products and
falls over at 5,000. The API does all of it in SQL:

```js
import { getProducts } from "@/lib/api/products";

const ProductsPage = async ({ searchParams }) => {
  const sp = await searchParams;

  const { products, total } = await getProducts({
    category: sp.category,
    sort: sp.sort,
    minPrice: sp.minPrice,
    maxPrice: sp.maxPrice,
    limit: sp.limit || 16,
    page: sp.page || 1,
  });

  // `products` is already the correct page, `total` drives <Pagination />
};
```

All the `filteredProducts` / `slice` logic in that file can be deleted.

## 4. Checkout

`components/CheckoutForm.jsx` currently computes
`shippingCharge = shippingMethod === 'inside' ? 70 : 130` in the component.
Two changes:

```js
import { getSettings } from "@/lib/api/misc";
import { placeOrder } from "@/lib/api/orders";
```

- Pull the charges from `getSettings()` so the admin can change them without a
  redeploy.
- On submit, call `placeOrder({ ...formData, shippingMethod, items: cart })`,
  then `clearCart()` and redirect to the tracking page with the returned
  `order.orderNumber`.

The form has no submit handler wired up yet — that is the one piece of the
checkout flow still missing on the client side.

## 5. Order tracking

`app/order/ordertrack/page.jsx` has a `MOCK_ORDERS` object at the top. Delete
it and call `trackOrder(orderNumber, phone)`. The response shape is identical
to the mock — `{ id, status, date, eta, carrier, steps[] }` — so the JSX
underneath does not change at all.

You will need to add a phone input next to the order-number input, because
tracking is phone-verified.

## 6. Auth

`app/login/page.jsx` and `app/signup/page.jsx` are UI only. Wire them to
`login(identifier, password)` and `register(payload)`. The token is stored in
localStorage by the client module, so subsequent `auth: true` calls just work.

After a successful login, call `syncWishlist(wishlist.map(p => p.id))` to merge
the guest wishlist into the account.

## 7. Cart — keep it client-side

Do **not** move the Zustand cart to the server. Keeping it in localStorage is
the right call for a COD storefront: it survives refreshes, works for guests,
and needs no session. The server re-prices it at checkout anyway, so a stale or
tampered client cart cannot cause a wrong charge.

The one thing worth adding: call `validateCart()` when the checkout page mounts,
and show the returned `issues[]` if a product went out of stock while it sat in
someone's cart.
