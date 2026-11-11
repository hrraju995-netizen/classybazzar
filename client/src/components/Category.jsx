'use client';

import { useEffect, useMemo, useState } from 'react';
import { FiChevronLeft, FiChevronRight } from 'react-icons/fi';
import { Navigation } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';
import 'swiper/css';
import 'swiper/css/navigation';
import { getCategories } from '@/lib/api/products';
import CategoryCard from './CategoryCard';
import SectionHeading from './SectionHeading';

const Category = ({ config = {}, categoryIds = [] }) => {
  const [categories, setCategories] = useState([]);

  useEffect(() => {
    let active = true;
    getCategories().then((data) => active && setCategories(data || [])).catch(() => active && setCategories([]));
    return () => { active = false; };
  }, []);

  const shown = useMemo(() => {
    const limit = Number(config.limit) || 12;
    if (!categoryIds.length) return categories.slice(0, limit);
    const byId = new Map(categories.map((item) => [Number(item.id), item]));
    return categoryIds.map((id) => byId.get(Number(id))).filter(Boolean).slice(0, limit);
  }, [categories, categoryIds, config.limit]);

  if (!shown.length) return null;

  return (
    <section className='w-full py-10 md:py-16 overflow-hidden'>
      <div className='container px-4 md:px-0'>
        <SectionHeading subHeading={config.eyebrow || 'Categories'} heading={config.title || 'Browse By Category'} />
        <div className='relative mt-8'>
          <Swiper modules={[Navigation]} navigation={{ prevEl: '.category-prev', nextEl: '.category-next' }} spaceBetween={18} breakpoints={{ 320: { slidesPerView: 2.1 }, 640: { slidesPerView: 3.2 }, 900: { slidesPerView: 5 }, 1200: { slidesPerView: 6 } }}>
            {shown.map((category) => <SwiperSlide key={category.id}><CategoryCard category={category} /></SwiperSlide>)}
          </Swiper>
          <NavButton className='category-prev left-1' icon={<FiChevronLeft />} />
          <NavButton className='category-next right-1' icon={<FiChevronRight />} />
        </div>
      </div>
    </section>
  );
};

const NavButton = ({ className, icon }) => (
  <button className={`${className} absolute top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full shadow-lg flex items-center justify-center`} style={{ backgroundColor: 'var(--color-button)', color: 'var(--color-button-text)' }}>
    {icon}
  </button>
);

export default Category;
