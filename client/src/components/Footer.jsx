'use client';

import { useState } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import {
  FaFacebookF,
  FaInstagram,
  FaLinkedinIn,
  FaTiktok,
  FaTwitter,
  FaWhatsapp,
  FaYoutube,
} from 'react-icons/fa';
import { IoSendOutline } from 'react-icons/io5';
import { toast } from 'react-toastify';
import fallbackLogo from '../../public/footerLogo.png';

const socialIcons = {
  facebook: FaFacebookF,
  twitter: FaTwitter,
  x: FaTwitter,
  instagram: FaInstagram,
  youtube: FaYoutube,
  tiktok: FaTiktok,
  linkedin: FaLinkedinIn,
  whatsapp: FaWhatsapp,
};

const defaultAccountLinks = [
  { label: 'My Account', url: '/user/profile' },
  { label: 'Login / Register', url: '/login' },
  { label: 'Cart', url: '/cart' },
  { label: 'Wishlist', url: '/wishlist' },
  { label: 'Shop', url: '/products' },
];

const defaultQuickLinks = [
  { label: 'Privacy Policy', url: '/privacy-policy' },
  { label: 'Terms Of Use', url: '/terms' },
  { label: 'FAQ', url: '/faq' },
  { label: 'Contact', url: '/contact' },
];

const Footer = ({ settings }) => {
  const [email, setEmail] = useState('');
  const site = settings?.site ?? {};
  const footer = settings?.footer ?? {};
  const contact = settings?.contact ?? {};
  const menus = settings?.menus ?? {};
  const logo = site.footerLogo || fallbackLogo;

  const accountItems =
    menus.account?.length > 0
      ? menus.account
      : menus.footer_account?.length > 0
      ? menus.footer_account
      : defaultAccountLinks;

  const quickItems =
    menus.quick?.length > 0
      ? menus.quick
      : menus.footer_quick?.length > 0
      ? menus.footer_quick
      : defaultQuickLinks;

  const handleSubscribe = (e) => {
    e.preventDefault();
    if (!email.trim()) return;
    toast.success('Thank you for subscribing!', { position: 'top-center', autoClose: 2500 });
    setEmail('');
  };

  return (
    <footer
      className='pt-12 md:pt-16 pb-24 lg:pb-7 font-poppins'
      style={{
        backgroundColor: 'var(--color-footer-bg, #000000)',
        color: 'var(--color-footer-text, #ffffff)',
      }}
    >
      <div className='container mx-auto px-4 sm:px-6 md:px-0'>
        <div className='grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-12'>
          {/* Column 1: Brand, Subscribe & Social */}
          <div>
            <Link href='/' className='inline-block'>
              <Image
                src={logo}
                width={200}
                height={70}
                alt={site.name || 'Classy Bazar'}
                className='max-w-[180px] max-h-14 w-auto object-contain'
              />
            </Link>
            <h3 className='font-medium text-lg mt-5 text-white'>
              {footer.subscribeTitle || 'Subscribe'}
            </h3>
            <p className='text-sm mt-3 text-gray-200'>
              {footer.subscribeText || 'Get 10% off your first order'}
            </p>
            <form
              onSubmit={handleSubscribe}
              className='mt-4 flex items-center border border-white/80 rounded px-3.5 py-2.5 max-w-[230px] bg-transparent focus-within:border-[var(--color-primary,#eb6e1b)] transition-colors'
            >
              <input
                type='email'
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder='enter your email'
                required
                className='min-w-0 flex-1 bg-transparent text-sm text-white outline-none placeholder:text-gray-400'
              />
              <button
                type='submit'
                aria-label='Subscribe'
                className='ml-2 text-white hover:text-[var(--color-primary,#eb6e1b)] transition-colors cursor-pointer shrink-0 flex items-center justify-center'
              >
                <IoSendOutline size={18} />
              </button>
            </form>
            <div className='flex items-center gap-6 mt-6 text-white'>
              {(footer.social || []).map((item) => {
                const Icon = socialIcons[item.platform?.toLowerCase()];
                return Icon ? (
                  <a
                    key={`${item.platform}-${item.url}`}
                    href={item.url}
                    target='_blank'
                    rel='noreferrer'
                    className='text-base hover:text-[var(--color-primary,#eb6e1b)] transition-colors'
                    aria-label={item.platform}
                  >
                    <Icon />
                  </a>
                ) : null;
              })}
            </div>
          </div>

          {/* Column 2: Support */}
          <div>
            <h3 className='font-medium text-lg mb-5 text-white'>Support</h3>
            <div className='space-y-4 text-sm text-gray-200'>
              <p className='leading-relaxed'>
                {contact.address || 'Gulshan-2, Dhaka, Bangladesh'}
              </p>
              {contact.email ? (
                <a
                  className='block hover:text-white transition-colors'
                  href={`mailto:${contact.email}`}
                >
                  {contact.email}
                </a>
              ) : (
                <a
                  className='block hover:text-white transition-colors'
                  href='mailto:classybazar@gmail.com'
                >
                  classybazar@gmail.com
                </a>
              )}
              {contact.phone ? (
                <a
                  className='block hover:text-white transition-colors'
                  href={`tel:${contact.phone}`}
                >
                  {contact.phone}
                </a>
              ) : (
                <a
                  className='block hover:text-white transition-colors'
                  href='tel:01925909902'
                >
                  01925909902
                </a>
              )}
            </div>
          </div>

          {/* Column 3: Account */}
          <FooterColumn title='Account' items={accountItems} />

          {/* Column 4: Quick Link */}
          <FooterColumn title='Quick Link' items={quickItems} />
        </div>

        {/* Bottom Copyright */}
        <div className='mt-14 pt-6 border-t border-white/10 text-center text-sm text-gray-500'>
          &copy; Copyright{' '}
          <span
            className='font-medium'
            style={{ color: 'var(--color-primary, #eb6e1b)' }}
          >
            {site.name || 'Classy Bazar'}
          </span>{' '}
          {new Date().getFullYear()}. All rights reserved.
        </div>
      </div>
    </footer>
  );
};

const FooterColumn = ({ title, items = [] }) => (
  <div>
    <h3 className='font-medium text-lg mb-5 text-white'>{title}</h3>
    <ul className='space-y-3.5'>
      {(items || []).map((item) => (
        <li key={`${item.label}-${item.url}`}>
          <Link
            href={item.url || '/'}
            target={item.newTab ? '_blank' : undefined}
            className='text-sm text-gray-200 hover:text-white hover:underline transition-colors'
          >
            {item.label}
          </Link>
        </li>
      ))}
    </ul>
  </div>
);

export default Footer;
