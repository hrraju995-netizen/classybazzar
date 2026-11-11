'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { getBestSellingProducts } from '@/lib/api/products';
import Button from './Button';
import ProductCard from './ProductCard';
import SectionHeading from './SectionHeading';

const BestSelling = ({ config = {} }) => {
  const [products, setProducts] = useState([]);
  const limit = Number(config.limit) || 4;

  useEffect(() => {
    let active = true;
    getBestSellingProducts(limit).then((data) => active && setProducts(data.products || [])).catch(() => active && setProducts([]));
    return () => { active = false; };
  }, [limit]);

  if (!products.length) return null;

  return (
    <section className='w-full py-10 md:py-16'>
      <div className='container px-4 md:px-0'>
        <div className='flex flex-col md:flex-row md:items-end justify-between gap-4'>
          <SectionHeading subHeading={config.eyebrow || 'This Month'} heading={config.title || 'Best Selling Products'} />
          <div className='shrink-0'><Button TagName={Link} href='/products?sort=best-selling'>View All</Button></div>
        </div>
        <div className='grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-8 mt-8'>
          {products.slice(0, limit).map((product) => <ProductCard key={product.id} product={product} />)}
        </div>
      </div>
    </section>
  );
};

export default BestSelling;
