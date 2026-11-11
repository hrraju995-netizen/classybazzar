import NewArrivalsProducts from './NewArrivalsProducts';
import SectionHeading from './SectionHeading';

const Featured = ({ config = {} }) => (
  <section className='w-full py-10 md:py-16'>
    <div className='container mx-auto px-4 md:px-0'>
      <SectionHeading subHeading={config.eyebrow || 'Featured'} heading={config.title || 'New Arrival'} />
      <div className='mt-8'><NewArrivalsProducts limit={config.limit || 4} /></div>
    </div>
  </section>
);

export default Featured;
