"use client";

import React, { useState } from "react";
import { FaWhatsapp, FaFacebookMessenger, FaPhoneAlt, FaEnvelope } from "react-icons/fa";
import { BiCommentDetail } from "react-icons/bi";
import { IoClose } from "react-icons/io5";

const MultiChatFAB = ({ settings }) => {
  const [isOpen, setIsOpen] = useState(false);

  const contact = settings?.contact ?? {};
  if (contact.showFloatingChat === false) {
    return null;
  }

  const whatsappNumber = (contact.whatsapp || "8801793293600").replace(/\D/g, "");
  const messengerUsername = contact.messenger || "amarClassyBazar";
  const phoneNumber = contact.phone || "+8801793293600";
  const emailAddress = contact.email || "support@classybazar.com";

  const actions = [
    emailAddress && {
      id: "email",
      icon: <FaEnvelope size={20} />,
      bg: "bg-red-500 hover:bg-red-600",
      href: `mailto:${emailAddress}`,
      label: "Email Us",
    },
    phoneNumber && {
      id: "call",
      icon: <FaPhoneAlt size={18} />,
      bg: "bg-[var(--color-button)] hover:opacity-90",
      href: `tel:${phoneNumber}`,
      label: "Call Us",
    },
    messengerUsername && {
      id: "messenger",
      icon: <FaFacebookMessenger size={22} />,
      bg: "bg-[#0084FF] hover:bg-[#0073e6]",
      href: `https://m.me/${messengerUsername}`,
      label: "Messenger",
      target: "_blank",
    },
    whatsappNumber && {
      id: "whatsapp",
      icon: <FaWhatsapp size={22} />,
      bg: "bg-[#25D366] hover:bg-[#20ba5a]",
      href: `https://wa.me/${whatsappNumber}`,
      label: "WhatsApp",
      target: "_blank",
    },
  ].filter(Boolean);

  return (
    <div className="fixed bottom-20 right-4 sm:bottom-20 sm:right-6 md:bottom-8 md:right-8 z-[9990] flex flex-col items-center">
      <div className="flex flex-col-reverse items-center gap-2 sm:gap-3 mb-2.5 pointer-events-none">
        {actions.map((action, index) => {
          const delayClass = isOpen
            ? `transition-all duration-300 ease-out`
            : `transition-all duration-200 ease-in`;

          return (
            <a
              key={action.id}
              href={action.href}
              target={action.target || "_self"}
              rel="noopener noreferrer"
              aria-label={action.label}
              style={{
                transitionDelay: isOpen ? `${index * 50}ms` : `${(actions.length - 1 - index) * 30}ms`,
              }}
              className={`
                w-11 h-11 sm:w-12 sm:h-12 rounded-full text-white ${action.bg} shadow-lg 
                flex items-center justify-center cursor-pointer 
                hover:scale-110 active:scale-95
                ${delayClass}
                ${isOpen
                  ? "opacity-100 translate-y-0 scale-100 pointer-events-auto"
                  : "opacity-0 translate-y-12 scale-50 pointer-events-none"
                }
              `}
            >
              {action.icon}
            </a>
          );
        })}
      </div>

      <button
        onClick={() => setIsOpen(!isOpen)}
        aria-label="Toggle Contact Menu"
        className={`
          w-12 h-12 sm:w-14 sm:h-14 bg-[var(--color-button)] hover:bg-black text-white rounded-full 
          shadow-xl hover:shadow-2xl transition-all duration-300 
          flex items-center justify-center cursor-pointer relative
          ${isOpen ? "rotate-90 bg-black" : "rotate-0"}
        `}
      >
        <span
          className={`absolute transition-all duration-300 ${isOpen ? "opacity-100 rotate-0 scale-100" : "opacity-0 -rotate-90 scale-50"
            }`}
        >
          <IoClose size={26} />
        </span>
        <span
          className={`absolute transition-all duration-300 ${isOpen ? "opacity-0 rotate-90 scale-50" : "opacity-100 rotate-0 scale-100"
            }`}
        >
          <BiCommentDetail size={24} />
        </span>
      </button>
    </div>
  );
};

export default MultiChatFAB;
