import Link from 'next/link';
import { FiGrid } from 'react-icons/fi';
import { getCategories } from '@/lib/api/products';
import Banner from './Banner';

export default async function HomeHero({ home = {} }) {
  if (home.sliderEnabled === false) {
    return null;
  }

  const bannerProps = {
    slides: home.slides || [],
    autoplay: home.sliderAutoplay !== false,
    arrows: home.sliderArrows !== false,
    delay: home.sliderDelay || 4500,
  };

  if (!home.fixedCategorySidebar) {
    return <Banner {...bannerProps} />;
  }

  let categories = [];

  try {
    categories = await getCategories();
  } catch {
    categories = [];
  }

  return (
    <section className='w-full px-3 pt-4 md:px-4'>
      <div className='container mx-auto lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-4'>
        <aside className='hidden min-h-[470px] max-h-[470px] overflow-y-auto rounded-2xl border border-gray-200 bg-white text-gray-800 shadow-sm lg:block'>
          {categories.length > 0 ? categories.slice(0, 14).map((category) => (
            <Link
              key={category.slug}
              href={'/products?category=' + category.slug}
              className='flex min-h-11 items-center justify-between border-b border-gray-100 px-4 py-2.5 text-sm transition-colors last:border-b-0 hover:bg-orange-50 hover:text-[var(--color-primary)]'
            >
              <span className='min-w-0 truncate'>{category.name}</span>
              <span className='ml-3 text-xs text-gray-400'>{category.productsCount ?? ''}</span>
            </Link>
          )) : (
            <div className='flex h-full flex-col items-center justify-center p-6 text-center text-gray-400'>
              <FiGrid size={28} />
              <p className='mt-2 text-sm'>No categories available</p>
            </div>
          )}
        </aside>

        <Banner {...bannerProps} embedded />
      </div>
    </section>
  );
}
