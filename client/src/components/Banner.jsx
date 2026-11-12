'use client';

import Link from 'next/link';
import { FiArrowRight, FiChevronLeft, FiChevronRight } from 'react-icons/fi';
import { FaApple } from 'react-icons/fa';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';
import 'swiper/css';
import 'swiper/css/pagination';
import bannerOne from '../assets/images/banner.jpg';
import bannerTwo from '../assets/images/banner_2.jpg';
import bannerThree from '../assets/images/banner_3.jpg';

const demoSlides = [
  {
    image: bannerOne.src,
    isApple: true,
    eyebrow: 'Iphone X Series',
    title: 'Up to 40% off Voucher',
    description: '',
    buttonLabel: 'Shop Now',
    buttonUrl: '/products?category=mobile-accessories',
  },
  {
    image: bannerTwo.src,
    eyebrow: 'Latest collection',
    title: 'Upgrade your everyday tech',
    description: 'Discover new arrivals and exclusive deals.',
    buttonLabel: 'Shop Now',
    buttonUrl: '/products',
  },
  {
    image: bannerThree.src,
    eyebrow: 'Member offer',
    title: 'Big savings on customer favourites',
    description: 'Limited-time prices across popular categories.',
    buttonLabel: 'Explore Deals',
    buttonUrl: '/products?sort=best-selling',
  },
];

const Banner = ({ slides = [], autoplay = true, arrows = true, delay = 4500, embedded = false }) => {
  // Use demoSlides if slides is empty, or ensure the classic slide is active
  const items = slides.length && slides.some(s => s.image && !s.image.includes('banner-1.jpg'))
    ? slides
    : demoSlides;

  const slider = (
    <div className='group relative min-w-0 overflow-hidden custom-swiper rounded-lg md:rounded-xl shadow-sm'>
      <Swiper
        slidesPerView={1}
        loop={items.length > 1}
        pagination={{ clickable: true }}
        navigation={arrows && items.length > 1 ? { prevEl: '.hero-prev', nextEl: '.hero-next' } : false}
        autoplay={autoplay && items.length > 1 ? { delay, disableOnInteraction: false } : false}
        modules={[Pagination, Autoplay, Navigation]}
        className='h-full'
      >
        {items.map((slide, index) => (
          <SwiperSlide key={`${slide.title}-${index}`}>
            <Slide slide={slide} embedded={embedded} />
          </SwiperSlide>
        ))}
      </Swiper>

      {arrows && items.length > 1 && (
        <>
          <button
            type='button'
            aria-label='Previous slide'
            className='hero-prev absolute left-3 top-1/2 z-20 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm opacity-0 transition hover:bg-black/70 group-hover:opacity-100'
          >
            <FiChevronLeft size={18} />
          </button>
          <button
            type='button'
            aria-label='Next slide'
            className='hero-next absolute right-3 top-1/2 z-20 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm opacity-0 transition hover:bg-black/70 group-hover:opacity-100'
          >
            <FiChevronRight size={18} />
          </button>
        </>
      )}
    </div>
  );

  if (embedded) return slider;

  return (
    <section className='w-full px-4 md:px-0 pt-3'>
      <div className='container mx-auto'>{slider}</div>
    </section>
  );
};

const Slide = ({ slide, embedded }) => {
  const isApple = slide.isApple || slide.eyebrow?.toLowerCase().includes('iphone') || slide.eyebrow?.toLowerCase().includes('apple');
  const bgImage = slide.image || bannerOne.src;

  return (
    <div
      className={`relative w-full bg-cover bg-center flex items-center ${embedded ? 'h-[320px] sm:h-[380px] lg:h-[445px]' : 'min-h-[320px] sm:min-h-[380px] lg:min-h-[445px]'
        }`}
      style={{ backgroundImage: `url(${bgImage})` }}
    >
      {/* Dark gradient overlay matching Image 1 */}
      <div
        className='absolute inset-0'
        style={{
          background: 'linear-gradient(90deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.55) 45%, rgba(0,0,0,0.1) 100%)'
        }}
      />

      <div className='relative z-10 flex flex-col justify-center px-6 sm:px-12 lg:px-14 py-8 sm:py-12 text-white w-full max-w-xl font-poppins'>
        {slide.eyebrow && (
          <div className='flex items-center gap-2 mb-3 text-xs sm:text-sm font-medium tracking-wide text-white/90'>
            {isApple && <FaApple size={20} className='shrink-0' />}
            <span>{slide.eyebrow}</span>
          </div>
        )}

        <h1 className='text-2xl sm:text-4xl lg:text-[44px] leading-tight font-bold text-white tracking-tight max-w-md'>
          {slide.title}
        </h1>

        {slide.description && (
          <p className='mt-3 text-xs sm:text-sm text-white/80 max-w-sm line-clamp-2'>
            {slide.description}
          </p>
        )}

        {/* Sleek Underlined Shop Now button matching Image 1 */}
        <div className='mt-5'>
          <Link
            href={slide.buttonUrl || '/products'}
            className='inline-flex items-center gap-2 text-white font-medium group transition-colors'
          >
            <span className='border-b-2 border-white pb-0.5 group-hover:border-orange-400 group-hover:text-orange-400 text-sm sm:text-base'>
              {slide.buttonLabel || 'Shop Now'}
            </span>
            <FiArrowRight size={17} className='group-hover:translate-x-1 transition-transform' />
          </Link>
        </div>
      </div>
    </div>
  );
};

export default Banner;
