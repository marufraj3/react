import React, { useState } from 'react';
import { Tag, Plus, Trash2, X, CheckCircle2, Percent, DollarSign } from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { Coupon } from '../../types';

export const AdminCoupons: React.FC = () => {
  const { coupons, addCoupon, deleteCoupon, updateCoupon, settings, showToast } = useStore();

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [code, setCode] = useState('');
  const [type, setType] = useState<'fixed' | 'percentage'>('fixed');
  const [amount, setAmount] = useState<number>(100);
  const [minPurchase, setMinPurchase] = useState<number>(1000);
  const [maxDiscount, setMaxDiscount] = useState<number>(500);
  const [expiryDate, setExpiryDate] = useState<string>('2026-12-31');

  const handleOpenAdd = () => {
    setCode('');
    setType('fixed');
    setAmount(100);
    setMinPurchase(1000);
    setMaxDiscount(500);
    setExpiryDate('2026-12-31');
    setIsModalOpen(true);
  };

  const handleSave = (e: React.FormEvent) => {
    e.preventDefault();
    if (!code.trim()) return;

    addCoupon({
      code: code.trim().toUpperCase(),
      type,
      amount,
      min_purchase: minPurchase,
      max_discount: type === 'percentage' ? maxDiscount : undefined,
      expiry_date: expiryDate,
      status: 1,
    });

    showToast(`কুপন কোড '${code.toUpperCase()}' তৈরি হয়েছে!`, 'success');
    setIsModalOpen(false);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-gray-950">
            কুপন ও ডিসকাউন্ট কোড
          </h1>
          <p className="text-xs text-gray-500">
            গ্রাহকদের জন্য প্রোমো কুপন কোড তৈরি ও ছাড় নিয়ন্ত্রণ করুন
          </p>
        </div>

        <button
          onClick={handleOpenAdd}
          className="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer self-start sm:self-auto"
        >
          <Plus className="w-4 h-4" />
          <span>+ নতুন কুপন তৈরি করুন</span>
        </button>
      </div>

      {/* Coupons List */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {coupons.map((c) => (
          <div
            key={c.id}
            className="bg-white rounded-3xl p-5 border border-gray-100 shadow-xs flex flex-col justify-between space-y-4"
          >
            <div className="flex items-start justify-between">
              <div className="space-y-1">
                <span className="font-mono font-black text-base text-red-600 bg-red-50 px-2.5 py-1 rounded-xl border border-red-200">
                  {c.code}
                </span>
                <div className="text-xs font-bold text-gray-900 pt-1">
                  ছাড়:{' '}
                  {c.type === 'fixed'
                    ? `${settings.currency}${c.amount} ফ্ল্যাট ডিসকাউন্ট`
                    : `${c.amount}% পার্সেন্টেজ ডিসকাউন্ট`}
                </div>
              </div>

              <button
                onClick={() => updateCoupon({ ...c, status: c.status === 1 ? 0 : 1 })}
                className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                  c.status === 1 ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600'
                }`}
              >
                {c.status === 1 ? 'Active' : 'Disabled'}
              </button>
            </div>

            <div className="text-xs text-gray-500 space-y-1 bg-gray-50 p-3 rounded-2xl">
              <div>সর্বনিম্ন ক্রয়: {settings.currency}{c.min_purchase.toLocaleString()}</div>
              {c.max_discount && <div>সর্বোচ্চ ছাড়: {settings.currency}{c.max_discount}</div>}
              <div>মেয়াদ শেষ: {c.expiry_date}</div>
            </div>

            <div className="pt-2 border-t border-gray-100 flex justify-end">
              <button
                onClick={() => {
                  if (confirm(`আপনি কি কুপন "${c.code}" মুছে ফেলতে চান?`)) {
                    deleteCoupon(c.id);
                  }
                }}
                className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Add Coupon Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150">
          <div className="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <h3 className="font-extrabold text-base text-gray-900">নতুন কুপন যোগ করুন</h3>
              <button onClick={() => setIsModalOpen(false)} className="p-1 text-gray-400 hover:text-gray-600">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleSave} className="space-y-3.5 text-xs">
              <div>
                <label className="block font-bold text-gray-700 mb-1">কুপন কোড *</label>
                <input
                  type="text"
                  required
                  placeholder="যেমন: EID2026 বা SAVE100"
                  value={code}
                  onChange={(e) => setCode(e.target.value)}
                  className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 uppercase font-mono font-bold outline-hidden focus:border-red-500"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-gray-700 mb-1">ডিসকাউন্ট টাইপ</label>
                  <select
                    value={type}
                    onChange={(e) => setType(e.target.value as any)}
                    className="w-full bg-gray-50 rounded-xl px-3 py-2.5 border border-gray-200 font-bold outline-hidden"
                  >
                    <option value="fixed">ফিক্সড টাকা (Fixed ৳)</option>
                    <option value="percentage">শতাংশ (Percentage %)</option>
                  </select>
                </div>

                <div>
                  <label className="block font-bold text-gray-700 mb-1">ডিসকাউন্ট পরিমাণ *</label>
                  <input
                    type="number"
                    required
                    min={1}
                    value={amount}
                    onChange={(e) => setAmount(Number(e.target.value))}
                    className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-bold outline-hidden focus:border-red-500"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-gray-700 mb-1">মিনিমাম অর্ডার (৳)</label>
                  <input
                    type="number"
                    min={0}
                    value={minPurchase}
                    onChange={(e) => setMinPurchase(Number(e.target.value))}
                    className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden"
                  />
                </div>

                <div>
                  <label className="block font-bold text-gray-700 mb-1">মেয়াদ শেষ তারিখ</label>
                  <input
                    type="date"
                    value={expiryDate}
                    onChange={(e) => setExpiryDate(e.target.value)}
                    className="w-full bg-gray-50 rounded-xl px-3 py-2 border border-gray-200 outline-hidden"
                  />
                </div>
              </div>

              <div className="flex gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl"
                >
                  বাতিল
                </button>
                <button
                  type="submit"
                  className="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-xl shadow-xs"
                >
                  কুপন সেভ করুন
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
