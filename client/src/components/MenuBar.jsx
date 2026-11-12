'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useEffect, useRef, useState } from 'react';
import { 
  FiChevronDown, 
  FiChevronRight, 
  FiGrid, 
  FiHeart, 
  FiHelpCircle, 
  FiHome, 
  FiInfo, 
  FiMenu, 
  FiPackage, 
  FiTruck, 
  FiUser, 
  FiX 
} from 'react-icons/fi';
import { useDrawerStore } from '@/store/useDrawerStore';
import { useWishlistStore } from '@/store/useWishlistStore';
import { getCategories } from '@/lib/api/products';
import { getStoredUser } from '@/lib/api/auth';
import { useHydrated } from '@/hooks/useHydrated';
import MenuSection from './MenuSection';
import CategorySidebar, { getCategoryIcon } from './CategorySidebar';

const fallbackCategories = [
  { name: 'Beauty', slug: 'beauty' },
  { name: 'Fragrances', slug: 'fragrances' },
  { name: 'Furniture', slug: 'furniture' },
  { name: 'Groceries', slug: 'groceries' },
  { name: 'Home Decoration', slug: 'home-decoration' },
  { name: 'Kitchen Accessories', slug: 'kitchen-accessories' },
  { name: 'Laptops', slug: 'laptops' },
  { name: 'Mens Shirts', slug: 'mens-shirts' },
  { name: 'Mens Shoes', slug: 'mens-shoes' },
  { name: 'Mens Watches', slug: 'mens-watches' },
  { name: 'Mobile Accessories', slug: 'mobile-accessories' },
];

const MenuBar = ({ settings }) => {
  const pathname = usePathname();
  const drawerRef = useRef(null);
  const dropdownRef = useRef(null);
  const wishlist = useWishlistStore((state) => state.wishlist);
  const { isDrawerOpen, closeDrawer, toggleDrawer } = useDrawerStore();
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [categories, setCategories] = useState(fallbackCategories);
  const [user, setUser] = useState(null);
  const isMounted = useHydrated();

  const menuItems = settings?.menus?.header || [];
  const isHomePage = pathname === '/';

  useEffect(() => {
    closeDrawer();
    setDropdownOpen(false);
  }, [pathname, closeDrawer]);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      setUser(getStoredUser());
    }
    getCategories()
      .then((data) => {
        if (Array.isArray(data) && data.length > 0) {
          setCategories(data.slice(0, 12));
        }
      })
      .catch(() => {});
  }, []);

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
      {/* Desktop Menu Bar */}
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

      {/* Mobile Side Menu Drawer (Matches Image 1 & 5) */}
      <div
        className={`fixed inset-0 bg-black/60 backdrop-blur-[2px] z-[99999] transition-opacity duration-300 lg:hidden ${
          isDrawerOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'
        }`}
      >
        <aside
          ref={drawerRef}
          className={`fixed top-0 left-0 bottom-0 w-[84vw] max-w-[320px] sm:max-w-[340px] bg-[#F7F8FA] shadow-2xl transition-transform duration-300 ease-out overflow-y-auto p-4 flex flex-col gap-3.5 pb-24 ${
            isDrawerOpen ? 'translate-x-0' : '-translate-x-full'
          }`}
        >
          {/* Top Header Card + Close Button (Matches Image 1 & 5) */}
          <div className='flex items-center gap-2.5'>
            <div
              className='flex-1 rounded-2xl p-4 flex items-center gap-3.5 text-white shadow-sm'
              style={{ backgroundColor: 'var(--color-primary, #eb6e1b)' }}
            >
              <div className='w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-white shrink-0'>
                <FiUser size={24} className='stroke-[2]' />
              </div>
              <div className='min-w-0'>
                <h3 className='font-bold text-base leading-tight truncate text-white'>
                  {isMounted && user ? user.name : 'Hello there!'}
                </h3>
                <Link
                  href={isMounted && user ? '/user/profile' : '/login'}
                  onClick={closeDrawer}
                  className='text-white/95 hover:text-white text-xs underline font-medium mt-0.5 block'
                >
                  {isMounted && user ? 'My Account' : 'Signin'}
                </Link>
              </div>
            </div>

            <button
              onClick={closeDrawer}
              className='w-10 h-10 rounded-full bg-white shadow-xs border border-gray-200/80 flex items-center justify-center text-gray-500 hover:text-black transition-colors shrink-0 cursor-pointer'
              aria-label='Close drawer'
            >
              <FiX size={20} />
            </button>
          </div>

          {/* Categories Rounded Card (Matches Image 1 & 5) */}
          <div className='bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden divide-y divide-gray-50'>
            {categories.map((cat, index) => (
              <Link
                key={cat.slug || index}
                href={`/products?category=${cat.slug}`}
                onClick={closeDrawer}
                className='flex items-center justify-between px-4 py-3 hover:bg-orange-50/40 transition-colors group'
              >
                <div className='flex items-center gap-3.5 min-w-0'>
                  <span className='text-gray-400 group-hover:text-[var(--color-primary,#eb6e1b)] transition-colors shrink-0'>
                    {getCategoryIcon(cat.name, cat.slug)}
                  </span>
                  <span className='text-sm font-medium text-gray-700 group-hover:text-gray-900 truncate'>
                    {cat.name}
                  </span>
                </div>
                <FiChevronRight
                  size={15}
                  className='text-gray-300 group-hover:text-[var(--color-primary,#eb6e1b)] group-hover:translate-x-0.5 transition-all shrink-0'
                />
              </Link>
            ))}
          </div>

          {/* Quick Links Section (Matches Image 1 & 5) */}
          <div className='flex flex-col gap-1.5 pt-1'>
            <span className='text-[11px] font-semibold text-gray-400 uppercase tracking-wider px-2 font-mono'>
              QUICK LINKS
            </span>
            <div className='bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden divide-y divide-gray-50'>
              <Link
                href='/about'
                onClick={closeDrawer}
                className='flex items-center gap-3.5 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-[var(--color-primary,#eb6e1b)] transition-colors'
              >
                <FiInfo size={17} className='text-gray-400 shrink-0' />
                <span>About Us</span>
              </Link>
              <Link
                href='/wishlist'
                onClick={closeDrawer}
                className='flex items-center gap-3.5 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-[var(--color-primary,#eb6e1b)] transition-colors'
              >
                <FiHeart size={17} className='text-gray-400 shrink-0' />
                <span>Wishlists</span>
              </Link>
              <Link
                href='/contact'
                onClick={closeDrawer}
                className='flex items-center gap-3.5 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-[var(--color-primary,#eb6e1b)] transition-colors'
              >
                <FiHelpCircle size={17} className='text-gray-400 shrink-0' />
                <span>Faqs</span>
              </Link>
            </div>
          </div>
        </aside>
      </div>

      {/* Mobile Bottom Navigation (Matches Image 1, 2, 4, 6, 7, 8, 9) */}
      <nav
        className='lg:hidden fixed bottom-0 left-0 right-0 h-16 grid grid-cols-5 z-[9999] shadow-[0_-2px_10px_rgba(0,0,0,0.12)] select-none'
        style={{ backgroundColor: 'var(--color-primary, #eb6e1b)', color: '#fff' }}
      >
        <MobileLink href='/' label='HOME' active={pathname === '/'} icon={<FiHome size={20} />} />
        <MobileLink href='/wishlist' label='WISHLIST' active={pathname === '/wishlist'} icon={<FiHeart size={20} />} badge={wishlist.length} />
        <button
          onClick={toggleDrawer}
          className='flex flex-col items-center justify-center gap-1 text-[9px] font-bold tracking-wider uppercase cursor-pointer hover:opacity-90'
        >
          <FiGrid size={21} />
          <span>CATEGORIES</span>
        </button>
        <MobileLink href='/order/ordertrack' label='ORDER TRACK' active={pathname.includes('ordertrack')} icon={<FiTruck size={21} />} />
        <MobileLink href='/user/profile' label='ACCOUNT' active={pathname.startsWith('/user')} icon={<FiUser size={20} />} />
      </nav>
    </>
  );
};

const MobileLink = ({ href, label, icon, active, badge }) => (
  <Link
    href={href}
    className='relative flex flex-col items-center justify-center gap-1 text-[9px] font-bold tracking-wider uppercase'
    style={{ color: active ? 'var(--color-secondary, #ffd166)' : '#fff' }}
  >
    {icon}
    <span>{label}</span>
    {badge > 0 && (
      <small
        className='absolute top-1 right-3 sm:right-4 bg-white rounded-full min-w-4 h-4 px-1 text-[9px] flex items-center justify-center font-bold shadow-xs'
        style={{ color: 'var(--color-primary, #eb6e1b)' }}
      >
        {badge}
      </small>
    )}
  </Link>
);

export default MenuBar;
