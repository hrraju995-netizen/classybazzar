"use client";
import Image from "next/image";
import aboutImage from "../assets/images/about.png";
import { BiSolidOffer } from "react-icons/bi";
import { FaStar } from "react-icons/fa";
import { AiFillFire } from "react-icons/ai";

const AboutDetailsSection = () => {
  return (
    <div className="container mx-auto px-4 py-12 space-y-20 max-w-7xl">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
        <div className="space-y-6">
          <h1 className="text-4xl md:text-5xl font-bold tracking-tight text-black">
            Our Story
          </h1>
          <div className="space-y-4 text-gray-600 text-base leading-relaxed">
            <p>
              Welcome to <strong>Classy Bazar</strong>! We are a trusted online shopping platform in Bangladesh,
              bringing essential and trendy products together under one roof with budget-friendly prices
              and reliable customer service.
            </p>
            <p>
              Our goal is not just selling products; we believe in building long-term relationships
              with every single customer by maintaining proper quality, correct pricing, and on-time delivery.
            </p>
          </div>
        </div>

        <div className="w-full max-h-[400px] md:max-h-[500px] rounded-tl-[300px] rounded-br-[200px] relative overflow-hidden bg-pink-100 flex items-center justify-center">
          <Image className="" src={aboutImage} height={609} width={705} alt="aboutImage" />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-8 pt-10 border-t border-gray-100">
        <div className="bg-gray-100 p-6 rounded-xl space-y-3">
          <div className="text-2xl"><AiFillFire /></div>
          <h3 className="text-lg font-semibold text-black">Our Purpose</h3>
          <p className="text-sm text-gray-600 leading-relaxed">
            We believe that online shopping should be easy, secure, and hassle-free. That is why we carefully select every single product keeping quality, practical needs, and customer satisfaction in mind.
          </p>
        </div>

        <div className="bg-gray-100 p-6 rounded-xl space-y-3">
          <div className="text-2xl"><BiSolidOffer /></div>
          <h3 className="text-lg font-semibold text-black">What We Offer</h3>
          <ul className="text-sm text-gray-600 space-y-1.5 list-disc pl-4">
            <li>Lifestyle Products</li>
            <li>Electronics & Gadgets</li>
            <li>Home & Kitchen Items</li>
            <li>Fashion & Accessories</li>
            <li>Islamic Decor & Travel Gear</li>
          </ul>
        </div>

        <div className="bg-gray-100 p-6 rounded-xl space-y-3">
          <div className="text-2xl"><FaStar /></div>
          <h3 className="text-lg font-semibold text-black">Why Classy Bazar?</h3>
          <ul className="text-sm text-gray-600 space-y-1.5">
            <li className="flex items-center gap-2">✅ 100% Quality Checked</li>
            <li className="flex items-center gap-2">✅ Reliable Delivery Service</li>
            <li className="flex items-center gap-2">✅ Cash on Delivery Facility</li>
            <li className="flex items-center gap-2">✅ Fast Customer Support</li>
          </ul>
        </div>

      </div>

      <div className="bg-gray-100 rounded-2xl p-8 md:p-12 text-center w-full mx-auto space-y-4">
        <h3 className="text-xl font-bold text-[var(--color-primary)]">Our Promise</h3>
        <p className="text-black max-w-xl mx-auto text-sm md:text-base">
          Authentic products, fair pricing, timely delivery, and respect for our customers. Your trust is our greatest asset. We strive to deliver your products across Bangladesh in the fastest possible time.
        </p>
      </div>

    </div>
  );
};

export default AboutDetailsSection;