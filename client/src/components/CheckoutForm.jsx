'use client';

import React, { useEffect, useState } from 'react';
import { useCartStore } from '@/store/useCartStore';
import Image from 'next/image';
import { toast } from 'react-toastify';
import { GiShoppingBag } from 'react-icons/gi';
import { IoClose } from 'react-icons/io5';
import { Spinner } from '@heroui/react';
import { useRouter, useSearchParams } from 'next/navigation';
import { HiPlus, HiMinus } from 'react-icons/hi2';
import { useHydrated } from '@/hooks/useHydrated';
import { placeOrder } from '@/lib/api/orders';
import { getStoredUser, isAuthenticated, me, updateProfile } from '@/lib/api/auth';
import { getSettingsSafe } from '@/lib/api/misc';

const CheckoutForm = ({ settings: initialSettings }) => {
  const { cart, shippingMethod, setShippingMethod, removeFromCart, updateQuantity, clearCart } = useCartStore();
  const isMounted = useHydrated();
  const router = useRouter();

  const searchParams = useSearchParams();
  const isBuyNow = searchParams.get('buyNow') === 'true';

  const [formData, setFormData] = useState({
    fullName: '',
    streetAddress: '',
    phoneNumber: '',
    saveInfo: true,
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [settings, setSettings] = useState(initialSettings || null);

  useEffect(() => {
    if (!settings) {
      getSettingsSafe().then(setSettings).catch(() => {});
    }
  }, [settings]);

  const paymentMethods = settings?.paymentMethods || {
    cod: { enabled: true, title: 'Cash on Delivery', instructions: 'Pay with cash upon delivery.' },
    bkash: { enabled: false, title: 'bKash', number: '', type: 'Personal', instructions: '' },
    nagad: { enabled: false, title: 'Nagad', number: '', type: 'Personal', instructions: '' },
    rocket: { enabled: false, title: 'Rocket', number: '', type: 'Personal', instructions: '' },
  };

  const enabledPaymentKeys = Object.keys(paymentMethods).filter((k) => paymentMethods[k]?.enabled !== false);
  const defaultMethod = enabledPaymentKeys.includes('cod') ? 'cod' : (enabledPaymentKeys[0] || 'cod');

  const [paymentMethod, setPaymentMethod] = useState(defaultMethod);
  const [paymentNumber, setPaymentNumber] = useState('');
  const [transactionId, setTransactionId] = useState('');
  const [copiedKey, setCopiedKey] = useState(null);

  useEffect(() => {
    if (enabledPaymentKeys.length > 0 && !enabledPaymentKeys.includes(paymentMethod)) {
      setPaymentMethod(enabledPaymentKeys[0]);
    }
  }, [enabledPaymentKeys, paymentMethod]);

  useEffect(() => {
    const applyUser = (u) => {
      if (!u) return;
      setFormData((prev) => ({
        ...prev,
        fullName: prev.fullName || u.name || '',
        phoneNumber: prev.phoneNumber || u.phone || '',
        streetAddress: prev.streetAddress || u.address || '',
      }));
    };

    const cached = getStoredUser();
    if (cached) applyUser(cached);
    if (isAuthenticated()) {
      me().then(applyUser).catch(() => {});
    }
  }, []);

  if (!isMounted) return (
    <div className="flex flex-col items-center gap-2">
      <Spinner color="danger" />
      <span className="text-xs text-muted">loading...</span>
    </div>
  );

  const subtotal = cart.reduce((total, item) => total + item.price * item.quantity, 0);
  const shippingInside = Number(settings?.shipping?.inside) || 70;
  const shippingOutside = Number(settings?.shipping?.outside) || 130;
  const shippingFreeAbove = Number(settings?.shipping?.freeAbove) || 0;
  const isFreeShipping = shippingFreeAbove > 0 && subtotal >= shippingFreeAbove;
  const shippingCharge = isFreeShipping ? 0 : (shippingMethod === 'inside' ? shippingInside : shippingOutside);
  const totalCost = subtotal + shippingCharge;

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const handleQuantityChange = (id, newQty) => {
    if (newQty < 1) return;
    if (updateQuantity) {
      updateQuantity(id, newQty);
    }
  };

  const handleCopy = (text, key) => {
    if (!text) return;
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
      navigator.clipboard.writeText(text);
      setCopiedKey(key);
      toast.success('Number copied to clipboard!');
      setTimeout(() => setCopiedKey(null), 2500);
    }
  };

  const handlePlaceOrder = async (e) => {
    e.preventDefault();
    if (cart.length === 0) {
      toast.error('Your cart is empty!');
      return;
    }
    if (!formData.fullName.trim() || !formData.streetAddress.trim() || !formData.phoneNumber.trim()) {
      toast.error('Please fill in all required fields.');
      return;
    }

    if (paymentMethod !== 'cod') {
      if (!paymentNumber.trim() || !transactionId.trim()) {
        toast.error('Please enter your mobile banking number and Transaction ID (TrxID).');
        return;
      }
    }

    const phoneNumber = formData.phoneNumber.replace(/\D/g, '').replace(/^88(?=01)/, '');

    setIsSubmitting(true);
    try {
      const result = await placeOrder({
        fullName: formData.fullName.trim(),
        phoneNumber,
        streetAddress: formData.streetAddress.trim(),
        shippingMethod,
        paymentMethod,
        paymentNumber: paymentMethod !== 'cod' ? paymentNumber.trim() : null,
        transactionId: paymentMethod !== 'cod' ? transactionId.trim() : null,
        items: cart,
      });
      const orderNumber = result.order?.orderNumber || result.order?.id;

      if (formData.saveInfo && isAuthenticated()) {
        updateProfile({
          name: formData.fullName.trim(),
          phone: phoneNumber,
          address: formData.streetAddress.trim(),
        }).catch(() => {});
      }

      if (typeof window !== 'undefined' && result.order) {
        try {
          window.sessionStorage.setItem('classybazar_last_order', JSON.stringify(result.order));
        } catch {
          // Ignore storage quota errors
        }
      }

      clearCart();
      toast.success(result.message || 'Order placed successfully!');
      router.replace(orderNumber ? `/thank-you?order=${encodeURIComponent(orderNumber)}` : '/thank-you');
    } catch (error) {
      const firstValidationError = Object.values(error?.errors || {}).flat().find(Boolean);
      toast.error(firstValidationError || error?.message || 'Could not place the order. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handlePlaceOrder} className="flex flex-col lg:grid lg:grid-cols-[1fr_450px] gap-8 lg:gap-16 items-start">
      <div className="space-y-6 w-full">
        <div>
          <label className="block text-gray-400 text-sm mb-2">Your Full Name<span className="text-[#DB4444]">*</span></label>
          <input type="text" name="fullName" value={formData.fullName} onChange={handleInputChange} className="w-full bg-[#F5F5F5] rounded p-3 focus:outline-none" required />
        </div>
        <div>
          <label className="block text-gray-400 text-sm mb-2">Your Full Address<span className="text-[#DB4444]">*</span></label>
          <input type="text" name="streetAddress" value={formData.streetAddress} onChange={handleInputChange} className="w-full bg-[#F5F5F5] rounded p-3 focus:outline-none" required />
        </div>
        <div>
          <label className="block text-gray-400 text-sm mb-2">Your Phone Number<span className="text-[#DB4444]">*</span></label>
          <input type="tel" name="phoneNumber" value={formData.phoneNumber} onChange={handleInputChange} placeholder="01XXXXXXXXX" autoComplete="tel" className="w-full bg-[#F5F5F5] rounded p-3 focus:outline-none" required />
        </div>
        <label className="flex items-center space-x-3 cursor-pointer select-none">
          <input type="checkbox" name="saveInfo" checked={formData.saveInfo} onChange={handleInputChange} className="w-5 h-5 accent-[#DB4444]" />
          <span className="text-sm font-medium">Save this information for faster check-out</span>
        </label>
      </div>

      <div className="space-y-6 w-full">
        <div className="max-h-[300px] overflow-y-auto pr-2 pl-2 pt-2 space-y-4">
          {cart.length === 0 ? (
            <p className="text-sm text-gray-500 py-4">Your cart is empty.</p>
          ) : (
            cart.map((item) => (
              <div key={item.cartKey || item.id} className="flex items-center justify-between gap-3 py-2 border-b border-gray-100 last:border-none">
                <div className="flex items-center gap-3 min-w-0">
                  <div className="relative pt-1 pl-1 flex-shrink-0">
                    <div className="w-12 h-12 bg-white border border-gray-200 rounded-lg p-1 flex items-center justify-center">
                      <Image
                        src={item.thumbnail}
                        alt={item.title}
                        width={40}
                        height={40}
                        className="object-contain w-full h-full"
                      />
                    </div>
                    <button
                      type="button"
                      onClick={() => removeFromCart && removeFromCart(item.cartKey || item.id)}
                      className="absolute -top-1 -left-1 bg-[#E53E3E] hover:bg-red-700 text-white rounded-full p-0.5 shadow-md transition-transform hover:scale-110 z-10 flex items-center justify-center cursor-pointer"
                      title="Remove item"
                    >
                      <IoClose className="text-xs" />
                    </button>
                  </div>
                  <div className="flex flex-col min-w-0">
                    <span className="text-sm font-medium truncate text-gray-800 max-w-[150px] sm:max-w-[200px]">
                      {item.title} {!isBuyNow && `(x${item.quantity})`}
                    </span>
                    {item.variantName && <span className="text-xs text-gray-500">{item.variantName}</span>}

                    {isBuyNow && (
                      <div className="flex items-center border border-gray-300 rounded w-fit mt-1 bg-white">
                        <button
                          type="button"
                          onClick={() => handleQuantityChange(item.cartKey || item.id, item.quantity - 1)}
                          className="px-1.5 py-0.5 text-gray-600 hover:bg-gray-100 border-r border-gray-200"
                        >
                          <HiMinus className="text-xs" />
                        </button>
                        <span className="px-2 text-xs font-semibold text-gray-800">
                          {item.quantity}
                        </span>
                        <button
                          type="button"
                          onClick={() => handleQuantityChange(item.cartKey || item.id, item.quantity + 1)}
                          className="px-1.5 py-0.5 text-gray-600 hover:bg-gray-100 border-l border-gray-200"
                        >
                          <HiPlus className="text-xs" />
                        </button>
                      </div>
                    )}
                  </div>
                </div>

                <span className="font-semibold text-gray-900 flex-shrink-0">BDT {(item.price * item.quantity).toFixed(2)}</span>
              </div>
            ))
          )}
        </div>

        {/* Shipping Area */}
        <div className="space-y-3 bg-gray-50 p-4 rounded-lg border border-gray-200">
          <p className="text-sm font-semibold text-gray-700">Select Shipping Area:</p>
          <div className="flex flex-col gap-2">
            <label className="flex items-center gap-2 text-sm cursor-pointer">
              <input type="radio" name="shipping" checked={shippingMethod === 'inside'} onChange={() => setShippingMethod('inside')} className="accent-[#DB4444]" />
              <span>Inside Dhaka (BDT {shippingInside})</span>
            </label>
            <label className="flex items-center gap-2 text-sm cursor-pointer">
              <input type="radio" name="shipping" checked={shippingMethod === 'outside'} onChange={() => setShippingMethod('outside')} className="accent-[#DB4444]" />
              <span>Outside Dhaka (BDT {shippingOutside})</span>
            </label>
          </div>
        </div>

        {/* Payment Methods */}
        <div className="space-y-3 bg-gray-50 p-4 rounded-lg border border-gray-200">
          <p className="text-sm font-semibold text-gray-700">Payment Method / পেমেন্ট মাধ্যম:</p>
          <div className="flex flex-col gap-2.5">
            {enabledPaymentKeys.length === 0 ? (
              <p className="text-xs text-gray-500">No payment methods currently enabled.</p>
            ) : (
              enabledPaymentKeys.map((key) => {
                const method = paymentMethods[key];
                const isSelected = paymentMethod === key;

                return (
                  <div
                    key={key}
                    className={`rounded-lg border transition-all ${
                      isSelected
                        ? 'border-[var(--color-primary)] bg-white shadow-sm ring-1 ring-[var(--color-primary)]'
                        : 'border-gray-200 bg-white hover:border-gray-300'
                    }`}
                  >
                    <label className="flex items-center justify-between p-3 cursor-pointer">
                      <div className="flex items-center gap-3">
                        <input
                          type="radio"
                          name="paymentMethod"
                          value={key}
                          checked={isSelected}
                          onChange={() => setPaymentMethod(key)}
                          className="accent-[var(--color-primary)] w-4 h-4 cursor-pointer"
                        />
                        <span className="text-sm font-semibold text-gray-800">
                          {method?.title || key.toUpperCase()}
                        </span>
                      </div>

                      {key === 'cod' && (
                        <span className="text-xs font-semibold px-2.5 py-0.5 rounded bg-emerald-100 text-emerald-700 border border-emerald-200">
                          Cash on Delivery
                        </span>
                      )}
                      {key === 'bkash' && (
                        <span className="text-xs font-bold px-2.5 py-0.5 rounded bg-[#D12053] text-white tracking-wide">
                          bKash {method?.type ? `(${method.type})` : ''}
                        </span>
                      )}
                      {key === 'nagad' && (
                        <span className="text-xs font-bold px-2.5 py-0.5 rounded bg-[#F7941D] text-white tracking-wide">
                          Nagad {method?.type ? `(${method.type})` : ''}
                        </span>
                      )}
                      {key === 'rocket' && (
                        <span className="text-xs font-bold px-2.5 py-0.5 rounded bg-[#8C3494] text-white tracking-wide">
                          Rocket {method?.type ? `(${method.type})` : ''}
                        </span>
                      )}
                    </label>

                    {/* Mobile banking details if selected */}
                    {isSelected && key !== 'cod' && (
                      <div className="px-4 pb-4 pt-2 border-t border-gray-100 space-y-3 bg-gray-50/70 rounded-b-lg">
                        {method?.number && (
                          <div className="flex items-center justify-between bg-white p-2.5 rounded border border-gray-200 text-xs">
                            <div>
                              <span className="text-gray-500 block">
                                {method.title} ({method.type || 'Personal'}) Account Number:
                              </span>
                              <span className="text-sm font-bold text-gray-900 tracking-wider">
                                {method.number}
                              </span>
                            </div>
                            <button
                              type="button"
                              onClick={() => handleCopy(method.number, key)}
                              className="px-2.5 py-1 text-xs font-medium rounded bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 transition-colors cursor-pointer"
                            >
                              {copiedKey === key ? 'Copied! ✓' : 'Copy'}
                            </button>
                          </div>
                        )}

                        {method?.instructions && (
                          <p className="text-xs text-gray-600 leading-relaxed bg-amber-50/80 border border-amber-200 p-2.5 rounded">
                            {method.instructions}
                          </p>
                        )}

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                          <div>
                            <label className="block text-xs font-medium text-gray-700 mb-1">
                              Sender Mobile Number / যে নাম্বার থেকে টাকা পাঠিয়েছেন <span className="text-red-500">*</span>
                            </label>
                            <input
                              type="tel"
                              placeholder="01XXXXXXXXX"
                              value={paymentNumber}
                              onChange={(e) => setPaymentNumber(e.target.value)}
                              className="w-full text-xs p-2.5 bg-white border border-gray-300 rounded focus:outline-none focus:border-[var(--color-primary)]"
                              required
                            />
                          </div>
                          <div>
                            <label className="block text-xs font-medium text-gray-700 mb-1">
                              Transaction ID (TrxID) / ট্রানজেকশন আইডি <span className="text-red-500">*</span>
                            </label>
                            <input
                              type="text"
                              placeholder="e.g. 9X8Y7Z2W"
                              value={transactionId}
                              onChange={(e) => setTransactionId(e.target.value)}
                              className="w-full text-xs p-2.5 bg-white border border-gray-300 rounded focus:outline-none focus:border-[var(--color-primary)] uppercase font-mono font-medium"
                              required
                            />
                          </div>
                        </div>
                      </div>
                    )}

                    {/* COD instructions if selected */}
                    {isSelected && key === 'cod' && method?.instructions && (
                      <div className="px-4 pb-3 pt-1 border-t border-gray-100 text-xs text-gray-500">
                        {method.instructions}
                      </div>
                    )}
                  </div>
                );
              })
            )}
          </div>
        </div>

        <div className="border-b pb-3 space-y-3 text-sm">
          <div className="flex justify-between">
            <span className="text-gray-600">Subtotal:</span>
            <span className="font-medium">BDT {subtotal.toFixed(2)}</span>
          </div>
          <div className="flex justify-between border-t pt-3">
            <span className="text-gray-600">Shipping:</span>
            <span className="font-medium">BDT {shippingCharge}</span>
          </div>
        </div>
        <div className="flex justify-between font-bold text-base">
          <span>Total:</span>
          <span>BDT {totalCost.toFixed(2)}</span>
        </div>

        <button
          type="submit"
          disabled={isSubmitting || cart.length === 0}
          className="bg-[var(--color-button)] text-white py-3 w-full rounded-md hover:bg-black transition-all font-semibold cursor-pointer flex items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {isSubmitting ? <Spinner size="sm" color="current" /> : <GiShoppingBag className="text-xl" />}
          <span>{isSubmitting ? 'Placing order...' : 'Order now'}</span>
        </button>
      </div>
    </form>
  );
};

export default CheckoutForm;
