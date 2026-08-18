import React, { useState } from 'react';
import {
  ShoppingBag,
  Search,
  Filter,
  Eye,
  Truck,
  CheckCircle2,
  Printer,
  Trash2,
  X,
  User,
  Phone,
  MapPin,
  FileText,
  AlertCircle,
  Clock,
  Sparkles
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { Order, OrderStatus } from '../../types';

export const AdminOrders: React.FC = () => {
  const { orders, updateOrderStatus, updateOrderCourier, deleteOrder, settings, showToast } = useStore();

  const [activeStatusFilter, setActiveStatusFilter] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [courierModalOrder, setCourierModalOrder] = useState<Order | null>(null);

  // Courier Assign State
  const [courierName, setCourierName] = useState('Steadfast Courier');
  const [courierTrackingId, setCourierTrackingId] = useState('');

  // Filter logic
  const filteredOrders = orders.filter((order) => {
    if (activeStatusFilter !== 'all' && order.status !== activeStatusFilter) {
      return false;
    }
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase().trim();
      const matchId = order.id.toLowerCase().includes(q);
      const matchName = order.customer_name.toLowerCase().includes(q);
      const matchPhone = order.customer_phone.includes(q);
      if (!matchId && !matchName && !matchPhone) return false;
    }
    return true;
  });

  const handleOpenCourierModal = (order: Order) => {
    setCourierModalOrder(order);
    setCourierName(order.courier_name || 'Steadfast Courier');
    setCourierTrackingId(order.courier_tracking_id || `SF-${Math.floor(100000 + Math.random() * 900000)}`);
  };

  const handleSaveCourier = (e: React.FormEvent) => {
    e.preventDefault();
    if (!courierModalOrder) return;
    updateOrderCourier(courierModalOrder.id, courierName, courierTrackingId);
    updateOrderStatus(courierModalOrder.id, 'courier');
    setCourierModalOrder(null);
    showToast(`অর্ডার ${courierModalOrder.id} কুরিয়ারে পাঠানো হয়েছে!`, 'success');
  };

  const handlePrintInvoice = () => {
    window.print();
  };

  const statusList: { id: string; label: string; count: number }[] = [
    { id: 'all', label: 'সকল অর্ডার', count: orders.length },
    { id: 'pending', label: 'পেন্ডিং (Pending)', count: orders.filter((o) => o.status === 'pending').length },
    { id: 'confirmed', label: 'কনফার্মড (Confirmed)', count: orders.filter((o) => o.status === 'confirmed').length },
    { id: 'processing', label: 'প্রসেসিং (Processing)', count: orders.filter((o) => o.status === 'processing').length },
    { id: 'courier', label: 'কুরিয়ারে (Courier)', count: orders.filter((o) => o.status === 'courier').length },
    { id: 'delivered', label: 'ডেলিভার্ড (Delivered)', count: orders.filter((o) => o.status === 'delivered').length },
    { id: 'cancelled', label: 'বাতিল (Cancelled)', count: orders.filter((o) => o.status === 'cancelled').length },
  ];

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-gray-950">
            অর্ডার ম্যানেজমেন্ট ও কুরিয়ার প্রেরণ
          </h1>
          <p className="text-xs text-gray-500">
            গ্রাহকের অর্ডার যাচাই, কুরিয়ার পার্সেল তৈরি ও ইনভয়েস প্রিন্ট
          </p>
        </div>
      </div>

      {/* Filter Status Pills */}
      <div className="flex items-center gap-2 overflow-x-auto pb-1">
        {statusList.map((st) => (
          <button
            key={st.id}
            onClick={() => setActiveStatusFilter(st.id)}
            className={`px-3.5 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition-all flex items-center gap-1.5 cursor-pointer ${
              activeStatusFilter === st.id
                ? 'bg-red-600 text-white shadow-sm'
                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200/80'
            }`}
          >
            <span>{st.label}</span>
            <span
              className={`px-1.5 py-0.2 rounded-full text-[10px] ${
                activeStatusFilter === st.id ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'
              }`}
            >
              {st.count}
            </span>
          </button>
        ))}
      </div>

      {/* Search Input Bar */}
      <div className="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3">
        <Search className="w-4 h-4 text-gray-400 shrink-0" />
        <input
          type="text"
          placeholder="অর্ডার আইডি (ORD-..), কাস্টমার নাম অথবা ফোন নম্বর দিয়ে খুঁজুন..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="flex-1 bg-transparent text-xs sm:text-sm outline-hidden"
        />
        {searchQuery && (
          <button onClick={() => setSearchQuery('')} className="text-xs text-gray-400 hover:text-gray-600">
            ক্লিয়ার
          </button>
        )}
      </div>

      {/* Orders Table */}
      <div className="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-gray-50 border-b border-gray-100 text-gray-500 font-bold">
              <tr>
                <th className="py-3.5 pl-4">অর্ডার আইডি ও তারিখ</th>
                <th className="py-3.5">গ্রাহকের বিবরণ</th>
                <th className="py-3.5">পণ্য ও পরিমাণ</th>
                <th className="py-3.5">মোট বিল</th>
                <th className="py-3.5">পেমেন্ট</th>
                <th className="py-3.5">স্ট্যাটাস</th>
                <th className="py-3.5 text-right pr-4">অ্যাকশন</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 text-gray-700">
              {filteredOrders.length > 0 ? (
                filteredOrders.map((order) => (
                  <tr key={order.id} className="hover:bg-gray-50/80 transition-colors">
                    <td className="py-3 pl-4">
                      <div className="font-mono font-bold text-gray-950 text-xs">{order.id}</div>
                      <div className="text-[10px] text-gray-400 font-mono">{order.created_at}</div>
                    </td>

                    <td className="py-3">
                      <div className="font-bold text-gray-900">{order.customer_name}</div>
                      <div className="font-mono text-[11px] text-gray-500">{order.customer_phone}</div>
                      <div className="text-[10px] text-gray-400 truncate max-w-xs">{order.customer_address}</div>
                    </td>

                    <td className="py-3">
                      <div className="space-y-1">
                        {order.items.map((item, idx) => (
                          <div key={idx} className="flex items-center gap-1.5 text-[11px]">
                            <span className="font-bold text-gray-900 truncate max-w-[140px]">
                              {item.product_name}
                            </span>
                            <span className="text-gray-400">×{item.quantity}</span>
                          </div>
                        ))}
                      </div>
                    </td>

                    <td className="py-3">
                      <div className="font-black text-gray-950 text-sm">
                        {settings.currency}{order.total.toLocaleString()}
                      </div>
                      <div className="text-[10px] text-gray-400">ডেলিভারি: ৳{order.delivery_charge}</div>
                    </td>

                    <td className="py-3">
                      <span className="uppercase font-bold text-[10px] bg-gray-100 px-2 py-0.5 rounded text-gray-700">
                        {order.payment_method}
                      </span>
                      {order.transaction_id && (
                        <div className="text-[10px] text-pink-600 font-mono mt-0.5">
                          Trx: {order.transaction_id}
                        </div>
                      )}
                    </td>

                    <td className="py-3">
                      <select
                        value={order.status}
                        onChange={(e) => updateOrderStatus(order.id, e.target.value as OrderStatus)}
                        className={`font-bold text-[11px] rounded-lg px-2.5 py-1 outline-hidden border ${
                          order.status === 'delivered'
                            ? 'bg-emerald-50 text-emerald-800 border-emerald-300'
                            : order.status === 'pending'
                            ? 'bg-amber-50 text-amber-800 border-amber-300'
                            : order.status === 'courier'
                            ? 'bg-blue-50 text-blue-800 border-blue-300'
                            : order.status === 'cancelled'
                            ? 'bg-red-50 text-red-800 border-red-300'
                            : 'bg-purple-50 text-purple-800 border-purple-300'
                        }`}
                      >
                        <option value="pending">Pending (পেন্ডিং)</option>
                        <option value="confirmed">Confirmed (কনফার্ম)</option>
                        <option value="processing">Processing (প্যাকেজিং)</option>
                        <option value="courier">Courier (কুরিয়ার)</option>
                        <option value="delivered">Delivered (ডেলিভার্ড)</option>
                        <option value="cancelled">Cancelled (বাতিল)</option>
                        <option value="returned">Returned (ফেরত)</option>
                      </select>
                      {order.courier_name && (
                        <div className="text-[10px] text-blue-600 font-medium mt-0.5">
                          {order.courier_name} ({order.courier_tracking_id})
                        </div>
                      )}
                    </td>

                    <td className="py-3 text-right pr-4">
                      <div className="flex items-center justify-end gap-1.5">
                        {/* Send to courier button */}
                        <button
                          onClick={() => handleOpenCourierModal(order)}
                          className="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                          title="কুরিয়ার পার্সেল তৈরি"
                        >
                          <Truck className="w-4 h-4" />
                        </button>

                        {/* View Details / Invoice */}
                        <button
                          onClick={() => setSelectedOrder(order)}
                          className="p-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
                          title="ইনভয়েস ও বিবরণ"
                        >
                          <Eye className="w-4 h-4" />
                        </button>

                        {/* Delete */}
                        <button
                          onClick={() => {
                            if (confirm(`আপনি কি সত্যিই অর্ডার ${order.id} মুছে ফেলতে চান?`)) {
                              deleteOrder(order.id);
                            }
                          }}
                          className="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                          title="মুছুন"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={7} className="py-12 text-center text-gray-400 text-xs">
                    কোনো অর্ডার পাওয়া যায়নি।
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Courier Handover Modal */}
      {courierModalOrder && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150">
          <div className="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <h3 className="font-extrabold text-base text-gray-900 flex items-center gap-2">
                <Truck className="w-5 h-5 text-blue-600" />
                <span>কুরিয়ারে পার্সেল হ্যান্ডওভার ({courierModalOrder.id})</span>
              </h3>
              <button onClick={() => setCourierModalOrder(null)} className="p-1 text-gray-400 hover:text-gray-600">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleSaveCourier} className="space-y-3.5 text-xs">
              <div>
                <label className="block font-bold text-gray-700 mb-1">কুরিয়ার সার্ভিস নির্বাচন করুন *</label>
                <select
                  value={courierName}
                  onChange={(e) => setCourierName(e.target.value)}
                  className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-bold outline-hidden focus:border-blue-500"
                >
                  <option value="Steadfast Courier">Steadfast Courier (স্টেডফাস্ট)</option>
                  <option value="Pathao Courier">Pathao Courier (পাঠাও)</option>
                  <option value="RedX Logistics">RedX Logistics (রেডএক্স)</option>
                  <option value="Paperfly">Paperfly</option>
                  <option value="eCourier">eCourier</option>
                  <option value="Sundarban Courier">সুন্দরবন কুরিয়ার</option>
                </select>
              </div>

              <div>
                <label className="block font-bold text-gray-700 mb-1">কুরিয়ার ট্র্যাকিং / কনসাইনমেন্ট আইডি *</label>
                <input
                  type="text"
                  required
                  placeholder="যেমন: SF-992810"
                  value={courierTrackingId}
                  onChange={(e) => setCourierTrackingId(e.target.value)}
                  className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-blue-500"
                />
              </div>

              <div className="p-3 bg-blue-50 text-blue-900 rounded-xl text-[11px] leading-relaxed">
                এটি সেভ করলে গ্রাহক তার ট্র্যাকিং পেজে কুরিয়ারের নাম ও ট্র্যাকিং নম্বর লাইভ দেখতে পাবেন।
              </div>

              <div className="flex gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setCourierModalOrder(null)}
                  className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl"
                >
                  বাতিল
                </button>
                <button
                  type="submit"
                  className="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl shadow-xs"
                >
                  কুরিয়ার নিশ্চিত করুন
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Invoice & Details Modal */}
      {selectedOrder && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150 overflow-y-auto">
          <div className="bg-white rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
            {/* Header */}
            <div className="flex items-center justify-between border-b border-gray-100 pb-4">
              <div>
                <h3 className="text-lg font-black text-gray-900">
                  অর্ডার ইনভয়েস: <span className="font-mono text-red-600">{selectedOrder.id}</span>
                </h3>
                <p className="text-xs text-gray-400">{selectedOrder.created_at}</p>
              </div>
              <div className="flex items-center gap-2">
                <button
                  onClick={handlePrintInvoice}
                  className="flex items-center gap-1.5 bg-gray-900 text-white text-xs font-bold px-3 py-1.5 rounded-xl"
                >
                  <Printer className="w-3.5 h-3.5" />
                  <span>প্রিন্ট</span>
                </button>
                <button
                  onClick={() => setSelectedOrder(null)}
                  className="p-1.5 text-gray-400 hover:text-gray-700 rounded-lg"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Customer Details */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-gray-50 text-xs text-gray-700">
              <div className="space-y-1">
                <span className="text-[10px] text-gray-400 uppercase font-bold">গ্রাহকের তথ্য</span>
                <div className="font-bold text-gray-900 text-sm">{selectedOrder.customer_name}</div>
                <div className="font-mono">{selectedOrder.customer_phone}</div>
                {selectedOrder.customer_email && <div>{selectedOrder.customer_email}</div>}
              </div>
              <div className="space-y-1">
                <span className="text-[10px] text-gray-400 uppercase font-bold">ডেলিভারি ঠিকানা</span>
                <div className="text-gray-800">{selectedOrder.customer_address}</div>
                <div className="text-[11px] text-gray-500 font-bold">এরিয়া: {selectedOrder.delivery_area}</div>
              </div>
            </div>

            {/* Items */}
            <div className="space-y-2">
              <span className="text-xs font-bold text-gray-800">আইটেম তালিকা</span>
              <div className="divide-y divide-gray-100 border border-gray-100 rounded-2xl p-3">
                {selectedOrder.items.map((item, idx) => (
                  <div key={idx} className="py-2 flex items-center justify-between text-xs">
                    <div className="flex items-center gap-2">
                      <img src={item.product_image} alt="" className="w-10 h-10 rounded-lg object-cover" />
                      <div>
                        <div className="font-bold text-gray-900">{item.product_name}</div>
                        <div className="text-[11px] text-gray-400">
                          {settings.currency}{item.price} × {item.quantity}
                          {item.color && ` | রং: ${item.color}`}
                          {item.size && ` | সাইজ: ${item.size}`}
                        </div>
                      </div>
                    </div>
                    <span className="font-bold text-gray-900">
                      {settings.currency}{(item.price * item.quantity).toLocaleString()}
                    </span>
                  </div>
                ))}
              </div>
            </div>

            {/* Bill Summary */}
            <div className="space-y-1.5 text-xs text-gray-700 pt-2 border-t border-gray-100">
              <div className="flex justify-between">
                <span>সাবটোটাল:</span>
                <span>{settings.currency}{selectedOrder.subtotal.toLocaleString()}</span>
              </div>
              {selectedOrder.discount > 0 && (
                <div className="flex justify-between text-emerald-600">
                  <span>ডিসকাউন্ট:</span>
                  <span>-{settings.currency}{selectedOrder.discount.toLocaleString()}</span>
                </div>
              )}
              <div className="flex justify-between">
                <span>ডেলিভারি চার্জ:</span>
                <span>{settings.currency}{selectedOrder.delivery_charge.toLocaleString()}</span>
              </div>
              <div className="flex justify-between font-black text-sm text-gray-950 pt-2 border-t border-gray-200">
                <span>সর্বমোট:</span>
                <span className="text-red-600">{settings.currency}{selectedOrder.total.toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
