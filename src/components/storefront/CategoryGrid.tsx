import React from 'react';
import { useStore } from '../../context/StoreContext';

export const CategoryGrid: React.FC = () => {
  const { categories, selectedCategory, setSelectedCategory, navigate } = useStore();

  const handleCategoryClick = (catId: number) => {
    setSelectedCategory(selectedCategory === catId ? null : catId);
    navigate('home');
    const el = document.getElementById('products');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <section className="max-w-7xl mx-auto px-4 sm:px-6 py-6">
      <div className="flex items-center justify-between mb-4">
        <div>
          <h3 className="text-lg sm:text-xl font-extrabold text-gray-900">
            টপ ক্যাটাগরি সমূহ
          </h3>
          <p className="text-xs text-gray-500">আপনার প্রয়োজনীয় পণ্য সহজে খুঁজে নিন</p>
        </div>
        {selectedCategory !== null && (
          <button
            onClick={() => setSelectedCategory(null)}
            className="text-xs font-bold text-red-600 hover:underline cursor-pointer"
          >
            সব দেখুন (রিসেট)
          </button>
        )}
      </div>

      {/* Categories Grid */}
      <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-4 lg:grid-cols-8 gap-3 sm:gap-4">
        {categories.map((cat) => {
          const isSelected = selectedCategory === cat.id;

          return (
            <div
              key={cat.id}
              onClick={() => handleCategoryClick(cat.id)}
              className={`cursor-pointer rounded-2xl p-3 sm:p-3.5 flex flex-col items-center text-center transition-all duration-200 group border ${
                isSelected
                  ? 'bg-red-50 border-red-500 shadow-md ring-2 ring-red-500/20'
                  : 'bg-white border-gray-100 hover:border-red-200 hover:shadow-sm'
              }`}
            >
              <div className="relative w-14 h-14 sm:w-16 sm:h-16 rounded-full overflow-hidden mb-2.5 bg-gray-50 p-1 border border-gray-100 group-hover:scale-105 transition-transform">
                <img
                  src={cat.image}
                  alt={cat.name}
                  className="w-full h-full object-cover rounded-full"
                />
                <div className="absolute inset-0 bg-black/10 rounded-full group-hover:bg-transparent transition-colors" />
              </div>
              <h4
                className={`text-xs font-bold line-clamp-2 leading-tight ${
                  isSelected ? 'text-red-600 font-extrabold' : 'text-gray-800 group-hover:text-red-600'
                }`}
              >
                {cat.name}
              </h4>
              <span className="text-[10px] text-gray-400 mt-1">
                {cat.product_count || 5}+ আইটেম
              </span>
            </div>
          );
        })}
      </div>
    </section>
  );
};
