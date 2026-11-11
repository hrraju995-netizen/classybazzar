"use client";

import React from "react";
import { useCartStore } from "@/store/useCartStore";
import { toast } from "react-toastify";
import { FiShoppingCart } from "react-icons/fi";
import { useRouter } from "next/navigation";

const AddToCartButton = ({ product, quantity = 1 }) => {
  const addToCart = useCartStore((state) => state.addToCart);
  const cart = useCartStore((state) => state.cart);
  const openCart = useCartStore((state) => state.openCart);
  const router = useRouter();

  const handleAddToCart = (e) => {
    e.preventDefault();
    if (product.productType === "variable" && !product.variantId) {
      toast.info("Choose product options first.", { position: "top-center", autoClose: 2000 });
      router.push(`/products/${product.slug || product.id}`);
      return;
    }

    if (product.stock < 1) {
      toast.error("This product is out of stock.", { position: "top-center", autoClose: 2000 });
      return;
    }

    const productKey = product.cartKey || `${product.id}:${product.variantId || 0}`;
    const isAlreadyInCart = cart.some((item) => (item.cartKey || `${item.id}:${item.variantId || 0}`) === productKey);

    if (isAlreadyInCart) {
      toast.info(`"${product.title}" is already in cart!`, { position: "top-center", autoClose: 2000 });
      openCart();
      return;
    }
    for (let index = 0; index < quantity; index += 1) {
      addToCart(product);
    }
    openCart();
    toast.success(`${product.title} added!`, { position: "top-center", autoClose: 1500 });
  };

  return (
    <button
      onClick={handleAddToCart}
      className="group/cart relative flex items-center justify-center w-full bg-black text-white transition-all duration-300 rounded-sm text-xs sm:text-sm font-medium font-poppins h-9 sm:h-10 overflow-hidden cursor-pointer"
    >
      <span className="transition-all duration-300 group-hover/cart:-translate-y-10 group-hover/cart:opacity-0">
        {product.productType === "variable" && !product.variantId ? "Choose Options" : "Add To Cart"}
      </span>
      <span className="absolute inset-0 flex items-center justify-center translate-y-10 opacity-0 group-hover/cart:translate-y-0 group-hover/cart:opacity-100">
        <FiShoppingCart size={18} />
      </span>
    </button>
  );
};

export default AddToCartButton;
