import { apiFetch } from "./client";

/** Shipping charges, currency and support details — stop hard-coding ৳70/৳130. */
export function getSettings() {
  return apiFetch("/settings", { next: { revalidate: 300 } });
}

export function sendContactMessage(payload) {
  return apiFetch("/contact", { method: "POST", body: payload });
}

export function getWishlist() {
  return apiFetch("/wishlist", { auth: true, cache: "no-store" });
}

export function toggleWishlist(productId) {
  return apiFetch("/wishlist/toggle", {
    method: "POST",
    auth: true,
    body: { product_id: productId },
  });
}

/** Merge the guest localStorage wishlist into the account after login. */
export function syncWishlist(productIds) {
  return apiFetch("/wishlist/sync", {
    method: "POST",
    auth: true,
    body: { product_ids: productIds },
  });
}
