"use client";

import { Swiper, SwiperSlide } from "swiper/react";
import { Autoplay } from "swiper/modules";
import ProductCard from "./ProductCard";
import "swiper/css";
export default function RelatedProductsSlider({ relatedProducts }) {
  return (
    <div className="w-full relative pt-4">
      <Swiper
        modules={[Autoplay]}
        spaceBetween={16}
        slidesPerView={1}
        observer={true}
        observeParents={true}
        watchOverflow={true}
        autoplay={{
          delay: 1000,
          disableOnInteraction: false,
          pauseOnMouseEnter: true,
        }}
        breakpoints={{
          450: { slidesPerView: 1.5, spaceBetween: 16 },
          640: { slidesPerView: 2, spaceBetween: 16 },
          768: { slidesPerView: 2.5, spaceBetween: 20 },
          1024: { slidesPerView: 3, spaceBetween: 24 },
          1280: { slidesPerView: 4, spaceBetween: 24 },
        }}
        className="mySwiper !overflow-visible"
      >
        {relatedProducts.map((product) => (
          <SwiperSlide key={product.id} className="!h-auto">
            <div className="pb-4">
              <ProductCard product={product} />
            </div>
          </SwiperSlide>
        ))}
      </Swiper>
    </div>
  );
}