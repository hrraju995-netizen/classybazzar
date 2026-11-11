'use client';
import { useWishlistStore } from "@/store/useWishlistStore";
import { Heart } from "lucide-react";
import { toast } from 'react-toastify';

const WishListButton = ({ product }) => {
    const { wishlist, toggleWishlist } = useWishlistStore();
    const isWishlisted = wishlist.some((item) => item.id === product.id);

    const handleWishlistToggle = (e) => {
        e.preventDefault();
        toggleWishlist(product);
        if (isWishlisted) {
            toast.error("Removed from wishlist!", { position: "top-center", autoClose: 1500 });
        } else {
            toast.success("Added to wishlist!", { position: "top-center", autoClose: 1500 });
        }
    };

    return (
        <button
            onClick={handleWishlistToggle}
            className={`w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-full shadow-sm transition-colors cursor-pointer border shrink-0 ${isWishlisted
                    ? "bg-[#DB4444] text-white border-[#DB4444]"
                    : "bg-white text-black border-transparent hover:bg-gray-100"
                }`}
        >
            <Heart
                className="w-3.5 h-3.5 sm:w-4 sm:h-4"
                strokeWidth={1.5}
                fill={isWishlisted ? "currentColor" : "transparent"}
            />
        </button>
    );
};

export default WishListButton;