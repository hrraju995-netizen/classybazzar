"use client";

import { useEffect, useState } from "react";
export default function CountdownTimer() {
  const [targetDate] = useState(() => {
    const date = new Date();
    date.setDate(date.getDate() + 3);
    return date;
  });

  const calculateTimeLeft = () => {
    const difference = +targetDate - +new Date();
    let timeLeft = { days: "03", hours: "23", minutes: "19", seconds: "56" };

    if (difference > 0) {
      const d = Math.floor(difference / (1000 * 60 * 60 * 24));
      const h = Math.floor((difference / (1000 * 60 * 60)) % 24);
      const m = Math.floor((difference / 1000 / 60) % 60);
      const s = Math.floor((difference / 1000) % 60);

      timeLeft = {
        days: d < 10 ? `0${d}` : `${d}`,
        hours: h < 10 ? `0${h}` : `${h}`,
        minutes: m < 10 ? `0${m}` : `${m}`,
        seconds: s < 10 ? `0${s}` : `${s}`,
      };
    }
    return timeLeft;
  };

  const [timeLeft, setTimeLeft] = useState(calculateTimeLeft);

  useEffect(() => {
    const timer = setInterval(() => {
      setTimeLeft(calculateTimeLeft());
    }, 1000);
    return () => clearInterval(timer);
  }, [targetDate]);

  return (
    <div className="flex items-center justify-start font-sans select-none w-full my-2">
      <div className="flex items-center gap-2 sm:gap-4 md:gap-5">
        <div className="flex flex-col items-center">
          <span className="text-[10px] sm:text-xs md:text-sm font-semibold text-gray-800 mb-0.5 sm:mb-1 uppercase tracking-wider">Days</span>
          <span className="text-xl sm:text-2xl md:text-[32px] font-bold text-black tracking-tight leading-none">{timeLeft.days}</span>
        </div>
        <div className="text-lg sm:text-xl md:text-3xl font-bold text-[var(--color-primary)] self-center pt-3 sm:pt-4">:</div>
        <div className="flex flex-col items-center">
          <span className="text-[10px] sm:text-xs md:text-sm font-semibold text-gray-800 mb-0.5 sm:mb-1 uppercase tracking-wider">Hours</span>
          <span className="text-xl sm:text-2xl md:text-[32px] font-bold text-black tracking-tight leading-none">{timeLeft.hours}</span>
        </div>
        <div className="text-lg sm:text-xl md:text-3xl font-bold text-[var(--color-primary)] self-center pt-3 sm:pt-4">:</div>
        <div className="flex flex-col items-center">
          <span className="text-[10px] sm:text-xs md:text-sm font-semibold text-gray-800 mb-0.5 sm:mb-1 uppercase tracking-wider">Minutes</span>
          <span className="text-xl sm:text-2xl md:text-[32px] font-bold text-black tracking-tight leading-none">{timeLeft.minutes}</span>
        </div>
        <div className="text-lg sm:text-xl md:text-3xl font-bold text-[var(--color-primary)] self-center pt-3 sm:pt-4">:</div>
        <div className="flex flex-col items-center">
          <span className="text-[10px] sm:text-xs md:text-sm font-semibold text-gray-800 mb-0.5 sm:mb-1 uppercase tracking-wider">Seconds</span>
          <span className="text-xl sm:text-2xl md:text-[32px] font-bold text-[var(--color-primary)] tracking-tight leading-none">{timeLeft.seconds}</span>
        </div>
      </div>
    </div>
  );
}