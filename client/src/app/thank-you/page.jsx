import Link from 'next/link';
import { Check, PackageSearch, ShoppingBag } from 'lucide-react';
import ThankYouOrderSummary from '@/components/ThankYouOrderSummary';

export const metadata = {
  title: 'Thank You',
  description: 'Your Classybazar order has been received.',
  robots: { index: false, follow: false },
};

export default async function ThankYouPage({ searchParams }) {
  const params = await searchParams;
  const orderParam = Array.isArray(params?.order) ? params.order[0] : params?.order;
  const orderNumber = orderParam || '';

  return (
    <main className="min-h-[65vh] bg-gray-50 px-4 py-12 font-poppins md:py-20">
      <section className="mx-auto max-w-2xl rounded-2xl border border-gray-200 bg-white px-6 py-10 text-center shadow-sm md:px-10">
        <span className="mx-auto grid h-16 w-16 place-items-center rounded-full bg-green-100 text-green-700">
          <Check size={34} strokeWidth={2.5} />
        </span>
        <h1 className="mt-5 text-2xl font-bold text-gray-950 md:text-3xl">Thank you for your order!</h1>
        <p className="mx-auto mt-3 max-w-lg text-sm leading-6 text-gray-600">
          Your order has been placed successfully. You can download your PDF invoice below or use the Order ID to track delivery status.
        </p>

        {orderNumber ? (
          <div className="mx-auto mt-6 max-w-md rounded-xl border border-gray-200 bg-gray-50 py-4 px-5">
            <p className="text-xs font-medium uppercase tracking-wider text-gray-500">Order ID</p>
            <p className="mt-1 break-all font-mono text-lg font-bold text-gray-950">{orderNumber}</p>
          </div>
        ) : (
          <p className="mx-auto mt-7 max-w-md border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-left text-sm text-amber-800">
            No order ID was supplied. You can still track an existing order from the tracking page.
          </p>
        )}

        <ThankYouOrderSummary orderNumber={orderNumber} />

        <p className="mt-6 text-xs text-gray-500">Tracking also requires the phone number used during checkout.</p>

        <div className="mt-5 flex flex-col justify-center gap-3 sm:flex-row">
          <Link
            href={`/order/ordertrack${orderNumber ? `?order=${encodeURIComponent(orderNumber)}` : ''}`}
            className="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-[var(--color-button)] px-5 text-sm font-semibold text-white hover:bg-black"
          >
            <PackageSearch size={18} />
            Track order
          </Link>
          <Link
            href="/products"
            className="inline-flex h-11 items-center justify-center gap-2 rounded-md border border-gray-300 px-5 text-sm font-semibold text-gray-800 hover:border-black"
          >
            <ShoppingBag size={18} />
            Continue shopping
          </Link>
        </div>
      </section>
    </main>
  );
}
