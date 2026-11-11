'use client';

import React, { useState, useEffect, useCallback } from 'react';
import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { toast } from 'react-toastify';
import {
  FiLogOut,
  FiUser,
  FiShoppingBag,
  FiPhone,
  FiMail,
  FiMapPin,
  FiDownload,
  FiPrinter,
  FiLock,
  FiCheckCircle,
  FiXCircle,
} from 'react-icons/fi';
import { FcGoogle } from 'react-icons/fc';
import {
  changePassword,
  getStoredUser,
  isAuthenticated,
  logout,
  me,
  updateProfile,
} from '@/lib/api/auth';
import { cancelOrder, getMyOrders, getOrderInvoicePdfUrl } from '@/lib/api/orders';

const UserProfileDashboard = () => {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [mounted, setMounted] = useState(false);
  const [loadingProfile, setLoadingProfile] = useState(true);
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingPassword, setSavingPassword] = useState(false);
  const [user, setUser] = useState(null);
  const [orders, setOrders] = useState([]);
  const [loadingOrders, setLoadingOrders] = useState(false);

  const [activeMenu, setActiveMenu] = useState('My Profile');

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    city: '',
    address: '',
  });

  const [passwordData, setPasswordData] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  });

  const populateForm = useCallback((u) => {
    if (!u) return;
    setFormData({
      name: u.name || '',
      email: u.email || '',
      phone: u.phone || '',
      city: u.city || '',
      address: u.address || '',
    });
  }, []);

  const loadOrders = useCallback(async () => {
    if (!isAuthenticated()) return;
    setLoadingOrders(true);
    try {
      const res = await getMyOrders(1);
      setOrders(res?.orders || []);
    } catch {
      // Ignore if unauthorized
    } finally {
      setLoadingOrders(false);
    }
  }, []);

  useEffect(() => {
    setMounted(true);
    const cached = getStoredUser();
    if (cached) {
      setUser(cached);
      populateForm(cached);
    }

    if (!isAuthenticated()) {
      setLoadingProfile(false);
      return;
    }

    me()
      .then((freshUser) => {
        setUser(freshUser);
        populateForm(freshUser);
      })
      .catch(() => {
        setUser(null);
      })
      .finally(() => {
        setLoadingProfile(false);
      });

    loadOrders();
  }, [populateForm, loadOrders]);

  useEffect(() => {
    const tab = searchParams.get('tab');
    if (tab) {
      setActiveMenu(tab);
    }
  }, [searchParams]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handlePasswordChange = (e) => {
    const { name, value } = e.target;
    setPasswordData((prev) => ({ ...prev, [name]: value }));
  };

  const handleProfileSubmit = async (e) => {
    e.preventDefault();
    if (!formData.name.trim()) {
      toast.error('Please enter your name.');
      return;
    }

    setSavingProfile(true);
    try {
      const updated = await updateProfile({
        name: formData.name.trim(),
        email: formData.email.trim() || null,
        phone: formData.phone.trim() || null,
        city: formData.city.trim() || null,
        address: formData.address.trim() || null,
      });
      setUser(updated);
      populateForm(updated);
      loadOrders();
      toast.success('Profile updated successfully!');
    } catch (err) {
      const firstErr = Object.values(err?.errors || {}).flat().find(Boolean);
      toast.error(firstErr || err?.message || 'Could not update profile.');
    } finally {
      setSavingProfile(false);
    }
  };

  const handlePasswordSubmit = async (e) => {
    e.preventDefault();
    if (!passwordData.newPassword || passwordData.newPassword.length < 6) {
      toast.error('New password must be at least 6 characters.');
      return;
    }
    if (passwordData.newPassword !== passwordData.confirmPassword) {
      toast.error('Password confirmation does not match.');
      return;
    }

    setSavingPassword(true);
    try {
      await changePassword(
        passwordData.currentPassword,
        passwordData.newPassword,
        passwordData.confirmPassword
      );
      setPasswordData({ currentPassword: '', newPassword: '', confirmPassword: '' });
      toast.success('Password updated successfully!');
    } catch (err) {
      const firstErr = Object.values(err?.errors || {}).flat().find(Boolean);
      toast.error(firstErr || err?.message || 'Could not update password.');
    } finally {
      setSavingPassword(false);
    }
  };

  const handleCancelOrder = async (orderNumber) => {
    try {
      await cancelOrder(orderNumber);
      toast.success(`Order ${orderNumber} cancelled.`);
      loadOrders();
    } catch (err) {
      toast.error(err?.message || 'Could not cancel this order.');
    }
  };

  const handleLogout = async () => {
    await logout();
    setUser(null);
    toast.info('Logged out successfully!');
    router.push('/login');
    router.refresh();
  };

  if (!mounted || loadingProfile) {
    return (
      <div className="container mx-auto px-4 py-16 text-center font-poppins">
        <p className="text-sm text-gray-500">Loading your profile...</p>
      </div>
    );
  }

  if (!user) {
    return (
      <div className="container mx-auto px-4 py-16 font-poppins">
        <div className="mx-auto max-w-lg rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-xs">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-50 text-[var(--color-primary)]">
            <FiUser size={30} />
          </div>
          <h2 className="text-2xl font-bold text-gray-900">Sign in to your account</h2>
          <p className="mt-2 text-sm text-gray-600">
            Log in with your Phone Number, Google account, or Email to view your profile, edit your delivery details, and download order PDF invoices.
          </p>
          <div className="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
            <Link
              href="/login"
              className="inline-flex h-11 items-center justify-center rounded-lg bg-[var(--color-button)] px-6 text-sm font-semibold text-white hover:bg-black transition"
            >
              Log In / Phone / Google
            </Link>
            <Link
              href="/signup"
              className="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 px-6 text-sm font-semibold text-gray-800 hover:border-black transition"
            >
              Create Account
            </Link>
          </div>
        </div>
      </div>
    );
  }

  const filteredOrders = orders.filter((order) => {
    if (activeMenu === 'My Cancellations') return order.statusKey === 'cancelled';
    if (activeMenu === 'My Returns') return order.statusKey === 'returned';
    return true;
  });

  const initials = (user.name || 'U')
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

  return (
    <div className="container py-10 mx-auto px-4 font-poppins">
      {/* Top Customer Summary Banner */}
      <div className="mb-8 flex flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-xs sm:flex-row sm:items-center">
        <div className="flex items-center gap-4">
          <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[var(--color-button)] text-lg font-bold text-white">
            {initials}
          </div>
          <div>
            <div className="flex flex-wrap items-center gap-2.5">
              <h1 className="text-xl font-bold text-gray-900">{user.name}</h1>
              <span className="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                {user.authProvider === 'google' && <FcGoogle size={14} />}
                {user.authProvider === 'phone' && <FiPhone size={12} className="text-emerald-600" />}
                {user.authProvider === 'google'
                  ? 'Google Verified'
                  : user.authProvider === 'phone'
                  ? 'Phone Verified'
                  : 'Email Account'}
              </span>
            </div>
            <div className="mt-1 flex flex-wrap items-center gap-4 text-xs text-gray-500">
              {user.phone && (
                <span className="inline-flex items-center gap-1">
                  <FiPhone size={13} /> {user.phone}
                </span>
              )}
              {user.email && (
                <span className="inline-flex items-center gap-1">
                  <FiMail size={13} /> {user.email}
                </span>
              )}
              {user.address && (
                <span className="inline-flex items-center gap-1">
                  <FiMapPin size={13} /> {user.address}
                </span>
              )}
            </div>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <div className="rounded-xl bg-orange-50 px-4 py-2 text-center">
            <p className="text-xs text-gray-500">Total Orders</p>
            <p className="text-lg font-bold text-[var(--color-primary)]">{orders.length}</p>
          </div>
          <button
            type="button"
            onClick={handleLogout}
            className="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-xs font-semibold text-red-600 hover:bg-red-100 transition cursor-pointer"
          >
            <FiLogOut size={15} />
            <span>Log Out</span>
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
        {/* Left Navigation Sidebar */}
        <div className="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-xs h-fit">
          <div>
            <h3 className="font-semibold text-gray-900 mb-3 text-sm uppercase tracking-wider">
              Manage My Account
            </h3>
            <ul className="space-y-2 text-sm text-gray-600">
              <li
                onClick={() => setActiveMenu('My Profile')}
                className={`flex items-center gap-2.5 rounded-lg px-3 py-2 cursor-pointer transition ${
                  activeMenu === 'My Profile'
                    ? 'bg-orange-50 text-[var(--color-primary)] font-semibold'
                    : 'hover:bg-gray-50 hover:text-black'
                }`}
              >
                <FiUser size={16} />
                <span>My Profile &amp; Edit</span>
              </li>
              <li
                onClick={() => setActiveMenu('Security')}
                className={`flex items-center gap-2.5 rounded-lg px-3 py-2 cursor-pointer transition ${
                  activeMenu === 'Security'
                    ? 'bg-orange-50 text-[var(--color-primary)] font-semibold'
                    : 'hover:bg-gray-50 hover:text-black'
                }`}
              >
                <FiLock size={16} />
                <span>Change Password</span>
              </li>
            </ul>
          </div>

          <div className="border-t border-gray-100 pt-5">
            <h3 className="font-semibold text-gray-900 mb-3 text-sm uppercase tracking-wider">
              My Orders
            </h3>
            <ul className="space-y-2 text-sm text-gray-600">
              <li
                onClick={() => setActiveMenu('My Orders')}
                className={`flex items-center justify-between rounded-lg px-3 py-2 cursor-pointer transition ${
                  activeMenu === 'My Orders' || activeMenu === 'Order Track'
                    ? 'bg-orange-50 text-[var(--color-primary)] font-semibold'
                    : 'hover:bg-gray-50 hover:text-black'
                }`}
              >
                <span className="flex items-center gap-2.5">
                  <FiShoppingBag size={16} />
                  <span>All Orders &amp; PDF</span>
                </span>
                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">
                  {orders.length}
                </span>
              </li>
              <li
                onClick={() => setActiveMenu('My Cancellations')}
                className={`flex items-center gap-2.5 rounded-lg px-3 py-2 cursor-pointer transition ${
                  activeMenu === 'My Cancellations'
                    ? 'bg-orange-50 text-[var(--color-primary)] font-semibold'
                    : 'hover:bg-gray-50 hover:text-black'
                }`}
              >
                <FiXCircle size={16} />
                <span>My Cancellations</span>
              </li>
            </ul>
          </div>
        </div>

        {/* Main Content Panel */}
        <div className="md:col-span-3 rounded-2xl border border-gray-200 bg-white p-6 md:p-8 shadow-xs">
          {activeMenu === 'My Profile' && (
            <div>
              <div className="mb-6 border-b border-gray-100 pb-4">
                <h2 className="text-xl font-bold text-gray-900">View &amp; Edit Your Profile</h2>
                <p className="mt-1 text-xs text-gray-500">
                  Update your name, phone number, email address, and default delivery address.
                </p>
              </div>

              <form onSubmit={handleProfileSubmit} className="space-y-5">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">
                      Full Name <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      placeholder="Your full name"
                      className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[var(--color-primary)] focus:bg-white"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">
                      Phone Number
                    </label>
                    <input
                      type="tel"
                      name="phone"
                      value={formData.phone}
                      onChange={handleChange}
                      placeholder="017XXXXXXXX"
                      className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[var(--color-primary)] focus:bg-white"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">
                      Email Address
                    </label>
                    <input
                      type="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      placeholder="yourname@gmail.com"
                      className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[var(--color-primary)] focus:bg-white"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">
                      City / District
                    </label>
                    <input
                      type="text"
                      name="city"
                      value={formData.city}
                      onChange={handleChange}
                      placeholder="e.g. Dhaka"
                      className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[var(--color-primary)] focus:bg-white"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">
                    Full Delivery Address
                  </label>
                  <textarea
                    name="address"
                    rows={3}
                    value={formData.address}
                    onChange={handleChange}
                    placeholder="House, Road, Area, District"
                    className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[var(--color-primary)] focus:bg-white"
                  />
                </div>

                <div className="flex justify-end items-center gap-4 pt-3">
                  <button
                    type="button"
                    onClick={() => populateForm(user)}
                    className="rounded-xl border border-gray-200 px-5 py-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                  >
                    Reset
                  </button>
                  <button
                    type="submit"
                    disabled={savingProfile}
                    className="inline-flex items-center gap-2 rounded-xl bg-[var(--color-button)] px-7 py-3 text-xs font-semibold text-white hover:bg-black transition disabled:opacity-60 cursor-pointer"
                  >
                    <FiCheckCircle size={15} />
                    <span>{savingProfile ? 'Saving...' : 'Save Profile Changes'}</span>
                  </button>
                </div>
              </form>
            </div>
          )}

          {activeMenu === 'Security' && (
            <div>
              <div className="mb-6 border-b border-gray-100 pb-4">
                <h2 className="text-xl font-bold text-gray-900">Change Password</h2>
                <p className="mt-1 text-xs text-gray-500">
                  Set or update your account password so you can also sign in with your phone or email.
                </p>
              </div>

              <form onSubmit={handlePasswordSubmit} className="max-w-lg space-y-4">
                {user.authProvider === 'email' && (
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">
                      Current Password
                    </label>
                    <input
                      type="password"
                      name="currentPassword"
                      value={passwordData.currentPassword}
                      onChange={handlePasswordChange}
                      placeholder="Enter current password"
                      className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[var(--color-primary)] focus:bg-white"
                      required
                    />
                  </div>
                )}

                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">
                    New Password
                  </label>
                  <input
                    type="password"
                    name="newPassword"
                    value={passwordData.newPassword}
                    onChange={handlePasswordChange}
                    placeholder="Minimum 6 characters"
                    className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[var(--color-primary)] focus:bg-white"
                    required
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">
                    Confirm New Password
                  </label>
                  <input
                    type="password"
                    name="confirmPassword"
                    value={passwordData.confirmPassword}
                    onChange={handlePasswordChange}
                    placeholder="Re-enter new password"
                    className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none focus:border-[var(--color-primary)] focus:bg-white"
                    required
                  />
                </div>

                <div className="pt-2">
                  <button
                    type="submit"
                    disabled={savingPassword}
                    className="rounded-xl bg-[var(--color-button)] px-7 py-3 text-xs font-semibold text-white hover:bg-black transition disabled:opacity-60 cursor-pointer"
                  >
                    {savingPassword ? 'Updating...' : 'Update Password'}
                  </button>
                </div>
              </form>
            </div>
          )}

          {(activeMenu === 'My Orders' ||
            activeMenu === 'Order Track' ||
            activeMenu === 'My Cancellations' ||
            activeMenu === 'My Returns') && (
            <div>
              <div className="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4">
                <div>
                  <h2 className="text-xl font-bold text-gray-900">
                    {activeMenu === 'My Cancellations' ? 'Cancelled Orders' : 'My Orders & Invoices'}
                  </h2>
                  <p className="mt-1 text-xs text-gray-500">
                    View your order status and download official PDF invoices anytime.
                  </p>
                </div>
                <Link
                  href="/order/ordertrack"
                  className="rounded-lg border border-gray-200 px-3.5 py-2 text-xs font-semibold text-gray-700 hover:border-black transition"
                >
                  Track by Order ID
                </Link>
              </div>

              {loadingOrders ? (
                <p className="py-12 text-center text-sm text-gray-500">Loading your orders...</p>
              ) : filteredOrders.length === 0 ? (
                <div className="rounded-xl border border-dashed border-gray-200 bg-gray-50 py-12 text-center">
                  <FiShoppingBag className="mx-auto mb-3 text-gray-400" size={32} />
                  <p className="text-sm font-medium text-gray-700">No orders found in this section.</p>
                  <Link
                    href="/products"
                    className="mt-4 inline-flex rounded-lg bg-[var(--color-button)] px-5 py-2.5 text-xs font-semibold text-white hover:bg-black transition"
                  >
                    Start Shopping
                  </Link>
                </div>
              ) : (
                <div className="space-y-4">
                  {filteredOrders.map((order) => {
                    const orderNo = order.orderNumber || order.id;
                    const pdfDownloadUrl = getOrderInvoicePdfUrl(orderNo, {
                      inline: false,
                      token: order.invoiceToken || '',
                    });
                    const pdfPrintUrl = getOrderInvoicePdfUrl(orderNo, {
                      inline: true,
                      token: order.invoiceToken || '',
                    });

                    return (
                      <div
                        key={orderNo}
                        className="rounded-xl border border-gray-200 bg-gray-50/50 p-5 transition hover:border-gray-300"
                      >
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-3.5">
                          <div>
                            <div className="flex items-center gap-2.5">
                              <span className="font-mono text-sm font-bold text-gray-900">
                                #{orderNo}
                              </span>
                              <span className="rounded-full bg-orange-100 px-2.5 py-0.5 text-[11px] font-semibold text-[#c95008]">
                                {order.status}
                              </span>
                            </div>
                            <p className="mt-1 text-xs text-gray-500">
                              Placed on {order.date} • Total:{' '}
                              <strong className="text-gray-900">
                                BDT {Number(order.totals?.total || 0).toFixed(2)}
                              </strong>
                            </p>
                          </div>

                          <div className="flex flex-wrap items-center gap-2">
                            <a
                              href={pdfDownloadUrl}
                              download={`Invoice-${orderNo}.pdf`}
                              className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-button)] px-3.5 py-2 text-xs font-semibold text-white hover:bg-black transition"
                            >
                              <FiDownload size={14} />
                              <span>Download PDF</span>
                            </a>
                            <a
                              href={pdfPrintUrl}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:border-black transition"
                            >
                              <FiPrinter size={14} />
                              <span>Print</span>
                            </a>
                            {order.isCancellable && (
                              <button
                                type="button"
                                onClick={() => handleCancelOrder(orderNo)}
                                className="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100 transition cursor-pointer"
                              >
                                Cancel
                              </button>
                            )}
                          </div>
                        </div>

                        {Array.isArray(order.items) && order.items.length > 0 && (
                          <div className="mt-3 divide-y divide-gray-100">
                            {order.items.map((item) => (
                              <div
                                key={item.id}
                                className="flex items-center justify-between gap-3 py-2 text-xs"
                              >
                                <div className="min-w-0">
                                  <p className="truncate font-medium text-gray-800">
                                    {item.title}{' '}
                                    <span className="text-gray-500">× {item.quantity}</span>
                                  </p>
                                  {item.variantName && (
                                    <p className="text-[11px] text-gray-500">{item.variantName}</p>
                                  )}
                                </div>
                                <span className="shrink-0 font-semibold text-gray-900">
                                  BDT {Number(item.subtotal || item.price * item.quantity).toFixed(2)}
                                </span>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default UserProfileDashboard;
