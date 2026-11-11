import { apiFetch } from "./client";

/**
 * Re-prices the localStorage cart against live server data.
 * Call this when the checkout page mounts so the customer sees real totals
 * and any stock problems before they submit.
 */
export function validateCart({ items, shippingArea = "inside", couponCode }) {
  return apiFetch("/cart/validate", {
    method: "POST",
    auth: true,
    body: {
      items: items.map((i) => ({ id: i.id, quantity: i.quantity })),
      shipping_area: shippingArea,
      coupon_code: couponCode,
    },
  });
}

/**
 * Only ids and quantities are sent — the server decides every price.
 */
export function placeOrder({
  fullName,
  phoneNumber,
  streetAddress,
  email,
  shippingMethod = "inside",
  paymentMethod = "cod",
  couponCode,
  note,
  items,
}) {
  return apiFetch("/orders", {
    method: "POST",
    auth: true,
    body: {
      customer_name: fullName,
      phone: phoneNumber,
      email,
      address: streetAddress,
      shipping_area: shippingMethod,
      payment_method: paymentMethod,
      coupon_code: couponCode,
      note,
      items: items.map((i) => ({ id: i.id, quantity: i.quantity })),
    },
  });
}

export function trackOrder(orderNumber, phone) {
  return apiFetch("/orders/track", {
    method: "POST",
    body: { order_number: orderNumber, phone },
  });
}

export function getMyOrders(page = 1) {
  return apiFetch(`/orders?page=${page}`, { auth: true, cache: "no-store" });
}

export function getMyOrder(orderNumber) {
  return apiFetch(`/orders/${orderNumber}`, { auth: true, cache: "no-store" });
}

export function cancelOrder(orderNumber) {
  return apiFetch(`/orders/${orderNumber}/cancel`, {
    method: "POST",
    auth: true,
  });
}
