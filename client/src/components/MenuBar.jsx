'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useEffect, useRef, useState } from 'react';
import { FiGrid, FiHeart, FiHome, FiPackage, FiUser, FiX } from 'react-icons/fi';
import { getCategories } from '@/lib/api/products';
import { useDrawerStore } from '@/store/useDrawerStore';
import { useWishlistStore } from '@/store/useWishlistStore';
import MenuSection from './MenuSection';

const MenuBar = ({ settings }) => {
  const pathname = usePathname();
  const drawerRef = useRef(null);
  const wishlist = useWishlistStore((state) => state.wishlist);
  const { isDrawerOpen, closeDrawer, toggleDrawer } = useDrawerStore();
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const menuItems = settings?.menus?.header || [];
  const hasFixedHomeCategories = pathname === '/' && settings?.home?.fixedCategorySidebar === true;

  useEffect(() => {
    let active = true;
    getCategories()
      .then((data) => active && setCategories((data || []).slice(0, 14)))
      .catch(() => active && setCategories([]))
      .finally(() => active && setLoading(false));
    return () => { active = false; };
  }, []);

  useEffect(() => closeDrawer(), [pathname, closeDrawer]);

  useEffect(() => {
    const close = (event) => {
      if (drawerRef.current && !drawerRef.current.contains(event.target)) closeDrawer();
    };
    if (isDrawerOpen) document.addEventListener('mousedown', close);
    return () => document.removeEventListener('mousedown', close);
  }, [isDrawerOpen, closeDrawer]);

  return (
    <>
      <section className='hidden lg:block border-b shadow-sm' style={{ backgroundColor: 'var(--color-menubar-bg, var(--color-header-bg))', color: 'var(--color-menubar-text, var(--color-header-text))' }}>
        <div className='container mx-auto flex items-stretch'>
          <div className='w-64 group relative shrink-0'>
            <button className='w-full h-full min-h-12 px-5 flex items-center gap-2 font-semibold' style={{ backgroundColor: 'var(--color-primary)', color: '#fff' }}>
              <FiGrid /> Browse Categories
            </button>
            <div className={`absolute top-full left-0 w-full bg-white text-gray-800 border shadow-xl z-[100] max-h-[430px] overflow-y-auto ${hasFixedHomeCategories ? 'hidden' : 'hidden group-hover:block'}`}>
              {loading && <p className='p-4 text-sm'>Loading categories...</p>}
              {categories.map((category) => (
                <Link key={category.slug} href={`/products?category=${category.slug}`} className='flex justify-between px-4 py-3 text-sm border-b hover:bg-gray-50'>
                  <span>{category.name}</span><span className='text-gray-400'>{category.productsCount ?? ''}</span>
                </Link>
              ))}
            </div>
          </div>
          <div className='flex-1 flex justify-center items-center px-6'><MenuSection items={menuItems} /></div>
          <Link href='/order/ordertrack' className='self-center px-5 py-2 rounded-lg font-semibold text-sm mr-1' style={{ backgroundColor: 'var(--color-button)', color: 'var(--color-button-text)' }}>
            Order Track
          </Link>
        </div>
      </section>

      <div className={`fixed inset-0 bg-black/60 z-[99999] transition-opacity lg:hidden ${isDrawerOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'}`}>
        <aside ref={drawerRef} className={`fixed top-0 left-0 bottom-0 w-[300px] bg-white shadow-2xl transition-transform ${isDrawerOpen ? 'translate-x-0' : '-translate-x-full'}`}>
          <div className='p-5 flex items-center justify-between border-b'>
            <strong>Browse Categories</strong>
            <button onClick={closeDrawer} className='p-2 rounded-full bg-gray-100'><FiX /></button>
          </div>
          <div className='p-3 overflow-y-auto h-[calc(100%-74px)]'>
            {loading && <p className='p-4 text-sm'>Loading categories...</p>}
            {categories.map((category) => (
              <Link key={category.slug} href={`/products?category=${category.slug}`} className='flex items-center justify-between p-3 rounded-xl hover:bg-gray-100'>
                <span>{category.name}</span><small>{category.productsCount ?? ''}</small>
              </Link>
            ))}
          </div>
        </aside>
      </div>

      <nav className='lg:hidden fixed bottom-0 left-0 right-0 h-16 grid grid-cols-5 z-[9999] shadow-[0_-2px_10px_rgba(0,0,0,0.12)]' style={{ backgroundColor: 'var(--color-primary)', color: '#fff' }}>
        <MobileLink href='/' label='Home' active={pathname === '/'} icon={<FiHome size={21} />} />
        <MobileLink href='/wishlist' label='Wishlist' active={pathname === '/wishlist'} icon={<FiHeart size={21} />} badge={wishlist.length} />
        <button onClick={toggleDrawer} className='flex flex-col items-center justify-center gap-1 text-[10px]'><FiGrid size={22} /><span>Categories</span></button>
        <MobileLink href='/order/ordertrack' label='Track' active={pathname.includes('ordertrack')} icon={<FiPackage size={21} />} />
        <MobileLink href='/user/profile' label='Account' active={pathname.startsWith('/user')} icon={<FiUser size={21} />} />
      </nav>
    </>
  );
};

const MobileLink = ({ href, label, icon, active, badge }) => (
  <Link href={href} className='relative flex flex-col items-center justify-center gap-1 text-[10px]' style={{ color: active ? 'var(--color-secondary)' : '#fff' }}>
    {icon}<span>{label}</span>
    {badge > 0 && <small className='absolute top-1 right-4 bg-white rounded-full min-w-4 h-4 px-1 text-[9px]' style={{ color: 'var(--color-primary)' }}>{badge}</small>}
  </Link>
);

export default MenuBar;
