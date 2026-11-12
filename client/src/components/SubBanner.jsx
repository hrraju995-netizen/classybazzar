import Link from 'next/link';
import { MdOutlineKeyboardArrowRight } from 'react-icons/md';
import subBanner from '../assets/images/subBanner.jpg';

const SubBanner = ({ title, pageName }) => {
  return (
    <section
      style={{ backgroundImage: `url(${subBanner.src})` }}
      className="w-full bg-cover bg-center bg-no-repeat relative flex items-center justify-center border-b border-gray-100 font-poppins py-4 sm:py-8 md:py-12"
    >
      <div className="absolute inset-0 bg-white/60 backdrop-blur-[2px]"></div>
      <div className="relative z-10 text-center space-y-1 sm:space-y-2 px-4 w-full max-w-xl mx-auto">
        <h1 className="text-lg sm:text-2xl md:text-3xl font-bold text-black tracking-wide font-poppins capitalize leading-tight">
          {title}
        </h1>
        <div className="flex items-center justify-center flex-wrap gap-1 sm:gap-2 text-[11px] sm:text-xs md:text-sm font-medium">
          <Link href="/" className="text-gray-600 hover:text-[#DB4444] transition-colors duration-200">
            Home
          </Link>
          <MdOutlineKeyboardArrowRight size={14} className="text-gray-400 sm:size-[16px]" />
          <span className="text-black capitalize font-semibold truncate max-w-[150px] sm:max-w-xs">
            {pageName || title}
          </span>
        </div>
      </div>
    </section>
  );
};

export default SubBanner;