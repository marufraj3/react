import React, { useState } from 'react';
import { Settings, Save, CheckCircle2, Phone, MapPin, Truck, Share2, AlertCircle } from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const AdminSettings: React.FC = () => {
  const { settings, updateSettings, showToast } = useStore();
  const [form, setForm] = useState(settings);

  const handleSave = (e: React.FormEvent) => {
    e.preventDefault();
    updateSettings(form);
    showToast('দোকানের সেটিংস সফলভাবে আপডেট হয়েছে!', 'success');
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200 max-w-4xl">
      <div>
        <h1 className="text-xl sm:text-2xl font-black text-gray-950">
          দোকান সেটিংস ও কাস্টমাইজেশন
        </h1>
        <p className="text-xs text-gray-500">
          স্টোরের নাম, হটলাইন নম্বর, ডেলিভারি চার্জ এবং সোশ্যাল লিঙ্ক কনফিগার করুন
        </p>
      </div>

      <form onSubmit={handleSave} className="space-y-6">
        {/* Basic Info */}
        <div className="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
          <h2 className="font-extrabold text-base text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
            <Settings className="w-4 h-4 text-red-600" />
            <span>বেসিক তথ্য ও যোগাযোগ</span>
          </h2>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
              <label className="block font-bold text-gray-700 mb-1">স্টোরের নাম *</label>
              <input
                type="text"
                required
                value={form.store_name}
                onChange={(e) => setForm({ ...form, store_name: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-bold outline-hidden focus:border-red-500"
              />
            </div>

            <div>
              <label className="block font-bold text-gray-700 mb-1">মুদ্রার প্রতীক (Currency)</label>
              <input
                type="text"
                required
                value={form.currency}
                onChange={(e) => setForm({ ...form, currency: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-bold outline-hidden focus:border-red-500"
              />
            </div>

            <div>
              <label className="block font-bold text-gray-700 mb-1">হটলাইন নম্বর *</label>
              <input
                type="tel"
                required
                value={form.hotline}
                onChange={(e) => setForm({ ...form, hotline: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
              />
            </div>

            <div>
              <label className="block font-bold text-gray-700 mb-1">হোয়াটসঅ্যাপ নম্বর *</label>
              <input
                type="tel"
                required
                value={form.whatsapp_number}
                onChange={(e) => setForm({ ...form, whatsapp_number: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
              />
            </div>

            <div>
              <label className="block font-bold text-gray-700 mb-1">ইমেইল এড্রেস</label>
              <input
                type="email"
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500"
              />
            </div>

            <div>
              <label className="block font-bold text-gray-700 mb-1">দোকানের সম্পূর্ণ ঠিকানা</label>
              <input
                type="text"
                value={form.address}
                onChange={(e) => setForm({ ...form, address: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500"
              />
            </div>
          </div>
        </div>

        {/* Notices & Banners */}
        <div className="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
          <h2 className="font-extrabold text-base text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
            <AlertCircle className="w-4 h-4 text-amber-600" />
            <span>হেডার নোটিশ ও চেকআউট সতর্কবার্তা</span>
          </h2>

          <div className="space-y-4 text-xs">
            <div>
              <label className="block font-bold text-gray-700 mb-1">
                টপ হেডার নোটিশ স্ক্রলিং টেক্সট (Marquee Ticker)
              </label>
              <input
                type="text"
                value={form.top_notice_text}
                onChange={(e) => setForm({ ...form, top_notice_text: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500"
              />
            </div>

            <div>
              <label className="block font-bold text-gray-700 mb-1">
                চেকআউট পেজ সতর্কবার্তা (Order Caution Disclaimer)
              </label>
              <textarea
                rows={2}
                value={form.checkout_note}
                onChange={(e) => setForm({ ...form, checkout_note: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 resize-none"
              />
            </div>

            <div>
              <label className="block font-bold text-gray-700 mb-1">
                ফুটার অ্যাবাউট সংক্ষিপ্ত বিবরণ (Footer About Text)
              </label>
              <textarea
                rows={2}
                value={form.footer_about_text}
                onChange={(e) => setForm({ ...form, footer_about_text: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 resize-none"
              />
            </div>
          </div>
        </div>

        {/* Social Links */}
        <div className="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
          <h2 className="font-extrabold text-base text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
            <Share2 className="w-4 h-4 text-blue-600" />
            <span>সোশ্যাল মিডিয়া ও পেইজ লিঙ্ক</span>
          </h2>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div>
              <label className="block font-bold text-gray-700 mb-1">ফেসবুক পেইজ URL</label>
              <input
                type="text"
                value={form.facebook_page}
                onChange={(e) => setForm({ ...form, facebook_page: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
              />
            </div>
            <div>
              <label className="block font-bold text-gray-700 mb-1">ইউটিউব চ্যানেল লিঙ্ক</label>
              <input
                type="text"
                value={form.youtube_link}
                onChange={(e) => setForm({ ...form, youtube_link: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
              />
            </div>
            <div>
              <label className="block font-bold text-gray-700 mb-1">ইনস্টাগ্রাম প্রোফাইল লিঙ্ক</label>
              <input
                type="text"
                value={form.instagram_link}
                onChange={(e) => setForm({ ...form, instagram_link: e.target.value })}
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
              />
            </div>
          </div>
        </div>

        <button
          type="submit"
          className="w-full bg-red-600 hover:bg-red-700 text-white font-black text-sm py-3.5 rounded-2xl shadow-lg shadow-red-500/20 flex items-center justify-center gap-2 cursor-pointer transition-transform active:scale-98"
        >
          <Save className="w-4 h-4" />
          <span>সকল সেটিংস সংরক্ষণ করুন</span>
        </button>
      </form>
    </div>
  );
};
