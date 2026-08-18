import React, { useState } from 'react';
import { Image as ImageIcon, Plus, Trash2, X, ExternalLink } from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { Banner } from '../../types';

export const AdminBanners: React.FC = () => {
  const { banners, addBanner, deleteBanner, showToast } = useStore();

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [subtitle, setSubtitle] = useState('');
  const [image, setImage] = useState('');
  const [link, setLink] = useState('#');
  const [category, setCategory] = useState<'slider' | 'middle' | 'hot_deal'>('slider');

  const handleOpenAdd = () => {
    setTitle('');
    setSubtitle('');
    setImage('https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1200&auto=format&fit=crop&q=80');
    setLink('#');
    setCategory('slider');
    setIsModalOpen(true);
  };

  const handleSave = (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim() || !image.trim()) return;

    addBanner({
      title: title.trim(),
      subtitle: subtitle.trim() || undefined,
      image: image.trim(),
      link: link.trim() || '#',
      category,
      status: 1,
    });

    showToast('নতুন ব্যানার সফলভাবে তৈরি হয়েছে!', 'success');
    setIsModalOpen(false);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-gray-950">
            ব্যানার ও প্রোমো স্লাইডার
          </h1>
          <p className="text-xs text-gray-500">
            হোমপেজের মেইন স্লাইডার এবং অফার ব্যানারসমূহ নিয়ন্ত্রণ করুন
          </p>
        </div>

        <button
          onClick={handleOpenAdd}
          className="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer self-start sm:self-auto"
        >
          <Plus className="w-4 h-4" />
          <span>+ নতুন ব্যানার যোগ করুন</span>
        </button>
      </div>

      {/* Banners Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        {banners.map((b) => (
          <div
            key={b.id}
            className="bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-xs flex flex-col justify-between"
          >
            <div className="aspect-[16/8] relative bg-gray-100">
              <img src={b.image} alt={b.title} className="w-full h-full object-cover" />
              <span className="absolute top-2 left-2 bg-black/70 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md uppercase">
                {b.category}
              </span>
            </div>

            <div className="p-4 space-y-2">
              <h3 className="font-extrabold text-sm text-gray-950">{b.title}</h3>
              {b.subtitle && <p className="text-xs text-gray-500">{b.subtitle}</p>}
            </div>

            <div className="p-4 border-t border-gray-100 flex items-center justify-between">
              <span className="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded">
                Active
              </span>
              <button
                onClick={() => {
                  if (confirm('আপনি কি ব্যানারটি ডিলিট করতে চান?')) {
                    deleteBanner(b.id);
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

      {/* Add Banner Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150">
          <div className="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <h3 className="font-extrabold text-base text-gray-900">নতুন ব্যানার যোগ করুন</h3>
              <button onClick={() => setIsModalOpen(false)} className="p-1 text-gray-400 hover:text-gray-600">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleSave} className="space-y-3.5 text-xs">
              <div>
                <label className="block font-bold text-gray-700 mb-1">ব্যানার টাইটেল *</label>
                <input
                  type="text"
                  required
                  placeholder="যেমন: সেরা স্মার্টওয়াচে ৫০% ছাড়"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 font-bold"
                />
              </div>

              <div>
                <label className="block font-bold text-gray-700 mb-1">সাব-টাইটেল / স্লোগান</label>
                <input
                  type="text"
                  placeholder="যেমন: সীমিত সময়ের সুপার মেগা অফার"
                  value={subtitle}
                  onChange={(e) => setSubtitle(e.target.value)}
                  className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500"
                />
              </div>

              <div>
                <label className="block font-bold text-gray-700 mb-1">ব্যানার ইমেজ লিঙ্ক (URL) *</label>
                <input
                  type="text"
                  required
                  placeholder="https://..."
                  value={image}
                  onChange={(e) => setImage(e.target.value)}
                  className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
                />
              </div>

              <div>
                <label className="block font-bold text-gray-700 mb-1">ব্যানারের ধরণ / পজিশন</label>
                <select
                  value={category}
                  onChange={(e) => setCategory(e.target.value as any)}
                  className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-bold outline-hidden focus:border-red-500"
                >
                  <option value="slider">মেইন হিরো স্লাইডার (Hero Slider)</option>
                  <option value="middle">মাঝের অফার ব্যানার (Middle Promo)</option>
                  <option value="hot_deal">হট ডিল ব্যানার</option>
                </select>
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
                  যোগ করুন
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
