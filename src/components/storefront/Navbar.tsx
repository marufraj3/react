import React, { useState } from 'react';
import {
  LayoutGrid,
  Zap,
  Flame,
  BookOpen,
  MapPin,
  X,
  ChevronRight,
  Phone,
  Mail,
  ShieldCheck,
  Tag
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';

interface NavbarProps {
  isMobileNavOpen: boolean;
  onCloseMobileNav: () => void;
}

export const Navbar: React.FC<NavbarProps> = ({ isMobileNavOpen, onCloseMobileNav }) => {
  const {
    categories,
    subcategories,
    selectedCategory,
    setSelectedCategory,
    navigate,
    settings,
    adminUrl
  } = useStore();

  const [hoveredCatId, setHoveredCatId] = useState<number | null>(null);

  const handleCategorySelect = (catId: number | null) => {
    setSelectedCategory(catId);
    navigate('home');
    onCloseMobileNav();
  };

  return (
    <>
      {/* Desktop Horizontal Navbar */}
      <nav className="bg-white border-y border-gray-100 hidden lg:block sticky top-[73px] z-30 shadow-2xs">
        <div className="max-w-7xl mx-auto px-4 sm:px-6">
          <div className="flex items-center justify-between">
            {/* Category Dropdown Navigation */}
            <div className="flex items-center gap-1">
              {/* All Categories Button */}
              <button
                onClick={() => handleCategorySelect(null)}
                className={`flex items-center gap-2 px-4 py-3 font-bold text-sm transition-colors border-b-2 ${
                  selectedCategory === null
                    ? 'border-red-600 text-red-600'
                    : 'border-transparent text-gray-700 hover:text-gray-950'
                }`}
              >
                <LayoutGrid className="w-4 h-4" />
                <span>সব ক্যাটাগরি</span>
              </button>

              {/* Main Categories */}
              {categories.map((cat) => {
                const isSelected = selectedCategory === cat.id;
                const catSubs = subcategories.filter((s) => s.category_id === cat.id);

                return (
                  <div
                    key={cat.id}
                    className="relative group"
                    onMouseEnter={() => setHoveredCatId(cat.id)}
                    onMouseLeave={() => setHoveredCatId(null)}
                  >
                    <button
                      onClick={() => handleCategorySelect(cat.id)}
                      className={`flex items-center gap-1.5 px-3 py-3 text-sm font-semibold transition-colors border-b-2 ${
                        isSelected
                          ? 'border-red-600 text-red-600'
                          : 'border-transparent text-gray-700 hover:text-red-600'
                      }`}
                    >
                      <span className="text-base">{cat.icon}</span>
                      <span>{cat.name}</span>
                    </button>

                    {/* Subcategory Dropdown */}
                    {catSubs.length > 0 && hoveredCatId === cat.id && (
                      <div className="absolute top-full left-0 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50 animate-in fade-in duration-100">
                        {catSubs.map((sub) => (
                          <button
                            key={sub.id}
                            onClick={() => handleCategorySelect(cat.id)}
                            className="w-full text-left px-4 py-2 text-xs font-medium text-gray-700 hover:bg-red-50 hover:text-red-600 flex items-center justify-between"
                          >
                            <span>{sub.name}</span>
                            <ChevronRight className="w-3 h-3 text-gray-400" />
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>

            {/* Special Quick Action Links */}
            <div className="flex items-center gap-2">
              <button
                onClick={() => {
                  setSelectedCategory(null);
                  navigate('home');
                  const el = document.getElementById('flash-sales');
                  if (el) el.scrollIntoView({ behavior: 'smooth' });
                }}
                className="flex items-center gap-1.5 text-xs font-bold text-amber-600 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-full transition-colors"
              >
                <Zap className="w-3.5 h-3.5 fill-amber-500 text-amber-500 animate-bounce" />
                <span>ফ্ল্যাশ সেল</span>
              </button>

              <button
                onClick={() => {
                  setSelectedCategory(null);
                  navigate('home');
                  const el = document.getElementById('hot-deals');
                  if (el) el.scrollIntoView({ behavior: 'smooth' });
                }}
                className="flex items-center gap-1.5 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-full transition-colors"
              >
                <Flame className="w-3.5 h-3.5 fill-red-500 text-red-500" />
                <span>হট ডিলস</span>
              </button>

              <button
                onClick={() => navigate('blogs')}
                className="flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 px-2.5 py-1.5 transition-colors"
              >
                <BookOpen className="w-3.5 h-3.5 text-gray-500" />
                <span>ব্লগ</span>
              </button>
            </div>
          </div>
        </div>
      </nav>

      {/* Mobile Drawer Menu */}
      {isMobileNavOpen && (
        <div className="fixed inset-0 z-50 lg:hidden flex">
          {/* Backdrop */}
          <div
            className="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
            onClick={onCloseMobileNav}
          />

          {/* Drawer Content */}
          <div className="relative w-4/5 max-w-xs bg-white h-full shadow-2xl flex flex-col z-10 animate-in slide-in-from-left duration-200">
            {/* Drawer Header */}
            <div className="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
              <div className="flex items-center gap-2">
                <div className="w-8 h-8 rounded-lg bg-red-600 text-white flex items-center justify-center font-bold text-lg">
                  G
                </div>
                <span className="font-extrabold text-lg text-gray-900">Shop Genie</span>
              </div>
              <button
                onClick={onCloseMobileNav}
                className="p-1.5 rounded-lg text-gray-500 hover:bg-gray-200"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Drawer Categories List */}
            <div className="flex-1 overflow-y-auto p-4 divide-y divide-gray-100">
              <div className="pb-3">
                <div className="text-xs uppercase font-bold text-gray-400 mb-2 px-1">ক্যাটাগরি সমূহ</div>
                <button
                  onClick={() => handleCategorySelect(null)}
                  className={`w-full text-left px-3 py-2.5 rounded-xl font-bold text-sm flex items-center justify-between ${
                    selectedCategory === null ? 'bg-red-50 text-red-600' : 'text-gray-800 hover:bg-gray-50'
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <LayoutGrid className="w-4 h-4 text-red-600" />
                    <span>সব প্রোডাক্ট</span>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-400" />
                </button>

                {categories.map((cat) => {
                  const isSelected = selectedCategory === cat.id;
                  return (
                    <button
                      key={cat.id}
                      onClick={() => handleCategorySelect(cat.id)}
                      className={`w-full text-left px-3 py-2.5 rounded-xl font-semibold text-sm flex items-center justify-between ${
                        isSelected ? 'bg-red-50 text-red-600 font-bold' : 'text-gray-800 hover:bg-gray-50'
                      }`}
                    >
                      <div className="flex items-center gap-2">
                        <span>{cat.icon}</span>
                        <span className="truncate">{cat.name}</span>
                      </div>
                      <ChevronRight className="w-4 h-4 text-gray-400" />
                    </button>
                  );
                })}
              </div>

              {/* Drawer Secondary Links */}
              <div className="py-3 space-y-1">
                <div className="text-xs uppercase font-bold text-gray-400 mb-2 px-1">কুইক লিংক</div>
                <button
                  onClick={() => {
                    navigate('order_track');
                    onCloseMobileNav();
                  }}
                  className="w-full text-left px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg flex items-center gap-2"
                >
                  <MapPin className="w-4 h-4 text-emerald-600" />
                  <span>অর্ডার ট্র্যাকিং</span>
                </button>
                <button
                  onClick={() => {
                    navigate('blogs');
                    onCloseMobileNav();
                  }}
                  className="w-full text-left px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg flex items-center gap-2"
                >
                  <BookOpen className="w-4 h-4 text-blue-600" />
                  <span>আমাদের ব্লগ</span>
                </button>
                <button
                  onClick={() => {
                    navigate('complaint');
                    onCloseMobileNav();
                  }}
                  className="w-full text-left px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg flex items-center gap-2"
                >
                  <ShieldCheck className="w-4 h-4 text-amber-600" />
                  <span>অভিযোগ ও সাপোর্ট</span>
                </button>
                <button
                  onClick={() => {
                    if (adminUrl) {
                      window.open(adminUrl, '_blank', 'noopener');
                    } else {
                      navigate('admin');
                    }
                    onCloseMobileNav();
                  }}
                  className="w-full text-left px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-50 rounded-lg flex items-center gap-2"
                >
                  <ShieldCheck className="w-4 h-4 text-red-600" />
                  <span>অ্যাডমিন ড্যাশবোর্ড</span>
                </button>
              </div>
            </div>

            {/* Drawer Footer Contact */}
            <div className="p-4 bg-gray-50 border-t border-gray-100 text-xs text-gray-600">
              <a
                href={`tel:${settings.hotline}`}
                className="flex items-center gap-2 font-bold text-gray-900 mb-1"
              >
                <Phone className="w-3.5 h-3.5 text-emerald-600" />
                <span>হটলাইন: {settings.hotline}</span>
              </a>
              <div className="flex items-center gap-2 text-gray-500">
                <Mail className="w-3.5 h-3.5" />
                <span className="truncate">{settings.email}</span>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
};
