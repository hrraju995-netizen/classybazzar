import Link from 'next/link';
import { getFeaturedProducts } from '@/lib/api/products';
import Button from './Button';
import ProductCard from './ProductCard';
import SectionHeading from './SectionHeading';

const OurProducts = async ({ config = {} }) => {
  const limit = Number(config.limit) || 8;
  const { products = [] } = await getFeaturedProducts(limit);
  if (!products.length) return null;

  return (
    <section className='w-full py-10 md:py-16'>
      <div className='container mx-auto px-4 md:px-0 border-b pb-12'>
        <SectionHeading subHeading={config.eyebrow || 'Our Products'} heading={config.title || 'Explore Our Products'} />
        <div className='mt-8 grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-8'>
          {products.slice(0, limit).map((product) => <ProductCard key={product.id} product={product} />)}
        </div>
        <div className='text-center mt-10'><Button TagName={Link} href='/products'>View All Products</Button></div>
      </div>
    </section>
  );
};

export default OurProducts;
