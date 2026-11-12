'use client';

import React from 'react';
import { useCartStore } from '@/store/useCartStore';
import Image from 'next/image';
import Link from 'next/link';
import { X } from 'lucide-react';
import { FiTrash2 } from 'react-icons/fi';
import Button from '@/components/Button';
import SubBanner from '@/components/SubBanner';
import { IoBagCheckOutline } from 'react-icons/io5';
import { GrCart } from 'react-icons/gr';
import { useHydrated } from '@/hooks/useHydrated';

const CartPage = () => {
  const { cart, removeFromCart, updateQuantity, shippingMethod, setShippingMethod } = useCartStore();
  const isMounted = useHydrated();
  if (!isMounted) return <div className="text-center py-10 font-sans">Loading cart...</div>;
  const subtotal = cart.reduce((total, item) => total + item.price * item.quantity, 0);
  const shippingCharge = shippingMethod === 'inside' ? 70 : 130;
  const totalCost = subtotal + shippingCharge;

  return (
    <section className="w-full bg-gray-50/30 pb-12">
      <div>
        <SubBanner title={"Cart"} pageName={"Cart"} />
      </div>

      <div className="container pt-3 md:pt-5 px-4 md:px-6 font-sans text-black mt-6 md:mt-10">
        {cart.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-20 text-gray-400">
            <GrCart size={64} strokeWidth={1} className="mb-4 text-gray-300" />
            <p className="text-lg font-medium">Your cart is empty!</p>
            <p className="text-sm pb-4 md:pb-6">Add some items to your cart to see them here.</p>
            <Button TagName={Link} href={"/products"}>back to Shop</Button>
          </div>
        ) : (
          <div className="flex flex-col gap-6">
            <div className="hidden md:block bg-white rounded-md border shadow-sm overflow-hidden">
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse min-w-[700px]">
                  <thead>
                    <tr className="border-b bg-gray-50 text-gray-600 font-medium text-sm">
                      <th className="py-4 px-4">Product</th>
                      <th className="py-4 px-4">Price</th>
                      <th className="py-4 px-4">Quantity</th>
                      <th className="py-4 px-4">Subtotal</th>
                      <th className="py-4 px-4 text-center">Delete</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {cart.map((item) => (
                      <tr key={item.cartKey || item.id} className="border-b group hover:bg-gray-50/30 transition-colors">
                        <td className="py-5 px-4 flex items-center space-x-4 relative">
                          <div className="relative shrink-0">
                            <button
                              onClick={() => removeFromCart(item.cartKey || item.id)}
                              className="absolute -top-2 -left-2 bg-[#DB4444] text-white rounded-full p-0.5 z-10 cursor-pointer shadow hover:scale-105 transition-transform"
                            >
                              <X className="w-3 h-3" />
                            </button>
                            <div className="w-14 h-14 bg-white border rounded-md p-1 flex items-center justify-center">
                              <Image src={item.thumbnail} alt={item.title} width={50} height={50} className="object-contain rounded-sm" />
                            </div>
                          </div>
                          <div className="min-w-0">
                            <span className="block font-medium text-gray-800 truncate max-w-[180px] lg:max-w-[240px]">{item.title}</span>
                            {item.variantName && <span className="block text-xs text-gray-500">{item.variantName}</span>}
                          </div>
                        </td>
                        <td className="py-5 px-4 text-gray-700">৳{item.price.toFixed(2)}</td>
                        <td className="py-5 px-4">
                          <div className="flex items-center border border-gray-400 rounded-md overflow-hidden w-fit h-10 select-none bg-white">
                            <button
                              onClick={() => updateQuantity(item.cartKey || item.id, item.quantity - 1)}
                              className="px-4 h-full bg-white text-gray-700 hover:bg-gray-100 transition-colors font-medium border-r border-gray-400 flex items-center justify-center cursor-pointer text-lg"
                            >
                              −
                            </button>
                            <div className="w-12 text-center font-bold text-black text-base flex items-center justify-center h-full bg-white">
                              {String(item.quantity).padStart(2, '0')}
                            </div>
                            <button
                              onClick={() => updateQuantity(item.cartKey || item.id, item.quantity + 1)}
                              className="px-4 h-full bg-[var(--color-button)] text-white hover:bg-orange-600 transition-colors font-medium flex items-center justify-center cursor-pointer text-lg"
                            >
                              +
                            </button>
                          </div>
                        </td>
                        <td className="py-5 px-4 font-medium text-gray-900">
                          ৳{(item.price * item.quantity).toFixed(2)}
                        </td>
                        <td className="py-5 px-4 text-center">
                          <button
                            onClick={() => removeFromCart(item.cartKey || item.id)}
                            className="text-gray-400 hover:text-[#DB4444] transition-colors p-2 cursor-pointer inline-flex items-center justify-center"
                            title="Remove Item"
                          >
                            <FiTrash2 className="w-5 h-5" />
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
            <div className="block md:hidden space-y-4">
              {cart.map((item) => (
                <div key={item.cartKey || item.id} className="bg-white p-4 rounded-md border border-gray-200 shadow-sm flex gap-4 relative">
                  <button
                    onClick={() => removeFromCart(item.cartKey || item.id)}
                    className="absolute top-3 right-3 text-gray-400 hover:text-[#DB4444] p-1 cursor-pointer"
                  >
                    <FiTrash2 className="w-5 h-5" />
                  </button>
                  <div className="w-20 h-20 bg-gray-50 border border-gray-200 rounded-md p-1 flex items-center justify-center shrink-0">
                    <Image src={item.thumbnail} alt={item.title} width={70} height={70} className="object-contain rounded-sm" />
                  </div>

                  <div className="flex flex-col justify-between flex-1 pr-6">
                    <div>
                      <h3 className="font-semibold text-gray-800 text-sm line-clamp-1">{item.title}</h3>
                      {item.variantName && <p className="text-xs text-gray-500">{item.variantName}</p>}
                      <p className="text-xs text-gray-500 mt-0.5">Price: ৳{item.price.toFixed(2)}</p>
                    </div>

                    <div className="flex items-center justify-between mt-2">
                      <div className="flex items-center border border-gray-400 rounded-md overflow-hidden h-8 select-none bg-white">
                        <button
                          onClick={() => updateQuantity(item.cartKey || item.id, item.quantity - 1)}
                          className="px-2.5 h-full text-gray-600 hover:bg-gray-100 border-r border-gray-400 flex items-center justify-center cursor-pointer text-sm"
                        >
                          −
                        </button>
                        <div className="w-8 text-center font-medium text-gray-800 text-xs flex items-center justify-center h-full">
                          {String(item.quantity).padStart(2, '0')}
                        </div>
                        <button
                          onClick={() => updateQuantity(item.cartKey || item.id, item.quantity + 1)}
                          className="px-2.5 h-full bg-[var(--color-button)] text-white hover:bg-orange-600 flex items-center justify-center cursor-pointer text-sm"
                        >
                          +
                        </button>
                      </div>
                      <span className="font-bold text-gray-900 text-sm">
                        ৳{(item.price * item.quantity).toFixed(2)}
                      </span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            <div className="flex flex-col lg:flex-row gap-6 justify-between items-start mt-2">
              <div className="w-full lg:w-auto order-2 lg:order-1">
                <Button TagName={Link} href={"/products"} className="w-full sm:w-auto text-center justify-center">
                  Return To Shop
                </Button>
              </div>
              <div className="w-full lg:max-w-[450px] order-1 lg:order-2 border-2 border-black rounded-xl p-5 md:p-6 bg-white shadow-sm">
                <h2 className="text-lg md:text-xl font-bold mb-4">Cart Total</h2>
                <div className="flex justify-between border-b pb-3 mb-3 text-sm">
                  <span className="text-gray-600">Subtotal:</span>
                  <span className="font-semibold">৳{subtotal.toFixed(2)}</span>
                </div>
                <div className="border-b pb-3 mb-4 text-sm">
                  <span className="block mb-2 font-medium text-gray-700">Shipping Location:</span>
                  <div className="space-y-2.5 pl-1">
                    <label className="flex items-center space-x-3 cursor-pointer group text-gray-600">
                      <input
                        type="radio"
                        name="shipping"
                        checked={shippingMethod === 'inside'}
                        onChange={() => setShippingMethod('inside')}
                        className="accent-[var(--color-primary)] w-4 h-4"
                      />
                      <span className="group-hover:text-black transition-colors">Inside Dhaka (৳70)</span>
                    </label>
                    <label className="flex items-center space-x-3 cursor-pointer group text-gray-600">
                      <input
                        type="radio"
                        name="shipping"
                        checked={shippingMethod === 'outside'}
                        onChange={() => setShippingMethod('outside')}
                        className="accent-[var(--color-primary)] w-4 h-4"
                      />
                      <span className="group-hover:text-black transition-colors">Outside Dhaka (৳130)</span>
                    </label>
                  </div>
                </div>

                <div className="flex justify-between font-bold text-base md:text-lg mb-6">
                  <span>Total:</span>
                  <span className="text-[var(--color-primary)]">৳{totalCost.toFixed(2)}</span>
                </div>

                <div>
                  <Link href={"/checkout"} className="block w-full">
                    <button className="w-full flex items-center justify-center gap-2 bg-[var(--color-button)] text-white py-3 rounded-xl font-semibold hover:bg-black transition-colors duration-300 cursor-pointer shadow-sm py-3.5">
                      <IoBagCheckOutline size={19} />
                      <span>Proceed to checkout</span>
                    </button>
                  </Link>
                </div>
              </div>
            </div>

          </div>
        )}
      </div>
    </section>
  );
};

export default CartPage;
