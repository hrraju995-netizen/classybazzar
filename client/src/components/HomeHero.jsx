import CategorySidebar from './CategorySidebar';
import Banner from './Banner';

export default function HomeHero({ home = {} }) {
  if (home.sliderEnabled === false) {
    return null;
  }

  const bannerProps = {
    slides: home.slides || [],
    autoplay: home.sliderAutoplay !== false,
    arrows: home.sliderArrows !== false,
    delay: home.sliderDelay || 4500,
  };

  return (
    <section className='w-full px-0 lg:px-4 xl:px-0 pt-0 pb-4 md:pb-6'>
      <div className='container mx-auto lg:grid lg:grid-cols-[17rem_1fr] lg:gap-5 items-start'>
        {/* Left Column: All Categories Dropdown/Sidebar (matches Image 1) */}
        <aside className='hidden lg:block w-[17rem] bg-white border border-t-0 border-gray-200 rounded-b-md shadow-sm overflow-hidden'>
          <CategorySidebar />
        </aside>

        {/* Right Column: Hero Banner Slider (Full Width on mobile, matches Image 4) */}
        <div className='min-w-0 w-full pt-0 lg:pt-0'>
          <Banner {...bannerProps} embedded />
        </div>
      </div>
    </section>
  );
}
