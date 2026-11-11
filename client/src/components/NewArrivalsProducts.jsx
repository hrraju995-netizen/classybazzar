import Image from 'next/image';
import Link from 'next/link';
import { getNewArrivals } from '@/lib/api/products';

const NewArrivalsProducts = async ({ limit = 4 }) => {
  const { products = [] } = await getNewArrivals(Math.max(Number(limit) || 4, 4));
  const items = products.slice(0, 4);
  if (!items.length) return null;

  return (
    <div className='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4'>
      {items.map((product, index) => (
        <Link key={product.id} href={`/products/${product.slug || product.id}`} className={`relative min-h-[280px] rounded-2xl overflow-hidden bg-gray-950 group ${index === 0 ? 'md:col-span-2 lg:row-span-2 lg:min-h-[580px]' : ''}`}>
          <Image src={product.thumbnail} fill alt={product.title} className='object-contain p-6 transition-transform duration-500 group-hover:scale-105' />
          <div className='absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent' />
          <div className='absolute left-0 right-0 bottom-0 p-6 text-white'>
            <h3 className='text-lg md:text-xl font-bold'>{product.title}</h3>
            <span className='inline-block mt-3 text-sm underline underline-offset-4' style={{ color: 'var(--color-primary)' }}>Shop Now</span>
          </div>
        </Link>
      ))}
    </div>
  );
};

export default NewArrivalsProducts;
