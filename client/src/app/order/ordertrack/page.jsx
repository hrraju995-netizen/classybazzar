import OrderTracker from '@/components/OrderTracker';

export const metadata = {
  title: 'Track Order',
  description: 'Check the latest status of your Classybazar order.',
};

export default async function OrderTrackPage({ searchParams }) {
  const params = await searchParams;
  const orderParam = Array.isArray(params?.order) ? params.order[0] : params?.order;

  return <OrderTracker initialOrderNumber={orderParam || ''} />;
}
