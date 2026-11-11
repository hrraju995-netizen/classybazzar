"use client"
import { useState, useEffect } from 'react';

export default function CountdownTwo() {
  const targetDate = new Date('2026-12-31T23:59:59').getTime();

  const [timeLeft, setTimeLeft] = useState({
    days: '00',
    hours: '00',
    minutes: '00',
    seconds: '00',
  });

  useEffect(() => {
    const timer = setInterval(() => {
      const now = new Date().getTime();
      const difference = targetDate - now;

      if (difference <= 0) {
        clearInterval(timer);
        return;
      }
      const d = Math.floor(difference / (1000 * 60 * 60 * 24));
      const h = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const m = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
      const s = Math.floor((difference % (1000 * 60)) / 1000);
      setTimeLeft({
        days: d < 10 ? `0${d}` : d.toString(),
        hours: h < 10 ? `0${h}` : h.toString(),
        minutes: m < 10 ? `0${m}` : m.toString(),
        seconds: s < 10 ? `0${s}` : s.toString(),
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [targetDate]);

  const timerItems = [
    { value: timeLeft.days, label: 'Days' },
    { value: timeLeft.hours, label: 'Hours' },
    { value: timeLeft.minutes, label: 'Minutes' },
    { value: timeLeft.seconds, label: 'Seconds' },
  ];

  return (
    <div className="flex items-center justify-center md:justify-start bg-transparent">
      <div className="flex gap-3 sm:gap-4 md:gap-5">
        {timerItems.map((item, index) => (
          <div
            key={index}
            className="
              flex flex-col items-center justify-center 
              /* মোবাইলে নিখুঁত গোলাকার বৃত্ত (56px) এবং ডেস্কটপে আপনার ডিজাইনে (80px) রূপান্তর হবে */
              w-[56px] h-[56px] 
              sm:w-[70px] sm:h-[70px] 
              md:w-[80px] md:h-[80px] 
              bg-white rounded-full text-black 
              shadow-lg transition-all duration-300
            "
          >
            <span className="text-sm sm:text-base md:text-lg font-bold leading-none text-black">
              {item.value}
            </span>
            <span className="text-[8px] sm:text-[9px] md:text-[10px] text-gray-500 font-medium uppercase mt-0.5 tracking-wider">
              {item.label}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}