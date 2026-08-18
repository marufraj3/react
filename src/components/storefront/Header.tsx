import React, { useState, useRef, useEffect } from 'react';
import {
  Phone,
  Search,
  ShoppingBag,
  Heart,
  User,
  ShieldCheck,
  Truck,
  Sparkles,
  X,
  ArrowRight,
  Menu,
  SlidersHorizontal,
  ChevronDown
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';

interface HeaderProps {
  onOpenMobileNav: () => void;
}

export const Header: React.FC<HeaderProps> = ({ onOpenMobileNav }) => {
  const {
    settings,
    products,
    categories,
    cartTotalItems,
    cartSubtotal,
    wishlist,
    searchQuery,
    setSearchQuery,
    navigate,
    setIsCartDrawerOpen,
    setSelectedCategory,
    authUser,
    isAuthenticated,
    openAuthModal,
    logout,
  } = useStore();

  const [isSearchFocused, setIsSearchFocused] = useState(false);
  const [showAccountMenu, setShowAccountMenu] = useState(false);
  const searchContainerRef = useRef<HTMLDivElement>(null);

  // Close search dropdown on click outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (searchContainerRef.current && !searchContainerRef.current.contains(event.target as Node)) {
        setIsSearchFocused(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Filter products for live instant search
  const filteredSearchProducts = searchQuery.trim()
    ? products.filter(
        (p) =>
          p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
          p.product_code.toLowerCase().includes(searchQuery.toLowerCase()) ||
          p.description.toLowerCase().includes(searchQuery.toLowerCase())
      ).slice(0, 6)
    : [];

  return (
    <header className="sticky top-0 z-40 bg-white shadow-xs">
      {/* Top Announcement Bar */}
      {settings.news_ticker_enabled && settings.top_headline && (
        <div className="bg-gray-950 text-white text-xs py-1.5 px-4 overflow-hidden border-b border-gray-800">
          <div className="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div className="flex items-center gap-2 text-amber-400 font-semibold shrink-0">
              <Sparkles className="w-3.5 h-3.5 animate-pulse" />
              <span className="hidden sm:inline">জরুরি বিজ্ঞপ্তি:</span>
            </div>
            <div className="overflow-hidden whitespace-nowrap w-full">
              <div className="inline-block animate-marquee pl-full hover:[animation-play-state:paused] text-gray-200">
                {settings.top_headline}
              </div>
            </div>
            <div className="hidden lg:flex items-center gap-4 text-gray-300 text-xs shrink-0 font-medium">
              <span className="flex items-center gap-1.5">
                <Truck className="w-3.5 h-3.5 text-emerald-400" /> সারা দেশে ক্যাশ অন ডেলিভারি
              </span>
              <span className="text-gray-600">|</span>
              <button
                onClick={() => navigate('order_track')}
                className="text-amber-300 hover:underline flex items-center gap-1 cursor-pointer font-bold"
              >
                অর্ডার ট্র্যাক করুন
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Main Header Container */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3.5">
        <div className="flex items-center justify-between gap-3 sm:gap-6">
          {/* Mobile Menu Toggle & Brand Logo */}
          <div className="flex items-center gap-3">
            <button
              onClick={onOpenMobileNav}
              className="lg:hidden p-2 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
              aria-label="Open Menu"
            >
              <Menu className="w-6 h-6" />
            </button>

            <div
              onClick={() => {
                setSelectedCategory(null);
                navigate('home');
              }}
              className="cursor-pointer flex items-center gap-2 group"
            >
              <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-gray-900 to-red-600 flex items-center justify-center text-white font-bold text-xl shadow-md shadow-red-500/20 group-hover:scale-105 transition-transform">
                G
              </div>
              <div>
                <span className="font-extrabold text-xl sm:text-2xl tracking-tight text-gray-950 font-['Plus_Jakarta_Sans']">
                  Shop<span className="text-red-600">Genie</span>
                </span>
                <span className="hidden sm:block text-[10px] text-gray-500 font-medium -mt-1 tracking-wider uppercase">
                  Bangladeshi Trusted Store
                </span>
              </div>
            </div>
          </div>

          {/* Search Bar with Live Instant Results */}
          <div ref={searchContainerRef} className="flex-1 max-w-2xl relative hidden md:block">
            <div className="relative flex items-center">
              <input
                type="text"
                placeholder="প্রোডাক্টের নাম বা কোড দিয়ে সার্চ করুন (যেমন: Ugreen, Hijab, Shirt)..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                onFocus={() => setIsSearchFocused(true)}
                className="w-full bg-gray-100/90 hover:bg-gray-100 focus:bg-white text-gray-900 text-sm rounded-full pl-5 pr-24 py-2.5 border border-transparent focus:border-red-500 focus:ring-4 focus:ring-red-500/10 transition-all outline-hidden"
              />
              {searchQuery && (
                <button
                  onClick={() => setSearchQuery('')}
                  className="absolute right-12 text-gray-400 hover:text-gray-600 p-1"
                >
                  <X className="w-4 h-4" />
                </button>
              )}
              <button
                onClick={() => {
                  if (searchQuery.trim()) {
                    navigate('home');
                  }
                }}
                className="absolute right-1.5 bg-red-600 hover:bg-red-700 text-white rounded-full p-2 transition-transform active:scale-95 shadow-sm"
                aria-label="Search"
              >
                <Search className="w-4 h-4" />
              </button>
            </div>

            {/* Live Search Popup Dropdown */}
            {isSearchFocused && searchQuery.trim().length > 0 && (
              <div className="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                <div className="p-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between text-xs text-gray-500 font-medium">
                  <span>অনুসন্ধানের ফলাফল ({filteredSearchProducts.length})</span>
                  <span className="text-red-600">খুঁজছেন: "{searchQuery}"</span>
                </div>
                <div className="max-h-96 overflow-y-auto divide-y divide-gray-100">
                  {filteredSearchProducts.length > 0 ? (
                    filteredSearchProducts.map((product) => (
                      <div
                        key={product.id}
                        onClick={() => {
                          setIsSearchFocused(false);
                          navigate('product_details', { productId: product.id });
                        }}
                        className="p-3 hover:bg-red-50/50 cursor-pointer flex items-center gap-3.5 transition-colors group"
                      >
                        <img
                          src={product.image}
                          alt={product.name}
                          className="w-12 h-12 rounded-lg object-cover bg-gray-100 shrink-0 border border-gray-100"
                        />
                        <div className="flex-1 min-w-0">
                          <h4 className="text-sm font-semibold text-gray-900 truncate group-hover:text-red-600">
                            {product.name}
                          </h4>
                          <div className="flex items-center gap-2 mt-0.5">
                            <span className="text-sm font-bold text-red-600">
                              {settings.currency}{product.new_price.toLocaleString()}
                            </span>
                            {product.old_price && (
                              <span className="text-xs text-gray-400 line-through">
                                {settings.currency}{product.old_price.toLocaleString()}
                              </span>
                            )}
                            <span className="text-[11px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-mono">
                              {product.product_code}
                            </span>
                          </div>
                        </div>
                        <ArrowRight className="w-4 h-4 text-gray-300 group-hover:text-red-600 transition-colors" />
                      </div>
                    ))
                  ) : (
                    <div className="p-8 text-center text-gray-500">
                      <p className="text-sm">কোনো প্রোডাক্ট পাওয়া যায়নি</p>
                      <p className="text-xs text-gray-400 mt-1">অন্য কোনো নাম দিয়ে চেষ্টা করুন</p>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>

          {/* Right Action Icons: Phone, Wishlist, Account, Cart */}
          <div className="flex items-center gap-2 sm:gap-3">
            {/* Helpline Hotline */}
            <a
              href={`tel:${settings.hotline}`}
              className="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200/60 hover:bg-emerald-100 transition-colors"
            >
              <div className="w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center">
                <Phone className="w-3.5 h-3.5" />
              </div>
              <div className="text-left">
                <div className="text-[10px] uppercase font-bold text-emerald-600 leading-none">হটলাইন সাপোর্ট</div>
                <div className="text-xs font-extrabold text-emerald-950 font-mono">{settings.hotline}</div>
              </div>
            </a>

            {/* Admin Switcher / Quick Access */}
            <button
              onClick={() => navigate('admin')}
              className="flex items-center gap-1.5 text-xs font-bold bg-gray-900 hover:bg-black text-white px-3 py-2 rounded-xl transition-transform active:scale-95 shadow-sm"
              title="Switch to Admin Dashboard"
            >
              <ShieldCheck className="w-4 h-4 text-amber-400" />
              <span className="hidden sm:inline">অ্যাডমিন প্যানেল</span>
            </button>

            {/* Wishlist */}
            <button
              onClick={() => {
                if (wishlist.length > 0) {
                  navigate('customer_account', { tab: 'wishlist' });
                } else {
                  navigate('customer_account', { tab: 'wishlist' });
                }
              }}
              className="relative p-2 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
              title="উইশলিস্ট"
            >
              <Heart className="w-5 h-5" />
              {wishlist.length > 0 && (
                <span className="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                  {wishlist.length}
                </span>
              )}
            </button>

            {/* Account Menu Dropdown */}
            <div className="relative">
              <button
                onClick={() => setShowAccountMenu(!showAccountMenu)}
                className="flex items-center gap-1 p-2 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
              >
                <User className="w-5 h-5" />
                <ChevronDown className="w-3.5 h-3.5 text-gray-400 hidden sm:block" />
              </button>

              {showAccountMenu && (
                <div className="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 z-50 animate-in fade-in duration-150">
                  <div className="px-3.5 py-2 border-b border-gray-100 text-xs font-semibold text-gray-500">
                    {isAuthenticated ? `স্বাগতম, ${authUser?.name?.split(' ')[0]}` : 'কাস্টমার অ্যাকাউন্ট'}
                  </div>

                  {!isAuthenticated ? (
                    <>
                      <button
                        onClick={() => {
                          setShowAccountMenu(false);
                          openAuthModal('login');
                        }}
                        className="w-full text-left px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50"
                      >
                        লগইন / রেজিস্টার
                      </button>
                      <button
                        onClick={() => {
                          setShowAccountMenu(false);
                          openAuthModal('register');
                        }}
                        className="w-full text-left px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50"
                      >
                        নতুন অ্যাকাউন্ট খুলুন
                      </button>
                    </>
                  ) : (
                    <button
                      onClick={() => {
                        setShowAccountMenu(false);
                        navigate('customer_account', { tab: 'orders' });
                      }}
                      className="w-full text-left px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                      আমার অর্ডারসমূহ
                    </button>
                  )}

                  <button
                    onClick={() => {
                      setShowAccountMenu(false);
                      navigate('order_track');
                    }}
                    className="w-full text-left px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50"
                  >
                    অর্ডার ট্র্যাক করুন
                  </button>

                  {isAuthenticated && (
                    <button
                      onClick={() => {
                        setShowAccountMenu(false);
                        navigate('customer_account', { tab: 'downloads' });
                      }}
                      className="w-full text-left px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                      ডিজিটাল ডাউনলোড
                    </button>
                  )}

                  <div className="border-t border-gray-100 my-1"></div>

                  {isAuthenticated && (
                    <button
                      onClick={() => {
                        setShowAccountMenu(false);
                        logout();
                      }}
                      className="w-full text-left px-3.5 py-2 text-sm text-red-600 hover:bg-red-50"
                    >
                      লগআউট
                    </button>
                  )}

                  <button
                    onClick={() => {
                      setShowAccountMenu(false);
                      navigate('complaint');
                    }}
                    className="w-full text-left px-3.5 py-2 text-sm text-red-600 hover:bg-red-50"
                  >
                    অভিযোগ ও সাপোর্ট
                  </button>
                </div>
              )}
            </div>

            {/* Cart Drawer Trigger */}
            <button
              onClick={() => setIsCartDrawerOpen(true)}
              className="flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-700 px-3 py-2 rounded-xl transition-all font-semibold text-xs sm:text-sm cursor-pointer active:scale-95"
            >
              <div className="relative">
                <ShoppingBag className="w-5 h-5 text-red-600" />
                {cartTotalItems > 0 && (
                  <span className="absolute -top-2 -right-2 bg-red-600 text-white text-[10px] font-extrabold w-4 h-4 rounded-full flex items-center justify-center animate-bounce">
                    {cartTotalItems}
                  </span>
                )}
              </div>
              <span className="hidden sm:inline font-bold">
                {settings.currency}{cartSubtotal.toLocaleString()}
              </span>
            </button>
          </div>
        </div>

        {/* Mobile Search Bar */}
        <div className="mt-2.5 md:hidden">
          <div className="relative">
            <input
              type="text"
              placeholder="সার্চ করুন (প্রোডাক্ট বা কোড)..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-gray-100 text-gray-900 text-sm rounded-xl pl-4 pr-10 py-2.5 border border-transparent focus:border-red-500 focus:bg-white transition-all outline-hidden"
            />
            <Search className="w-4 h-4 text-gray-400 absolute right-3.5 top-3" />
          </div>
        </div>
      </div>
    </header>
  );
};
