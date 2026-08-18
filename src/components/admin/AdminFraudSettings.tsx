import React, { useState } from 'react';
import { ShieldAlert, Plus, Trash2, CheckCircle2, AlertTriangle, ShieldCheck } from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const AdminFraudSettings: React.FC = () => {
  const { fraudSettings, updateFraudSettings, showToast } = useStore();

  const [settingsState, setSettingsState] = useState(fraudSettings);
  const [newPhone, setNewPhone] = useState('');
  const [newIp, setNewIp] = useState('');

  const handleAddPhone = (e: React.FormEvent) => {
    e.preventDefault();
    const clean = newPhone.trim().replace(/[-+\s]/g, '');
    if (!clean) return;
    if (settingsState.blacklisted_phones.includes(clean)) {
      showToast('এই নম্বরটি ইতিমধ্যে ব্ল্যাকলিস্টে আছে', 'info');
      return;
    }
    const updated = {
      ...settingsState,
      blacklisted_phones: [...settingsState.blacklisted_phones, clean],
    };
    setSettingsState(updated);
    updateFraudSettings(updated);
    setNewPhone('');
    showToast(`নম্বর ${clean} ব্ল্যাকলিস্টে যুক্ত হয়েছে`, 'success');
  };

  const handleRemovePhone = (phone: string) => {
    const updated = {
      ...settingsState,
      blacklisted_phones: settingsState.blacklisted_phones.filter((p) => p !== phone),
    };
    setSettingsState(updated);
    updateFraudSettings(updated);
  };

  const handleSaveGeneral = (e: React.FormEvent) => {
    e.preventDefault();
    updateFraudSettings(settingsState);
    showToast('ফ্রড সেটিংস সফলভাবে আপডেট হয়েছে!', 'success');
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-gray-950">
            ফ্রড ও ফেইক অর্ডার প্রিভেনশন
          </h1>
          <p className="text-xs text-gray-500">
            বট অ্যাটাক, স্প্যাম ও ফেক অর্ডার প্রতিরোধে অটোমেটিক সিকিউরিটি ও ব্ল্যাকলিস্ট
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Rules Card (6 cols) */}
        <div className="lg:col-span-6 bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
          <h2 className="font-extrabold text-base text-gray-900 flex items-center gap-2 pb-3 border-b border-gray-100">
            <ShieldCheck className="w-5 h-5 text-emerald-600" />
            <span>অর্ডার ফ্রিকোয়েন্সি লিমিট</span>
          </h2>

          <form onSubmit={handleSaveGeneral} className="space-y-4 text-xs">
            <label className="flex items-center justify-between p-3.5 bg-gray-50 rounded-2xl border border-gray-200 cursor-pointer">
              <span className="font-bold text-gray-900">অটোমেটিক ফ্রড প্রোটেকশন চালু</span>
              <input
                type="checkbox"
                checked={settingsState.is_enabled}
                onChange={(e) => setSettingsState({ ...settingsState, is_enabled: e.target.checked })}
                className="w-4 h-4 text-red-600 rounded"
              />
            </label>

            <div>
              <label className="block font-bold text-gray-700 mb-1">
                একই নম্বর থেকে দৈনিক সর্বোচ্চ অর্ডার সংখ্যা (Max Orders / Day)
              </label>
              <input
                type="number"
                min={1}
                max={50}
                value={settingsState.max_orders_per_phone_per_day}
                onChange={(e) =>
                  setSettingsState({
                    ...settingsState,
                    max_orders_per_phone_per_day: Number(e.target.value),
                  })
                }
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-bold outline-hidden focus:border-red-500"
              />
              <p className="text-[11px] text-gray-400 mt-1">
                একই মোবাইল নম্বর দিয়ে ২৪ ঘণ্টার মধ্যে এর বেশি অর্ডার করা যাবে না।
              </p>
            </div>

            <div>
              <label className="block font-bold text-gray-700 mb-1">
                পরপর দুটি অর্ডারের মধ্যকার বিরতি (মিনিট)
              </label>
              <input
                type="number"
                min={1}
                max={120}
                value={settingsState.cooldown_minutes}
                onChange={(e) =>
                  setSettingsState({
                    ...settingsState,
                    cooldown_minutes: Number(e.target.value),
                  })
                }
                className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-bold outline-hidden focus:border-red-500"
              />
            </div>

            <button
              type="submit"
              className="w-full bg-gray-900 hover:bg-black text-white font-bold py-2.5 rounded-xl shadow-xs transition-colors"
            >
              সেটিংস সংরক্ষণ করুন
            </button>
          </form>
        </div>

        {/* Blacklisted Numbers (6 cols) */}
        <div className="lg:col-span-6 bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
          <h2 className="font-extrabold text-base text-gray-900 flex items-center gap-2 pb-3 border-b border-gray-100">
            <ShieldAlert className="w-5 h-5 text-red-600" />
            <span>ব্ল্যাকলিস্টেড ফোন নম্বর ({settingsState.blacklisted_phones.length})</span>
          </h2>

          <form onSubmit={handleAddPhone} className="flex gap-2">
            <input
              type="tel"
              required
              placeholder="017xxxxxxxx"
              value={newPhone}
              onChange={(e) => setNewPhone(e.target.value)}
              className="flex-1 bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
            />
            <button
              type="submit"
              className="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs flex items-center gap-1 shrink-0"
            >
              <Plus className="w-4 h-4" />
              <span>ব্ল্যাকলিস্ট করুন</span>
            </button>
          </form>

          <div className="space-y-2 max-h-60 overflow-y-auto">
            {settingsState.blacklisted_phones.map((phone) => (
              <div
                key={phone}
                className="p-3 bg-red-50/60 border border-red-200/80 rounded-xl flex items-center justify-between text-xs"
              >
                <div className="flex items-center gap-2 font-mono font-bold text-red-950">
                  <AlertTriangle className="w-4 h-4 text-red-600" />
                  <span>{phone}</span>
                </div>
                <button
                  type="button"
                  onClick={() => handleRemovePhone(phone)}
                  className="text-red-600 hover:text-red-800 p-1"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};
