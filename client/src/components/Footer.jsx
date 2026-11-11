import Image from 'next/image';
import Link from 'next/link';
import { FaFacebookF, FaInstagram, FaLinkedinIn, FaTiktok, FaWhatsapp, FaYoutube } from 'react-icons/fa';
import fallbackLogo from '../../public/footerLogo.png';

const socialIcons = {
  facebook: FaFacebookF, instagram: FaInstagram, youtube: FaYoutube,
  tiktok: FaTiktok, linkedin: FaLinkedinIn, whatsapp: FaWhatsapp,
};

const Footer = ({ settings }) => {
  const site = settings?.site ?? {};
  const footer = settings?.footer ?? {};
  const contact = settings?.contact ?? {};
  const menus = settings?.menus ?? {};
  const logo = site.footerLogo || site.logo || fallbackLogo;

  return (
    <footer className='pt-12 md:pt-16 pb-24 md:pb-7' style={{ backgroundColor: 'var(--color-footer-bg)', color: 'var(--color-footer-text)' }}>
      <div className='container px-4 sm:px-6 md:px-0'>
        <div className='grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-8'>
          <div>
            <Image src={logo} width={220} height={90} alt={site.name || 'Store logo'} className='max-w-[210px] max-h-20 w-auto object-contain' />
            {footer.about && <p className='mt-5 text-sm leading-6 opacity-80'>{footer.about}</p>}
            <h3 className='font-semibold text-lg mt-6'>{footer.subscribeTitle || 'Subscribe'}</h3>
            <p className='text-sm mt-2 opacity-75'>{footer.subscribeText}</p>
            <form className='mt-4 flex border rounded-lg overflow-hidden border-current/40'>
              <input type='email' placeholder='Email address' className='min-w-0 flex-1 bg-transparent px-3 py-2.5 outline-none placeholder:opacity-60' />
              <button type='submit' className='px-4 font-semibold' style={{ backgroundColor: 'var(--color-button)', color: 'var(--color-button-text)' }}>Join</button>
            </form>
            <div className='flex items-center gap-3 mt-5'>
              {(footer.social || []).map((item) => {
                const Icon = socialIcons[item.platform];
                return Icon ? (
                  <a key={`${item.platform}-${item.url}`} href={item.url} target='_blank' rel='noreferrer' className='w-9 h-9 rounded-full border border-current/30 flex items-center justify-center hover:opacity-70' aria-label={item.platform}>
                    <Icon />
                  </a>
                ) : null;
              })}
            </div>
          </div>

          <FooterColumn title='Support' items={menus.footer_support}>
            {contact.address && <p className='text-sm leading-6 opacity-80'>{contact.address}</p>}
            {contact.email && <a className='block mt-3 text-sm hover:opacity-70' href={`mailto:${contact.email}`}>{contact.email}</a>}
            {contact.phone && <a className='block mt-3 text-sm hover:opacity-70' href={`tel:${contact.phone}`}>{contact.phone}</a>}
          </FooterColumn>
          <FooterColumn title='Account' items={menus.footer_account} />
          <FooterColumn title='Quick links' items={menus.footer_quick} />
        </div>
        <div className='mt-12 pt-6 border-t border-current/15 text-center text-sm opacity-65'>
          {footer.copyright || `© ${new Date().getFullYear()} ${site.name || 'Classybazar'}. All rights reserved.`}
        </div>
      </div>
    </footer>
  );
};

const FooterColumn = ({ title, items = [], children }) => (
  <div>
    <h3 className='font-semibold text-lg mb-5'>{title}</h3>
    {children}
    <ul className={children ? 'mt-5 space-y-3' : 'space-y-3'}>
      {(items || []).map((item) => (
        <li key={`${item.label}-${item.url}`}>
          <Link href={item.url || '/'} target={item.newTab ? '_blank' : undefined} className='text-sm opacity-80 hover:opacity-100 hover:underline'>
            {item.label}
          </Link>
        </li>
      ))}
    </ul>
  </div>
);

export default Footer;
