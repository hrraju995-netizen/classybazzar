'use client';

import Link from 'next/link';

const Topbar = ({ settings }) => {
  const header = settings?.header ?? {};
  if (header.showTopbar === false || !header.topbarText) return null;

  return (
    <section
      className='w-full px-3 py-2 md:py-3 font-poppins'
      style={{ backgroundColor: 'var(--color-topbar-bg)', color: 'var(--color-topbar-text)' }}
    >
      <div className='container flex items-center justify-center text-center text-xs md:text-sm tracking-wide leading-5'>
        <span>{header.topbarText}</span>
        {header.topbarLinkLabel && header.topbarLinkUrl && (
          <Link href={header.topbarLinkUrl} className='font-semibold underline ml-2 hover:opacity-80'>
            {header.topbarLinkLabel}
          </Link>
        )}
      </div>
    </section>
  );
};

export default Topbar;
