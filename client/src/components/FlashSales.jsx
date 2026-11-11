'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { FiChevronLeft, FiChevronRight } from 'react-icons/fi';
import { Navigation } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';
import 'swiper/css';
import { getFlashSaleProducts } from '@/lib/api/products';
import Button from './Button';
import ProductCard from './ProductCard';
import SectionHeading from './SectionHeading';

const FlashSales = ({ config = {} }) => {
  const [products, setProducts] = useState([]);
  const limit = Number(config.limit) || 8;

  useEffect(() => {
    let active = true;
    getFlashSaleProducts(limit).then((data) => active && setProducts(data.products || [])).catch(() => active && setProducts([]));
    return () => { active = false; };
  }, [limit]);

  if (!products.length) return null;

  return (
    <section className='w-full py-12 md:py-16'>
      <div className='container mx-auto px-4 border-b pb-12'>
        <SectionHeading subHeading={config.eyebrow || 'Today'} heading={config.title || 'Flash Sales'} countDown navigationButtons={<div className='flex gap-2'><Circle className='flash-prev' icon={<FiChevronLeft />} /><Circle className='flash-next' icon={<FiChevronRight />} /></div>} />
        <div className='mt-8'>
          <Swiper modules={[Navigation]} navigation={{ prevEl: '.flash-prev', nextEl: '.flash-next' }} spaceBetween={18} breakpoints={{ 320: { slidesPerView: 2 }, 768: { slidesPerView: 3 }, 1024: { slidesPerView: 4 } }}>
            {products.slice(0, limit).map((product) => <SwiperSlide key={product.id}><ProductCard product={product} /></SwiperSlide>)}
          </Swiper>
        </div>
        <div className='text-center mt-8'><Button TagName={Link} href='/products'>View All Products</Button></div>
      </div>
    </section>
  );
};

const Circle = ({ className, icon }) => <button className={`${className} w-10 h-10 rounded-full flex items-center justify-center`} style={{ backgroundColor: 'var(--color-button)', color: 'var(--color-button-text)' }}>{icon}</button>;

export default FlashSales;
