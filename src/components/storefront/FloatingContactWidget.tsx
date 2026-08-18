import React, { useState } from 'react';
import { Phone, MessageCircle, MessageSquare, X } from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const FloatingContactWidget: React.FC = () => {
  const { settings } = useStore();
  const [isOpen, setIsOpen] = useState(false);

  const cleanPhone = settings.whatsapp_number.replace(/[-+\s]/g, '');

  return (
    <div className="fixed bottom-5 right-5 z-40 flex flex-col items-end gap-2.5">
      {/* Expanded Quick Contact Menu */}
      {isOpen && (
        <div className="flex flex-col gap-2 animate-in slide-in-from-bottom-3 duration-200">
          {/* WhatsApp Button */}
          <a
            href={`https://wa.me/${cleanPhone}`}
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-full shadow-lg text-xs font-bold transition-transform active:scale-95 group"
          >
            <MessageCircle className="w-4 h-4" />
            <span>হোয়াটসঅ্যাপে চ্যাট করুন</span>
          </a>

          {/* Direct Hotline Call */}
          <a
            href={`tel:${settings.hotline}`}
            className="flex items-center gap-2.5 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-full shadow-lg text-xs font-bold transition-transform active:scale-95"
          >
            <Phone className="w-4 h-4" />
            <span>সরাসরি কল দিন</span>
          </a>
        </div>
      )}

      {/* Floating Main Toggle Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="w-13 h-13 rounded-full bg-gradient-to-tr from-gray-900 to-red-600 hover:from-black hover:to-red-700 text-white flex items-center justify-center shadow-xl shadow-red-500/20 transition-transform active:scale-90 cursor-pointer"
        aria-label="Contact support"
      >
        {isOpen ? <X className="w-6 h-6" /> : <MessageSquare className="w-6 h-6 animate-pulse" />}
      </button>
    </div>
  );
};
