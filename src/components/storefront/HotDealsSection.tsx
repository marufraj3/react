import React from 'react';
import { Flame, ArrowRight } from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { ProductCard } from './ProductCard';

export const HotDealsSection: React.FC = () => {
  const { products, banners } = useStore();
  const hotDealProducts = products.filter((p) => (p.topsale || p.feature_product) && p.status === 1);
  const hotDealBanner = banners.find((b) => b.position === 'hotdeals' && b.status === 1);

  if (hotDealProducts.length === 0) return null;

  return (
    <section id="hot-deals" className="max-w-7xl mx-auto px-4 sm:px-6 py-6 scroll-mt-24">
      {/* Header */}
      <div className="flex items-center justify-between mb-5">
        <div className="flex items-center gap-2.5">
          <div className="w-8 h-8 rounded-xl bg-red-100 text-red-600 flex items-center justify-center">
            <Flame className="w-5 h-5 fill-red-500 text-red-500 animate-pulse" />
          </div>
          <div>
            <h2 className="text-lg sm:text-xl font-extrabold text-gray-900">
              হট ডিলস ও ট্রেন্ডিং আইটেম
            </h2>
            <p className="text-xs text-gray-500">গ্রাহকদের পছন্দের সেরা বিক্রিত পণ্যসমূহ</p>
          </div>
        </div>
      </div>

      {/* Grid */}
      <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
        {hotDealProducts.slice(0, 4).map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
    </section>
  );
};
