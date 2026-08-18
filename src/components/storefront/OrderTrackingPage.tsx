import React, { useState, useEffect } from 'react';
import {
  Search,
  Truck,
  CheckCircle2,
  Clock,
  Package,
  MapPin,
  Phone,
  AlertCircle,
  ExternalLink,
  ShieldCheck
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { Order, OrderStatus } from '../../types';

interface OrderTrackingPageProps {
  orderId?: string;
  phone?: string;
}

export const OrderTrackingPage: React.FC<OrderTrackingPageProps> = ({ orderId = '', phone = '' }) => {
  const { trackOrderAsync, settings, orders } = useStore();

  const [inputOrderId, setInputOrderId] = useState(orderId);
  const [inputPhone, setInputPhone] = useState(phone);
  const [matchedOrder, setMatchedOrder] = useState<Order | null>(null);
  const [searched, setSearched] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Auto-search if props provided
  useEffect(() => {
    if (orderId && phone) {
      trackOrderAsync(orderId, phone).then((found) => {
        if (found) {
          setMatchedOrder(found);
          setSearched(true);
        }
      });
    } else if (orderId) {
      const found = orders.find((o) => o.id.toUpperCase() === orderId.toUpperCase());
      if (found) {
        setMatchedOrder(found);
        setSearched(true);
      }
    }
  }, [orderId, phone, orders, trackOrderAsync]);

  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);
    setSearched(true);

    if (!inputOrderId.trim()) {
      setErrorMsg('অনুগ্রহ করে অর্ডার আইডি প্রদান করুন (যেমন: ORD-9841)');
      return;
    }

    const cleanId = inputOrderId.trim().toUpperCase();
    const cleanPhone = inputPhone.trim().replace(/[-+\s]/g, '');

    // Search by ID and optional phone — hits the Laravel API in API mode.
    const found = await trackOrderAsync(cleanId, cleanPhone);

    if (found) {
      setMatchedOrder(found);
    } else {
      setMatchedOrder(null);
      setErrorMsg('দুঃখিত! এই অর্ডার আইডি বা নম্বরের কোনো তথ্য পাওয়া যায়নি। অনুগ্রহ করে সঠিক তথ্য দিন।');
    }
  };

  // Steps definition
  const getStepIndex = (status: OrderStatus): number => {
    switch (status) {
      case 'pending':
        return 0;
      case 'confirmed':
        return 1;
      case 'processing':
        return 2;
      case 'courier':
        return 3;
      case 'delivered':
        return 4;
      case 'cancelled':
      case 'returned':
        return -1;
      default:
        return 0;
    }
  };

  const steps = [
    { title: 'অর্ডার প্লেসড', desc: 'অর্ডার সিস্টেমে গৃহীত হয়েছে' },
    { title: 'কনফার্মেশন', desc: 'অর্ডারটি ভেরিফাই করা হয়েছে' },
    { title: 'প্যাকেজিং', desc: 'পণ্য কোয়ালিটি চেক ও প্যাকিং' },
    { title: 'কুরিয়ারে হস্তান্তর', desc: 'ডেলিভারির জন্য কুরিয়ারে রওয়ানা' },
    { title: 'ডেলিভারি সম্পন্ন', desc: 'গ্রাহকের হাতে হস্তান্তর' },
  ];

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-8 animate-in fade-in duration-200">
      {/* Header */}
      <div className="text-center space-y-2">
        <div className="inline-flex items-center gap-2 bg-red-50 text-red-600 px-3.5 py-1.5 rounded-full text-xs font-bold">
          <Truck className="w-4 h-4" />
          <span>লাইভ পার্সেল ট্র্যাকিং</span>
        </div>
        <h1 className="text-2xl sm:text-3xl font-black text-gray-950">
          আপনার অর্ডারের বর্তমান অবস্থা জানুন
        </h1>
        <p className="text-xs sm:text-sm text-gray-500 max-w-md mx-auto">
          অর্ডার কনফার্মেশনের সময় প্রাপ্ত অর্ডার আইডি এবং মোবাইল নম্বর দিয়ে ট্র্যাক করুন
        </p>
      </div>

      {/* Search Input Box */}
      <div className="bg-white p-5 sm:p-7 rounded-3xl border border-gray-100 shadow-sm">
        <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
          <div className="sm:col-span-5">
            <label className="block text-xs font-bold text-gray-700 mb-1">অর্ডার আইডি *</label>
            <input
              type="text"
              required
              placeholder="যেমন: ORD-9841"
              value={inputOrderId}
              onChange={(e) => setInputOrderId(e.target.value)}
              className="w-full bg-gray-50 focus:bg-white text-xs sm:text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden uppercase font-mono"
            />
          </div>

          <div className="sm:col-span-4">
            <label className="block text-xs font-bold text-gray-700 mb-1">মোবাইল নম্বর (ঐচ্ছিক)</label>
            <input
              type="tel"
              placeholder="017xxxxxxxx"
              value={inputPhone}
              onChange={(e) => setInputPhone(e.target.value)}
              className="w-full bg-gray-50 focus:bg-white text-xs sm:text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden font-mono"
            />
          </div>

          <div className="sm:col-span-3">
            <button
              type="submit"
              className="w-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs sm:text-sm py-2.5 rounded-xl shadow-xs flex items-center justify-center gap-1.5 transition-transform active:scale-95 cursor-pointer"
            >
              <Search className="w-4 h-4" />
              <span>ট্র্যাক করুন</span>
            </button>
          </div>
        </form>

        {errorMsg && (
          <div className="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs flex items-center gap-2">
            <AlertCircle className="w-4 h-4 shrink-0 text-red-600" />
            <span>{errorMsg}</span>
          </div>
        )}
      </div>

      {/* Matched Order Result Showcase */}
      {matchedOrder && (
        <div className="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-8 animate-in slide-in-from-bottom-2 duration-200">
          {/* Top Status Bar */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100">
            <div>
              <div className="text-xs text-gray-400">অর্ডার নম্বর:</div>
              <div className="text-xl font-black font-mono text-gray-950">{matchedOrder.id}</div>
              <div className="text-[11px] text-gray-500 mt-0.5">
                অর্ডারের তারিখ: {matchedOrder.created_at}
              </div>
            </div>

            <div className="flex items-center gap-2">
              <span
                className={`px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider ${
                  matchedOrder.status === 'delivered'
                    ? 'bg-emerald-100 text-emerald-800'
                    : matchedOrder.status === 'cancelled'
                    ? 'bg-red-100 text-red-800'
                    : matchedOrder.status === 'courier'
                    ? 'bg-blue-100 text-blue-800'
                    : 'bg-amber-100 text-amber-800'
                }`}
              >
                স্ট্যাটাস: {matchedOrder.status}
              </span>
            </div>
          </div>

          {/* Stepper Timeline */}
          {matchedOrder.status !== 'cancelled' && matchedOrder.status !== 'returned' ? (
            <div className="py-2">
              <div className="grid grid-cols-5 gap-2 relative">
                {steps.map((step, idx) => {
                  const currentIdx = getStepIndex(matchedOrder.status);
                  const isCompleted = idx <= currentIdx;
                  const isCurrent = idx === currentIdx;

                  return (
                    <div key={idx} className="flex flex-col items-center text-center relative z-10">
                      <div
                        className={`w-9 h-9 sm:w-11 sm:h-11 rounded-full flex items-center justify-center font-bold text-xs sm:text-sm mb-2 transition-all ${
                          isCompleted
                            ? 'bg-emerald-600 text-white shadow-md'
                            : 'bg-gray-100 text-gray-400 border border-gray-200'
                        } ${isCurrent ? 'ring-4 ring-emerald-500/20' : ''}`}
                      >
                        {isCompleted ? <CheckCircle2 className="w-5 h-5" /> : idx + 1}
                      </div>
                      <span
                        className={`text-xs font-bold leading-tight ${
                          isCompleted ? 'text-gray-900' : 'text-gray-400'
                        }`}
                      >
                        {step.title}
                      </span>
                      <span className="hidden sm:block text-[10px] text-gray-400 mt-0.5">
                        {step.desc}
                      </span>
                    </div>
                  );
                })}
              </div>
            </div>
          ) : (
            <div className="p-4 bg-red-50 rounded-2xl border border-red-200 text-red-800 text-xs text-center">
              এই অর্ডারটি বাতিল অথবা রিটার্ন করা হয়েছে। বিস্তারিত জানতে আমাদের হটলাইনে যোগাযোগ করুন।
            </div>
          )}

          {/* Courier Info if dispatched */}
          {matchedOrder.courier_name && (
            <div className="p-4 rounded-2xl bg-blue-50/70 border border-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-blue-950">
              <div className="flex items-center gap-3">
                <Truck className="w-6 h-6 text-blue-600 shrink-0" />
                <div>
                  <div className="font-bold text-sm">{matchedOrder.courier_name}</div>
                  <div className="text-blue-700">
                    কুরিয়ার ট্র্যাকিং নম্বর:{' '}
                    <span className="font-mono font-bold bg-white px-2 py-0.5 rounded border border-blue-200">
                      {matchedOrder.courier_tracking_id || 'SF-992810'}
                    </span>
                  </div>
                </div>
              </div>
              <span className="text-emerald-700 font-bold text-[11px] bg-white px-2.5 py-1 rounded-lg border border-emerald-200">
                পার্সেল কুরিয়ারে হস্তান্তর হয়েছে
              </span>
            </div>
          )}

          {/* Customer & Items Summary */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100 text-xs">
            {/* Customer Details */}
            <div className="p-4 bg-gray-50 rounded-2xl border border-gray-100 space-y-2">
              <h3 className="font-extrabold text-sm text-gray-900 mb-2">গ্রাহকের ডেলিভারি ঠিকানা</h3>
              <div className="flex items-center gap-2">
                <span className="text-gray-500 font-semibold">নাম:</span>
                <span className="font-bold text-gray-900">{matchedOrder.customer_name}</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-gray-500 font-semibold">মোবাইল:</span>
                <span className="font-mono text-gray-900">{matchedOrder.customer_phone}</span>
              </div>
              <div className="flex items-start gap-2">
                <span className="text-gray-500 font-semibold">ঠিকানা:</span>
                <span className="text-gray-800">{matchedOrder.customer_address}</span>
              </div>
            </div>

            {/* Financials */}
            <div className="p-4 bg-gray-50 rounded-2xl border border-gray-100 space-y-2">
              <h3 className="font-extrabold text-sm text-gray-900 mb-2">মূল্য বিবরণী</h3>
              <div className="flex justify-between">
                <span>পণ্য সংখ্যা:</span>
                <span className="font-bold">{matchedOrder.items.length} টি</span>
              </div>
              <div className="flex justify-between">
                <span>ডেলিভারি চার্জ:</span>
                <span>{settings.currency}{matchedOrder.delivery_charge.toLocaleString()}</span>
              </div>
              <div className="flex justify-between font-extrabold text-sm text-gray-950 pt-1 border-t border-gray-200">
                <span>সর্বমোট বিল:</span>
                <span className="text-red-600">{settings.currency}{matchedOrder.total.toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
