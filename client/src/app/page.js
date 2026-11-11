import BestSelling from '@/components/BestSelling';
import Category from '@/components/Category';
import Featured from '@/components/Featured';
import FlashSales from '@/components/FlashSales';
import OurProduct from '@/components/OurProduct';
import OurSupport from '@/components/OurSupport';
import RadioExprience from '@/components/RadioExprience';
import HomeHero from '@/components/HomeHero';
import { getSettingsSafe } from '@/lib/api/misc';

const fallbackSections = [
  { type: 'flash_sales', enabled: true, eyebrow: 'Today', title: 'Flash Sales', limit: 8 },
  { type: 'categories', enabled: true, eyebrow: 'Categories', title: 'Browse By Category', limit: 12 },
  { type: 'best_selling', enabled: true, eyebrow: 'This Month', title: 'Best Selling Products', limit: 4 },
  { type: 'promo', enabled: true, eyebrow: 'Featured offer', title: '', limit: 1 },
  { type: 'products', enabled: true, eyebrow: 'Our Products', title: 'Explore Our Products', limit: 8 },
  { type: 'featured', enabled: true, eyebrow: 'Featured', title: 'New Arrival', limit: 4 },
  { type: 'support', enabled: true, limit: 3 },
];

export default async function Home() {
  const settings = await getSettingsSafe();
  const home = settings.home ?? {};
  const sections = home.sections?.length ? home.sections : fallbackSections;

  return (
    <main>
      <HomeHero home={home} />
      {sections.filter((section) => section.enabled !== false).map((section, index) => (
        <HomepageSection key={`${section.type}-${index}`} section={section} home={home} />
      ))}
    </main>
  );
}

const HomepageSection = ({ section, home }) => {
  switch (section.type) {
    case 'flash_sales': return <FlashSales config={section} />;
    case 'categories': return <Category config={section} categoryIds={home.categoryIds || []} />;
    case 'best_selling': return <BestSelling config={section} />;
    case 'promo': return <RadioExprience config={section} />;
    case 'products': return <OurProduct config={section} />;
    case 'featured': return <Featured config={section} />;
    case 'support': return <OurSupport items={home.supportItems || []} limit={section.limit || 3} />;
    default: return null;
  }
};
