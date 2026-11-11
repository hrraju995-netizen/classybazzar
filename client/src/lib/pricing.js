export function getWholesaleMinQuantity(item) {
  return Math.max(2, Number(item?.wholesaleMinQuantity) || 2);
}

export function hasWholesaleOffer(item) {
  const wholesalePrice = Number(item?.wholesalePrice);

  return Boolean(item?.isWholesale && Number.isFinite(wholesalePrice) && wholesalePrice > 0);
}

export function isWholesaleCartItem(item, quantity = item?.quantity) {
  return hasWholesaleOffer(item)
    && Number(quantity || 0) >= getWholesaleMinQuantity(item);
}

export function getCartUnitPrice(item, quantity = item?.quantity) {
  return isWholesaleCartItem(item, quantity)
    ? Number(item.wholesalePrice)
    : Number(item?.retailPrice ?? item?.price ?? 0);
}

export function getCartLineTotal(item) {
  return getCartUnitPrice(item) * Number(item?.quantity || 0);
}

export function getWholesaleRemaining(item) {
  if (!hasWholesaleOffer(item)) return 0;

  return Math.max(0, getWholesaleMinQuantity(item) - Number(item?.quantity || 0));
}

export function applyCartQuantityPricing(item, quantity) {
  const retailPrice = Number(item?.retailPrice ?? item?.price ?? 0);
  const nextItem = { ...item, retailPrice, quantity };
  const wholesale = isWholesaleCartItem(nextItem, quantity);

  return {
    ...nextItem,
    price: wholesale ? Number(item.wholesalePrice) : retailPrice,
    pricingType: wholesale ? 'wholesale' : 'retail',
  };
}
