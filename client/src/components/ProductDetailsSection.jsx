'use client';
import React, { useState, useEffect, useMemo } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { HiMinusSmall } from 'react-icons/hi2';
import { GoPlus } from 'react-icons/go';
import { useCartStore } from '@/store/useCartStore';
import { useWishlistStore } from '@/store/useWishlistStore';
import { FaBangladeshiTakaSign, FaWhatsapp } from 'react-icons/fa6';
import { FiPhoneCall, FiChevronLeft, FiChevronRight, FiX } from 'react-icons/fi';
import AddToCartButton from './AddToCartButton';
import { toast } from 'react-toastify';
import { useHydrated } from '@/hooks/useHydrated';
import { getCartUnitPrice, getWholesaleMinQuantity, hasWholesaleOffer, isWholesaleCartItem } from '@/lib/pricing';

const normalizedOptionKey = (value) => String(value || '').trim().toLocaleLowerCase();

const normalizeAttributes = (attributes = {}) => Object.entries(attributes).reduce((result, [name, value]) => {
  const key = normalizedOptionKey(name);
  if (key && String(value || '').trim()) {
    result[key] = String(value).trim();
  }
  return result;
}, {});

const ProductDetailsSection = ({ product }) => {
  const initialQuantity = hasWholesaleOffer(product)
    ? Math.min(getWholesaleMinQuantity(product), Math.max(1, Number(product?.stock) || 1))
    : 1;
  const [selectedImage, setSelectedImage] = useState(0);
  const [quantity, setQuantity] = useState(initialQuantity);
  const isMounted = useHydrated();
  const [isOpenPopup, setIsOpenPopup] = useState(false);
  const [selectedOptions, setSelectedOptions] = useState({});
  const [siteSettings, setSiteSettings] = useState({
    whatsappNumber: "+8801793-293600",
    phoneNumber: "+8801793-293600"
  });

  const addToCart = useCartStore((state) => state.addToCart);
  const wishlist = useWishlistStore((state) => state.wishlist);
  const toggleWishlist = useWishlistStore((state) => state.toggleWishlist);

  const isProductInWishlist = wishlist.some((item) => item.id === product?.id);

  useEffect(() => {
    if (isOpenPopup) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'unset';
    }
    return () => { document.body.style.overflow = 'unset'; };
  }, [isOpenPopup]);

  const isVariable = product?.productType === 'variable';
  const variants = useMemo(() => (product?.variants || [])
    .filter((variant) => variant.isActive)
    .map((variant) => ({ ...variant, normalizedAttributes: normalizeAttributes(variant.attributes) })), [product?.variants]);
  const variationAttributes = useMemo(() => {
    const groups = new Map();

    for (const attribute of product?.variationAttributes || []) {
      const key = normalizedOptionKey(attribute.name);
      if (!key) continue;

      const current = groups.get(key) || { name: String(attribute.name).trim(), key, values: [] };
      for (const rawValue of attribute.values || []) {
        const value = String(rawValue).trim();
        if (value && !current.values.some((existing) => normalizedOptionKey(existing) === normalizedOptionKey(value))) {
          current.values.push(value);
        }
      }
      groups.set(key, current);
    }

    return [...groups.values()];
  }, [product?.variationAttributes]);

  const selectedVariant = useMemo(() => {
    if (!isVariable || variationAttributes.some(({ key }) => !selectedOptions[key])) {
      return null;
    }

    return variants.find((variant) =>
      Object.entries(selectedOptions).every(([name, value]) =>
        normalizedOptionKey(variant.normalizedAttributes[name]) === normalizedOptionKey(value)
      )
    ) || null;
  }, [isVariable, selectedOptions, variants, variationAttributes]);

  const productImages = useMemo(() => {
    const images = [selectedVariant?.image, product?.thumbnail, ...(product?.images || [])].filter(Boolean);
    return [...new Set(images)];
  }, [product?.images, product?.thumbnail, selectedVariant?.image]);

  const availableStock = selectedVariant?.stock ?? product?.stock ?? 0;
  const retailDisplayPrice = selectedVariant?.price ?? product?.price;
  const cartProduct = selectedVariant ? {
    ...product,
    price: selectedVariant.price,
    wholesalePrice: selectedVariant.wholesalePrice,
    originalPrice: selectedVariant.originalPrice,
    discountPercentage: selectedVariant.discountPercentage,
    stock: selectedVariant.stock,
    thumbnail: selectedVariant.image || product.thumbnail,
    variantId: selectedVariant.id,
    variantName: selectedVariant.name,
    variantSku: selectedVariant.sku,
    variantAttributes: selectedVariant.attributes,
    cartKey: `${product.id}:${selectedVariant.id}`,
  } : product;
  const wholesaleOffer = hasWholesaleOffer(cartProduct);
  const wholesaleMinQuantity = getWholesaleMinQuantity(cartProduct);
  const wholesaleActive = isWholesaleCartItem(cartProduct, quantity);
  const displayPrice = wholesaleActive ? getCartUnitPrice(cartProduct, quantity) : retailDisplayPrice;
  const minimumSelectableQuantity = wholesaleOffer ? wholesaleMinQuantity : 1;
  const regularTotal = Number(retailDisplayPrice || 0) * quantity;
  const totalPrice = Number(displayPrice || 0) * quantity;
  const totalSavings = wholesaleActive ? Math.max(0, regularTotal - totalPrice) : 0;

  const selectOption = (name, value) => {
    setSelectedImage(0);
    const candidate = { ...selectedOptions, [name]: value };
    const hasCompatibleVariant = variants.some((variant) =>
      variant.stock > 0 && Object.entries(candidate).every(([key, selected]) =>
        normalizedOptionKey(variant.normalizedAttributes[key]) === normalizedOptionKey(selected)
      )
    );

    // Keep the option the customer just chose and release stale choices
    // when that combination does not exist (for example Red/M -> Blue/L).
    const nextOptions = hasCompatibleVariant ? candidate : { [name]: value };
    const selectionComplete = variationAttributes.every(({ key }) => nextOptions[key]);
    const nextVariant = selectionComplete
      ? variants.find((variant) =>
        Object.entries(nextOptions).every(([key, selected]) =>
          normalizedOptionKey(variant.normalizedAttributes[key]) === normalizedOptionKey(selected)
        )
      )
      : null;

    if (nextVariant) {
      const nextCartProduct = {
        ...product,
        wholesalePrice: nextVariant.wholesalePrice,
      };
      const nextMinimum = hasWholesaleOffer(nextCartProduct)
        ? getWholesaleMinQuantity(nextCartProduct)
        : 1;
      setQuantity(Math.min(nextMinimum, nextVariant.stock));
    } else {
      setQuantity(1);
    }

    setSelectedOptions(nextOptions);
  };

  const isOptionAvailable = (name, value) => {
    return variants.some((variant) =>
      variant.stock > 0 && normalizedOptionKey(variant.normalizedAttributes[name]) === normalizedOptionKey(value)
    );
  };

  const handlePrevImage = () => {
    setSelectedImage((prev) => (prev === 0 ? productImages.length - 1 : prev - 1));
  };

  const handleNextImage = () => {
    setSelectedImage((prev) => (prev === productImages.length - 1 ? 0 : prev + 1));
  };

  const handleBuyNow = (event) => {
    if (isVariable && !selectedVariant) {
      event.preventDefault();
      toast.info('Please choose all product options first.', { position: 'top-center', autoClose: 2000 });
      return;
    }

    if (availableStock < 1) {
      event.preventDefault();
      toast.error('This variation is out of stock.', { position: 'top-center', autoClose: 2000 });
      return;
    }

    if (cartProduct) {
      for (let i = 0; i < quantity; i++) {
        addToCart(cartProduct);
      }
    }
  };

  const rawWhatsapp = siteSettings?.whatsappNumber || "8801700000000";
  const whatsappNumber = rawWhatsapp.replace(/[^\d]/g, '');
  const callNumber = siteSettings?.phoneNumber || "+8801700000000";

  const currentUrl = typeof window !== 'undefined' ? window.location.href : '';
  const whatsappMessage = `Hello, I want to order this product:\n\n🛍️ *Product:* ${product?.title}\n💰 *Price:* ৳${product?.price}\n🔢 *Quantity:* ${quantity}\n🔗 *Link:* ${currentUrl}`;
  const pricingMessage = `${whatsappMessage}\nSelected unit price: BDT ${displayPrice}${wholesaleActive ? ' (wholesale)' : ''}`;
  const variationMessage = selectedVariant
    ? `${pricingMessage}\nVariation: ${selectedVariant.name}`
    : pricingMessage;
  const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(variationMessage)}`;

  return (
    <div className="pt-4 sm:pt-8 pb-12 sm:pb-16 px-4 md:px-8 lg:px-16 text-black select-none font-poppins">
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 lg:gap-16 items-start">
        <div className="lg:col-span-7 flex flex-row gap-2.5 sm:gap-4 items-start w-full">
          {/* Thumbnails Column on the Left (Matches Image 2) */}
          <div className="flex flex-col gap-2 shrink-0 w-14 sm:w-16 md:w-20 max-h-[380px] sm:max-h-[480px] md:max-h-[520px] overflow-y-auto no-scrollbar pb-1">
            {productImages.map((img, index) => (
              <button
                key={index}
                onClick={() => setSelectedImage(index)}
                className={`w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20 bg-white p-1 rounded-xl border-2 transition-all flex items-center justify-center shrink-0 cursor-pointer ${
                  selectedImage === index ? 'border-[var(--color-primary,#eb6e1b)]' : 'border-gray-200 hover:border-gray-400'
                }`}
              >
                <div className="relative w-full h-full">
                  <Image src={img} alt={`thumb-${index}`} fill className="object-contain" sizes="80px" />
                </div>
              </button>
            ))}
          </div>

          {/* Main Product Image Large Rounded Card on the Right (Matches Image 2) */}
          <div
            onClick={() => setIsOpenPopup(true)}
            className="flex-1 aspect-square bg-white border border-gray-100 rounded-2xl relative group overflow-hidden shadow-xs cursor-zoom-in flex items-center justify-center shrink-0 sm:shrink"
          >
            <button
              onClick={(e) => {
                e.stopPropagation();
                handlePrevImage();
              }}
              className="absolute left-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/80 hover:bg-white text-gray-600 rounded-full flex items-center justify-center shadow-md transition-all cursor-pointer border border-gray-100 opacity-80 sm:opacity-0 sm:group-hover:opacity-100"
            >
              <FiChevronLeft size={20} />
            </button>
            <div className="absolute inset-3 sm:inset-4 flex items-center justify-center">
              <Image
                src={productImages[selectedImage] || product?.thumbnail || '/navbarLogo.png'}
                alt={product?.title || "Product Image"}
                fill
                className="object-contain p-2"
                priority
                sizes="(max-width: 768px) 80vw, 45vw"
              />
            </div>
            <button
              onClick={(e) => {
                e.stopPropagation();
                handleNextImage();
              }}
              className="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/80 hover:bg-white text-gray-600 rounded-full flex items-center justify-center shadow-md transition-all cursor-pointer border border-gray-100 opacity-80 sm:opacity-0 sm:group-hover:opacity-100"
            >
              <FiChevronRight size={20} />
            </button>
          </div>
        </div>
        <div className="lg:col-span-5 flex flex-col justify-start gap-5">
          <div>
            <h1 className="text-2xl md:text-3xl font-semibold mb-2">{product?.title}</h1>
            <span className={`${availableStock > 0 ? 'text-emerald-600' : 'text-red-500'} font-medium text-sm`}>
              {availableStock > 0 ? `${availableStock} in stock` : 'Out of Stock'}
            </span>
          </div>

          <div className="text-2xl font-bold flex items-center gap-1">
            {isVariable && !selectedVariant && <span className="mr-1 text-sm font-medium text-gray-500">From</span>}
            <FaBangladeshiTakaSign />{displayPrice}
          </div>

          {wholesaleOffer && (
            <div className={'rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm'}>
              <div className={'flex flex-wrap items-center justify-between gap-2'}>
                <span className={'font-semibold text-emerald-900'}>Wholesale: BDT {cartProduct.wholesalePrice} per unit</span>
                <span className={'rounded-full bg-emerald-700 px-2.5 py-1 text-xs font-semibold text-white'}>Min {wholesaleMinQuantity} units</span>
              </div>
              <p className={'mt-2 text-emerald-700'}>
                {wholesaleActive
                  ? 'Wholesale price is active for this quantity.'
                  : 'Add ' + Math.max(0, wholesaleMinQuantity - quantity) + ' more to unlock the wholesale price.'}
              </p>
              <div className={'mt-4 grid grid-cols-1 gap-2 border-t border-emerald-200 pt-3 sm:grid-cols-3'}>
                <div className={'rounded-md bg-white/80 p-3'}>
                  <span className={'block text-xs text-gray-500'}>Quantity</span>
                  <strong className={'mt-1 block text-base text-gray-950'}>{quantity} units</strong>
                </div>
                <div className={'rounded-md bg-white/80 p-3'}>
                  <span className={'block text-xs text-gray-500'}>Total price</span>
                  <strong className={'mt-1 block text-base text-gray-950'}>
                    BDT {totalPrice.toLocaleString('en-BD', { maximumFractionDigits: 2 })}
                  </strong>
                </div>
                <div className={'rounded-md bg-emerald-700 p-3 text-white'}>
                  <span className={'block text-xs text-emerald-100'}>You save</span>
                  <strong className={'mt-1 block text-base'}>
                    BDT {totalSavings.toLocaleString('en-BD', { maximumFractionDigits: 2 })}
                  </strong>
                </div>
              </div>
              {wholesaleActive && (
                <p className={'mt-2 text-xs text-emerald-700'}>
                  Regular total: BDT {regularTotal.toLocaleString('en-BD', { maximumFractionDigits: 2 })}
                </p>
              )}
            </div>
          )}

          {isVariable && variationAttributes.length > 0 && (
            <div className="space-y-4 border-y border-gray-200 py-5">
              {variationAttributes.map(({ name, key, values }) => (
                <fieldset key={key}>
                  <legend className="mb-2 text-sm font-semibold text-gray-800">{name}</legend>
                  <div className="flex flex-wrap gap-2">
                    {values.map((value) => {
                      const selected = selectedOptions[key] === value;
                      const available = isOptionAvailable(key, value);

                      return (
                        <button
                          key={value}
                          type="button"
                          disabled={!available}
                          onClick={() => selectOption(key, value)}
                          className={`min-h-10 min-w-12 border px-4 py-2 text-sm font-medium transition-colors ${selected
                            ? 'border-[var(--color-primary)] bg-[var(--color-button)] text-white'
                            : available
                              ? 'border-gray-300 bg-white text-gray-800 hover:border-[var(--color-primary)]'
                              : 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 line-through'
                          }`}
                        >
                          {value}
                        </button>
                      );
                    })}
                  </div>
                </fieldset>
              ))}

              {selectedVariant && (
                <p className="text-xs text-gray-500">
                  Selected: <span className="font-semibold text-gray-800">{selectedVariant.name}</span>
                  {selectedVariant.sku ? ` | SKU: ${selectedVariant.sku}` : ''}
                </p>
              )}
            </div>
          )}

          <div className="flex flex-col gap-4">
            <div className="flex items-center gap-2 md:gap-4 h-12">
              <div className="flex items-center border border-gray-300 rounded overflow-hidden h-full bg-white">
                <button
                  onClick={() => setQuantity(Math.max(minimumSelectableQuantity, quantity - 1))}
                  disabled={quantity <= minimumSelectableQuantity}
                  className="px-4 hover:bg-gray-100 h-full border-r border-gray-300 cursor-pointer"
                >
                  <HiMinusSmall />
                </button>
                <div className="w-12 text-center font-medium">{String(quantity).padStart(2, '0')}</div>
                <button
                  onClick={() => setQuantity(Math.min(quantity + 1, availableStock || 1))}
                  disabled={availableStock < 1}
                  className="px-4 bg-[var(--color-button)] text-white h-full cursor-pointer disabled:cursor-not-allowed disabled:bg-gray-300"
                >
                  <GoPlus />
                </button>
              </div>

              <Link
                href="/checkout"
                onClick={handleBuyNow}
                className="flex-1 bg-[var(--color-button)] hover:bg-black text-white font-medium h-full rounded flex items-center justify-center transition-all"
              >
                Buy Now
              </Link>

              <button
                onClick={() => product && toggleWishlist(product)}
                className="w-12 h-full border border-gray-300 rounded flex items-center justify-center hover:border-black transition-all"
              >
                <svg className={`w-6 h-6 ${isMounted && isProductInWishlist ? 'fill-red-500 text-red-500' : 'text-black'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
              </button>
            </div>

            <div className="w-full">
              <AddToCartButton product={cartProduct} quantity={quantity} />
            </div>

            <div className="flex flex-col sm:flex-row gap-3 w-full">
              <Link
                href={isMounted ? whatsappUrl : '#'}
                target="_blank"
                rel="noopener noreferrer"
                className="flex-1 flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#20ba5a] text-white font-medium py-3 px-4 rounded transition-all text-sm sm:text-base shadow-sm whitespace-nowrap min-h-[48px]"
              >
                <FaWhatsapp className="text-xl shrink-0" />
                WhatsApp Order
              </Link>
              <Link
                href={isMounted ? `tel:${callNumber}` : '#'}
                className="flex-1 flex items-center justify-center gap-2 bg-[#10b981] hover:bg-[#059669] text-white font-medium py-3 px-4 rounded transition-all text-sm sm:text-base shadow-sm whitespace-nowrap min-h-[48px]"
              >
                <FiPhoneCall className="text-lg shrink-0" />
                Order On Call
              </Link>
            </div>
          </div>
        </div>
      </div>

      <div className="mt-16 border-t pt-8">
        <h2 className="text-lg font-semibold mb-4">Product Description</h2>
        <p className="text-gray-600 leading-relaxed text-sm max-w-4xl">{product?.description}</p>
      </div>
      {isOpenPopup && (
        <div
          onClick={() => setIsOpenPopup(false)}
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4 animate-fadeIn"
        >
          <div
            onClick={(e) => e.stopPropagation()}
            className="relative bg-white rounded-xl p-6 w-full max-w-xl md:max-w-2xl aspect-square flex items-center justify-center shadow-2xl overflow-hidden"
          >
            <button
              onClick={() => setIsOpenPopup(false)}
              className="absolute top-4 right-4 z-50 p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 hover:text-black rounded-full transition-all cursor-pointer shadow-sm"
            >
              <FiX size={20} />
            </button>
            <button
              onClick={handlePrevImage}
              className="absolute left-4 top-1/2 -translate-y-1/2 z-40 w-10 h-10 bg-white hover:bg-gray-50 text-gray-800 rounded-full flex items-center justify-center shadow-lg transition-all cursor-pointer border border-gray-100"
            >
              <FiChevronLeft size={24} />
            </button>
            <div className="relative w-full h-full max-h-[85%]">
              <Image
                src={productImages[selectedImage]}
                alt="Popup Product View"
                fill
                className="object-contain"
                sizes="(max-width: 768px) 90vw, 60vw"
              />
            </div>
            <button
              onClick={handleNextImage}
              className="absolute right-4 top-1/2 -translate-y-1/2 z-40 w-10 h-10 bg-white hover:bg-gray-50 text-gray-800 rounded-full flex items-center justify-center shadow-lg transition-all cursor-pointer border border-gray-100"
            >
              <FiChevronRight size={24} />
            </button>
            <div className="absolute bottom-4 left-1/2 -translate-x-1/2 bg-gray-900/80 text-white px-3 py-1 rounded-full text-xs font-medium tracking-wide">
              {selectedImage + 1} / {productImages.length}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProductDetailsSection;
