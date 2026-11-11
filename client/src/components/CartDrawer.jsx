'use client';

import { useEffect } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { Minus, Plus, ShoppingBag, Trash2, X } from 'lucide-react';
import { useCartStore } from '@/store/useCartStore';
import { useHydrated } from '@/hooks/useHydrated';

const itemKey = (item) => item.cartKey || item.id;

export default function CartDrawer() {
  const hydrated = useHydrated();
  const cart = useCartStore((state) => state.cart);
  const isOpen = useCartStore((state) => state.isCartOpen);
  const closeCart = useCartStore((state) => state.closeCart);
  const removeFromCart = useCartStore((state) => state.removeFromCart);
  const updateQuantity = useCartStore((state) => state.updateQuantity);

  useEffect(() => {
    if (!isOpen) return undefined;

    const previousOverflow = document.body.style.overflow;
    const handleKeyDown = (event) => {
      if (event.key === 'Escape') closeCart();
    };

    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isOpen, closeCart]);

  if (!hydrated) return null;

  const itemCount = cart.reduce((total, item) => total + item.quantity, 0);
  const subtotal = cart.reduce((total, item) => total + Number(item.price) * item.quantity, 0);

  return (
    <div
      className={`fixed inset-0 z-[200] transition ${isOpen ? 'visible' : 'invisible delay-300'}`}
      aria-hidden={!isOpen}
    >
      <button
        type="button"
        onClick={closeCart}
        aria-label="Close shopping cart"
        className={`absolute inset-0 bg-black/45 transition-opacity duration-300 ${isOpen ? 'opacity-100' : 'opacity-0'}`}
      />

      <aside
        role="dialog"
        aria-modal="true"
        aria-label="Shopping cart"
        className={`absolute right-0 top-0 flex h-full w-full max-w-[420px] flex-col bg-white shadow-2xl transition-transform duration-300 ${isOpen ? 'translate-x-0' : 'translate-x-full'}`}
      >
        <div className="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 px-5">
          <div className="flex items-center gap-2">
            <ShoppingBag size={20} aria-hidden="true" />
            <h2 className="text-base font-semibold text-gray-950">Shopping cart</h2>
            <span className="text-sm text-gray-500">({itemCount})</span>
          </div>
          <button
            type="button"
            onClick={closeCart}
            className="grid h-9 w-9 place-items-center rounded-md text-gray-600 hover:bg-gray-100 hover:text-black"
            aria-label="Close shopping cart"
          >
            <X size={20} />
          </button>
        </div>

        {cart.length === 0 ? (
          <div className="flex flex-1 flex-col items-center justify-center px-6 text-center">
            <ShoppingBag size={42} strokeWidth={1.4} className="mb-4 text-gray-300" />
            <p className="font-semibold text-gray-900">Your cart is empty</p>
            <p className="mt-1 text-sm text-gray-500">Add a product to start your order.</p>
            <Link
              href="/products"
              onClick={closeCart}
              className="mt-6 rounded-md bg-[var(--color-button)] px-5 py-2.5 text-sm font-semibold text-white hover:bg-black"
            >
              Browse products
            </Link>
          </div>
        ) : (
          <>
            <div className="flex-1 overflow-y-auto px-5">
              {cart.map((item) => (
                <div key={itemKey(item)} className="grid grid-cols-[72px_1fr_auto] gap-3 border-b border-gray-100 py-5">
                  <Link
                    href={`/products/${item.slug || item.id}`}
                    onClick={closeCart}
                    className="relative flex h-[72px] items-center justify-center overflow-hidden rounded-md border border-gray-200 bg-gray-50"
                  >
                    {item.thumbnail ? (
                      <Image src={item.thumbnail} alt={item.title} fill sizes="72px" className="object-contain p-1" />
                    ) : (
                      <ShoppingBag size={24} className="text-gray-300" />
                    )}
                  </Link>

                  <div className="min-w-0">
                    <Link
                      href={`/products/${item.slug || item.id}`}
                      onClick={closeCart}
                      className="line-clamp-2 text-sm font-semibold text-gray-900 hover:text-[var(--color-primary)]"
                    >
                      {item.title}
                    </Link>
                    {item.variantName && <p className="mt-1 text-xs text-gray-500">{item.variantName}</p>}
                    <p className="mt-1 text-sm font-semibold text-[var(--color-primary)]">BDT {Number(item.price).toFixed(2)}</p>

                    <div className="mt-3 flex h-8 w-fit items-center border border-gray-300">
                      <button
                        type="button"
                        onClick={() => updateQuantity(itemKey(item), item.quantity - 1)}
                        className="grid h-full w-8 place-items-center text-gray-600 hover:bg-gray-100"
                        aria-label={`Decrease ${item.title} quantity`}
                      >
                        <Minus size={14} />
                      </button>
                      <span className="min-w-9 border-x border-gray-300 text-center text-xs font-semibold leading-[30px]">{item.quantity}</span>
                      <button
                        type="button"
                        onClick={() => updateQuantity(itemKey(item), item.quantity + 1)}
                        className="grid h-full w-8 place-items-center text-gray-600 hover:bg-gray-100"
                        aria-label={`Increase ${item.title} quantity`}
                      >
                        <Plus size={14} />
                      </button>
                    </div>
                  </div>

                  <button
                    type="button"
                    onClick={() => removeFromCart(itemKey(item))}
                    className="grid h-8 w-8 place-items-center rounded-md text-gray-400 hover:bg-red-50 hover:text-red-600"
                    aria-label={`Remove ${item.title} from cart`}
                  >
                    <Trash2 size={17} />
                  </button>
                </div>
              ))}
            </div>

            <div className="shrink-0 border-t border-gray-200 bg-white p-5">
              <div className="mb-4 flex items-center justify-between">
                <span className="text-sm text-gray-600">Subtotal</span>
                <strong className="text-base text-gray-950">BDT {subtotal.toFixed(2)}</strong>
              </div>
              <p className="mb-4 text-xs text-gray-500">Shipping is calculated at checkout.</p>
              <Link
                href="/checkout"
                onClick={closeCart}
                className="flex h-11 w-full items-center justify-center rounded-md bg-[var(--color-button)] text-sm font-semibold text-white hover:bg-black"
              >
                Checkout
              </Link>
              <Link
                href="/cart"
                onClick={closeCart}
                className="mt-2 flex h-11 w-full items-center justify-center rounded-md border border-gray-300 text-sm font-semibold text-gray-800 hover:border-black"
              >
                View cart
              </Link>
            </div>
          </>
        )}
      </aside>
    </div>
  );
}
