'use client';

import Link from 'next/link';
import { FiChevronDown } from 'react-icons/fi';

const Topbar = ({ settings }) => {
  const header = settings?.header ?? {};
  if (header.showTopbar === false) return null;

  const topbarText = header.topbarText || 'Summer Sale For All Swim Suits And Free Express Delivery - OFF 50%!';
  const linkLabel = header.topbarLinkLabel || 'ShopNow';
  const linkUrl = header.topbarLinkUrl || '/products';

  return (
    <section
      className='w-full px-3 sm:px-4 py-2 font-poppins text-xs md:text-sm bg-black text-white'
    >
      <div className='container mx-auto relative flex items-center justify-center text-center'>
        <div className='flex items-center justify-center text-center gap-1.5 leading-tight text-[11px] sm:text-xs md:text-sm px-1 max-w-full'>
          <span className='text-gray-200 truncate sm:overflow-visible'>{topbarText}</span>
          <Link href={linkUrl} className='font-semibold underline text-white hover:text-orange-400 whitespace-nowrap shrink-0 transition-colors'>
            {linkLabel}
          </Link>
        </div>

        <div className='hidden sm:flex items-center gap-1 absolute right-0 text-xs font-normal text-gray-200 cursor-pointer select-none hover:text-white'>
          <span>English</span>
          <FiChevronDown size={14} />
        </div>
      </div>
    </section>
  );
};

export default Topbar;
