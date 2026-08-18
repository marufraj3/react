import React, { useState, useEffect } from 'react';
import {
  X,
  Truck,
  ShieldCheck,
  Zap,
  CheckCircle2,
  AlertCircle,
  Phone,
  MapPin,
  User,
  Sparkles
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { ShippingCharge } from '../../types';

export const QuickOrderModal: React.FC = () => {
  const {
    quickOrderProduct,
    closeQuickOrder,
    shippingCharges,
    settings,
    createOrder,
    showToast
  } = useStore();

  const [quantity, setQuantity] = useState(1);
  const [selectedColor, setSelectedColor] = useState<string>('');
  const [selectedSize, setSelectedSize] = useState<string>('');

  // Customer Form Inputs
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [selectedZone, setSelectedZone] = useState<'inside_dhaka' | 'outside_dhaka' | 'sub_dhaka'>('inside_dhaka');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  // Initialize variant defaults when modal opens
  useEffect(() => {
    if (quickOrderProduct) {
      setQuantity(1);
      if (quickOrderProduct.colors && quickOrderProduct.colors.length > 0) {
        setSelectedColor(quickOrderProduct.colors[0]);
      } else {
        setSelectedColor('');
      }
      if (quickOrderProduct.sizes && quickOrderProduct.sizes.length > 0) {
        setSelectedSize(quickOrderProduct.sizes[0]);
      } else {
        setSelectedSize('');
      }
      setFormError(null);
    }
  }, [quickOrderProduct]);

  if (!quickOrderProduct) return null;

  // Delivery charge calculation
  const getDeliveryCharge = (): number => {
    if (quickOrderProduct.free_delivery || quickOrderProduct.is_digital) return 0;
    if (selectedZone === 'inside_dhaka') return 60;
    if (selectedZone === 'sub_dhaka') return 100;
    return 120;
  };

  const deliveryCharge = getDeliveryCharge();
  const subtotal = quickOrderProduct.new_price * quantity;
  const grandTotal = subtotal + deliveryCharge;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError(null);

    // Form Validations
    if (!name.trim()) {
      setFormError('অনুগ্রহ করে আপনার নাম লিখুন।');
      return;
    }
    const cleanPhone = phone.trim().replace(/[-+\s]/g, '');
    if (!cleanPhone || cleanPhone.length < 11) {
      setFormError('অনুগ্রহ করে সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন (যেমন: 017xxxxxxxx)।');
      return;
    }
    if (!address.trim() && !quickOrderProduct.is_digital) {
      setFormError('অনুগ্রহ করে আপনার সম্পূর্ণ ডেলিভারি ঠিকানা লিখুন।');
      return;
    }

    setIsSubmitting(true);

    try {
      const result = await createOrder({
        customer_name: name.trim(),
        customer_phone: cleanPhone,
        customer_address: address.trim() || 'Digital Delivery via Phone/Email',
        delivery_area: selectedZone,
        delivery_charge: deliveryCharge,
        payment_method: 'cod',
        items: [
          {
            product_id: quickOrderProduct.id,
            product_name: quickOrderProduct.name,
            product_code: quickOrderProduct.product_code,
            product_image: quickOrderProduct.image,
            price: quickOrderProduct.new_price,
            quantity,
            color: selectedColor || undefined,
            size: selectedSize || undefined,
            is_digital: quickOrderProduct.is_digital,
            digital_file: quickOrderProduct.digital_file,
          },
        ],
        subtotal,
        discount: 0,
        total: grandTotal,
      });

      if (!result.success) {
        setFormError(result.message);
        setIsSubmitting(false);
      }
    } catch {
      setFormError('অর্ডার প্রক্রিয়া করার সময় সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।');
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-3 sm:p-4 animate-in fade-in duration-200">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/70 backdrop-blur-xs transition-opacity"
        onClick={closeQuickOrder}
      />

      {/* Modal Card */}
      <div className="relative bg-white rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden z-10 border border-gray-100 max-h-[92vh] flex flex-col">
        {/* Modal Header */}
        <div className="p-4 bg-gradient-to-r from-red-600 to-rose-700 text-white flex items-center justify-between shrink-0">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center">
              <Zap className="w-4 h-4 fill-white" />
            </div>
            <div>
              <h3 className="font-extrabold text-base sm:text-lg leading-tight">
                ১-ক্লিকে কুইক অর্ডার (Quick Checkout)
              </h3>
              <p className="text-xs text-white/80">অর্ডার করতে নিচের তথ্যগুলো পূরণ করুন</p>
            </div>
          </div>
          <button
            onClick={closeQuickOrder}
            className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Scrollable Content */}
        <div className="p-4 sm:p-6 overflow-y-auto space-y-5">
          {/* Product Summary Row */}
          <div className="flex gap-3.5 p-3 rounded-2xl bg-gray-50 border border-gray-100 items-center">
            <img
              src={quickOrderProduct.image}
              alt={quickOrderProduct.name}
              className="w-16 h-16 rounded-xl object-cover bg-white border border-gray-200 shrink-0"
            />
            <div className="flex-1 min-w-0">
              <h4 className="text-xs sm:text-sm font-bold text-gray-900 line-clamp-2">
                {quickOrderProduct.name}
              </h4>
              <div className="flex items-center gap-2 mt-1">
                <span className="text-sm font-extrabold text-red-600">
                  {settings.currency}{quickOrderProduct.new_price.toLocaleString()}
                </span>
                {quickOrderProduct.old_price && (
                  <span className="text-xs text-gray-400 line-through">
                    {settings.currency}{quickOrderProduct.old_price.toLocaleString()}
                  </span>
                )}
                <span className="text-[10px] bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded font-mono">
                  {quickOrderProduct.product_code}
                </span>
              </div>
            </div>

            {/* Quantity Stepper */}
            <div className="flex items-center border border-gray-200 rounded-xl bg-white overflow-hidden shadow-2xs shrink-0">
              <button
                type="button"
                onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                className="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 font-bold"
              >
                -
              </button>
              <span className="w-8 text-center text-xs font-bold text-gray-900">{quantity}</span>
              <button
                type="button"
                onClick={() => setQuantity((q) => Math.min(quickOrderProduct.stock, q + 1))}
                className="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 font-bold"
              >
                +
              </button>
            </div>
          </div>

          {/* Color Selection (if available) */}
          {quickOrderProduct.colors && quickOrderProduct.colors.length > 0 && (
            <div>
              <label className="block text-xs font-bold text-gray-700 mb-1.5">
                কালার নির্বাচন করুন (Color):
              </label>
              <div className="flex flex-wrap gap-2">
                {quickOrderProduct.colors.map((c) => (
                  <button
                    key={c}
                    type="button"
                    onClick={() => setSelectedColor(c)}
                    className={`px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all cursor-pointer ${
                      selectedColor === c
                        ? 'bg-red-50 border-red-600 text-red-600 ring-2 ring-red-500/20'
                        : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                    }`}
                  >
                    {c}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Size Selection (if available) */}
          {quickOrderProduct.sizes && quickOrderProduct.sizes.length > 0 && (
            <div>
              <label className="block text-xs font-bold text-gray-700 mb-1.5">
                সাইজ / ভ্যারিয়েন্ট নির্বাচন করুন (Size / Variant):
              </label>
              <div className="flex flex-wrap gap-2">
                {quickOrderProduct.sizes.map((s) => (
                  <button
                    key={s}
                    type="button"
                    onClick={() => setSelectedSize(s)}
                    className={`px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all cursor-pointer ${
                      selectedSize === s
                        ? 'bg-red-50 border-red-600 text-red-600 ring-2 ring-red-500/20'
                        : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                    }`}
                  >
                    {s}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Delivery Form */}
          <form onSubmit={handleSubmit} className="space-y-3.5">
            {formError && (
              <div className="p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0 text-red-600" />
                <span>{formError}</span>
              </div>
            )}

            {/* Name Input */}
            <div>
              <label className="block text-xs font-bold text-gray-800 mb-1 flex items-center gap-1">
                <User className="w-3.5 h-3.5 text-gray-500" />
                <span>আপনার নাম *</span>
              </label>
              <input
                type="text"
                required
                placeholder="যেমন: তানভীর আহমেদ"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full bg-gray-50 focus:bg-white text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 focus:ring-3 focus:ring-red-500/10 outline-hidden transition-all"
              />
            </div>

            {/* Mobile Number Input */}
            <div>
              <label className="block text-xs font-bold text-gray-800 mb-1 flex items-center gap-1">
                <Phone className="w-3.5 h-3.5 text-gray-500" />
                <span>মোবাইল নম্বর * (১১ ডিজিট)</span>
              </label>
              <input
                type="tel"
                required
                placeholder="যেমন: 01712345678"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                className="w-full bg-gray-50 focus:bg-white text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 focus:ring-3 focus:ring-red-500/10 outline-hidden transition-all font-mono"
              />
            </div>

            {/* Delivery Address */}
            {!quickOrderProduct.is_digital && (
              <div>
                <label className="block text-xs font-bold text-gray-800 mb-1 flex items-center gap-1">
                  <MapPin className="w-3.5 h-3.5 text-gray-500" />
                  <span>সম্পূর্ণ ঠিকানা * (জেলা, থানা, এলাকা ও বাড়ি নং)</span>
                </label>
                <textarea
                  required
                  rows={2}
                  placeholder="যেমন: বাসা নং ১২, রোড নং ৪, সেক্টর ৭, উত্তরা, ঢাকা"
                  value={address}
                  onChange={(e) => setAddress(e.target.value)}
                  className="w-full bg-gray-50 focus:bg-white text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 focus:ring-3 focus:ring-red-500/10 outline-hidden transition-all resize-none"
                />
              </div>
            )}

            {/* Delivery Area Selection */}
            {!quickOrderProduct.is_digital && (
              <div>
                <label className="block text-xs font-bold text-gray-800 mb-1.5 flex items-center gap-1">
                  <Truck className="w-3.5 h-3.5 text-gray-500" />
                  <span>ডেলিভারি এরিয়া নির্বাচন করুন:</span>
                </label>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                  <label
                    className={`p-2.5 rounded-xl border text-xs cursor-pointer flex flex-col justify-between transition-all ${
                      selectedZone === 'inside_dhaka'
                        ? 'bg-red-50/70 border-red-500 text-red-950 font-bold ring-2 ring-red-500/20'
                        : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                    }`}
                  >
                    <div className="flex items-center justify-between">
                      <span>ঢাকা সিটি</span>
                      <input
                        type="radio"
                        name="delivery_zone"
                        checked={selectedZone === 'inside_dhaka'}
                        onChange={() => setSelectedZone('inside_dhaka')}
                        className="text-red-600 focus:ring-red-500"
                      />
                    </div>
                    <span className="text-red-600 font-extrabold mt-1">৳৬০</span>
                  </label>

                  <label
                    className={`p-2.5 rounded-xl border text-xs cursor-pointer flex flex-col justify-between transition-all ${
                      selectedZone === 'sub_dhaka'
                        ? 'bg-red-50/70 border-red-500 text-red-950 font-bold ring-2 ring-red-500/20'
                        : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                    }`}
                  >
                    <div className="flex items-center justify-between">
                      <span>ঢাকা সাব-এরিয়া</span>
                      <input
                        type="radio"
                        name="delivery_zone"
                        checked={selectedZone === 'sub_dhaka'}
                        onChange={() => setSelectedZone('sub_dhaka')}
                        className="text-red-600 focus:ring-red-500"
                      />
                    </div>
                    <span className="text-red-600 font-extrabold mt-1">৳১০০</span>
                  </label>

                  <label
                    className={`p-2.5 rounded-xl border text-xs cursor-pointer flex flex-col justify-between transition-all ${
                      selectedZone === 'outside_dhaka'
                        ? 'bg-red-50/70 border-red-500 text-red-950 font-bold ring-2 ring-red-500/20'
                        : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                    }`}
                  >
                    <div className="flex items-center justify-between">
                      <span>ঢাকার বাইরে</span>
                      <input
                        type="radio"
                        name="delivery_zone"
                        checked={selectedZone === 'outside_dhaka'}
                        onChange={() => setSelectedZone('outside_dhaka')}
                        className="text-red-600 focus:ring-red-500"
                      />
                    </div>
                    <span className="text-red-600 font-extrabold mt-1">৳১২০</span>
                  </label>
                </div>
              </div>
            )}

            {/* Payment Method Badge */}
            <div className="p-3 bg-emerald-50 rounded-xl border border-emerald-200 flex items-center justify-between text-xs text-emerald-900">
              <div className="flex items-center gap-2 font-bold">
                <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                <span>পেমেন্ট মেথড: ক্যাশ অন ডেলিভারি</span>
              </div>
              <span className="text-emerald-700 text-[11px]">হাতে পেয়ে টাকা দিন</span>
            </div>

            {/* Order Total Breakdown */}
            <div className="p-3.5 bg-gray-50 rounded-2xl border border-gray-200/80 space-y-1.5 text-xs text-gray-700">
              <div className="flex justify-between">
                <span>পণ্যের মূল্য ({quantity} টি):</span>
                <span className="font-semibold">{settings.currency}{subtotal.toLocaleString()}</span>
              </div>
              <div className="flex justify-between">
                <span>ডেলিভারি চার্জ:</span>
                <span className="font-semibold">
                  {deliveryCharge === 0 ? 'ফ্রি (৳০)' : `${settings.currency}${deliveryCharge}`}
                </span>
              </div>
              <div className="border-t border-gray-200 pt-1.5 flex justify-between text-sm font-extrabold text-gray-950">
                <span>সর্বমোট পরিশোধ করতে হবে:</span>
                <span className="text-red-600 text-base">{settings.currency}{grandTotal.toLocaleString()}</span>
              </div>
            </div>

            {/* Notice Caution */}
            {settings.checkout_note && (
              <p className="text-[11px] text-gray-500 bg-amber-50/70 p-2.5 rounded-xl border border-amber-200/60 leading-relaxed">
                {settings.checkout_note}
              </p>
            )}

            {/* Submit Button */}
            <button
              type="submit"
              disabled={isSubmitting}
              className="w-full bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-sm sm:text-base py-3 rounded-2xl shadow-lg shadow-red-500/25 transition-all transform active:scale-98 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              {isSubmitting ? (
                <div className="flex items-center gap-2">
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  <span>অর্ডার প্রসেস হচ্ছে...</span>
                </div>
              ) : (
                <>
                  <Zap className="w-5 h-5 fill-white" />
                  <span>অর্ডার কনফার্ম করুন (৳{grandTotal.toLocaleString()})</span>
                </>
              )}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};
