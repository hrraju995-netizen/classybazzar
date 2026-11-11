import Image from 'next/image';
import Link from 'next/link';
import { FiGrid } from 'react-icons/fi';

const CategoryCard = ({ category }) => (
  <Link href={`/products?category=${category.slug}`} className='group flex flex-col items-center justify-center border border-gray-200 rounded-xl p-4 md:p-6 w-full h-32 md:h-40 bg-white transition-all hover:-translate-y-1 hover:shadow-lg'>
    <div className='w-14 h-14 md:w-16 md:h-16 rounded-full flex items-center justify-center overflow-hidden mb-3 bg-gray-100' style={{ color: 'var(--color-primary)' }}>
      {category.image ? <Image src={category.image} width={72} height={72} alt={category.name} className='w-full h-full object-cover' /> : <FiGrid size={28} />}
    </div>
    <p className='font-poppins text-sm md:text-base font-medium text-gray-900 text-center group-hover:opacity-75'>{category.name}</p>
    {category.productsCount !== undefined && <small className='text-gray-400 mt-1'>{category.productsCount} products</small>}
  </Link>
);

export default CategoryCard;
