import React from 'react';
import CheckoutForm from '@/components/CheckoutForm';
import SubBanner from '@/components/SubBanner';

export const metadata = {
  title: 'Checkout | classybazar e-commarce',
  description: 'Provide your billing details and complete your purchase securely.',
};

const CheckoutPage = () => {
  return (
    <section className="font-sans text-black pb-10 lg:pb-20">
      <SubBanner title={"Checkout"} pageName={"Checkout"} />
      <div className='container mx-auto pt-5 px-4 lg:px-0'>
        <h1 className="text-2xl lg:text-3xl font-bold mb-6 lg:mb-10 tracking-wide">Billing Details</h1>
        <CheckoutForm />
      </div>
    </section>
  );
};

export default CheckoutPage;