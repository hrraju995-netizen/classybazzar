'use client';

import Link from 'next/link';
import { FiArrowRight, FiChevronLeft, FiChevronRight } from 'react-icons/fi';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';
import 'swiper/css';
import 'swiper/css/pagination';
import bannerOne from '../assets/images/banner.jpg';
import bannerTwo from '../assets/images/banner_2.jpg';
import bannerThree from '../assets/images/banner_3.jpg';

const demoSlides = [
  { image: bannerOne.src, eyebrow: 'Latest collection', title: 'Upgrade your everyday tech', description: 'Discover new arrivals and exclusive deals.', buttonLabel: 'Shop Now', buttonUrl: '/products' },
  { image: bannerTwo.src, eyebrow: 'Member offer', title: 'Big savings on customer favourites', description: 'Limited-time prices across popular categories.', buttonLabel: 'Explore Deals', buttonUrl: '/products?sort=best-selling' },
  { image: bannerThree.src, eyebrow: 'Fresh arrivals', title: 'New products, ready to discover', description: 'Shop the newest additions to Classybazar.', buttonLabel: 'View New Arrivals', buttonUrl: '/products?sort=latest' },
];

const Banner = ({ slides = [], autoplay = true, arrows = true, delay = 4500, embedded = false }) => {
  const items = slides.length ? slides : demoSlides;

  const slider = (
    <div className='group relative min-w-0 overflow-hidden custom-swiper rounded-2xl md:rounded-3xl'>
      <Swiper
        slidesPerView={1}
        loop={items.length > 1}
        pagination={{ clickable: true }}
        navigation={arrows && items.length > 1 ? { prevEl: '.hero-prev', nextEl: '.hero-next' } : false}
        autoplay={autoplay && items.length > 1 ? { delay, disableOnInteraction: false } : false}
        modules={[Pagination, Autoplay, Navigation]}
      >
        {items.map((slide, index) => (
          <SwiperSlide key={`${slide.title}-${index}`}>
            <Slide slide={slide} />
          </SwiperSlide>
        ))}
      </Swiper>

      {arrows && items.length > 1 && (
        <>
          <button
            type='button'
            aria-label='Previous slide'
            className='hero-prev absolute left-3 top-1/2 z-20 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm opacity-0 transition hover:bg-black/70 group-hover:opacity-100'
          >
            <FiChevronLeft size={20} />
          </button>
          <button
            type='button'
            aria-label='Next slide'
            className='hero-next absolute right-3 top-1/2 z-20 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm opacity-0 transition hover:bg-black/70 group-hover:opacity-100'
          >
            <FiChevronRight size={20} />
          </button>
        </>
      )}
    </div>
  );

  if (embedded) return slider;

  return (
    <section className='w-full px-3 md:px-4 pt-4'>
      <div className='container mx-auto'>{slider}</div>
    </section>
  );
};

const Slide = ({ slide }) => {
  const align = slide.textAlign || 'left';
  const opacity = typeof slide.overlayOpacity === 'number' ? slide.overlayOpacity / 100 : 0.75;

  const alignClasses =
    align === 'center'
      ? 'mx-auto text-center items-center'
      : align === 'right'
        ? 'ml-auto text-right items-end'
        : 'text-left items-start';

  const overlayGradient =
    align === 'center'
      ? `linear-gradient(180deg, rgba(0,0,0,${opacity * 0.75}) 0%, rgba(0,0,0,${opacity}) 100%)`
      : align === 'right'
        ? `linear-gradient(270deg, rgba(0,0,0,${opacity}) 0%, rgba(0,0,0,${opacity * 0.55}) 55%, rgba(0,0,0,0.08) 100%)`
        : `linear-gradient(90deg, rgba(0,0,0,${opacity}) 0%, rgba(0,0,0,${opacity * 0.55}) 55%, rgba(0,0,0,0.08) 100%)`;

  return (
    <div
      className='relative min-h-[320px] sm:min-h-[380px] lg:min-h-[470px] bg-cover bg-center flex items-center'
      style={{ backgroundImage: `url(${slide.image})` }}
    >
      <div className='absolute inset-0' style={{ background: overlayGradient }} />
      <div className={`relative z-10 flex flex-col max-w-2xl px-7 sm:px-12 lg:px-20 py-14 text-white w-full ${alignClasses}`}>
        {slide.eyebrow && (
          <p
            className='uppercase tracking-[0.22em] text-xs sm:text-sm font-semibold mb-4'
            style={{ color: slide.eyebrowColor || 'var(--color-primary)' }}
          >
            {slide.eyebrow}
          </p>
        )}
        <h1
          className='text-3xl sm:text-5xl lg:text-6xl leading-tight font-bold'
          style={{ color: slide.titleColor || '#ffffff' }}
        >
          {slide.title}
        </h1>
        {slide.description && (
          <p className='mt-5 text-sm sm:text-lg text-white/85 max-w-xl'>{slide.description}</p>
        )}
        {(slide.buttonLabel || slide.secondaryButtonLabel) && (
          <div className='mt-7 flex flex-wrap items-center gap-3'>
            {slide.buttonLabel && (
              <Link
                href={slide.buttonUrl || '/products'}
                className='inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold transition hover:brightness-95 shadow-lg'
                style={{
                  backgroundColor: slide.buttonColor || 'var(--color-button)',
                  color: slide.buttonTextColor || 'var(--color-button-text)',
                }}
              >
                {slide.buttonLabel}
                <FiArrowRight />
              </Link>
            )}
            {slide.secondaryButtonLabel && (
              <Link
                href={slide.secondaryButtonUrl || '/products'}
                className='inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold border border-white/40 bg-white/15 text-white backdrop-blur-sm transition hover:bg-white/25'
              >
                {slide.secondaryButtonLabel}
              </Link>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default Banner;
