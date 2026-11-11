'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

const defaultLinks = [
  { label: 'Home', url: '/' },
  { label: 'All Products', url: '/products' },
  { label: 'Contact', url: '/contact' },
  { label: 'About', url: '/about' },
  { label: 'Sign Up', url: '/signup' },
];

const MenuSection = ({ items = [] }) => {
  const pathname = usePathname();
  // Ensure we display Home, All Products, Contact, About, Sign Up like Image 1
  const links = items.length ? items : defaultLinks;
  const hasSignUp = links.some((l) => l.url === '/signup' || l.label?.toLowerCase() === 'sign up');
  const finalLinks = hasSignUp ? links : [...links, { label: 'Sign Up', url: '/signup' }];

  return (
    <ul className='hidden lg:flex items-center gap-7 xl:gap-9 font-poppins'>
      {finalLinks.map((item) => {
        const href = item.url || '/';
        const active = pathname === href;
        return (
          <li key={`${item.label}-${href}`} className='text-sm xl:text-base leading-6 relative py-1'>
            <Link
              href={href}
              target={item.newTab ? '_blank' : undefined}
              className={`transition-colors block py-0.5 ${active ? 'text-black font-semibold border-b-2 border-black' : 'text-gray-700 hover:text-black font-normal'}`}
            >
              {item.label}
            </Link>
          </li>
        );
      })}
    </ul>
  );
};

export default MenuSection;
