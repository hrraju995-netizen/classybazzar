'use client';

import React, { useState } from 'react';
import { FiMapPin, FiPhone } from 'react-icons/fi';
import { TfiEmail } from 'react-icons/tfi';
import { toast } from 'react-toastify';
import Button from './Button';
import { sendContactMessage } from '@/lib/api/misc';

const ContactFormSection = ({ contact = {} }) => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    message: '',
  });
  const [sending, setSending] = useState(false);

  const supportPhone = contact?.phone || '+880 1793-293600';
  const supportEmail = contact?.email || 'support@classybazar.com';
  const supportAddress = contact?.address || 'Dhaka, Bangladesh';
  const workingHours = contact?.workingHours || 'Sat - Thu: 9:00 AM - 10:00 PM';

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSendMessage = async (e) => {
    e.preventDefault();
    if (!formData.name || !formData.email || !formData.phone || !formData.message) {
      toast.error('Please fill in all required (*) fields.');
      return;
    }

    setSending(true);
    try {
      await sendContactMessage(formData);
      toast.success('Message sent successfully! We will get back to you soon.');
      setFormData({ name: '', email: '', phone: '', message: '' });
    } catch (err) {
      const firstErr = Object.values(err?.errors || {}).flat().find(Boolean);
      toast.error(firstErr || err?.message || 'Could not send message. Please try again.');
    } finally {
      setSending(false);
    }
  };

  return (
    <section className="container mx-auto px-4 py-10 font-sans text-black">
      <div className="grid grid-cols-1 lg:grid-cols-[340px_1fr] gap-8 items-stretch">
        <div className="bg-white p-8 rounded shadow-[0_1px_13px_rgba(0,0,0,0.05)] flex flex-col justify-between space-y-8">
          <div className="space-y-4">
            <div className="flex items-center gap-4">
              <div className="w-10 h-10 bg-[var(--color-button)] rounded-full flex items-center justify-center text-white text-xl">
                <FiPhone />
              </div>
              <h3 className="font-medium text-base">Call To Us</h3>
            </div>
            <div className="space-y-2 text-sm text-gray-800">
              <p>{workingHours}</p>
              <p className="font-medium">Phone: {supportPhone}</p>
            </div>
          </div>

          <hr className="border-gray-300" />
          <div className="space-y-4">
            <div className="flex items-center gap-4">
              <div className="w-10 h-10 bg-[var(--color-button)] rounded-full flex items-center justify-center text-white text-xl">
                <TfiEmail />
              </div>
              <h3 className="font-medium text-base">Write To Us</h3>
            </div>
            <div className="space-y-2 text-sm text-gray-800">
              <p>Fill out our form and we will contact you within 24 hours.</p>
              <p>Email: {supportEmail}</p>
              {supportAddress && (
                <p className="flex items-center gap-1.5 text-gray-600 pt-1">
                  <FiMapPin className="shrink-0 text-[var(--color-primary)]" />
                  <span>{supportAddress}</span>
                </p>
              )}
            </div>
          </div>
        </div>

        <form onSubmit={handleSendMessage} className="bg-white p-8 rounded shadow-[0_1px_13px_rgba(0,0,0,0.05)] flex flex-col justify-between space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input
              type="text"
              name="name"
              placeholder="Your Name *"
              value={formData.name}
              onChange={handleInputChange}
              className="w-full bg-[#F5F5F5] rounded p-3 text-sm focus:outline-none border border-transparent focus:border-[var(--color-primary)] transition-all"
              required
            />
            <input
              type="email"
              name="email"
              placeholder="Your Email *"
              value={formData.email}
              onChange={handleInputChange}
              className="w-full bg-[#F5F5F5] rounded p-3 text-sm focus:outline-none border border-transparent focus:border-[var(--color-primary)] transition-all"
              required
            />
            <input
              type="tel"
              name="phone"
              placeholder="Your Phone *"
              value={formData.phone}
              onChange={handleInputChange}
              className="w-full bg-[#F5F5F5] rounded p-3 text-sm focus:outline-none border border-transparent focus:border-[var(--color-primary)] transition-all"
              required
            />
          </div>
          <div className="flex-1">
            <textarea
              name="message"
              placeholder="Your Message *"
              rows="6"
              value={formData.message}
              onChange={handleInputChange}
              className="w-full bg-[#F5F5F5] rounded p-3 text-sm focus:outline-none border border-transparent focus:border-[var(--color-primary)] transition-all resize-none h-full min-h-[150px]"
              required
            ></textarea>
          </div>
          <div className="flex justify-end pt-2">
            <Button TagName={"button"} type="submit" disabled={sending}>
              {sending ? 'Sending...' : 'Send Message'}
            </Button>
          </div>
        </form>
      </div>
    </section>
  );
};

export default ContactFormSection;
