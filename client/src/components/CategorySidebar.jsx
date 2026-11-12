'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { 
  FiChevronRight, 
  FiCoffee, 
  FiCompass, 
  FiDroplet, 
  FiFeather, 
  FiGrid, 
  FiHome, 
  FiLayers, 
  FiShoppingBag, 
  FiSmartphone, 
  FiTag, 
  FiTv, 
  FiWatch 
} from 'react-icons/fi';
import { getCategories } from '@/lib/api/products';

const defaultCategories = [
  { name: 'Beauty', slug: 'beauty', icon: 'beauty' },
  { name: 'Fragrances', slug: 'fragrances', icon: 'fragrance' },
  { name: 'Furniture', slug: 'furniture', icon: 'furniture' },
  { name: 'Groceries', slug: 'groceries', icon: 'groceries' },
  { name: 'Home Decoration', slug: 'home-decoration', icon: 'home' },
  { name: 'Kitchen Accessories', slug: 'kitchen-accessories', icon: 'kitchen' },
  { name: 'Laptops', slug: 'laptops', icon: 'laptop' },
  { name: 'Mens Shirts', slug: 'mens-shirts', icon: 'shirt' },
  { name: 'Mens Shoes', slug: 'mens-shoes', icon: 'shoes' },
  { name: 'Mens Watches', slug: 'mens-watches', icon: 'watch' },
  { name: 'Mobile Accessories', slug: 'mobile-accessories', icon: 'mobile' },
];

export function getCategoryIcon(name = '', slug = '') {
  const text = `${name} ${slug}`.toLowerCase();
  if (text.includes('beauty') || text.includes('cosmetic') || text.includes('personal')) return <FiFeather size={15} />;
  if (text.includes('fragrance') || text.includes('perfume') || text.includes('scent')) return <FiDroplet size={15} />;
  if (text.includes('furniture') || text.includes('chair') || text.includes('sofa')) return <FiLayers size={15} />;
  if (text.includes('grocer') || text.includes('food')) return <FiShoppingBag size={15} />;
  if (text.includes('home') || text.includes('decor')) return <FiHome size={15} />;
  if (text.includes('kitchen') || text.includes('cook') || text.includes('pot')) return <FiCoffee size={15} />;
  if (text.includes('laptop') || text.includes('computer') || text.includes('electronic')) return <FiTv size={15} />;
  if (text.includes('shirt') || text.includes('fashion') || text.includes('cloth') || text.includes('men')) return <FiTag size={15} />;
  if (text.includes('shoe') || text.includes('footwear')) return <FiCompass size={15} />;
  if (text.includes('watch') || text.includes('clock')) return <FiWatch size={15} />;
  if (text.includes('mobile') || text.includes('phone') || text.includes('gadget')) return <FiSmartphone size={15} />;
  return <FiGrid size={15} />;
}

export default function CategorySidebar({ limit = 11, onSelect }) {
  const [categories, setCategories] = useState(defaultCategories);

  useEffect(() => {
    let active = true;
    getCategories()
      .then((data) => {
        if (!active) return;
        if (Array.isArray(data) && data.length > 0) {
          setCategories(data.slice(0, limit));
        } else {
          setCategories(defaultCategories);
        }
      })
      .catch(() => {
        if (active) setCategories(defaultCategories);
      });
    return () => { active = false; };
  }, [limit]);

  return (
    <ul className='flex flex-col w-full py-1 bg-white font-poppins'>
      {categories.map((category, index) => (
        <li key={category.slug || index}>
          <Link
            href={`/products?category=${category.slug}`}
            onClick={onSelect}
            className='flex items-center justify-between px-4 py-2.5 text-sm text-gray-700 hover:text-[var(--color-primary,#eb6e1b)] hover:bg-orange-50/50 transition-colors border-b border-gray-100 last:border-b-0 group'
          >
            <div className='flex items-center gap-3 min-w-0'>
              <span className='text-gray-400 group-hover:text-[var(--color-primary,#eb6e1b)] transition-colors shrink-0'>
                {getCategoryIcon(category.name, category.slug)}
              </span>
              <span className='truncate font-normal'>{category.name}</span>
            </div>
            <FiChevronRight size={13} className='text-gray-300 group-hover:text-[var(--color-primary,#eb6e1b)] group-hover:translate-x-0.5 transition-all shrink-0' />
          </Link>
        </li>
      ))}
    </ul>
  );
}

