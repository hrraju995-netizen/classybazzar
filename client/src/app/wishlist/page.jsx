'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { useWishlistStore } from "@/store/useWishlistStore";
import { useCartStore } from "@/store/useCartStore";
import ProductCard from "@/components/ProductCard";
import { toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { HeartCrack } from 'lucide-react';

const WishList = () => {
  const router = useRouter();
  const { wishlist, clearWishlist } = useWishlistStore();
  const addToCart = useCartStore((state) => state.addToCart);

  const handleMoveAllToBag = () => {
    if (wishlist.length === 0) return;

    const variableProduct = wishlist.find((product) => product.productType === 'variable');
    if (variableProduct) {
      toast.info(`Choose options for ${variableProduct.title} first.`, {
        position: "top-center",
        autoClose: 2500,
      });
      router.push(`/products/${variableProduct.slug || variableProduct.id}`);
      return;
    }

    wishlist.forEach((product) => {
      addToCart(product);
    });

    clearWishlist();

    toast.success("All items moved to Cart successfully!", {
      position: "top-center",
      autoClose: 2000,
    });
    router.push('/cart');
  };

  return (
    <div className="container py-8 font-poppins px-4 md:px-0">
      <div className="flex justify-between  items-center mb-8">
        <h1 className="text-xl md:text-2xl font-bold text-black">
          Wishlist ({wishlist.length})
        </h1>
        {wishlist.length > 0 && (
          <button
            onClick={handleMoveAllToBag}
            className="border rounded-tr-2xl rounded-bl-2xl text-black px-6 py-3 rounded text-sm font-medium hover:bg-black hover:text-white transition duration-300 cursor-pointer"
          >
            Move All To Cart
          </button>
        )}
      </div>
      {wishlist.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-20 text-gray-400">
          <HeartCrack size={64} strokeWidth={1} className="mb-4 text-gray-300" />
          <p className="text-lg font-medium">Your wishlist is empty!</p>
          <p className="text-sm">Add some items to your wishlist to see them here.</p>
        </div>
      ) : (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 justify-items-center">
          {wishlist.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
      )}
    </div>
  );
};

export default WishList;
