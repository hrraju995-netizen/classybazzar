"use client";

import React from "react";
import { useRouter } from "next/navigation";
import { useCartStore } from "@/store/useCartStore";
import { toast } from "react-toastify";

const BuyNowButton = ({ product }) => {
  const router = useRouter();
  const addToCart = useCartStore((state) => state.addToCart);

  const handleBuyNow = (e) => {
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

    addToCart(product);
    toast.success(`Fill up the form and click the 'Order Now' button to buy the ${product.title} product.`, {
      position: "top-center",
      autoClose: 5000,
    });
    router.push("/checkout?buyNow=true");
  };

  return (
    <button
      onClick={handleBuyNow}
      className="w-full bg-[var(--color-button)] text-white font-poppins py-2 rounded-sm text-xs sm:text-sm font-medium hover:bg-black transition-colors cursor-pointer h-9 sm:h-10 flex items-center justify-center"
    >
      {product.productType === "variable" && !product.variantId ? "Choose Options" : "Buy Now"}
    </button>
  );
};

export default BuyNowButton;
