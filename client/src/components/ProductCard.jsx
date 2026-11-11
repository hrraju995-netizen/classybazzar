import Image from "next/image";
import Link from "next/link";
import AddToCartButton from "./AddToCartButton";
import BuyNowButton from "./BuyNowButton";
import { IoEyeOutline } from "react-icons/io5";
import WishListButton from "./WishListButton";

export default function ProductCard({ product }) {
  const imageSrc = product?.thumbnail || null;
  if (!imageSrc) return null;

  return (
    <div className="group w-full mx-auto rounded-lg overflow-hidden font-poppins bg-[#F5F5F5] hover:shadow-xl sm:hover:-translate-y-2 transition-all duration-300 border border-transparent hover:border-gray-200 will-change-transform flex flex-col justify-between h-full">

      <Link href={`/products/${product.slug || product.id}`} className="block flex-1">
        <div className="relative w-full h-[160px] sm:h-[210px] md:h-[250px] flex items-center justify-center p-1 sm:p-2 rounded-t-md overflow-hidden bg-white/50">

          <div className="absolute top-4 right-2 flex flex-col items-center gap-2 z-10 md:translate-x-6 md:opacity-0 md:group-hover:translate-x-0 md:group-hover:opacity-100 transition-all duration-300 ease-in-out">
            <WishListButton product={product} />
            <span className="hidden sm:flex bg-white w-7 h-7 sm:w-8 sm:h-8 items-center justify-center rounded-full shadow-sm hover:bg-gray-100 transition-colors cursor-pointer shrink-0">
              <IoEyeOutline className="w-3.5 h-3.5 sm:w-4 sm:h-4 text-black" strokeWidth={1.5} />
            </span>

          </div>

          <Image
            src={imageSrc}
            height={250}
            width={250}
            alt={product.title || "image"}
            className="object-contain w-full h-full p-1 sm:p-2 rounded-md transition-transform duration-300 group-hover:scale-105"
          />
        </div>
        <div className="pt-3 pb-2 flex flex-col space-y-1 sm:space-y-1.5 px-2.5 sm:px-3">
          <h3 className="text-xs sm:text-sm md:text-base font-medium text-black tracking-wide truncate font-poppins leading-tight">
            {product.title}
          </h3>

          <div className="flex items-center space-x-2">
            <span className="text-[#DB4444] text-sm sm:text-base font-semibold">
              <span className="font-bold text-xs sm:text-sm font-inter text-black mr-0.5">৳</span>
              {product.productType === "variable" ? "From " : ""}{product.price}
            </span>
          </div>
          <div className="flex items-center pt-0.5 sm:pt-1">
            {product.isWholesale && product.wholesalePrice && (
              <p className={'mb-1 text-[11px] font-semibold text-emerald-700 sm:text-xs'}>
                Wholesale BDT {product.wholesalePrice}/unit - Min {product.wholesaleMinQuantity}
              </p>
            )}
            {product?.stock > 0 ? (
              <span className="px-2 py-0.5 bg-green-100 text-green-700 border border-green-200 rounded text-[10px] sm:text-xs font-semibold">
                In Stock
              </span>
            ) : (
              <span className="px-2 py-0.5 bg-red-100 text-red-700 border border-red-200 rounded text-[10px] sm:text-xs font-semibold">
                Out of Stock
              </span>
            )}
          </div>
        </div>
      </Link>

      <div className="flex flex-col gap-1.5 sm:gap-2 px-2.5 sm:px-3 pb-3 sm:pb-4">
        <AddToCartButton product={product} />
        <BuyNowButton product={product} />
      </div>

    </div>
  );
}
