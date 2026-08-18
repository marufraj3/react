import React, { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight, Truck, ShieldCheck, RefreshCw, Headphones } from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const HeroBannerSlider: React.FC = () => {
  const { banners, navigate } = useStore();
  const heroBanners = banners.filter((b) => b.position === 'hero' && b.status === 1);

  const [currentSlide, setCurrentSlide] = useState(0);

  // Auto slide interval
  useEffect(() => {
    if (heroBanners.length <= 1) return;
    const interval = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % heroBanners.length);
    }, 5000);
    return () => clearInterval(interval);
  }, [heroBanners.length]);

  const handlePrev = () => {
    setCurrentSlide((prev) => (prev === 0 ? heroBanners.length - 1 : prev - 1));
  };

  const handleNext = () => {
    setCurrentSlide((prev) => (prev + 1) % heroBanners.length);
  };

  if (heroBanners.length === 0) return null;

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 pt-4 pb-2">
      <div className="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {/* Main Hero Slider (3 cols on lg) */}
        <div className="lg:col-span-3 relative rounded-2xl overflow-hidden shadow-sm bg-gray-900 aspect-[16/8] sm:aspect-[16/7] lg:aspect-[16/6.8] group">
          {heroBanners.map((banner, index) => (
            <div
              key={banner.id}
              className={`absolute inset-0 transition-opacity duration-700 ease-in-out ${
                index === currentSlide ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'
              }`}
            >
              <img
                src={banner.image}
                alt={banner.title || 'Hero Banner'}
                className="w-full h-full object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-r from-gray-950/80 via-gray-950/40 to-transparent flex items-center p-6 sm:p-10">
                <div className="max-w-md text-white space-y-2 sm:space-y-3">
                  {banner.subtitle && (
                    <span className="inline-block bg-red-600 text-white text-[11px] sm:text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md shadow-sm">
                      {banner.subtitle}
                    </span>
                  )}
                  {banner.title && (
                    <h2 className="text-xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight leading-tight drop-shadow-sm">
                      {banner.title}
                    </h2>
                  )}
                  <div>
                    <button
                      onClick={() => {
                        const el = document.getElementById('products');
                        if (el) el.scrollIntoView({ behavior: 'smooth' });
                      }}
                      className="mt-2 bg-white hover:bg-gray-100 text-gray-950 font-bold text-xs sm:text-sm px-5 py-2.5 rounded-xl transition-transform active:scale-95 shadow-lg inline-flex items-center gap-2 cursor-pointer"
                    >
                      <span>এখনই কেনাকাটা করুন</span>
                      <ChevronRight className="w-4 h-4 text-red-600" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}

          {/* Slider Controls */}
          {heroBanners.length > 1 && (
            <>
              <button
                onClick={handlePrev}
                className="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-white/70 hover:bg-white text-gray-900 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-md"
                aria-label="Previous slide"
              >
                <ChevronLeft className="w-5 h-5" />
              </button>
              <button
                onClick={handleNext}
                className="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-white/70 hover:bg-white text-gray-900 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-md"
                aria-label="Next slide"
              >
                <ChevronRight className="w-5 h-5" />
              </button>

              {/* Dots */}
              <div className="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 bg-black/30 backdrop-blur-xs px-2.5 py-1 rounded-full">
                {heroBanners.map((_, idx) => (
                  <button
                    key={idx}
                    onClick={() => setCurrentSlide(idx)}
                    className={`h-2 rounded-full transition-all ${
                      idx === currentSlide ? 'w-6 bg-red-600' : 'w-2 bg-white/60 hover:bg-white'
                    }`}
                    aria-label={`Go to slide ${idx + 1}`}
                  />
                ))}
              </div>
            </>
          )}
        </div>

        {/* Side Promo Card / Features (1 col on lg) */}
        <div className="hidden lg:flex flex-col justify-between gap-3">
          <div className="bg-gradient-to-br from-red-600 to-rose-700 rounded-2xl p-5 text-white flex flex-col justify-between flex-1 shadow-sm">
            <div>
              <span className="text-[10px] uppercase font-bold tracking-wider bg-white/20 px-2 py-0.5 rounded text-white">
                বিশেষ ধামাকা
              </span>
              <h3 className="text-lg font-extrabold mt-2 leading-snug">
                ডিজিটাল ও লাইফস্টাইল গ্যাজেট মেগা সেল!
              </h3>
              <p className="text-xs text-red-100 mt-1">
                সেরা দাম ও ক্যাশ অন ডেলিভারি সুবিধা
              </p>
            </div>
            <button
              onClick={() => {
                const el = document.getElementById('flash-sales');
                if (el) el.scrollIntoView({ behavior: 'smooth' });
              }}
              className="mt-3 w-full bg-white text-red-700 font-bold text-xs py-2 rounded-xl text-center hover:bg-red-50 transition-colors shadow-sm"
            >
              অফার দেখুন
            </button>
          </div>

          {/* Quick Trust badges */}
          <div className="bg-white rounded-2xl p-4 border border-gray-100 shadow-xs space-y-2.5">
            <div className="flex items-center gap-2.5 text-xs text-gray-700">
              <div className="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <Truck className="w-4 h-4" />
              </div>
              <span className="font-semibold">দ্রুত ক্যাশ অন ডেলিভারি</span>
            </div>
            <div className="flex items-center gap-2.5 text-xs text-gray-700">
              <div className="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <ShieldCheck className="w-4 h-4" />
              </div>
              <span className="font-semibold">১০০% অথেনটিক প্রোডাক্ট</span>
            </div>
            <div className="flex items-center gap-2.5 text-xs text-gray-700">
              <div className="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <RefreshCw className="w-4 h-4" />
              </div>
              <span className="font-semibold">সহজ রিটার্ন ও রিপ্লেসমেন্ট</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
