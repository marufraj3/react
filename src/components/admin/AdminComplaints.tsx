import React, { useState } from 'react';
import { MessageSquare, CheckCircle2, Clock, Mail, Phone, AlertCircle } from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const AdminComplaints: React.FC = () => {
  const { complaints, contactMessages, updateComplaintStatus, markContactMessageRead, showToast } = useStore();
  const [activeTab, setActiveTab] = useState<'complaints' | 'messages'>('complaints');

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-gray-950">
            অভিযোগ ও ইনকোয়ারি ইনবক্স
          </h1>
          <p className="text-xs text-gray-500">
            গ্রাহকদের সহায়তা টিকেট, রিফান্ড সংক্রান্ত সমস্যা ও ইনবক্স মেসেজ
          </p>
        </div>

        <div className="flex gap-2">
          <button
            onClick={() => setActiveTab('complaints')}
            className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'complaints'
                ? 'bg-red-600 text-white shadow-xs'
                : 'bg-white text-gray-700 border border-gray-200'
            }`}
          >
            অভিযোগ সেল ({complaints.length})
          </button>
          <button
            onClick={() => setActiveTab('messages')}
            className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'messages'
                ? 'bg-red-600 text-white shadow-xs'
                : 'bg-white text-gray-700 border border-gray-200'
            }`}
          >
            কন্টাক্ট মেসেজ ({contactMessages.length})
          </button>
        </div>
      </div>

      {activeTab === 'complaints' ? (
        <div className="space-y-3">
          {complaints.map((c) => (
            <div
              key={c.id}
              className="bg-white rounded-3xl p-5 border border-gray-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
              <div className="space-y-1.5 flex-1">
                <div className="flex items-center gap-2">
                  <span className="font-mono text-xs font-bold bg-gray-100 text-gray-800 px-2 py-0.5 rounded">
                    টোকেন: {c.token}
                  </span>
                  {c.order_id && (
                    <span className="font-mono text-xs font-bold text-red-600">
                      অর্ডার: {c.order_id}
                    </span>
                  )}
                  <span
                    className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                      c.status === 'resolved'
                        ? 'bg-emerald-100 text-emerald-800'
                        : 'bg-amber-100 text-amber-800'
                    }`}
                  >
                    {c.status === 'resolved' ? 'Resolved' : 'Open'}
                  </span>
                </div>

                <h3 className="font-extrabold text-sm text-gray-950">{c.subject}</h3>
                <p className="text-xs text-gray-600">{c.message}</p>

                <div className="flex items-center gap-3 text-[11px] text-gray-400 font-mono pt-1">
                  <span>গ্রাহক: {c.customer_name}</span>
                  <span>ফোন: {c.customer_phone}</span>
                  <span>তারিখ: {c.created_at}</span>
                </div>
              </div>

              <div className="shrink-0">
                {c.status === 'open' ? (
                  <button
                    onClick={() => {
                      updateComplaintStatus(c.id, 'resolved');
                      showToast('অভিযোগটি সমাধান হিসেবে চিহ্নিত করা হয়েছে!', 'success');
                    }}
                    className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl flex items-center gap-1.5 shadow-xs"
                  >
                    <CheckCircle2 className="w-3.5 h-3.5" />
                    <span>সমাধান করুন</span>
                  </button>
                ) : (
                  <span className="text-emerald-600 font-bold text-xs flex items-center gap-1">
                    <CheckCircle2 className="w-4 h-4" /> সমাধানকৃত
                  </span>
                )}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="space-y-3">
          {contactMessages.map((m) => (
            <div
              key={m.id}
              className="bg-white rounded-3xl p-5 border border-gray-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
              <div className="space-y-1 flex-1">
                <div className="flex items-center gap-2">
                  <h3 className="font-extrabold text-sm text-gray-950">{m.subject}</h3>
                  {!m.is_read && (
                    <span className="bg-red-500 text-white text-[9px] font-bold px-2 py-0.2 rounded-full">
                      New
                    </span>
                  )}
                </div>
                <p className="text-xs text-gray-600">{m.message}</p>
                <div className="flex items-center gap-3 text-[11px] text-gray-400 pt-1">
                  <span className="font-bold text-gray-700">{m.name}</span>
                  <span className="font-mono">{m.phone}</span>
                  {m.email && <span>{m.email}</span>}
                  <span>{m.created_at}</span>
                </div>
              </div>

              {!m.is_read && (
                <button
                  onClick={() => markContactMessageRead(m.id)}
                  className="bg-gray-900 text-white text-xs font-bold px-3 py-1.5 rounded-xl self-start sm:self-auto"
                >
                  পঠিত হিসেবে চিহ্নিত করুন
                </button>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};
