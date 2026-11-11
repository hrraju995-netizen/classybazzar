import Image from 'next/image';
import Link from 'next/link';
import { getFlashSaleProducts } from '@/lib/api/products';
import CountdownTwo from './CountDownTwo';

const RadioExprience = async ({ config = {} }) => {
  const { products = [] } = await getFlashSaleProducts(1);
  const product = products[0];
  if (!product) return null;

  return (
    <section className='w-full px-4 md:px-0 py-10'>
      <div className='container mx-auto rounded-3xl overflow-hidden' style={{ backgroundColor: 'var(--color-secondary)' }}>
        <div className='py-12 px-7 md:p-16 grid md:grid-cols-2 gap-10 items-center'>
          <div className='text-center md:text-left text-white'>
            <p className='font-semibold uppercase tracking-wider' style={{ color: 'var(--color-primary)' }}>{config.eyebrow || 'Featured offer'}</p>
            <h2 className='text-3xl md:text-5xl leading-tight font-bold mt-4'>{config.title || product.title}</h2>
            <div className='my-7 flex justify-center md:justify-start'><CountdownTwo /></div>
            <Link href={`/products/${product.slug || product.id}`} className='inline-block px-8 py-3.5 rounded-xl font-semibold hover:brightness-90' style={{ backgroundColor: 'var(--color-button)', color: 'var(--color-button-text)' }}>Buy Now</Link>
          </div>
          <div className='relative min-h-[280px] md:min-h-[360px]'><Image src={product.thumbnail} fill alt={product.title} className='object-contain' /></div>
        </div>
      </div>
    </section>
  );
};

export default RadioExprience;
