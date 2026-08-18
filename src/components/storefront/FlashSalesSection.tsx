import React, { useState, useEffect } from 'react';
import { Zap, Clock, ChevronRight } from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { ProductCard } from './ProductCard';

export const FlashSalesSection: React.FC = () => {
  const { products } = useStore();
  const flashSaleProducts = products.filter((p) => p.flashsale && p.status === 1);

  // Live Countdown timer
  const [timeLeft, setTimeLeft] = useState({
    hours: 18,
    minutes: 42,
    seconds: 35,
  });

  useEffect(() => {
    const timer = setInterval(() => {
      setTimeLeft((prev) => {
        if (prev.seconds > 0) {
          return { ...prev, seconds: prev.seconds - 1 };
        } else if (prev.minutes > 0) {
          return { ...prev, minutes: 59, seconds: 59 };
        } else if (prev.hours > 0) {
          return { ...prev, hours: prev.hours - 1, minutes: 59, seconds: 59 };
        }
        return { hours: 24, minutes: 0, seconds: 0 };
      });
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  if (flashSaleProducts.length === 0) return null;

  return (
    <section id="flash-sales" className="max-w-7xl mx-auto px-4 sm:px-6 py-6 scroll-mt-24">
      <div className="bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 rounded-3xl p-4 sm:p-6 text-white shadow-lg">
        {/* Header with Countdown Clock */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 border-b border-white/20 pb-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-xs flex items-center justify-center">
              <Zap className="w-6 h-6 fill-amber-300 text-amber-300 animate-bounce" />
            </div>
            <div>
              <h2 className="text-xl sm:text-2xl font-black tracking-tight">
                ফ্ল্যাশ সেল (Flash Deals)
              </h2>
              <p className="text-xs text-white/80">সীমিত সময়ের জন্য বিশেষ মূল্যছাড়</p>
            </div>
          </div>

          {/* Countdown Blocks */}
          <div className="flex items-center gap-2">
            <div className="flex items-center gap-1 text-xs font-semibold mr-1">
              <Clock className="w-4 h-4" />
              <span>সময় বাকি:</span>
            </div>
            <div className="flex items-center gap-1.5 font-mono font-black text-sm">
              <div className="bg-gray-950 text-amber-400 px-2.5 py-1.5 rounded-lg shadow-inner">
                {String(timeLeft.hours).padStart(2, '0')}
              </div>
              <span>:</span>
              <div className="bg-gray-950 text-amber-400 px-2.5 py-1.5 rounded-lg shadow-inner">
                {String(timeLeft.minutes).padStart(2, '0')}
              </div>
              <span>:</span>
              <div className="bg-gray-950 text-amber-400 px-2.5 py-1.5 rounded-lg shadow-inner">
                {String(timeLeft.seconds).padStart(2, '0')}
              </div>
            </div>
          </div>
        </div>

        {/* Flash Sale Product Cards Grid */}
        <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
          {flashSaleProducts.slice(0, 4).map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
      </div>
    </section>
  );
};
