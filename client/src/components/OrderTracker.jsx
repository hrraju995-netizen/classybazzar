'use client';

import { useState } from 'react';
import Link from 'next/link';
import {
  ArrowLeft,
  CalendarDays,
  Check,
  Clock3,
  Download,
  MapPin,
  PackageSearch,
  Phone,
  Printer,
  Search,
  Truck,
} from 'lucide-react';
import { getOrderInvoicePdfUrl, trackOrder } from '@/lib/api/orders';

const normalizePhone = (value) => value.replace(/\D/g, '').replace(/^88(?=01)/, '');

export default function OrderTracker({ initialOrderNumber = '' }) {
  const [orderNumber, setOrderNumber] = useState(initialOrderNumber);
  const [phone, setPhone] = useState('');
  const [orderData, setOrderData] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSearch = async (event) => {
    event.preventDefault();
    const cleanOrderNumber = orderNumber.trim();
    const cleanPhone = normalizePhone(phone);

    if (!cleanOrderNumber || !cleanPhone) {
      setError('Enter both your order ID and the phone number used at checkout.');
      setOrderData(null);
      return;
    }

    setLoading(true);
    setError('');

    try {
      const result = await trackOrder(cleanOrderNumber, cleanPhone);
      setOrderData(result);
    } catch (requestError) {
      setOrderData(null);
      setError(requestError?.message || 'Could not find this order. Please check the details and try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="min-h-[70vh] bg-gray-50 px-4 py-8 font-poppins md:py-16">
      <div className="container mx-auto max-w-xl">
        {/* Back to Shop + Live Tracking buttons (strictly inline on one row) */}
        <div className="mb-6 flex items-center justify-between gap-2">
          <Link
            href="/products"
            className="inline-flex items-center gap-1.5 text-gray-600 hover:text-[var(--color-primary)] border border-gray-200 bg-white rounded-xl px-3.5 py-2 sm:px-5 sm:py-2.5 shadow-xs transition-colors whitespace-nowrap shrink-0"
          >
            <ArrowLeft size={15} />
            <span className="uppercase text-[11px] sm:text-xs font-bold tracking-wider">Back to Shop</span>
          </Link>
          <div
            className="inline-flex items-center gap-1.5 text-white bg-[#0f172a] rounded-xl px-3.5 py-2 sm:px-5 sm:py-2.5 shadow-sm whitespace-nowrap shrink-0"
          >
            <Truck size={15} style={{ color: 'var(--color-button, #eb6e1b)' }} />
            <span className="text-[11px] sm:text-xs font-bold tracking-wide">Live Tracking</span>
          </div>
        </div>

        {/* Main Tracker Card */}
        <section className="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-8 sm:px-8 sm:py-10">
          <div className="text-center mb-7">
            <PackageSearch className="mx-auto mb-3 text-[var(--color-primary)]" size={38} />
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-950">
              Track Your <span className="underline decoration-2 underline-offset-4" style={{ color: 'var(--color-primary, #eb6e1b)' }}>Order</span>
            </h1>
            <p className="mt-2 text-sm text-gray-500 max-w-xs mx-auto">
              Enter your order ID and checkout phone number below to check live status.
            </p>
          </div>

          <form onSubmit={handleSearch} className="space-y-3.5">
            <div className="relative">
              <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
              <input
                type="text"
                value={orderNumber}
                onChange={(event) => setOrderNumber(event.target.value)}
                placeholder="Enter Order ID (e.g. CB-XXXXXXXX)"
                autoComplete="off"
                className="h-12 w-full rounded-xl border border-gray-200 bg-gray-50 pl-11 pr-4 text-sm outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-orange-100 transition-all"
              />
            </div>

            <div className="relative">
              <Phone className="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
              <input
                type="tel"
                value={phone}
                onChange={(event) => setPhone(event.target.value)}
                placeholder="Checkout Phone Number (01XXXXXXXXX)"
                autoComplete="tel"
                className="h-12 w-full rounded-xl border border-gray-200 bg-gray-50 pl-11 pr-4 text-sm outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-orange-100 transition-all"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full h-12 flex items-center justify-center gap-2 rounded-xl text-sm font-bold text-white transition-all hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 uppercase tracking-wider shadow-sm cursor-pointer"
              style={{ backgroundColor: 'var(--color-button, #eb6e1b)' }}
            >
              <Search size={17} />
              {loading ? 'Searching...' : 'SEARCH'}
            </button>
          </form>

          {error && (
            <p role="alert" className="mt-5 border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm text-red-700 rounded-r-lg">
              {error}
            </p>
          )}

          {!orderData && !error && (
            <div className="mt-8 text-center text-gray-300">
              <PackageSearch className="mx-auto mb-3" size={44} />
              <p className="text-xs sm:text-sm text-gray-400">
                Awaiting input telemetry data. Fill the tracker form above to visualize real-time package logistics.
              </p>
            </div>
          )}
        </section>

        {orderData && (
          <div className="mt-6 space-y-6">
            <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <OrderFact icon={PackageSearch} label="Order ID" value={orderData.id} />
              <OrderFact icon={Truck} label="Courier" value={orderData.carrier} />
              <OrderFact icon={CalendarDays} label="Estimated delivery" value={orderData.eta} />
              <OrderFact icon={MapPin} label="Current status" value={orderData.status} highlight />
            </section>

            <section className="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-7 sm:px-8">
              <div className="mb-7 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-5">
                <div>
                  <h2 className="text-lg font-bold text-gray-950">Order progress</h2>
                  <p className="mt-1 text-sm text-gray-500">Order placed on {orderData.date}</p>
                </div>
                <div className="flex flex-wrap items-center gap-2.5">
                  <span className="rounded-md bg-orange-50 px-3 py-1.5 text-xs font-semibold text-[#c95008]">{orderData.status}</span>
                  <a
                    href={getOrderInvoicePdfUrl(orderData.id, { inline: false, token: orderData.invoiceToken || '' })}
                    download={`Invoice-${orderData.id}.pdf`}
                    className="inline-flex h-9 items-center gap-1.5 rounded-md bg-[var(--color-button)] px-3.5 text-xs font-semibold text-white transition hover:bg-black"
                  >
                    <Download size={15} />
                    Download PDF
                  </a>
                  <a
                    href={getOrderInvoicePdfUrl(orderData.id, { inline: true, token: orderData.invoiceToken || '' })}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex h-9 items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:border-black"
                  >
                    <Printer size={15} />
                    Print
                  </a>
                </div>
              </div>

              <ol className="mx-auto max-w-2xl">
                {orderData.steps?.map((step, index) => (
                  <li key={`${step.title}-${index}`} className="relative grid grid-cols-[36px_1fr] gap-4 pb-8 last:pb-0">
                    {index < orderData.steps.length - 1 && (
                      <span className={`absolute left-[17px] top-9 h-[calc(100%-28px)] w-px ${step.completed ? 'bg-[var(--color-button)]' : 'bg-gray-200'}`} />
                    )}
                    <span className={`relative z-10 grid h-9 w-9 place-items-center rounded-full border-2 ${step.completed ? 'border-[var(--color-primary)] bg-[var(--color-button)] text-white' : 'border-gray-300 bg-white text-gray-400'}`}>
                      {step.completed ? <Check size={17} /> : <Clock3 size={15} />}
                    </span>
                    <div className="pt-0.5">
                      <div className="flex flex-wrap items-center justify-between gap-2">
                        <h3 className={`text-sm font-semibold ${step.current ? 'text-[#d85f12]' : 'text-gray-900'}`}>{step.title}</h3>
                        <span className="text-xs text-gray-400">{step.time}</span>
                      </div>
                      <p className="mt-1 text-sm text-gray-500">{step.desc}</p>
                    </div>
                  </li>
                ))}
              </ol>
            </section>
          </div>
        )}
      </div>
    </main>
  );
}

function OrderFact({ icon: Icon, label, value, highlight = false }) {
  return (
    <div className="min-w-0 bg-white rounded-xl border border-gray-100 shadow-xs p-4">
      <div className="mb-2 flex items-center gap-2 text-gray-500">
        <Icon size={16} />
        <span className="text-xs font-medium">{label}</span>
      </div>
      <p className={`truncate text-sm font-semibold ${highlight ? 'text-[#d85f12]' : 'text-gray-900'}`}>{value}</p>
    </div>
  );
}
