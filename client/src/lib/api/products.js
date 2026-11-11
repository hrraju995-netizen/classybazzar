import { apiFetch, buildQuery } from "./client";

/**
 * Returns { products, total, skip, limit } — the same envelope the pages
 * were already destructuring from DummyJSON.
 */
export function getProducts(params = {}, options = {}) {
  return apiFetch(`/products${buildQuery(params)}`, {
    // Tagged so the admin panel can flush this cache the moment a product
    // changes, instead of the shop owner waiting out the revalidate window.
    next: { revalidate: 60, tags: ["products"] },
    ...options,
  });
}

export function getProduct(productKey, options = {}) {
  return apiFetch(`/products/${productKey}`, {
    next: { revalidate: 60, tags: ["products", `product-${productKey}`] },
    ...options,
  });
}

export function getRelatedProducts(productKey, limit = 8) {
  return apiFetch(`/products/${productKey}/related${buildQuery({ limit })}`, {
    next: { revalidate: 60, tags: ["products"] },
  });
}

export function getCategories() {
  return apiFetch("/products/categories", {
    next: { revalidate: 300, tags: ["categories"] },
  });
}

export function searchProducts(q, params = {}) {
  return apiFetch(`/products/search${buildQuery({ q, ...params })}`, {
    cache: "no-store",
  });
}

export function getFlashSaleProducts(limit = 12) {
  return getProducts({ flashSale: 1, limit });
}

export function getFeaturedProducts(limit = 12) {
  return getProducts({ featured: 1, limit });
}

export function getBestSellingProducts(limit = 12) {
  return getProducts({ sort: "best-selling", limit });
}

export function getNewArrivals(limit = 12) {
  return getProducts({ sort: "latest", limit });
}

export function getProductReviews(id) {
  return apiFetch(`/products/${id}/reviews`, { cache: "no-store" });
}

export function submitReview(id, payload) {
  return apiFetch(`/products/${id}/reviews`, {
    method: "POST",
    body: payload,
    auth: true,
  });
}
