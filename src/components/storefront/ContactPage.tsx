import React, { useState } from 'react';
import { Phone, Mail, MapPin, Send, MessageSquare, Clock, CheckCircle2 } from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const ContactPage: React.FC = () => {
  const { settings, submitContactMessage } = useStore();

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [sent, setSent] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim() || !phone.trim() || !message.trim()) return;

    submitContactMessage({
      name: name.trim(),
      phone: phone.trim(),
      email: email.trim() || undefined,
      subject: subject.trim() || 'General Inquiry',
      message: message.trim(),
    });

    setSent(true);
    setName('');
    setPhone('');
    setEmail('');
    setSubject('');
    setMessage('');
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 py-10 space-y-8 animate-in fade-in duration-200">
      <div className="text-center space-y-2 max-w-lg mx-auto">
        <h1 className="text-2xl sm:text-3xl font-black text-gray-950">
          যোগাযোগ ও কাস্টমার কেয়ার
        </h1>
        <p className="text-xs sm:text-sm text-gray-500">
          যেকোনো প্রশ্ন, তথ্য কিংবা বাল্ক অর্ডারের জন্য আমাদের সাথে যোগাযোগ করুন
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Contact Info Cards (5 cols) */}
        <div className="lg:col-span-5 space-y-4">
          <div className="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-5">
            <h2 className="font-extrabold text-base text-gray-900 pb-3 border-b border-gray-100">
              আমাদের ঠিকানা ও হেল্পলাইন
            </h2>

            <div className="space-y-4 text-xs text-gray-700">
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                  <MapPin className="w-4 h-4" />
                </div>
                <div>
                  <span className="font-bold text-gray-900 block">প্রধান কার্যালয়:</span>
                  <span className="text-gray-600 leading-relaxed">{settings.address}</span>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <div className="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                  <Phone className="w-4 h-4" />
                </div>
                <div>
                  <span className="font-bold text-gray-900 block">হটলাইন নম্বর:</span>
                  <span className="font-mono text-gray-800 font-bold">{settings.hotline}</span>
                  <p className="text-[11px] text-gray-400">সকাল ৯টা থেকে রাত ১০টা পর্যন্ত</p>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <div className="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                  <Mail className="w-4 h-4" />
                </div>
                <div>
                  <span className="font-bold text-gray-900 block">ইমেইল সাপোর্ট:</span>
                  <span className="text-gray-800">{settings.email}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Contact Form (7 cols) */}
        <div className="lg:col-span-7 bg-white p-6 sm:p-8 rounded-3xl border border-gray-100 shadow-xs">
          <h2 className="font-extrabold text-base text-gray-900 mb-4">
            আমাদের একটি মেসেজ পাঠান
          </h2>

          {sent ? (
            <div className="p-6 bg-emerald-50 rounded-2xl border border-emerald-200 text-center space-y-2">
              <CheckCircle2 className="w-10 h-10 text-emerald-600 mx-auto" />
              <h3 className="font-bold text-emerald-950 text-sm">মেসেজ সফলভাবে পাঠানো হয়েছে!</h3>
              <p className="text-xs text-emerald-800">
                আমাদের প্রতিনিধি দ্রুত আপনার সাথে যোগাযোগ করবে।
              </p>
              <button
                onClick={() => setSent(false)}
                className="mt-3 bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded-xl"
              >
                আরেকটি মেসেজ পাঠান
              </button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">আপনার নাম *</label>
                  <input
                    type="text"
                    required
                    placeholder="আপনার নাম"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">মোবাইল নম্বর *</label>
                  <input
                    type="tel"
                    required
                    placeholder="017xxxxxxxx"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden font-mono"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">বিষয় / সাবজেক্ট</label>
                <input
                  type="text"
                  placeholder="যেমন: পাইকারি অর্ডার বা জিজ্ঞাসা"
                  value={subject}
                  onChange={(e) => setSubject(e.target.value)}
                  className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">বার্তা বা মন্তব্য *</label>
                <textarea
                  rows={4}
                  required
                  placeholder="আপনার বার্তা বিস্তারিত লিখুন..."
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                  className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden resize-none"
                />
              </div>

              <button
                type="submit"
                className="w-full bg-gray-950 hover:bg-black text-white text-xs font-bold py-3 rounded-xl shadow-xs flex items-center justify-center gap-2"
              >
                <Send className="w-3.5 h-3.5" />
                <span>বার্তা পাঠান</span>
              </button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
};

export const ComplaintPage: React.FC = () => {
  const { submitComplaint } = useStore();

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [orderId, setOrderId] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [complaintToken, setComplaintToken] = useState<string | null>(null);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim() || !phone.trim() || !subject.trim() || !message.trim()) return;

    const token = submitComplaint({
      customer_name: name.trim(),
      customer_phone: phone.trim(),
      order_id: orderId.trim() || undefined,
      subject: subject.trim(),
      message: message.trim(),
    });

    setComplaintToken(token);
  };

  return (
    <div className="max-w-2xl mx-auto px-4 sm:px-6 py-10 space-y-6 animate-in fade-in duration-200">
      <div className="text-center space-y-2">
        <h1 className="text-2xl font-black text-gray-950">গ্রাহক সেবা ও অভিযোগ প্রতিকার সেল</h1>
        <p className="text-xs text-gray-500">
          ডেলিভারি, পণ্যের মান বা সার্ভিস নিয়ে যেকোনো অভিযোগ সরাসরি অ্যাডমিনের কাছে জমা দিন
        </p>
      </div>

      <div className="bg-white p-6 sm:p-8 rounded-3xl border border-gray-100 shadow-xs">
        {complaintToken ? (
          <div className="p-6 bg-emerald-50 rounded-2xl border border-emerald-200 text-center space-y-3">
            <CheckCircle2 className="w-10 h-10 text-emerald-600 mx-auto" />
            <h3 className="font-extrabold text-base text-emerald-950">
              আপনার অভিযোগটি সফলভাবে নথিভুক্ত হয়েছে
            </h3>
            <p className="text-xs text-emerald-800">
              অভিযোগ ট্র্যাকিং টোকেন নম্বর:{' '}
              <strong className="font-mono bg-white px-2 py-0.5 rounded border border-emerald-300">
                {complaintToken}
              </strong>
            </p>
            <p className="text-[11px] text-gray-500">
              আমাদের স্পেশাল কমপ্লেইন টিম দ্রুত সমস্যা সমাধান করবে।
            </p>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">আপনার নাম *</label>
                <input
                  type="text"
                  required
                  placeholder="আপনার নাম"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">মোবাইল নম্বর *</label>
                <input
                  type="tel"
                  required
                  placeholder="017xxxxxxxx"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden font-mono"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-bold text-gray-700 mb-1">
                অর্ডার আইডি (যদি থাকে)
              </label>
              <input
                type="text"
                placeholder="যেমন: ORD-9841"
                value={orderId}
                onChange={(e) => setOrderId(e.target.value)}
                className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden uppercase font-mono"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-gray-700 mb-1">অভিযোগের শিরোনাম *</label>
              <input
                type="text"
                required
                placeholder="যেমন: ডেলিভারি বিলম্ব বা ভুল পণ্য প্রাপ্তি"
                value={subject}
                onChange={(e) => setSubject(e.target.value)}
                className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-gray-700 mb-1">অভিযোগের বিবরণ *</label>
              <textarea
                rows={4}
                required
                placeholder="সমস্যাটি স্পষ্টভাবে বুঝিয়ে লিখুন..."
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden resize-none"
              />
            </div>

            <button
              type="submit"
              className="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-3 rounded-xl shadow-xs"
            >
              অভিযোগ দাখিল করুন
            </button>
          </form>
        )}
      </div>
    </div>
  );
};
