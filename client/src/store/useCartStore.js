import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { applyCartQuantityPricing } from '@/lib/pricing';

export const useCartStore = create(
  persist(
    (set) => ({
      cart: [],
      shippingMethod: 'inside',
      isCartOpen: false,
      openCart: () => set({ isCartOpen: true }),
      closeCart: () => set({ isCartOpen: false }),
      toggleCart: () => set((state) => ({ isCartOpen: !state.isCartOpen })),
      addToCart: (product) => set((state) => {
        const productKey = product.cartKey || `${product.id}:${product.variantId || 0}`;
        const existingItem = state.cart.find((item) => (item.cartKey || `${item.id}:${item.variantId || 0}`) === productKey);
        if (existingItem) {
          return {
            cart: state.cart.map((item) =>
              (item.cartKey || `${item.id}:${item.variantId || 0}`) === productKey
                ? applyCartQuantityPricing(item, Math.min(item.quantity + 1, product.stock || Infinity))
                : item
            ),
          };
        }
        return { cart: [...state.cart, applyCartQuantityPricing({ ...product, cartKey: productKey }, 1)] };
      }),
      removeFromCart: (itemKey) => set((state) => ({
        cart: state.cart.filter((item) => (item.cartKey || item.id) !== itemKey),
      })),
      updateQuantity: (itemKey, quantity) => set((state) => ({
        cart: state.cart.map((item) =>
          (item.cartKey || item.id) === itemKey
            ? applyCartQuantityPricing(item, Math.min(Math.max(1, quantity), item.stock || Infinity))
            : item
        ),
      })),

      setShippingMethod: (method) => set({ shippingMethod: method }),
      clearCart: () => set({ cart: [], isCartOpen: false }),
    }),
    {
      name: 'classybazar-shopping-cart',
      partialize: (state) => ({
        cart: state.cart,
        shippingMethod: state.shippingMethod,
      }),
    }
  )
);
