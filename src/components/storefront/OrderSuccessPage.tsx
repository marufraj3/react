import React from 'react';
import { CheckCircle2, Package, MapPin, Phone, User, Printer, ArrowRight, Home } from 'lucide-react';
import { useStore } from '../../context/StoreContext';

interface OrderSuccessPageProps {
  orderId?: string;
}

export const OrderSuccessPage: React.FC<OrderSuccessPageProps> = ({ orderId }) => {
  const { orders, lastCreatedOrder, navigate, settings } = useStore();

  const targetOrderId = orderId || lastCreatedOrder?.id;
  const order = orders.find((o) => o.id === targetOrderId) || lastCreatedOrder;

  if (!order) {
    return (
      <div className="max-w-xl mx-auto px-4 py-16 text-center space-y-4">
        <h2 className="text-xl font-bold text-gray-900">অর্ডার সফলভাবে তৈরি হয়েছে!</h2>
        <button
          onClick={() => navigate('home')}
          className="bg-red-600 text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow-xs"
        >
          হোমে ফিরে যান
        </button>
      </div>
    );
  }

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6 animate-in fade-in duration-200">
      {/* Success Hero Box */}
      <div className="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm text-center space-y-3">
        <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto animate-bounce">
          <CheckCircle2 className="w-10 h-10" />
        </div>
        <h1 className="text-2xl sm:text-3xl font-black text-gray-950">
          অভিনন্দন! আপনার অর্ডারটি নিশ্চিত হয়েছে।
        </h1>
        <p className="text-sm text-gray-600 max-w-md mx-auto">
          আমাদের কাস্টমার কেয়ার টিম দ্রুত আপনার অর্ডারটি যাচাই করে ডেলিভারির জন্য প্রস্তুত করবে।
        </p>

        {/* Order ID Badge */}
        <div className="inline-flex items-center gap-2 bg-gray-100 px-4 py-2 rounded-2xl border border-gray-200 mt-2">
          <span className="text-xs text-gray-500 font-semibold">অর্ডার আইডি:</span>
          <span className="text-base font-black font-mono text-red-600">{order.id}</span>
        </div>
      </div>

      {/* Order Details Card */}
      <div className="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-6">
        <div className="flex items-center justify-between border-b border-gray-100 pb-4">
          <h2 className="text-base font-extrabold text-gray-900 flex items-center gap-2">
            <Package className="w-5 h-5 text-red-600" />
            <span>অর্ডারের বিস্তারিত তথ্য</span>
          </h2>
          <button
            onClick={handlePrint}
            className="flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-gray-950 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-xl transition-colors cursor-pointer"
          >
            <Printer className="w-3.5 h-3.5" />
            <span>প্রিন্ট রসিদ</span>
          </button>
        </div>

        {/* Customer & Shipping Details */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-gray-50 border border-gray-100 text-xs text-gray-700">
          <div className="space-y-1.5">
            <div className="flex items-center gap-2 font-bold text-gray-900">
              <User className="w-3.5 h-3.5 text-gray-500" />
              <span>{order.customer_name}</span>
            </div>
            <div className="flex items-center gap-2">
              <Phone className="w-3.5 h-3.5 text-gray-500" />
              <span className="font-mono">{order.customer_phone}</span>
            </div>
          </div>
          <div className="space-y-1.5">
            <div className="flex items-center gap-2">
              <MapPin className="w-3.5 h-3.5 text-gray-500" />
              <span>{order.customer_address}</span>
            </div>
            <div className="text-[11px] text-gray-500">
              পেমেন্ট মেথড: <span className="font-bold uppercase">{order.payment_method}</span>
            </div>
          </div>
        </div>

        {/* Items Table */}
        <div className="divide-y divide-gray-100">
          {order.items.map((item, idx) => (
            <div key={idx} className="py-3 flex items-center justify-between gap-3">
              <div className="flex items-center gap-3">
                <img
                  src={item.product_image}
                  alt={item.product_name}
                  className="w-12 h-12 rounded-xl object-cover border border-gray-100 shrink-0"
                />
                <div>
                  <h4 className="text-xs font-bold text-gray-900">{item.product_name}</h4>
                  <div className="text-[11px] text-gray-500">
                    পরিমাণ: {item.quantity} | দর: {settings.currency}{item.price.toLocaleString()}
                  </div>
                </div>
              </div>
              <span className="text-xs font-bold text-gray-900">
                {settings.currency}{(item.price * item.quantity).toLocaleString()}
              </span>
            </div>
          ))}
        </div>

        {/* Financials */}
        <div className="border-t border-gray-100 pt-3 space-y-1.5 text-xs text-gray-600">
          <div className="flex justify-between">
            <span>সাবটোটাল:</span>
            <span>{settings.currency}{order.subtotal.toLocaleString()}</span>
          </div>
          {order.discount > 0 && (
            <div className="flex justify-between text-emerald-600">
              <span>ডিসকাউন্ট:</span>
              <span>-{settings.currency}{order.discount.toLocaleString()}</span>
            </div>
          )}
          <div className="flex justify-between">
            <span>ডেলিভারি চার্জ:</span>
            <span>{settings.currency}{order.delivery_charge.toLocaleString()}</span>
          </div>
          <div className="border-t border-gray-200 pt-2 flex justify-between text-sm font-extrabold text-gray-950">
            <span>সর্বমোট বিল:</span>
            <span className="text-red-600 text-base">{settings.currency}{order.total.toLocaleString()}</span>
          </div>
        </div>
      </div>

      {/* Action Buttons */}
      <div className="flex flex-col sm:flex-row gap-3">
        <button
          onClick={() => navigate('order_track', { orderId: order.id, phone: order.customer_phone })}
          className="flex-1 bg-red-600 hover:bg-red-700 text-white font-extrabold text-sm py-3 rounded-2xl shadow-sm flex items-center justify-center gap-2 cursor-pointer"
        >
          <span>অর্ডার লাইভ ট্র্যাক করুন</span>
          <ArrowRight className="w-4 h-4" />
        </button>

        <button
          onClick={() => navigate('home')}
          className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-sm py-3 rounded-2xl flex items-center justify-center gap-2 cursor-pointer"
        >
          <Home className="w-4 h-4" />
          <span>হোম পেজে যান</span>
        </button>
      </div>
    </div>
  );
};
