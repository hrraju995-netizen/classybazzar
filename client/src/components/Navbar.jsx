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
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) setOpen(false);
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
      className={`${header.sticky === false ? '' : 'sticky top-0'} w-full z-[99] border-b shadow-sm py-3 md:py-4`}
      style={{ backgroundColor: 'var(--color-header-bg)', color: 'var(--color-header-text)' }}
    >
      <div className='container mx-auto px-4 md:px-0 flex items-center gap-4 md:gap-8'>
        <button onClick={openDrawer} className='lg:hidden p-2' aria-label='Open categories'>
          <FiMenu size={25} />
        </button>

        <Link href='/' className='shrink-0'>
          <Image src={logo} height={72} width={180} alt={settings?.site?.name || 'Store logo'} className='h-10 md:h-12 w-auto object-contain' priority />
        </Link>

        {header.showSearch !== false && (
          <div ref={dropdownRef} className='relative flex-1 max-w-xl xl:max-w-2xl hidden sm:block mx-2 md:mx-6'>
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
        )}

        <div className='ml-auto flex items-center gap-5 md:gap-7'>
          {header.showSearch !== false && (
            <button
              className='sm:hidden p-2 text-gray-800'
              aria-label='Search'
              onClick={() => router.push('/products')}
            >
              <FiSearch size={22} />
            </button>
          )}
          <Link href='/wishlist' className='relative hidden sm:flex items-center justify-center text-gray-800 hover:text-[var(--color-primary)] transition-colors' aria-label='Wishlist'>
            <FiHeart size={24} className='stroke-[1.8]' />
            <CountBadge count={wishlistCount} />
          </Link>
          <button onClick={openCart} className='relative flex items-center justify-center text-gray-800 hover:text-[var(--color-primary)] transition-colors cursor-pointer' aria-label='Shopping cart'>
            <FiShoppingCart size={24} className='stroke-[1.8]' />
            <CountBadge count={cartCount} />
          </button>
          <Link
            href={mounted && currentUser ? '/user/profile' : '/login'}
            className='w-10 h-10 rounded-full border border-gray-300 hover:border-gray-900 flex items-center justify-center text-gray-700 hover:text-black transition-all'
            aria-label='Account'
            title={mounted && currentUser ? `Profile: ${currentUser.name}` : 'Sign In / Profile'}
          >
            <FiUser size={20} className='stroke-[1.8]' />
          </Link>
        </div>
      </div>
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
