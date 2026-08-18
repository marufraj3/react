import React, { useState } from 'react';
import {
  LayoutDashboard,
  ShoppingBag,
  Package,
  Layers,
  Image as ImageIcon,
  Tag,
  Truck,
  ShieldAlert,
  Star,
  MessageSquare,
  Settings,
  Store,
  Menu,
  X,
  Bell,
  LogOut,
  ChevronRight,
  Sparkles,
  AlertTriangle
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';

interface AdminLayoutProps {
  currentTab: string;
  onSelectTab: (tab: string) => void;
  children: React.ReactNode;
}

export const AdminLayout: React.FC<AdminLayoutProps> = ({ currentTab, onSelectTab, children }) => {
  const { settings, orders, products, complaints, contactMessages, navigate } = useStore();
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);

  const pendingOrdersCount = orders.filter((o) => o.status === 'pending').length;
  const unreadComplaintsCount = complaints.filter((c) => c.status === 'open').length;
  const unreadMsgCount = contactMessages.filter((m) => !m.is_read).length;
  const lowStockCount = products.filter((p) => p.stock < 10).length;

  const navItems = [
    { id: 'dashboard', label: 'ড্যাশবোর্ড (Overview)', icon: LayoutDashboard },
    {
      id: 'orders',
      label: 'অর্ডার ম্যানেজমেন্ট',
      icon: ShoppingBag,
      badge: pendingOrdersCount > 0 ? pendingOrdersCount : undefined,
      badgeColor: 'bg-red-600',
    },
    {
      id: 'products',
      label: 'প্রোডাক্টস ও স্টক',
      icon: Package,
      badge: lowStockCount > 0 ? `${lowStockCount} Low` : undefined,
      badgeColor: 'bg-amber-600',
    },
    { id: 'categories', label: 'ক্যাটাগরি ও সাব-ক্যাটাগরি', icon: Layers },
    { id: 'banners', label: 'ব্যানার ও স্লাইডার', icon: ImageIcon },
    { id: 'coupons', label: 'কুপন ও ডিসকাউন্ট', icon: Tag },
    { id: 'fraud', label: 'ফ্রড ও অর্ডার লিমিট', icon: ShieldAlert },
    { id: 'reviews', label: 'কাস্টমার রিভিউ', icon: Star },
    {
      id: 'complaints',
      label: 'অভিযোগ ও মেসেজ',
      icon: MessageSquare,
      badge: unreadComplaintsCount + unreadMsgCount > 0 ? unreadComplaintsCount + unreadMsgCount : undefined,
      badgeColor: 'bg-blue-600',
    },
    { id: 'settings', label: 'দোকান সেটিংস', icon: Settings },
  ];

  return (
    <div className="min-h-screen bg-gray-100 flex flex-col font-['Plus_Jakarta_Sans','Hind_Siliguri',sans-serif]">
      {/* Admin Topbar */}
      <header className="bg-gray-950 text-white h-16 sticky top-0 z-40 px-4 sm:px-6 flex items-center justify-between border-b border-gray-800 shadow-md">
        <div className="flex items-center gap-3">
          <button
            onClick={() => setIsSidebarOpen(!isSidebarOpen)}
            className="lg:hidden p-2 rounded-xl text-gray-400 hover:text-white hover:bg-gray-800"
          >
            {isSidebarOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>

          <div
            onClick={() => navigate('home')}
            className="cursor-pointer flex items-center gap-2"
          >
            <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-red-600 to-rose-600 flex items-center justify-center text-white font-black text-lg shadow-sm">
              G
            </div>
            <div>
              <span className="font-black text-lg tracking-tight text-white">
                Shop<span className="text-red-500">Genie</span>
              </span>
              <span className="ml-2 text-[10px] bg-red-600/30 text-red-400 font-bold px-2 py-0.5 rounded-full border border-red-500/30 uppercase">
                Admin Portal
              </span>
            </div>
          </div>
        </div>

        {/* Right Topbar Actions */}
        <div className="flex items-center gap-3">
          {/* View Storefront Link */}
          <button
            onClick={() => navigate('home')}
            className="flex items-center gap-1.5 text-xs font-bold bg-gray-900 hover:bg-gray-800 text-gray-200 px-3.5 py-2 rounded-xl border border-gray-700 transition-colors cursor-pointer"
          >
            <Store className="w-4 h-4 text-emerald-400" />
            <span className="hidden sm:inline">স্টোরফ্রন্ট দেখুন</span>
          </button>

          {/* Admin Avatar */}
          <div className="flex items-center gap-2 pl-2 border-l border-gray-800">
            <div className="w-8 h-8 rounded-xl bg-red-600 text-white font-bold flex items-center justify-center text-xs">
              AD
            </div>
            <div className="hidden md:block text-left text-xs">
              <div className="font-bold text-white leading-none">Super Admin</div>
              <div className="text-[10px] text-gray-400 font-mono">admin@shopgenie.bd</div>
            </div>
          </div>
        </div>
      </header>

      {/* Admin Body */}
      <div className="flex-1 flex overflow-hidden">
        {/* Sidebar Navigation */}
        <aside
          className={`fixed inset-y-0 left-0 pt-16 z-30 w-64 bg-gray-950 text-gray-400 flex flex-col justify-between border-r border-gray-800 transition-transform duration-200 lg:static lg:translate-x-0 ${
            isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
          }`}
        >
          {/* Nav Items List */}
          <div className="p-3 space-y-1 overflow-y-auto flex-1">
            <div className="px-3 py-2 text-[10px] uppercase font-black text-gray-500 tracking-wider">
              মেইন মেনু (Main Menu)
            </div>

            {navItems.map((item) => {
              const Icon = item.icon;
              const isActive = currentTab === item.id;

              return (
                <button
                  key={item.id}
                  onClick={() => {
                    onSelectTab(item.id);
                    setIsSidebarOpen(false);
                  }}
                  className={`w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                    isActive
                      ? 'bg-red-600 text-white shadow-md shadow-red-600/20'
                      : 'text-gray-400 hover:text-white hover:bg-gray-900'
                  }`}
                >
                  <div className="flex items-center gap-2.5">
                    <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-gray-400'}`} />
                    <span>{item.label}</span>
                  </div>
                  {item.badge && (
                    <span
                      className={`text-[10px] text-white px-2 py-0.5 rounded-full font-extrabold ${item.badgeColor}`}
                    >
                      {item.badge}
                    </span>
                  )}
                </button>
              );
            })}
          </div>

          {/* Sidebar Footer */}
          <div className="p-4 border-t border-gray-850 bg-gray-950/80 text-xs">
            <div className="flex items-center justify-between text-gray-400">
              <span className="text-[11px]">Shop Genie v2.6.0</span>
              <button
                onClick={() => navigate('home')}
                className="text-red-400 hover:text-red-300 flex items-center gap-1 font-semibold"
              >
                <LogOut className="w-3.5 h-3.5" />
                <span>প্রস্থান</span>
              </button>
            </div>
          </div>
        </aside>

        {/* Backdrop for mobile */}
        {isSidebarOpen && (
          <div
            className="fixed inset-0 bg-black/60 z-20 lg:hidden"
            onClick={() => setIsSidebarOpen(false)}
          />
        )}

        {/* Main Content Area */}
        <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
          <div className="max-w-7xl mx-auto space-y-6">{children}</div>
        </main>
      </div>
    </div>
  );
};
