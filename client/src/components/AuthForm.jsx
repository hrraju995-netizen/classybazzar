'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { FcGoogle } from 'react-icons/fc';
import { FiPhone, FiMail, FiLock, FiUser, FiX } from 'react-icons/fi';
import { toast } from 'react-toastify';
import { googleLogin, login, phoneLogin, register } from '@/lib/api/auth';

export default function AuthForm({ mode = 'login' }) {
  const router = useRouter();
  const isSignup = mode === 'signup';

  // 'standard' = Email/Phone + Password, 'phone' = Quick Phone Number Sign-In
  const [authTab, setAuthTab] = useState('standard');
  const [loading, setLoading] = useState(false);

  // Standard form state
  const [name, setName] = useState('');
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');

  // Quick Phone Sign-In state
  const [phone, setPhone] = useState('');
  const [phoneName, setPhoneName] = useState('');

  // Google Sign-In Modal state
  const [googleModalOpen, setGoogleModalOpen] = useState(false);
  const [googleEmail, setGoogleEmail] = useState('');
  const [googleName, setGoogleName] = useState('');

  const handleStandardSubmit = async (e) => {
    e.preventDefault();
    if (!identifier.trim() || !password) {
      toast.error('Please enter your email/phone number and password.');
      return;
    }

    if (isSignup && !name.trim()) {
      toast.error('Please enter your full name.');
      return;
    }

    setLoading(true);
    try {
      if (isSignup) {
        await register({
          name: name.trim(),
          email: identifier.trim(),
          password,
          password_confirmation: password,
        });
        toast.success('Account created successfully!');
      } else {
        await login(identifier.trim(), password);
        toast.success('Signed in successfully!');
      }
      router.push('/user/profile');
      router.refresh();
    } catch (err) {
      const firstErr = Object.values(err?.errors || {}).flat().find(Boolean);
      toast.error(firstErr || err?.message || 'Authentication failed. Please check your details.');
    } finally {
      setLoading(false);
    }
  };

  const handlePhoneSubmit = async (e) => {
    e.preventDefault();
    const cleanPhone = phone.replace(/\D/g, '').replace(/^88(?=01)/, '');
    if (!/^01[3-9]\d{8}$/.test(cleanPhone)) {
      toast.error('Please enter a valid 11-digit Bangladeshi mobile number (e.g. 01712345678).');
      return;
    }

    setLoading(true);
    try {
      await phoneLogin({
        phone: cleanPhone,
        name: phoneName.trim() || undefined,
      });
      toast.success('Signed in with mobile number!');
      router.push('/user/profile');
      router.refresh();
    } catch (err) {
      const firstErr = Object.values(err?.errors || {}).flat().find(Boolean);
      toast.error(firstErr || err?.message || 'Could not sign in with this phone number.');
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleSubmit = async (e) => {
    e.preventDefault();
    if (!googleEmail.trim() || !googleEmail.includes('@')) {
      toast.error('Please enter a valid Google email address.');
      return;
    }

    setLoading(true);
    try {
      await googleLogin({
        email: googleEmail.trim(),
        name: googleName.trim() || undefined,
      });
      setGoogleModalOpen(false);
      toast.success('Signed in with Google!');
      router.push('/user/profile');
      router.refresh();
    } catch (err) {
      const firstErr = Object.values(err?.errors || {}).flat().find(Boolean);
      toast.error(firstErr || err?.message || 'Google sign-in failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="w-full max-w-[500px]">
      <h2 className="text-[28px] md:text-[36px] font-semibold text-black font-inter leading-tight">
        {isSignup ? 'Create an account' : 'Log in to Classy Bazar'}
      </h2>
      <p className="mt-2 text-[14px] md:text-[16px] text-gray-600 font-poppins">
        {isSignup
          ? 'Sign up using your Phone Number, Email, or Google account'
          : 'Sign in using your Email, Phone Number, or Google account'}
      </p>

      {/* Sign-in Method Tabs */}
      <div className="mt-6 grid grid-cols-2 gap-2 rounded-xl bg-gray-100 p-1.5 font-poppins text-xs sm:text-sm">
        <button
          type="button"
          onClick={() => setAuthTab('standard')}
          className={`flex items-center justify-center gap-2 rounded-lg py-2.5 font-medium transition cursor-pointer ${authTab === 'standard'
              ? 'bg-white text-gray-900 shadow-sm'
              : 'text-gray-600 hover:text-gray-900'
            }`}
        >
          <FiMail size={16} />
          <span>Email / Password</span>
        </button>
        <button
          type="button"
          onClick={() => setAuthTab('phone')}
          className={`flex items-center justify-center gap-2 rounded-lg py-2.5 font-medium transition cursor-pointer ${authTab === 'phone'
              ? 'bg-white text-gray-900 shadow-sm'
              : 'text-gray-600 hover:text-gray-900'
            }`}
        >
          <FiPhone size={16} />
          <span>Phone Number</span>
        </button>
      </div>

      {authTab === 'standard' ? (
        <form onSubmit={handleStandardSubmit} className="mt-7 space-y-6 font-poppins">
          {isSignup && (
            <div>
              <label className="mb-1.5 block text-xs font-medium text-gray-500">Full Name</label>
              <div className="relative">
                <input
                  type="text"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="Enter your full name"
                  className="w-full border-b border-gray-300 pb-2.5 pr-8 outline-none text-[15px] focus:border-[var(--color-primary)]"
                  required
                />
                <FiUser className="absolute right-1 top-1 text-gray-400" size={17} />
              </div>
            </div>
          )}

          <div>
            <label className="mb-1.5 block text-xs font-medium text-gray-500">
              Email or Phone Number
            </label>
            <div className="relative">
              <input
                type="text"
                value={identifier}
                onChange={(e) => setIdentifier(e.target.value)}
                placeholder="example@gmail.com or 017XXXXXXXX"
                className="w-full border-b border-gray-300 pb-2.5 pr-8 outline-none text-[15px] focus:border-[var(--color-primary)]"
                required
              />
              <FiMail className="absolute right-1 top-1 text-gray-400" size={17} />
            </div>
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-gray-500">Password</label>
            <div className="relative">
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Enter password (min 6 characters)"
                className="w-full border-b border-gray-300 pb-2.5 pr-8 outline-none text-[15px] focus:border-[var(--color-primary)]"
                required
              />
              <FiLock className="absolute right-1 top-1 text-gray-400" size={17} />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="cursor-pointer text-[15px] font-medium w-full bg-[var(--color-button)] text-white py-3.5 rounded-lg hover:bg-black transition disabled:opacity-60"
          >
            {loading ? 'Please wait...' : isSignup ? 'Create Account' : 'Log In'}
          </button>
        </form>
      ) : (
        <form onSubmit={handlePhoneSubmit} className="mt-7 space-y-6 font-poppins">
          <div>
            <label className="mb-1.5 block text-xs font-medium text-gray-500">
              Mobile Phone Number (Bangladesh)
            </label>
            <div className="relative">
              <input
                type="tel"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="017XXXXXXXX"
                className="w-full border-b border-gray-300 pb-2.5 pr-8 outline-none text-[15px] focus:border-[var(--color-primary)]"
                required
              />
              <FiPhone className="absolute right-1 top-1 text-gray-400" size={17} />
            </div>
            <p className="mt-1.5 text-xs text-gray-400">
              Instant sign-in or account creation using your 11-digit mobile number.
            </p>
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-gray-500">
              Your Name (for new customers)
            </label>
            <div className="relative">
              <input
                type="text"
                value={phoneName}
                onChange={(e) => setPhoneName(e.target.value)}
                placeholder="e.g. Rahim Uddin"
                className="w-full border-b border-gray-300 pb-2.5 pr-8 outline-none text-[15px] focus:border-[var(--color-primary)]"
              />
              <FiUser className="absolute right-1 top-1 text-gray-400" size={17} />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="cursor-pointer text-[15px] font-medium w-full bg-[var(--color-button)] text-white py-3.5 rounded-lg hover:bg-black transition disabled:opacity-60"
          >
            {loading ? 'Signing in...' : 'Continue with Phone Number'}
          </button>
        </form>
      )}

      {/* Divider */}
      <div className="my-5 flex items-center gap-3">
        <span className="h-px flex-1 bg-gray-200" />
        <span className="text-xs uppercase tracking-wider text-gray-400 font-poppins">OR</span>
        <span className="h-px flex-1 bg-gray-200" />
      </div>

      {/* Google Sign-In Button */}
      <button
        type="button"
        onClick={() => setGoogleModalOpen(true)}
        disabled={loading}
        className="cursor-pointer text-[15px] font-poppins w-full border border-gray-300 py-3.5 rounded-lg flex items-center justify-center gap-3 hover:bg-gray-50 transition"
      >
        <FcGoogle size={23} />
        <span>{isSignup ? 'Sign up with Google' : 'Sign In with Google'}</span>
      </button>

      {/* Footer Switch Link */}
      <p className="mt-6 text-center text-[14px] text-gray-600 font-poppins">
        {isSignup ? (
          <>
            Already have an account?{' '}
            <Link href="/login" className="font-semibold text-[var(--color-primary)] hover:text-black underline">
              Log in
            </Link>
          </>
        ) : (
          <>
            Don&apos;t have an account?{' '}
            <Link href="/signup" className="font-semibold text-[var(--color-primary)] hover:text-black underline">
              Create an account
            </Link>
          </>
        )}
      </p>

      {/* Google Account Sign-In Modal */}
      {googleModalOpen && (
        <div className="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl font-poppins">
            <div className="flex items-center justify-between border-b border-gray-100 pb-4">
              <div className="flex items-center gap-2.5">
                <FcGoogle size={26} />
                <div>
                  <h3 className="text-base font-semibold text-gray-900">Sign in with Google</h3>
                  <p className="text-xs text-gray-500">Continue to Classy Bazar</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setGoogleModalOpen(false)}
                className="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 cursor-pointer"
              >
                <FiX size={18} />
              </button>
            </div>

            <form onSubmit={handleGoogleSubmit} className="mt-5 space-y-4">
              <div>
                <label className="mb-1 block text-xs font-medium text-gray-700">
                  Google Email Address <span className="text-red-500">*</span>
                </label>
                <input
                  type="email"
                  value={googleEmail}
                  onChange={(e) => setGoogleEmail(e.target.value)}
                  placeholder="yourname@gmail.com"
                  className="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-orange-100"
                  required
                  autoFocus
                />
              </div>

              <div>
                <label className="mb-1 block text-xs font-medium text-gray-700">
                  Your Full Name
                </label>
                <input
                  type="text"
                  value={googleName}
                  onChange={(e) => setGoogleName(e.target.value)}
                  placeholder="e.g. Raju Ahmed"
                  className="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-orange-100"
                />
              </div>

              <div className="flex items-center justify-end gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setGoogleModalOpen(false)}
                  className="rounded-lg border border-gray-200 px-4 py-2.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={loading}
                  className="flex items-center gap-2 rounded-lg bg-[var(--color-button)] px-5 py-2.5 text-xs font-semibold text-white hover:bg-black disabled:opacity-60 cursor-pointer"
                >
                  <FcGoogle size={16} className="bg-white rounded-full p-0.5" />
                  <span>{loading ? 'Signing in...' : 'Continue with Google'}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

