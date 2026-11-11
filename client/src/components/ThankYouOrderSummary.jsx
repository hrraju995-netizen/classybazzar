'use client';

import { useEffect, useState } from 'react';
import { Download, FileText, Printer } from 'lucide-react';
import { getOrderInvoicePdfUrl } from '@/lib/api/orders';

export default function ThankYouOrderSummary({ orderNumber }) {
  const [order, setOrder] = useState(null);

  useEffect(() => {
    if (typeof window === 'undefined' || !orderNumber) return;
    try {
      const raw = window.sessionStorage.getItem('classybazar_last_order');
      if (!raw) return;
      const parsed = JSON.parse(raw);
      const id = parsed?.orderNumber || parsed?.id;
      if (id && id.toLowerCase() === orderNumber.toLowerCase()) {
        setOrder(parsed);
      }
    } catch {
      // Ignore parse errors
    }
  }, [orderNumber]);

  if (!orderNumber) return null;

  const downloadUrl = getOrderInvoicePdfUrl(orderNumber, {
    inline: false,
    token: order?.invoiceToken || '',
  });
  const previewUrl = getOrderInvoicePdfUrl(orderNumber, {
    inline: true,
    token: order?.invoiceToken || '',
  });

  return (
    <div className="mt-6 space-y-6 text-left">
      {/* PDF Download Callout Box */}
      <div className="rounded-xl border border-orange-200 bg-orange-50/70 p-5">
        <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
          <div className="flex items-center gap-3 text-center sm:text-left">
            <span className="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-[var(--color-button)] text-white shadow-sm">
              <FileText size={22} />
            </span>
            <div>
              <h2 className="text-sm font-bold text-gray-900">Order Invoice Ready (PDF)</h2>
              <p className="text-xs text-gray-600">
                Download or print your official order invoice &amp; receipt for <span className="font-mono font-semibold">{orderNumber}</span>.
              </p>
            </div>
          </div>

          <div className="flex flex-wrap items-center justify-center gap-2.5">
            <a
              href={downloadUrl}
              download={`Invoice-${orderNumber}.pdf`}
              className="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[var(--color-button)] px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-black"
            >
              <Download size={16} />
              Download PDF
            </a>
            <a
              href={previewUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 text-xs font-semibold text-gray-800 transition hover:border-black"
            >
              <Printer size={16} />
              View / Print
            </a>
          </div>
        </div>
      </div>

      {/* Detailed Order Summary when available from checkout */}
      {order && (
        <div className="rounded-xl border border-gray-200 bg-gray-50/60 p-5 text-sm">
          <div className="grid gap-4 border-b border-gray-200 pb-4 sm:grid-cols-2">
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">Customer Details</p>
              <p className="mt-1 font-semibold text-gray-900">{order.customer?.name}</p>
              <p className="text-xs text-gray-600">{order.customer?.phone}</p>
              <p className="mt-1 text-xs text-gray-600">{order.customer?.address}</p>
            </div>
            <div className="sm:text-right">
              <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">Order Info</p>
              <p className="mt-1 text-xs text-gray-600">
                Date: <span className="font-medium text-gray-900">{order.date || 'Today'}</span>
              </p>
              <p className="text-xs text-gray-600">
                Payment: <span className="font-medium uppercase text-gray-900">{order.paymentMethod || 'COD'}</span>
              </p>
              <p className="text-xs text-gray-600">
                Status: <span className="font-semibold text-emerald-700">{order.status || 'Order Placed'}</span>
              </p>
            </div>
          </div>

          {Array.isArray(order.items) && order.items.length > 0 && (
            <div className="divide-y divide-gray-200 border-b border-gray-200 py-2">
              {order.items.map((item) => (
                <div key={item.id} className="flex items-center justify-between gap-3 py-2 text-xs">
                  <div className="min-w-0">
                    <p className="truncate font-medium text-gray-900">
                      {item.title} <span className="text-gray-500">× {item.quantity}</span>
                    </p>
                    {item.variantName && <p className="text-[11px] text-gray-500">{item.variantName}</p>}
                  </div>
                  <span className="shrink-0 font-semibold text-gray-900">
                    BDT {Number(item.subtotal || item.price * item.quantity).toFixed(2)}
                  </span>
                </div>
              ))}
            </div>
          )}

          {order.totals && (
            <div className="mt-3 space-y-1.5 text-xs">
              <div className="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span>BDT {Number(order.totals.subtotal || 0).toFixed(2)}</span>
              </div>
              <div className="flex justify-between text-gray-600">
                <span>Delivery Charge</span>
                <span>BDT {Number(order.totals.shipping || 0).toFixed(2)}</span>
              </div>
              {Number(order.totals.discount || 0) > 0 && (
                <div className="flex justify-between text-emerald-700">
                  <span>Discount</span>
                  <span>- BDT {Number(order.totals.discount).toFixed(2)}</span>
                </div>
              )}
              <div className="flex justify-between border-t border-gray-200 pt-2 text-sm font-bold text-gray-950">
                <span>Total Amount</span>
                <span>BDT {Number(order.totals.total || 0).toFixed(2)}</span>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

