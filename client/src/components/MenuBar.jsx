'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useEffect, useRef, useState } from 'react';
import { FiChevronDown, FiGrid, FiHeart, FiHome, FiMenu, FiPackage, FiUser, FiX } from 'react-icons/fi';
import { useDrawerStore } from '@/store/useDrawerStore';
import { useWishlistStore } from '@/store/useWishlistStore';
import MenuSection from './MenuSection';
import CategorySidebar from './CategorySidebar';

const MenuBar = ({ settings }) => {
  const pathname = usePathname();
  const drawerRef = useRef(null);
  const dropdownRef = useRef(null);
  const wishlist = useWishlistStore((state) => state.wishlist);
  const { isDrawerOpen, closeDrawer, toggleDrawer } = useDrawerStore();
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const menuItems = settings?.menus?.header || [];
  const isHomePage = pathname === '/';

  useEffect(() => {
    closeDrawer();
    setDropdownOpen(false);
  }, [pathname, closeDrawer]);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (drawerRef.current && !drawerRef.current.contains(event.target)) {
        closeDrawer();
      }
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setDropdownOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [closeDrawer]);

  return (
    <>
      <section className='hidden lg:block border-b border-gray-200 bg-white font-poppins'>
        <div className='container mx-auto px-4 md:px-0 flex items-center justify-between'>
          {/* Browse Categories Button */}
          <div ref={dropdownRef} className='w-[17rem] shrink-0 relative group'>
            <button
              type='button'
              onClick={() => !isHomePage && setDropdownOpen((prev) => !prev)}
              className='w-full h-12 px-4 flex items-center justify-between font-semibold rounded-t-md text-white transition-opacity select-none'
              style={{ backgroundColor: 'var(--color-primary, #eb6e1b)' }}
              aria-label='Browse Categories'
            >
              <div className='flex items-center gap-2.5'>
                <FiMenu size={20} />
                <span className='text-sm xl:text-base tracking-wide'>Browse Categories</span>
              </div>
              <FiChevronDown
                size={18}
                className={`transition-transform duration-200 ${!isHomePage && dropdownOpen ? 'rotate-180' : ''}`}
              />
            </button>

            {/* Dropdown for non-home pages */}
            {!isHomePage && (
              <div
                className={`absolute top-full left-0 w-full bg-white border border-t-0 border-gray-200 rounded-b-md shadow-xl z-50 transition-all ${
                  dropdownOpen ? 'block' : 'hidden group-hover:block'
                }`}
              >
                <CategorySidebar onSelect={() => setDropdownOpen(false)} />
              </div>
            )}
          </div>

          {/* Center Navigation Links */}
          <div className='flex-1 flex justify-center items-center px-6'>
            <MenuSection items={menuItems} />
          </div>

          {/* Right Button: Order Track */}
          <Link
            href='/order/ordertrack'
            className='px-5 py-2.5 rounded-md font-semibold text-sm text-white shadow-sm hover:opacity-90 transition'
            style={{ backgroundColor: 'var(--color-button, #eb6e1b)', color: 'var(--color-button-text, #fff)' }}
          >
            Order Track
          </Link>
        </div>
      </section>

      {/* Mobile Sidebar Drawer */}
      <div
        className={`fixed inset-0 bg-black/60 z-[99999] transition-opacity lg:hidden ${
          isDrawerOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'
        }`}
      >
        <aside
          ref={drawerRef}
          className={`fixed top-0 left-0 bottom-0 w-[300px] bg-white shadow-2xl transition-transform duration-300 ${
            isDrawerOpen ? 'translate-x-0' : '-translate-x-full'
          }`}
        >
          <div
            className='p-4 flex items-center justify-between text-white'
            style={{ backgroundColor: 'var(--color-primary, #eb6e1b)' }}
          >
            <div className='flex items-center gap-2 font-semibold'>
              <FiMenu size={20} />
              <span>Browse Categories</span>
            </div>
            <button onClick={closeDrawer} className='p-1.5 rounded-full hover:bg-white/20 transition-colors' aria-label='Close'>
              <FiX size={20} />
            </button>
          </div>
          <div className='overflow-y-auto h-[calc(100%-60px)]'>
            <CategorySidebar onSelect={closeDrawer} />
          </div>
        </aside>
      </div>

      {/* Mobile Bottom Navigation */}
      <nav
        className='lg:hidden fixed bottom-0 left-0 right-0 h-16 grid grid-cols-5 z-[9999] shadow-[0_-2px_10px_rgba(0,0,0,0.12)]'
        style={{ backgroundColor: 'var(--color-primary, #eb6e1b)', color: '#fff' }}
      >
        <MobileLink href='/' label='Home' active={pathname === '/'} icon={<FiHome size={21} />} />
        <MobileLink href='/wishlist' label='Wishlist' active={pathname === '/wishlist'} icon={<FiHeart size={21} />} badge={wishlist.length} />
        <button onClick={toggleDrawer} className='flex flex-col items-center justify-center gap-1 text-[10px]'>
          <FiGrid size={22} />
          <span>Categories</span>
        </button>
        <MobileLink href='/order/ordertrack' label='Track' active={pathname.includes('ordertrack')} icon={<FiPackage size={21} />} />
        <MobileLink href='/user/profile' label='Account' active={pathname.startsWith('/user')} icon={<FiUser size={21} />} />
      </nav>
    </>
  );
};

const MobileLink = ({ href, label, icon, active, badge }) => (
  <Link
    href={href}
    className='relative flex flex-col items-center justify-center gap-1 text-[10px]'
    style={{ color: active ? 'var(--color-secondary, #ffd166)' : '#fff' }}
  >
    {icon}
    <span>{label}</span>
    {badge > 0 && (
      <small
        className='absolute top-1 right-4 bg-white rounded-full min-w-4 h-4 px-1 text-[9px] flex items-center justify-center font-bold'
        style={{ color: 'var(--color-primary, #eb6e1b)' }}
      >
        {badge}
      </small>
    )}
  </Link>
);

export default MenuBar;
