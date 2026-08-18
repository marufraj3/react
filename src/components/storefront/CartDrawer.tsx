import React, { useState } from 'react';
import {
  X,
  ShoppingBag,
  Trash2,
  ArrowRight,
  Sparkles,
  Tag,
  ShieldCheck,
  Plus,
  Minus
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const CartDrawer: React.FC = () => {
  const {
    cart,
    isCartDrawerOpen,
    setIsCartDrawerOpen,
    removeFromCart,
    updateCartQuantity,
    cartSubtotal,
    cartDiscount,
    appliedCoupon,
    applyCoupon,
    removeCoupon,
    navigate,
    settings
  } = useStore();

  const [couponInput, setCouponInput] = useState('');
  const [couponError, setCouponError] = useState<string | null>(null);

  if (!isCartDrawerOpen) return null;

  const handleApplyCoupon = (e: React.FormEvent) => {
    e.preventDefault();
    setCouponError(null);
    if (!couponInput.trim()) return;

    const res = applyCoupon(couponInput);
    if (!res.success) {
      setCouponError(res.message);
    } else {
      setCouponInput('');
    }
  };

  const finalTotal = Math.max(0, cartSubtotal - cartDiscount);

  return (
    <div className="fixed inset-0 z-50 overflow-hidden flex justify-end animate-in fade-in duration-200">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
        onClick={() => setIsCartDrawerOpen(false)}
      />

      {/* Drawer */}
      <div className="relative w-full max-w-md bg-white h-full shadow-2xl flex flex-col z-10 animate-in slide-in-from-right duration-250">
        {/* Drawer Header */}
        <div className="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
              <ShoppingBag className="w-4 h-4" />
            </div>
            <div>
              <h3 className="font-extrabold text-base text-gray-900">
                আপনার শপিং কার্ট ({cart.length})
              </h3>
            </div>
          </div>
          <button
            onClick={() => setIsCartDrawerOpen(false)}
            className="p-1.5 rounded-lg text-gray-500 hover:bg-gray-200"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Drawer Body Items */}
        <div className="flex-1 overflow-y-auto p-4 divide-y divide-gray-100">
          {cart.length > 0 ? (
            <div className="space-y-3 pb-4">
              {cart.map((item, idx) => (
                <div
                  key={`${item.product.id}-${item.selectedColor}-${item.selectedSize}-${idx}`}
                  className="flex gap-3 py-2 items-center"
                >
                  <img
                    src={item.product.image}
                    alt={item.product.name}
                    className="w-16 h-16 rounded-xl object-cover bg-gray-50 border border-gray-100 shrink-0"
                  />
                  <div className="flex-1 min-w-0">
                    <h4 className="text-xs font-bold text-gray-900 line-clamp-1">
                      {item.product.name}
                    </h4>
                    {(item.selectedColor || item.selectedSize) && (
                      <div className="flex gap-2 text-[11px] text-gray-500 mt-0.5">
                        {item.selectedColor && <span>রং: {item.selectedColor}</span>}
                        {item.selectedSize && <span>সাইজ: {item.selectedSize}</span>}
                      </div>
                    )}
                    <div className="flex items-center gap-2 mt-1">
                      <span className="text-xs font-extrabold text-red-600">
                        {settings.currency}{item.unitPrice.toLocaleString()}
                      </span>
                      <span className="text-xs text-gray-400">× {item.quantity}</span>
                    </div>
                  </div>

                  {/* Quantity Stepper */}
                  <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-gray-50 shrink-0">
                    <button
                      onClick={() =>
                        updateCartQuantity(item.product.id, item.quantity - 1, item.selectedColor, item.selectedSize)
                      }
                      className="p-1 text-gray-600 hover:bg-gray-200"
                    >
                      <Minus className="w-3 h-3" />
                    </button>
                    <span className="px-2 text-xs font-bold text-gray-900">{item.quantity}</span>
                    <button
                      onClick={() =>
                        updateCartQuantity(item.product.id, item.quantity + 1, item.selectedColor, item.selectedSize)
                      }
                      className="p-1 text-gray-600 hover:bg-gray-200"
                    >
                      <Plus className="w-3 h-3" />
                    </button>
                  </div>

                  {/* Delete Button */}
                  <button
                    onClick={() => removeFromCart(item.product.id, item.selectedColor, item.selectedSize)}
                    className="p-1.5 text-gray-400 hover:text-red-600 transition-colors"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              ))}
            </div>
          ) : (
            <div className="h-full flex flex-col items-center justify-center text-center p-6 text-gray-500">
              <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                <ShoppingBag className="w-8 h-8 text-gray-400" />
              </div>
              <p className="text-sm font-bold text-gray-800">আপনার কার্ট খালি!</p>
              <p className="text-xs text-gray-400 mt-1">পছন্দের পণ্য কার্টে যুক্ত করুন</p>
              <button
                onClick={() => {
                  setIsCartDrawerOpen(false);
                  navigate('home');
                }}
                className="mt-4 bg-red-600 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs"
              >
                কেনাকাটা শুরু করুন
              </button>
            </div>
          )}
        </div>

        {/* Drawer Footer & Checkout */}
        {cart.length > 0 && (
          <div className="p-4 border-t border-gray-100 bg-gray-50 space-y-3 shrink-0">
            {/* Coupon Code Section */}
            {!appliedCoupon ? (
              <form onSubmit={handleApplyCoupon} className="flex gap-2">
                <input
                  type="text"
                  placeholder="কুপন কোড (যেমন: SAVE100)"
                  value={couponInput}
                  onChange={(e) => setCouponInput(e.target.value)}
                  className="flex-1 bg-white text-xs rounded-xl px-3 py-2 border border-gray-200 uppercase outline-hidden focus:border-red-500 font-mono"
                />
                <button
                  type="submit"
                  className="bg-gray-900 hover:bg-black text-white text-xs font-bold px-3.5 py-2 rounded-xl transition-colors shrink-0"
                >
                  প্রয়োগ
                </button>
              </form>
            ) : (
              <div className="flex items-center justify-between bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-xl text-xs">
                <div className="flex items-center gap-1.5 font-bold">
                  <Tag className="w-3.5 h-3.5 text-emerald-600" />
                  <span>কুপন '{appliedCoupon.code}' প্রয়োগ হয়েছে (-৳{cartDiscount})</span>
                </div>
                <button
                  onClick={removeCoupon}
                  className="text-red-600 hover:underline font-bold text-[11px]"
                >
                  বাতিল
                </button>
              </div>
            )}
            {couponError && <p className="text-red-600 text-[11px] font-medium">{couponError}</p>}

            {/* Calculations Summary */}
            <div className="space-y-1 text-xs text-gray-600 pt-1">
              <div className="flex justify-between">
                <span>সাবটোটাল:</span>
                <span className="font-semibold text-gray-900">
                  {settings.currency}{cartSubtotal.toLocaleString()}
                </span>
              </div>
              {cartDiscount > 0 && (
                <div className="flex justify-between text-emerald-600 font-medium">
                  <span>ডিসকাউন্ট:</span>
                  <span>-{settings.currency}{cartDiscount.toLocaleString()}</span>
                </div>
              )}
              <div className="flex justify-between text-xs text-gray-500">
                <span>ডেলিভারি চার্জ:</span>
                <span>চেকআউটে নির্ধারিত হবে</span>
              </div>
              <div className="border-t border-gray-200 pt-2 flex justify-between text-sm font-extrabold text-gray-950">
                <span>মোট (আনুমানিক):</span>
                <span className="text-red-600 text-base">
                  {settings.currency}{finalTotal.toLocaleString()}
                </span>
              </div>
            </div>

            {/* Checkout Action Button */}
            <button
              onClick={() => {
                setIsCartDrawerOpen(false);
                navigate('checkout');
              }}
              className="w-full bg-red-600 hover:bg-red-700 text-white font-extrabold text-sm py-3 rounded-2xl shadow-md shadow-red-500/20 flex items-center justify-center gap-2 transition-transform active:scale-98 cursor-pointer"
            >
              <span>চেকআউট করুন</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
