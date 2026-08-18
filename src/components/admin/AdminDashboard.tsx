import React from 'react';
import {
  DollarSign,
  ShoppingBag,
  Clock,
  CheckCircle2,
  Package,
  AlertTriangle,
  TrendingUp,
  ArrowUpRight,
  Truck,
  Users,
  Eye
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { OrderStatus } from '../../types';

interface AdminDashboardProps {
  onSelectTab: (tab: string) => void;
}

export const AdminDashboard: React.FC<AdminDashboardProps> = ({ onSelectTab }) => {
  const { orders, products, settings, updateOrderStatus } = useStore();

  // Calculations
  const totalRevenue = orders
    .filter((o) => o.status !== 'cancelled' && o.status !== 'returned')
    .reduce((acc, curr) => acc + curr.total, 0);

  const pendingOrders = orders.filter((o) => o.status === 'pending');
  const confirmedOrders = orders.filter((o) => o.status === 'confirmed');
  const courierOrders = orders.filter((o) => o.status === 'courier');
  const deliveredOrders = orders.filter((o) => o.status === 'delivered');
  const cancelledOrders = orders.filter((o) => o.status === 'cancelled');

  const lowStockProducts = products.filter((p) => p.stock <= 10);
  const recentOrders = [...orders].reverse().slice(0, 8);

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* Page Title */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-gray-950">
            অ্যাডমিন ড্যাশবোর্ড ওভারভিউ
          </h1>
          <p className="text-xs text-gray-500">
            আপনার ই-কমার্স স্টোরের সার্বিক বিক্রয় ও কার্যক্রম রিপোর্ট
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => onSelectTab('orders')}
            className="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
          >
            <ShoppingBag className="w-4 h-4" />
            <span>সকল অর্ডার দেখুন ({orders.length})</span>
          </button>
        </div>
      </div>

      {/* Top 4 Metric KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Total Sales */}
        <div className="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center justify-between">
          <div className="space-y-1">
            <span className="text-xs text-gray-500 font-bold uppercase tracking-wider">মোট বিক্রয় (Revenue)</span>
            <div className="text-xl sm:text-2xl font-black text-gray-950">
              {settings.currency}{totalRevenue.toLocaleString()}
            </div>
            <div className="text-[11px] text-emerald-600 font-bold flex items-center gap-1">
              <TrendingUp className="w-3.5 h-3.5" />
              <span>সফল লেনদেন</span>
            </div>
          </div>
          <div className="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xl">
            ৳
          </div>
        </div>

        {/* Pending Orders */}
        <div
          onClick={() => onSelectTab('orders')}
          className="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center justify-between cursor-pointer hover:border-amber-200 transition-all group"
        >
          <div className="space-y-1">
            <span className="text-xs text-gray-500 font-bold uppercase tracking-wider">নতুন পেন্ডিং অর্ডার</span>
            <div className="text-xl sm:text-2xl font-black text-amber-600">
              {pendingOrders.length} টি
            </div>
            <div className="text-[11px] text-amber-700 font-medium group-hover:underline">
              কনফার্ম করতে ক্লিক করুন →
            </div>
          </div>
          <div className="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
            <Clock className="w-6 h-6" />
          </div>
        </div>

        {/* In Courier / Delivery */}
        <div className="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center justify-between">
          <div className="space-y-1">
            <span className="text-xs text-gray-500 font-bold uppercase tracking-wider">কুরিয়ারে রওয়ানা</span>
            <div className="text-xl sm:text-2xl font-black text-blue-600">
              {courierOrders.length} টি
            </div>
            <div className="text-[11px] text-gray-400 font-medium">
              ডেলিভারি সম্পন্ন: {deliveredOrders.length} টি
            </div>
          </div>
          <div className="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
            <Truck className="w-6 h-6" />
          </div>
        </div>

        {/* Total Products & Stock */}
        <div
          onClick={() => onSelectTab('products')}
          className="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center justify-between cursor-pointer hover:border-red-200 transition-all"
        >
          <div className="space-y-1">
            <span className="text-xs text-gray-500 font-bold uppercase tracking-wider">মোট প্রোডাক্ট সংখ্যা</span>
            <div className="text-xl sm:text-2xl font-black text-gray-950">
              {products.length} টি
            </div>
            <div className="text-[11px] text-red-600 font-bold">
              {lowStockProducts.length > 0 ? `${lowStockProducts.length} টি লো স্টক পণ্য` : 'স্টক সন্তোষজনক'}
            </div>
          </div>
          <div className="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center">
            <Package className="w-6 h-6" />
          </div>
        </div>
      </div>

      {/* Low Stock Warning Banner if any */}
      {lowStockProducts.length > 0 && (
        <div className="p-4 bg-amber-50 border border-amber-200 rounded-3xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-amber-950">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-amber-200 text-amber-900 flex items-center justify-center shrink-0">
              <AlertTriangle className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-xs font-extrabold">স্টক সতর্কতা (Low Stock Alert)!</h4>
              <p className="text-[11px] text-amber-800">
                {lowStockProducts.map((p) => p.name).slice(0, 3).join(', ')} সহ মোট {lowStockProducts.length} টি পণ্যের স্টক ১০ এর নিচে।
              </p>
            </div>
          </div>
          <button
            onClick={() => onSelectTab('products')}
            className="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-3.5 py-1.5 rounded-xl self-start sm:self-auto cursor-pointer"
          >
            স্টক আপডেট করুন
          </button>
        </div>
      )}

      {/* Main Grid: Recent Orders Table (8 cols) & Top Products (4 cols) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Recent Orders (8 cols) */}
        <div className="lg:col-span-8 bg-white rounded-3xl p-5 sm:p-6 border border-gray-100 shadow-xs space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-gray-100">
            <h2 className="text-base font-extrabold text-gray-900 flex items-center gap-2">
              <ShoppingBag className="w-4 h-4 text-red-600" />
              <span>সাম্প্রতিক অর্ডারসমূহ (Recent Orders)</span>
            </h2>
            <button
              onClick={() => onSelectTab('orders')}
              className="text-xs font-bold text-red-600 hover:underline"
            >
              সবগুলো দেখুন →
            </button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead>
                <tr className="border-b border-gray-100 text-gray-400 font-bold">
                  <th className="pb-3 pl-2">অর্ডার আইডি</th>
                  <th className="pb-3">কাস্টমার</th>
                  <th className="pb-3">মোট বিল</th>
                  <th className="pb-3">পেমেন্ট</th>
                  <th className="pb-3">স্ট্যাটাস</th>
                  <th className="pb-3 text-right pr-2">অ্যাকশন</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50 text-gray-700">
                {recentOrders.map((order) => (
                  <tr key={order.id} className="hover:bg-gray-50/80 transition-colors">
                    <td className="py-3 pl-2 font-mono font-bold text-gray-900">
                      {order.id}
                      <div className="text-[10px] text-gray-400 font-normal">{order.created_at}</div>
                    </td>
                    <td className="py-3">
                      <div className="font-bold text-gray-900">{order.customer_name}</div>
                      <div className="text-[10px] text-gray-400 font-mono">{order.customer_phone}</div>
                    </td>
                    <td className="py-3 font-extrabold text-gray-950">
                      {settings.currency}{order.total.toLocaleString()}
                    </td>
                    <td className="py-3 uppercase font-bold text-[10px] text-gray-600">
                      {order.payment_method}
                    </td>
                    <td className="py-3">
                      <span
                        className={`px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase ${
                          order.status === 'delivered'
                            ? 'bg-emerald-100 text-emerald-800'
                            : order.status === 'pending'
                            ? 'bg-amber-100 text-amber-800'
                            : order.status === 'courier'
                            ? 'bg-blue-100 text-blue-800'
                            : order.status === 'cancelled'
                            ? 'bg-red-100 text-red-800'
                            : 'bg-purple-100 text-purple-800'
                        }`}
                      >
                        {order.status}
                      </span>
                    </td>
                    <td className="py-3 text-right pr-2">
                      <select
                        value={order.status}
                        onChange={(e) => updateOrderStatus(order.id, e.target.value as OrderStatus)}
                        className="bg-gray-50 border border-gray-200 text-[11px] font-bold rounded-lg px-2 py-1 outline-hidden focus:border-red-500"
                      >
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="processing">Processing</option>
                        <option value="courier">Courier</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                      </select>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Top Products & Quick Links (4 cols) */}
        <div className="lg:col-span-4 space-y-6">
          {/* Top Selling Products */}
          <div className="bg-white rounded-3xl p-5 sm:p-6 border border-gray-100 shadow-xs space-y-4">
            <h3 className="text-base font-extrabold text-gray-900 pb-2 border-b border-gray-100">
              জনপ্রিয় প্রোডাক্টসমূহ
            </h3>
            <div className="space-y-3">
              {products.slice(0, 5).map((p) => (
                <div key={p.id} className="flex items-center gap-3">
                  <img
                    src={p.image}
                    alt={p.name}
                    className="w-10 h-10 rounded-xl object-cover border border-gray-100 shrink-0"
                  />
                  <div className="flex-1 min-w-0">
                    <h4 className="text-xs font-bold text-gray-900 truncate">{p.name}</h4>
                    <span className="text-[11px] text-gray-400 font-mono">
                      স্টক: {p.stock} টি | {settings.currency}{p.new_price}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Quick Admin Actions */}
          <div className="bg-gradient-to-br from-gray-900 to-gray-950 text-white rounded-3xl p-6 shadow-sm space-y-3">
            <h3 className="text-sm font-extrabold text-white">দ্রুত লিঙ্ক (Quick Action)</h3>
            <div className="space-y-2 text-xs">
              <button
                onClick={() => onSelectTab('products')}
                className="w-full bg-gray-800 hover:bg-gray-700 text-left px-3.5 py-2.5 rounded-xl font-bold transition-colors flex items-center justify-between"
              >
                <span>+ নতুন পণ্য যুক্ত করুন</span>
                <ArrowUpRight className="w-3.5 h-3.5" />
              </button>
              <button
                onClick={() => onSelectTab('coupons')}
                className="w-full bg-gray-800 hover:bg-gray-700 text-left px-3.5 py-2.5 rounded-xl font-bold transition-colors flex items-center justify-between"
              >
                <span>+ নতুন কুপন তৈরি করুন</span>
                <ArrowUpRight className="w-3.5 h-3.5" />
              </button>
              <button
                onClick={() => onSelectTab('fraud')}
                className="w-full bg-gray-800 hover:bg-gray-700 text-left px-3.5 py-2.5 rounded-xl font-bold transition-colors flex items-center justify-between"
              >
                <span>🛡️ ফ্রড প্রিভেনশন ও ব্ল্যাকলিস্ট</span>
                <ArrowUpRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
