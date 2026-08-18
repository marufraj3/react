import React, { useState } from 'react';
import { Layers, Plus, Edit2, Trash2, X, Image as ImageIcon } from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { Category, Subcategory } from '../../types';

export const AdminCategories: React.FC = () => {
  const { categories, subcategories, addCategory, updateCategory, deleteCategory, showToast } = useStore();

  const [isCatModalOpen, setIsCatModalOpen] = useState(false);
  const [editingCat, setEditingCat] = useState<Category | null>(null);
  const [catName, setCatName] = useState('');
  const [catImage, setCatImage] = useState('');

  const handleOpenAddCat = () => {
    setEditingCat(null);
    setCatName('');
    setCatImage('https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=300&auto=format&fit=crop&q=80');
    setIsCatModalOpen(true);
  };

  const handleOpenEditCat = (cat: Category) => {
    setEditingCat(cat);
    setCatName(cat.name);
    setCatImage(cat.image);
    setIsCatModalOpen(true);
  };

  const handleSaveCat = (e: React.FormEvent) => {
    e.preventDefault();
    if (!catName.trim()) return;

    if (editingCat) {
      updateCategory(editingCat.id, {
        name: catName.trim(),
        slug: catName.trim().toLowerCase().replace(/\s+/g, '-'),
        image: catImage.trim() || editingCat.image,
      });
      showToast('ক্যাটাগরি আপডেট করা হয়েছে!', 'success');
    } else {
      addCategory({
        name: catName.trim(),
        slug: catName.trim().toLowerCase().replace(/\s+/g, '-'),
        image: catImage.trim() || 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=300&auto=format&fit=crop&q=80',
        status: 1,
      });
      showToast('নতুন ক্যাটাগরি তৈরি হয়েছে!', 'success');
    }
    setIsCatModalOpen(false);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-gray-950">
            ক্যাটাগরি ও সাব-ক্যাটাগরি
          </h1>
          <p className="text-xs text-gray-500">
            পণ্য সাজানোর জন্য ক্যাটাগরি ও মেনু নেভিগেশন নিয়ন্ত্রণ করুন
          </p>
        </div>

        <button
          onClick={handleOpenAddCat}
          className="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer self-start sm:self-auto"
        >
          <Plus className="w-4 h-4" />
          <span>+ নতুন ক্যাটাগরি যোগ করুন</span>
        </button>
      </div>

      {/* Categories Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        {categories.map((cat) => {
          const catSubs = subcategories.filter((s) => s.category_id === cat.id);
          return (
            <div
              key={cat.id}
              className="bg-white rounded-3xl p-5 border border-gray-100 shadow-xs flex flex-col justify-between space-y-4"
            >
              <div className="flex items-center gap-3">
                <img
                  src={cat.image}
                  alt={cat.name}
                  className="w-14 h-14 rounded-2xl object-cover border border-gray-100"
                />
                <div>
                  <h3 className="font-extrabold text-sm text-gray-950">{cat.name}</h3>
                  <p className="text-[11px] text-gray-400 font-mono">/{cat.slug}</p>
                  <span className="text-[10px] bg-red-50 text-red-600 font-bold px-2 py-0.5 rounded-full">
                    {catSubs.length} টি সাব-ক্যাটাগরি
                  </span>
                </div>
              </div>

              {/* Subcategories preview tags */}
              {catSubs.length > 0 && (
                <div className="flex flex-wrap gap-1">
                  {catSubs.map((s) => (
                    <span
                      key={s.id}
                      className="bg-gray-100 text-gray-700 text-[10px] px-2 py-0.5 rounded-md font-medium"
                    >
                      {s.name}
                    </span>
                  ))}
                </div>
              )}

              {/* Actions */}
              <div className="flex items-center justify-between pt-3 border-t border-gray-100 text-xs">
                <button
                  onClick={() =>
                    updateCategory(cat.id, { status: cat.status === 1 ? 0 : 1 })
                  }
                  className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                    cat.status === 1 ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600'
                  }`}
                >
                  {cat.status === 1 ? 'Active' : 'Inactive'}
                </button>

                <div className="flex items-center gap-1">
                  <button
                    onClick={() => handleOpenEditCat(cat)}
                    className="p-1.5 text-gray-500 hover:text-gray-900 rounded-lg hover:bg-gray-100"
                  >
                    <Edit2 className="w-3.5 h-3.5" />
                  </button>
                  <button
                    onClick={() => {
                      if (confirm(`আপনি কি "${cat.name}" ক্যাটাগরিটি মুছে ফেলতে চান?`)) {
                        deleteCategory(cat.id);
                      }
                    }}
                    className="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Category Modal */}
      {isCatModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150">
          <div className="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <h3 className="font-extrabold text-base text-gray-900">
                {editingCat ? 'ক্যাটাগরি এডিট' : 'নতুন ক্যাটাগরি'}
              </h3>
              <button onClick={() => setIsCatModalOpen(false)} className="p-1 text-gray-400 hover:text-gray-600">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleSaveCat} className="space-y-3.5 text-xs">
              <div>
                <label className="block font-bold text-gray-700 mb-1">ক্যাটাগরির নাম *</label>
                <input
                  type="text"
                  required
                  placeholder="যেমন: ফ্যাশন ও পোশাক"
                  value={catName}
                  onChange={(e) => setCatName(e.target.value)}
                  className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-bold outline-hidden focus:border-red-500"
                />
              </div>

              <div>
                <label className="block font-bold text-gray-700 mb-1">ছবির লিঙ্ক (Image URL) *</label>
                <input
                  type="text"
                  required
                  placeholder="https://..."
                  value={catImage}
                  onChange={(e) => setCatImage(e.target.value)}
                  className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
                />
              </div>

              <div className="flex gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setIsCatModalOpen(false)}
                  className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl"
                >
                  বাতিল
                </button>
                <button
                  type="submit"
                  className="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-xl shadow-xs"
                >
                  সেভ করুন
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
