import React, { useState } from 'react';
import { StoreProvider, useStore } from './context/StoreContext';
import { Header } from './components/storefront/Header';
import { Navbar } from './components/storefront/Navbar';
import { HeroBannerSlider } from './components/storefront/HeroBannerSlider';
import { CategoryGrid } from './components/storefront/CategoryGrid';
import { FlashSalesSection } from './components/storefront/FlashSalesSection';
import { HotDealsSection } from './components/storefront/HotDealsSection';
import { ProductCard } from './components/storefront/ProductCard';
import { QuickOrderModal } from './components/storefront/QuickOrderModal';
import { CartDrawer } from './components/storefront/CartDrawer';
import { ProductDetailPage } from './components/storefront/ProductDetailPage';
import { CheckoutPage } from './components/storefront/CheckoutPage';
import { OrderSuccessPage } from './components/storefront/OrderSuccessPage';
import { OrderTrackingPage } from './components/storefront/OrderTrackingPage';
import { CustomerAccountPage } from './components/storefront/CustomerAccountPage';
import { BlogPage, BlogDetailPage } from './components/storefront/BlogPage';
import { ContactPage, ComplaintPage } from './components/storefront/ContactPage';
import { PolicyPage } from './components/storefront/PolicyPage';
import { FloatingContactWidget } from './components/storefront/FloatingContactWidget';
import { Footer } from './components/storefront/Footer';

// Admin Components
import { AdminLayout } from './components/admin/AdminLayout';
import { AdminDashboard } from './components/admin/AdminDashboard';
import { AdminOrders } from './components/admin/AdminOrders';
import { AdminProducts } from './components/admin/AdminProducts';
import { AdminCategories } from './components/admin/AdminCategories';
import { AdminBanners } from './components/admin/AdminBanners';
import { AdminCoupons } from './components/admin/AdminCoupons';
import { AdminFraudSettings } from './components/admin/AdminFraudSettings';
import { AdminReviews } from './components/admin/AdminReviews';
import { AdminComplaints } from './components/admin/AdminComplaints';
import { AdminSettings } from './components/admin/AdminSettings';

import { Sparkles, PackageOpen, AlertCircle, CheckCircle2, Info, X } from 'lucide-react';

const AppContent: React.FC = () => {
  const {
    currentView,
    viewParams,
    navigate,
    products,
    categories,
    selectedCategory,
    setSelectedCategory,
    banners,
    settings,
    toasts,
    removeToast,
  } = useStore();

  const [adminTab, setAdminTab] = useState<string>('dashboard');
  const [productSortBy, setProductSortBy] = useState<'default' | 'price_low' | 'price_high' | 'top_rated'>('default');

  // Handle Admin Portal Mode
  if (currentView === 'admin') {
    return (
      <AdminLayout currentTab={adminTab} onSelectTab={setAdminTab}>
        {adminTab === 'dashboard' && <AdminDashboard onSelectTab={setAdminTab} />}
        {adminTab === 'orders' && <AdminOrders />}
        {adminTab === 'products' && <AdminProducts />}
        {adminTab === 'categories' && <AdminCategories />}
        {adminTab === 'banners' && <AdminBanners />}
        {adminTab === 'coupons' && <AdminCoupons />}
        {adminTab === 'fraud' && <AdminFraudSettings />}
        {adminTab === 'reviews' && <AdminReviews />}
        {adminTab === 'complaints' && <AdminComplaints />}
        {adminTab === 'settings' && <AdminSettings />}
      </AdminLayout>
    );
  }

  // Filter and sort products for catalogue
  const filteredCatalogueProducts = products.filter((p) => {
    if (selectedCategory && p.category_id !== selectedCategory) {
      return false;
    }
    return p.status === 1;
  });

  const sortedCatalogueProducts = [...filteredCatalogueProducts].sort((a, b) => {
    if (productSortBy === 'price_low') return a.new_price - b.new_price;
    if (productSortBy === 'price_high') return b.new_price - a.new_price;
    if (productSortBy === 'top_rated') return b.ratting - a.ratting;
    return 0;
  });

  const activeCategoryObj = categories.find((c) => c.id === selectedCategory);
  const middleBanners = banners.filter((b) => b.position === 'middle_ad' || b.position === 'hero_bottom');

  return (
    <div className="min-h-screen flex flex-col bg-gray-100 font-['Plus_Jakarta_Sans','Hind_Siliguri',sans-serif] text-gray-900 selection:bg-red-500 selection:text-white">
      {/* Toast Notification Container */}
      <div className="fixed top-4 right-4 z-50 space-y-2 max-w-sm w-full pointer-events-none">
        {toasts.map((toast) => (
          <div
            key={toast.id}
            className={`pointer-events-auto px-4 py-3 rounded-2xl shadow-xl flex items-center justify-between gap-2.5 text-xs font-bold border animate-in slide-in-from-top-3 duration-200 ${
              toast.type === 'success'
                ? 'bg-emerald-950 text-emerald-100 border-emerald-800'
                : toast.type === 'error'
                ? 'bg-red-950 text-red-100 border-red-800'
                : 'bg-gray-950 text-gray-100 border-gray-800'
            }`}
          >
            <div className="flex items-center gap-2">
              {toast.type === 'success' && <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />}
              {toast.type === 'error' && <AlertCircle className="w-4 h-4 text-red-400 shrink-0" />}
              {toast.type === 'info' && <Info className="w-4 h-4 text-blue-400 shrink-0" />}
              <span>{toast.message}</span>
            </div>
            <button
              onClick={() => removeToast(toast.id)}
              className="text-gray-400 hover:text-white p-0.5"
            >
              <X className="w-3.5 h-3.5" />
            </button>
          </div>
        ))}
      </div>

      {/* Main Header & Navbar */}
      <Header />
      <Navbar />

      {/* Main Storefront Body Routed Views */}
      <main className="flex-1">
        {/* 1. HOME VIEW */}
        {currentView === 'home' && (
          <div className="space-y-6 sm:space-y-10 pb-8">
            {/* If a category is selected, show filtered view */}
            {selectedCategory ? (
              <div className="max-w-7xl mx-auto px-4 sm:px-6 pt-6 space-y-6 animate-in fade-in duration-150">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-6 rounded-3xl border border-gray-100 shadow-xs">
                  <div>
                    <h1 className="text-xl sm:text-2xl font-black text-gray-950">
                      {activeCategoryObj?.name || 'ক্যাটাগরি পণ্য'}
                    </h1>
                    <p className="text-xs text-gray-500 mt-0.5">
                      মোট {sortedCatalogueProducts.length} টি পণ্য পাওয়া গেছে
                    </p>
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => setSelectedCategory(null)}
                      className="text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-xl transition-colors cursor-pointer"
                    >
                      সকল ক্যাটাগরি দেখুন
                    </button>
                    <select
                      value={productSortBy}
                      onChange={(e) => setProductSortBy(e.target.value as any)}
                      className="bg-gray-50 border border-gray-200 text-xs font-bold rounded-xl px-3 py-2 outline-hidden"
                    >
                      <option value="default">সাজান: ডিফল্ট</option>
                      <option value="price_low">দাম: কম থেকে বেশি</option>
                      <option value="price_high">দাম: বেশি থেকে কম</option>
                      <option value="top_rated">সর্বোচ্চ রেটিং</option>
                    </select>
                  </div>
                </div>

                {sortedCatalogueProducts.length > 0 ? (
                  <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                    {sortedCatalogueProducts.map((prod) => (
                      <ProductCard key={prod.id} product={prod} />
                    ))}
                  </div>
                ) : (
                  <div className="bg-white rounded-3xl p-12 text-center text-gray-500 border border-gray-100 space-y-2">
                    <PackageOpen className="w-10 h-10 text-gray-300 mx-auto" />
                    <p className="text-sm font-bold">এই ক্যাটাগরিতে কোনো পণ্য পাওয়া যায়নি!</p>
                  </div>
                )}
              </div>
            ) : (
              <>
                {/* Hero Banner Carousel Slider */}
                <HeroBannerSlider />

                {/* Visual Category Grid */}
                <CategoryGrid />

                {/* Flash Sales with Countdown Timer */}
                <FlashSalesSection />

                {/* Middle Promo Banners if any */}
                {middleBanners.length > 0 && (
                  <div className="max-w-7xl mx-auto px-4 sm:px-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {middleBanners.map((mb) => (
                        <div
                          key={mb.id}
                          className="relative rounded-3xl overflow-hidden shadow-xs hover:shadow-md transition-shadow group aspect-[21/9]"
                        >
                          <img
                            src={mb.image}
                            alt={mb.title || ''}
                            className="w-full h-full object-cover group-hover:scale-103 transition-transform duration-500"
                          />
                          <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent p-6 flex flex-col justify-end text-white">
                            <h3 className="font-black text-lg sm:text-xl">{mb.title}</h3>
                            {mb.subtitle && <p className="text-xs text-gray-200 mt-1">{mb.subtitle}</p>}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Hot Deals & Trending Products */}
                <HotDealsSection />

                {/* All Products Catalogue */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 space-y-4">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-200/80 pb-3">
                    <div>
                      <h2 className="text-lg sm:text-2xl font-black text-gray-950 flex items-center gap-2">
                        <span>সকল পণ্যসম্ভার (All Products)</span>
                      </h2>
                      <p className="text-xs text-gray-500">আপনার পছন্দের সেরা প্রোডাক্টটি বেছে নিন</p>
                    </div>

                    <div className="flex items-center gap-2">
                      <select
                        value={productSortBy}
                        onChange={(e) => setProductSortBy(e.target.value as any)}
                        className="bg-white border border-gray-200 text-xs font-bold rounded-xl px-3 py-2 outline-hidden shadow-2xs"
                      >
                        <option value="default">সাজান: ডিফল্ট</option>
                        <option value="price_low">দাম: কম থেকে বেশি</option>
                        <option value="price_high">দাম: বেশি থেকে কম</option>
                        <option value="top_rated">সর্বোচ্চ রেটিং</option>
                      </select>
                    </div>
                  </div>

                  <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                    {sortedCatalogueProducts.map((prod) => (
                      <ProductCard key={prod.id} product={prod} />
                    ))}
                  </div>
                </div>
              </>
            )}
          </div>
        )}

        {/* 2. PRODUCT DETAILS VIEW */}
        {currentView === 'product_details' && (
          <ProductDetailPage productId={viewParams?.productId || 1} />
        )}

        {/* 3. CHECKOUT VIEW */}
        {currentView === 'checkout' && <CheckoutPage />}

        {/* 4. ORDER SUCCESS VIEW */}
        {currentView === 'order_success' && (
          <OrderSuccessPage orderId={viewParams?.orderId} />
        )}

        {/* 5. ORDER TRACKING VIEW */}
        {currentView === 'order_track' && (
          <OrderTrackingPage orderId={viewParams?.orderId} phone={viewParams?.phone} />
        )}

        {/* 6. CUSTOMER ACCOUNT VIEW */}
        {currentView === 'customer_account' && (
          <CustomerAccountPage initialTab={viewParams?.tab} />
        )}

        {/* 7. BLOGS VIEW */}
        {currentView === 'blogs' && <BlogPage />}

        {/* 8. BLOG DETAILS VIEW */}
        {currentView === 'blog_details' && (
          <BlogDetailPage blogId={viewParams?.blogId || 1} />
        )}

        {/* 9. CONTACT VIEW */}
        {currentView === 'contact' && <ContactPage />}

        {/* 10. COMPLAINT VIEW */}
        {currentView === 'complaint' && <ComplaintPage />}

        {/* 11. POLICY VIEW */}
        {currentView === 'policy' && <PolicyPage type={viewParams?.type} />}
      </main>

      {/* Global Quick Order Modal & Cart Drawer & Floating Contact */}
      <QuickOrderModal />
      <CartDrawer />
      <FloatingContactWidget />

      {/* Store Footer */}
      <Footer />
    </div>
  );
};

export default function App() {
  return (
    <StoreProvider>
      <AppContent />
    </StoreProvider>
  );
}
