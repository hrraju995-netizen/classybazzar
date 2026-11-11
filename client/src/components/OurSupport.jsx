import { FiHeadphones, FiRefreshCcw, FiTruck } from 'react-icons/fi';

const icons = [FiTruck, FiHeadphones, FiRefreshCcw];

const OurSupport = ({ items = [], limit = 3 }) => {
  const shown = items.slice(0, Number(limit) || 3);
  if (!shown.length) return null;

  return (
    <section className='w-full py-12 md:py-20'>
      <div className='container mx-auto px-4 md:px-0 grid sm:grid-cols-2 lg:grid-cols-3 gap-8'>
        {shown.map((item, index) => {
          const Icon = icons[index % icons.length];
          return (
            <div key={`${item.title}-${index}`} className='flex flex-col items-center text-center p-7 rounded-2xl bg-white border shadow-sm'>
              <div className='w-20 h-20 rounded-full flex items-center justify-center border-8 border-gray-200' style={{ backgroundColor: 'var(--color-primary)', color: '#fff' }}><Icon size={30} /></div>
              <h3 className='font-semibold text-lg mt-5'>{item.title}</h3>
              <p className='text-sm text-gray-600 mt-2'>{item.description}</p>
            </div>
          );
        })}
      </div>
    </section>
  );
};

export default OurSupport;
