'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

const fallbackLinks = [
  { label: 'Home', url: '/' },
  { label: 'All Products', url: '/products' },
  { label: 'Contact', url: '/contact' },
  { label: 'About', url: '/about' },
];

const MenuSection = ({ items = [] }) => {
  const pathname = usePathname();
  const links = items.length ? items : fallbackLinks;

  return (
    <ul className='hidden lg:flex items-center gap-8 xl:gap-10 font-poppins'>
      {links.map((item) => {
        const href = item.url || '/';
        const active = pathname === href;
        return (
          <li key={`${item.label}-${href}`} className='text-base leading-6 relative py-1'>
            <Link
              href={href}
              target={item.newTab ? '_blank' : undefined}
              className='transition-opacity hover:opacity-70 block'
              style={{ color: 'var(--color-header-text)', fontWeight: active ? 600 : 400 }}
            >
              {item.label}
              {active && <span className='absolute bottom-0 left-0 w-full h-0.5 rounded-full' style={{ backgroundColor: 'var(--color-primary)' }} />}
            </Link>
          </li>
        );
      })}
    </ul>
  );
};

export default MenuSection;
