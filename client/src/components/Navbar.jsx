'use client';

import Image from 'next/image';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useEffect, useRef, useState } from 'react';
import { FiHeart, FiMenu, FiSearch, FiShoppingCart, FiUser } from 'react-icons/fi';
import fallbackLogo from '../../public/navbarLogo.png';
import { searchProducts } from '@/lib/api/products';
import { getStoredUser, isAuthenticated } from '@/lib/api/auth';
import { useCartStore } from '@/store/useCartStore';
import { useDrawerStore } from '@/store/useDrawerStore';
import { useWishlistStore } from '@/store/useWishlistStore';

const Navbar = ({ settings }) => {
  const router = useRouter();
  const dropdownRef = useRef(null);
  const mobileDropdownRef = useRef(null);
  const cart = useCartStore((state) => state.cart);
  const openCart = useCartStore((state) => state.openCart);
  const wishlist = useWishlistStore((state) => state.wishlist);
  const openDrawer = useDrawerStore((state) => state.openDrawer);
  const [mounted, setMounted] = useState(false);
  const [currentUser, setCurrentUser] = useState(null);
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [open, setOpen] = useState(false);

  const header = settings?.header ?? {};
  const logo = settings?.site?.logo || fallbackLogo;
  const cartCount = mounted ? cart.reduce((sum, item) => sum + item.quantity, 0) : 0;
  const wishlistCount = mounted ? wishlist.length : 0;

  useEffect(() => {
    setMounted(true);
    const syncAuth = () => {
      setCurrentUser(isAuthenticated() ? getStoredUser() : null);
    };
    syncAuth();
    window.addEventListener('classybazar-auth-change', syncAuth);
    window.addEventListener('storage', syncAuth);
    return () => {
      window.removeEventListener('classybazar-auth-change', syncAuth);
      window.removeEventListener('storage', syncAuth);
    };
  }, []);

  useEffect(() => {
    if (query.trim().length < 2) {
      setResults([]);
      setOpen(false);
      return;
    }

    let active = true;
    setLoading(true);
    setOpen(true);
    searchProducts(query, { limit: 6 })
      .then((data) => active && setResults((data.products || []).slice(0, 6)))
      .catch(() => active && setResults([]))
      .finally(() => active && setLoading(false));
    return () => { active = false; };
  }, [query]);

  useEffect(() => {
    const close = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target) &&
          mobileDropdownRef.current && !mobileDropdownRef.current.contains(event.target)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', close);
    return () => document.removeEventListener('mousedown', close);
  }, []);

  const submit = (event) => {
    event.preventDefault();
    if (!query.trim()) return;
    setOpen(false);
    router.push(`/products?q=${encodeURIComponent(query.trim())}`);
  };

  return (
    <nav
      className={`${header.sticky === false ? '' : 'sticky top-0'} w-full z-[99] border-b shadow-sm py-2.5 md:py-4`}
      style={{ backgroundColor: 'var(--color-header-bg)', color: 'var(--color-header-text)' }}
    >
      <div className='container mx-auto px-4 md:px-0 flex items-center justify-between'>
        {/* Left: Mobile Drawer Trigger + Logo on desktop */}
        <div className='flex items-center gap-2 sm:gap-3 w-auto lg:w-72 shrink-0'>
          <button onClick={openDrawer} className='lg:hidden p-1.5 text-gray-800' aria-label='Open categories'>
            <FiMenu size={24} />
          </button>

          <Link href='/' className='shrink-0 hidden sm:flex items-center'>
            <Image
              src={logo}
              height={72}
              width={180}
              alt={settings?.site?.name || 'Classy Bazar'}
              className='h-10 md:h-12 w-auto object-contain'
              priority
            />
          </Link>
        </div>

        {/* Mobile Centered Logo */}
        <div className='sm:hidden flex-1 flex justify-center items-center px-2'>
          <Link href='/' className='flex items-center'>
            <Image
              src={logo}
              height={64}
              width={160}
              alt={settings?.site?.name || 'Classy Bazar'}
              className='h-9 w-auto object-contain'
              priority
            />
          </Link>
        </div>

        {/* Center: Search Bar (Centered on Desktop) */}
        {header.showSearch !== false && (
          <div className='flex-1 hidden sm:flex justify-center px-4'>
            <div ref={dropdownRef} className='relative w-full max-w-lg lg:max-w-xl xl:max-w-2xl'>
              <form onSubmit={submit} className='relative w-full'>
                <input
                  value={query}
                  onChange={(event) => setQuery(event.target.value)}
                  onFocus={() => query.trim().length > 1 && setOpen(true)}
                  placeholder='Search for product...'
                  className='w-full bg-[#f5f5f5] text-gray-800 text-sm pl-5 pr-14 py-3 rounded-2xl outline-none border border-gray-200/80 focus:border-gray-300 focus:bg-white transition-all placeholder:text-gray-400 font-normal'
                />
                <button
                  type='submit'
                  className='absolute right-1.5 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full flex items-center justify-center text-white transition hover:opacity-95 shadow-xs cursor-pointer'
                  style={{ backgroundColor: 'var(--color-button, #eb6e1b)', color: '#ffffff' }}
                  aria-label='Search'
                >
                  <FiSearch size={18} className='stroke-[2.2]' />
                </button>
              </form>
              {open && (
                <div className='absolute left-0 right-0 top-full mt-2 bg-white text-gray-900 border border-gray-200 rounded-xl shadow-2xl z-[999] overflow-hidden'>
                  {loading && <p className='p-5 text-sm text-gray-500'>Searching...</p>}
                  {!loading && results.map((product) => (
                    <Link key={product.id} href={`/products/${product.slug || product.id}`} onClick={() => setOpen(false)} className='flex items-center gap-3 p-3 hover:bg-gray-50 border-b last:border-0'>
                      <Image src={product.thumbnail} width={48} height={48} alt={product.title} className='w-12 h-12 object-contain bg-gray-100 rounded-lg' />
                      <span className='min-w-0'><strong className='text-sm block truncate'>{product.title}</strong><small style={{ color: 'var(--color-primary)' }}>BDT {product.price}</small></span>
                    </Link>
                  ))}
                  {!loading && !results.length && <p className='p-5 text-sm text-gray-500'>No products found.</p>}
                </div>
              )}
            </div>
          </div>
        )}

        {/* Right: Actions (Wishlist, Cart, Profile) */}
        <div className='flex items-center justify-end gap-4 sm:gap-5 md:gap-7 w-auto lg:w-72 shrink-0 mr-3 sm:mr-0'>
          <Link href='/wishlist' className='relative hidden sm:flex items-center justify-center text-gray-800 hover:text-[var(--color-primary)] transition-colors' aria-label='Wishlist'>
            <FiHeart size={24} className='stroke-[1.8]' />
            <CountBadge count={wishlistCount} />
          </Link>
          <button onClick={openCart} className='relative flex items-center justify-center text-gray-800 hover:text-[var(--color-primary)] transition-colors cursor-pointer' aria-label='Shopping cart'>
            <FiShoppingCart size={22} className='stroke-[1.8]' />
            <CountBadge count={cartCount} />
          </button>
          <Link
            href={mounted && currentUser ? '/user/profile' : '/login'}
            className='hidden sm:flex w-10 h-10 rounded-full border border-gray-300 hover:border-gray-900 items-center justify-center text-gray-700 hover:text-black transition-all'
            aria-label='Account'
            title={mounted && currentUser ? `Profile: ${currentUser.name}` : 'Sign In / Profile'}
          >
            <FiUser size={20} className='stroke-[1.8]' />
          </Link>
        </div>
      </div>

      {/* Row 2 on Mobile: Full-Width Search Bar */}
      {header.showSearch !== false && (
        <div ref={mobileDropdownRef} className='sm:hidden px-4 pt-2.5 pb-0.5 relative'>
          <form onSubmit={submit} className='relative w-full'>
            <input
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              onFocus={() => query.trim().length > 1 && setOpen(true)}
              placeholder='Search for product...'
              className='w-full bg-[#f5f5f5] text-gray-800 text-sm pl-4 pr-12 py-2.5 rounded-2xl outline-none border border-gray-200/80 focus:border-gray-300 focus:bg-white transition-all placeholder:text-gray-400 font-normal'
            />
            <button
              type='submit'
              className='absolute right-1.5 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full flex items-center justify-center text-white transition hover:opacity-95 shadow-xs cursor-pointer'
              style={{ backgroundColor: 'var(--color-button, #eb6e1b)', color: '#ffffff' }}
              aria-label='Search'
            >
              <FiSearch size={16} className='stroke-[2.2]' />
            </button>
          </form>
          {open && (
            <div className='absolute left-4 right-4 top-full mt-1.5 bg-white text-gray-900 border border-gray-200 rounded-xl shadow-2xl z-[999] overflow-hidden'>
              {loading && <p className='p-4 text-sm text-gray-500'>Searching...</p>}
              {!loading && results.map((product) => (
                <Link key={product.id} href={`/products/${product.slug || product.id}`} onClick={() => setOpen(false)} className='flex items-center gap-3 p-3 hover:bg-gray-50 border-b last:border-0'>
                  <Image src={product.thumbnail} width={44} height={44} alt={product.title} className='w-11 h-11 object-contain bg-gray-100 rounded-lg' />
                  <span className='min-w-0'><strong className='text-sm block truncate'>{product.title}</strong><small style={{ color: 'var(--color-primary)' }}>BDT {product.price}</small></span>
                </Link>
              ))}
              {!loading && !results.length && <p className='p-4 text-sm text-gray-500'>No products found.</p>}
            </div>
          )}
        </div>
      )}
    </nav>
  );
};

const CountBadge = ({ count }) => (
  <span
    className='absolute -top-1.5 -right-2 text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center text-white select-none'
    style={{ backgroundColor: 'var(--color-button, #eb6e1b)' }}
  >
    {count}
  </span>
);

export default Navbar;
