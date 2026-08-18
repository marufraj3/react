import React, { useState, useEffect } from 'react';
import {
  Truck,
  ShieldCheck,
  Zap,
  CheckCircle2,
  AlertCircle,
  Tag,
  Phone,
  User,
  MapPin,
  FileText,
  CreditCard,
  Sparkles,
  ArrowLeft
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const CheckoutPage: React.FC = () => {
  const {
    cart,
    cartSubtotal,
    cartDiscount,
    appliedCoupon,
    removeCoupon,
    applyCoupon,
    createOrder,
    navigate,
    settings,
    showToast
  } = useStore();

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [altPhone, setAltPhone] = useState('');
  const [email, setEmail] = useState('');
  const [address, setAddress] = useState('');
  const [selectedZone, setSelectedZone] = useState<'inside_dhaka' | 'outside_dhaka' | 'sub_dhaka'>('inside_dhaka');
  const [paymentMethod, setPaymentMethod] = useState<'cod' | 'bkash' | 'nagad'>('cod');
  const [trxId, setTrxId] = useState('');
  const [orderNote, setOrderNote] = useState('');
  const [couponCode, setCouponCode] = useState('');
  const [couponError, setCouponError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  // Delivery charge
  const getDeliveryCharge = () => {
    const hasOnlyDigital = cart.length > 0 && cart.every((item) => item.product.is_digital || item.product.free_delivery);
    if (hasOnlyDigital) return 0;
    if (selectedZone === 'inside_dhaka') return 60;
    if (selectedZone === 'sub_dhaka') return 100;
    return 120;
  };

  const deliveryCharge = getDeliveryCharge();
  const subtotalAfterDiscount = Math.max(0, cartSubtotal - cartDiscount);
  const grandTotal = subtotalAfterDiscount + deliveryCharge;

  if (cart.length === 0) {
    return (
      <div className="max-w-3xl mx-auto px-4 py-16 text-center space-y-4">
        <h2 className="text-xl font-bold text-gray-900">আপনার শপিং কার্ট খালি!</h2>
        <p className="text-xs text-gray-500">চেকআউট করার আগে কার্টে পণ্য যোগ করুন।</p>
        <button
          onClick={() => navigate('home')}
          className="bg-red-600 text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow-xs"
        >
          কেনাকাটা করুন
        </button>
      </div>
    );
  }

  const handleApplyCoupon = (e: React.FormEvent) => {
    e.preventDefault();
    setCouponError(null);
    if (!couponCode.trim()) return;
    const res = applyCoupon(couponCode);
    if (!res.success) {
      setCouponError(res.message);
    } else {
      setCouponCode('');
    }
  };

  const handleSubmitOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError(null);

    if (!name.trim()) {
      setFormError('অনুগ্রহ করে আপনার পূর্ণ নাম লিখুন।');
      return;
    }
    const cleanPhone = phone.trim().replace(/[-+\s]/g, '');
    if (!cleanPhone || cleanPhone.length < 11) {
      setFormError('অনুগ্রহ করে সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন।');
      return;
    }
    if (!address.trim() && cart.some((i) => !i.product.is_digital)) {
      setFormError('অনুগ্রহ করে আপনার সম্পূর্ণ ডেলিভারি ঠিকানা লিখুন।');
      return;
    }

    if ((paymentMethod === 'bkash' || paymentMethod === 'nagad') && !trxId.trim()) {
      setFormError('অনুগ্রহ করে বিকাশ/নগদ এর ট্রানজেকশন আইডি (TrxID) প্রদান করুন।');
      return;
    }

    setIsSubmitting(true);

    try {
      const orderItems = cart.map((item) => ({
        product_id: item.product.id,
        product_name: item.product.name,
        product_code: item.product.product_code,
        product_image: item.product.image,
        price: item.unitPrice,
        quantity: item.quantity,
        color: item.selectedColor,
        size: item.selectedSize,
        is_digital: item.product.is_digital,
        digital_file: item.product.digital_file,
      }));

      const res = await createOrder({
        customer_name: name.trim(),
        customer_phone: cleanPhone,
        customer_email: email.trim() || undefined,
        customer_address: address.trim() || 'Digital Delivery Account',
        delivery_area: selectedZone,
        delivery_charge: deliveryCharge,
        payment_method: paymentMethod,
        transaction_id: trxId.trim() || undefined,
        order_note: orderNote.trim() || undefined,
        items: orderItems,
        subtotal: cartSubtotal,
        discount: cartDiscount,
        total: grandTotal,
      });

      if (!res.success) {
        setFormError(res.message);
        setIsSubmitting(false);
      }
    } catch {
      setFormError('অর্ডার প্রক্রিয়া করার সময় ত্রুটি হয়েছে।');
      setIsSubmitting(false);
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6">
      {/* Top Header */}
      <div className="flex items-center justify-between">
        <button
          onClick={() => navigate('home')}
          className="flex items-center gap-1 text-xs font-bold text-gray-600 hover:text-gray-900"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>কেনাকাটা চালিয়ে যান</span>
        </button>
        <h1 className="text-lg sm:text-xl font-extrabold text-gray-950">
          নিরাপদ চেকআউট ও অর্ডার কনফার্মেশন
        </h1>
      </div>

      <form onSubmit={handleSubmitOrder} className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Left Column: Billing Details (7 cols) */}
        <div className="lg:col-span-7 space-y-6 bg-white p-5 sm:p-8 rounded-3xl border border-gray-100 shadow-xs">
          <div className="flex items-center gap-2 pb-3 border-b border-gray-100">
            <div className="w-8 h-8 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-sm">
              ১
            </div>
            <h2 className="text-base font-extrabold text-gray-900">
              গ্রাহকের তথ্য ও ডেলিভারি ঠিকানা
            </h2>
          </div>

          {formError && (
            <div className="p-3.5 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-xs flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0 text-red-600" />
              <span>{formError}</span>
            </div>
          )}

          <div className="space-y-4">
            <div>
              <label className="block text-xs font-bold text-gray-800 mb-1 flex items-center gap-1">
                <User className="w-3.5 h-3.5 text-gray-500" />
                <span>আপনার পূর্ণ নাম *</span>
              </label>
              <input
                type="text"
                required
                placeholder="যেমন: তানভীর আহমেদ"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full bg-gray-50 focus:bg-white text-sm rounded-xl px-4 py-3 border border-gray-200 focus:border-red-500 outline-hidden transition-all"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-bold text-gray-800 mb-1 flex items-center gap-1">
                  <Phone className="w-3.5 h-3.5 text-gray-500" />
                  <span>মোবাইল নম্বর * (১১ ডিজিট)</span>
                </label>
                <input
                  type="tel"
                  required
                  placeholder="017xxxxxxxx"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  className="w-full bg-gray-50 focus:bg-white text-sm rounded-xl px-4 py-3 border border-gray-200 focus:border-red-500 outline-hidden font-mono"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-gray-800 mb-1">
                  বিকল্প মোবাইল নম্বর (ঐচ্ছিক)
                </label>
                <input
                  type="tel"
                  placeholder="018xxxxxxxx"
                  value={altPhone}
                  onChange={(e) => setAltPhone(e.target.value)}
                  className="w-full bg-gray-50 focus:bg-white text-sm rounded-xl px-4 py-3 border border-gray-200 focus:border-red-500 outline-hidden font-mono"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-bold text-gray-800 mb-1">
                ইমেইল এড্রেস (ডিজিটাল প্রোডাক্ট ডেলিভারির জন্য প্রযোজ্য)
              </label>
              <input
                type="email"
                placeholder="yourname@gmail.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full bg-gray-50 focus:bg-white text-sm rounded-xl px-4 py-3 border border-gray-200 focus:border-red-500 outline-hidden"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-gray-800 mb-1 flex items-center gap-1">
                <MapPin className="w-3.5 h-3.5 text-gray-500" />
                <span>সম্পূর্ণ ডেলিভারি ঠিকানা *</span>
              </label>
              <textarea
                rows={2}
                required
                placeholder="জেলা, থানা, এলাকা, রোড ও বাসা নম্বর..."
                value={address}
                onChange={(e) => setAddress(e.target.value)}
                className="w-full bg-gray-50 focus:bg-white text-sm rounded-xl px-4 py-3 border border-gray-200 focus:border-red-500 outline-hidden resize-none"
              />
            </div>

            {/* Delivery Zone selector */}
            <div>
              <label className="block text-xs font-bold text-gray-800 mb-2 flex items-center gap-1">
                <Truck className="w-3.5 h-3.5 text-gray-500" />
                <span>ডেলিভারি এরিয়া নির্বাচন করুন:</span>
              </label>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                <label
                  className={`p-3 rounded-2xl border text-xs cursor-pointer flex flex-col justify-between transition-all ${
                    selectedZone === 'inside_dhaka'
                      ? 'bg-red-50/70 border-red-500 text-red-950 font-bold ring-2 ring-red-500/20'
                      : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <span>ঢাকা সিটির ভিতরে</span>
                    <input
                      type="radio"
                      name="zone"
                      checked={selectedZone === 'inside_dhaka'}
                      onChange={() => setSelectedZone('inside_dhaka')}
                      className="text-red-600"
                    />
                  </div>
                  <span className="text-red-600 font-extrabold mt-1">৳৬০ (১-২ দিন)</span>
                </label>

                <label
                  className={`p-3 rounded-2xl border text-xs cursor-pointer flex flex-col justify-between transition-all ${
                    selectedZone === 'sub_dhaka'
                      ? 'bg-red-50/70 border-red-500 text-red-950 font-bold ring-2 ring-red-500/20'
                      : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <span>ঢাকা সাব-এরিয়া</span>
                    <input
                      type="radio"
                      name="zone"
                      checked={selectedZone === 'sub_dhaka'}
                      onChange={() => setSelectedZone('sub_dhaka')}
                      className="text-red-600"
                    />
                  </div>
                  <span className="text-red-600 font-extrabold mt-1">৳১০০ (২ দিন)</span>
                </label>

                <label
                  className={`p-3 rounded-2xl border text-xs cursor-pointer flex flex-col justify-between transition-all ${
                    selectedZone === 'outside_dhaka'
                      ? 'bg-red-50/70 border-red-500 text-red-950 font-bold ring-2 ring-red-500/20'
                      : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <span>ঢাকার বাইরে</span>
                    <input
                      type="radio"
                      name="zone"
                      checked={selectedZone === 'outside_dhaka'}
                      onChange={() => setSelectedZone('outside_dhaka')}
                      className="text-red-600"
                    />
                  </div>
                  <span className="text-red-600 font-extrabold mt-1">৳১২০ (২-৩ দিন)</span>
                </label>
              </div>
            </div>

            {/* Order Note */}
            <div>
              <label className="block text-xs font-bold text-gray-800 mb-1 flex items-center gap-1">
                <FileText className="w-3.5 h-3.5 text-gray-500" />
                <span>অর্ডার নোট বা বিশেষ নির্দেশনা (ঐচ্ছিক)</span>
              </label>
              <input
                type="text"
                placeholder="ডেলিভারি সম্পর্কিত কোনো বিশেষ কথা..."
                value={orderNote}
                onChange={(e) => setOrderNote(e.target.value)}
                className="w-full bg-gray-50 focus:bg-white text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden"
              />
            </div>
          </div>

          {/* Payment Method Selector */}
          <div className="pt-4 border-t border-gray-100 space-y-3">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-sm">
                ২
              </div>
              <h2 className="text-base font-extrabold text-gray-900">
                পেমেন্ট পদ্ধতি নির্বাচন করুন
              </h2>
            </div>

            <div className="space-y-2">
              {/* Cash On Delivery Option */}
              <label
                className={`p-3.5 rounded-2xl border text-xs cursor-pointer flex items-center justify-between transition-all ${
                  paymentMethod === 'cod'
                    ? 'bg-emerald-50/70 border-emerald-500 text-emerald-950 font-bold ring-2 ring-emerald-500/20'
                    : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                }`}
              >
                <div className="flex items-center gap-3">
                  <CheckCircle2
                    className={`w-5 h-5 ${paymentMethod === 'cod' ? 'text-emerald-600' : 'text-gray-300'}`}
                  />
                  <div>
                    <span className="font-extrabold text-sm block">ক্যাশ অন ডেলিভারি (Cash on Delivery)</span>
                    <span className="text-[11px] text-gray-500">
                      পণ্য হাতে পেয়ে দেখে মূল্য পরিশোধ করুন
                    </span>
                  </div>
                </div>
                <input
                  type="radio"
                  name="payment"
                  checked={paymentMethod === 'cod'}
                  onChange={() => setPaymentMethod('cod')}
                  className="text-emerald-600"
                />
              </label>

              {/* bKash Option */}
              <label
                className={`p-3.5 rounded-2xl border text-xs cursor-pointer flex items-center justify-between transition-all ${
                  paymentMethod === 'bkash'
                    ? 'bg-pink-50/70 border-pink-500 text-pink-950 font-bold ring-2 ring-pink-500/20'
                    : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                }`}
              >
                <div className="flex items-center gap-3">
                  <CreditCard
                    className={`w-5 h-5 ${paymentMethod === 'bkash' ? 'text-pink-600' : 'text-gray-300'}`}
                  />
                  <div>
                    <span className="font-extrabold text-sm block text-pink-700">বিকাশ (bKash Send Money)</span>
                    <span className="text-[11px] text-gray-500">বিকাশে অগ্রিম পেমেন্ট করুন</span>
                  </div>
                </div>
                <input
                  type="radio"
                  name="payment"
                  checked={paymentMethod === 'bkash'}
                  onChange={() => setPaymentMethod('bkash')}
                  className="text-pink-600"
                />
              </label>

              {/* bKash instructions */}
              {paymentMethod === 'bkash' && (
                <div className="p-4 bg-pink-50 rounded-2xl border border-pink-200 space-y-2 text-xs text-pink-950 animate-in fade-in duration-150">
                  <p className="font-bold">
                    বিকাশ পার্সোনাল নম্বর: <span className="font-mono text-sm bg-white px-2 py-0.5 rounded border border-pink-300">01849832178</span>
                  </p>
                  <p className="text-[11px] text-pink-800">
                    অনুগ্রহ করে উপরে উল্লেখিত নম্বরে মোট <strong>৳{grandTotal.toLocaleString()}</strong> টাকা Send Money করুন এবং নিচের ঘরে আপনার ট্রানজেকশন আইডি (TrxID) লিখুন:
                  </p>
                  <input
                    type="text"
                    required
                    placeholder="যেমন: 9K72BKS81"
                    value={trxId}
                    onChange={(e) => setTrxId(e.target.value)}
                    className="w-full bg-white text-xs rounded-xl px-3.5 py-2.5 border border-pink-300 focus:border-pink-600 uppercase font-mono outline-hidden"
                  />
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Right Column: Order Summary (5 cols) */}
        <div className="lg:col-span-5 space-y-5">
          <div className="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <h2 className="text-base font-extrabold text-gray-900 pb-3 border-b border-gray-100">
              আপনার অর্ডারের বিবরণ ({cart.length} টি আইটেম)
            </h2>

            {/* Cart Items List */}
            <div className="max-h-60 overflow-y-auto divide-y divide-gray-100 pr-1 space-y-2">
              {cart.map((item, idx) => (
                <div key={idx} className="flex gap-3 py-2 items-center">
                  <img
                    src={item.product.image}
                    alt={item.product.name}
                    className="w-12 h-12 rounded-xl object-cover border border-gray-100 shrink-0"
                  />
                  <div className="flex-1 min-w-0">
                    <h4 className="text-xs font-bold text-gray-900 truncate">
                      {item.product.name}
                    </h4>
                    <div className="text-[11px] text-gray-500">
                      {item.unitPrice.toLocaleString()} × {item.quantity}
                    </div>
                  </div>
                  <span className="text-xs font-bold text-gray-900">
                    {settings.currency}{(item.unitPrice * item.quantity).toLocaleString()}
                  </span>
                </div>
              ))}
            </div>

            {/* Coupon Code section */}
            {!appliedCoupon ? (
              <div className="pt-2 border-t border-gray-100">
                <div className="flex gap-2">
                  <input
                    type="text"
                    placeholder="কুপন কোড দিন"
                    value={couponCode}
                    onChange={(e) => setCouponCode(e.target.value)}
                    className="flex-1 bg-gray-50 text-xs rounded-xl px-3 py-2 border border-gray-200 uppercase font-mono outline-hidden focus:border-red-500"
                  />
                  <button
                    type="button"
                    onClick={handleApplyCoupon}
                    className="bg-gray-900 text-white text-xs font-bold px-3 py-2 rounded-xl hover:bg-black transition-colors shrink-0"
                  >
                    প্রয়োগ
                  </button>
                </div>
                {couponError && <p className="text-[11px] text-red-600 mt-1">{couponError}</p>}
              </div>
            ) : (
              <div className="flex items-center justify-between bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-xl text-xs">
                <span className="font-bold flex items-center gap-1">
                  <Tag className="w-3.5 h-3.5" /> কুপন '{appliedCoupon.code}' (-৳{cartDiscount})
                </span>
                <button
                  type="button"
                  onClick={removeCoupon}
                  className="text-red-600 font-bold hover:underline"
                >
                  মুছুন
                </button>
              </div>
            )}

            {/* Financial Breakdown */}
            <div className="pt-3 border-t border-gray-100 space-y-2 text-xs text-gray-700">
              <div className="flex justify-between">
                <span>পণ্যের মোট মূল্য:</span>
                <span className="font-semibold">{settings.currency}{cartSubtotal.toLocaleString()}</span>
              </div>
              {cartDiscount > 0 && (
                <div className="flex justify-between text-emerald-600 font-medium">
                  <span>ডিসকাউন্ট:</span>
                  <span>-{settings.currency}{cartDiscount.toLocaleString()}</span>
                </div>
              )}
              <div className="flex justify-between">
                <span>ডেলিভারি চার্জ:</span>
                <span className="font-semibold">
                  {deliveryCharge === 0 ? 'ফ্রি (৳০)' : `${settings.currency}${deliveryCharge}`}
                </span>
              </div>
              <div className="border-t border-gray-200 pt-2 flex justify-between text-base font-black text-gray-950">
                <span>সর্বমোট প্রদেয়:</span>
                <span className="text-red-600 text-lg">
                  {settings.currency}{grandTotal.toLocaleString()}
                </span>
              </div>
            </div>

            {/* Caution disclaimer */}
            {settings.checkout_note && (
              <p className="text-[11px] text-gray-500 bg-amber-50 p-2.5 rounded-xl border border-amber-200/60 leading-relaxed">
                {settings.checkout_note}
              </p>
            )}

            {/* Place Order CTA */}
            <button
              type="submit"
              disabled={isSubmitting}
              className="w-full bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-base py-3.5 rounded-2xl shadow-lg shadow-red-500/25 flex items-center justify-center gap-2 transition-transform active:scale-98 cursor-pointer disabled:opacity-50"
            >
              {isSubmitting ? (
                <div className="flex items-center gap-2">
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  <span>অর্ডার প্রসেস হচ্ছে...</span>
                </div>
              ) : (
                <>
                  <Zap className="w-5 h-5 fill-white" />
                  <span>অর্ডার সম্পূর্ণ করুন (৳{grandTotal.toLocaleString()})</span>
                </>
              )}
            </button>
          </div>
        </div>
      </form>
    </div>
  );
};
