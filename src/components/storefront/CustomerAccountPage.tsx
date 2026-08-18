import React, { useState, useEffect } from 'react';
import {
  Package,
  Download,
  Heart,
  RotateCcw,
  User,
  Phone,
  FileText,
  CheckCircle2,
  ExternalLink,
  ShoppingBag,
  Lock,
  MapPin,
  Loader2,
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { ProductCard } from './ProductCard';

interface CustomerAccountPageProps {
  initialTab?: 'orders' | 'downloads' | 'wishlist' | 'refund' | 'profile';
}

export const CustomerAccountPage: React.FC<CustomerAccountPageProps> = ({ initialTab = 'orders' }) => {
  const {
    orders,
    myOrders,
    isAuthenticated,
    apiEnabled,
    openAuthModal,
    wishlist,
    products,
    settings,
    navigate,
    showToast,
    authUser,
    updateProfile,
    changePassword,
    downloads,
    refunds,
    submitRefund,
  } = useStore();
  const [activeTab, setActiveTab] = useState<'orders' | 'downloads' | 'wishlist' | 'refund' | 'profile'>(initialTab);

  // In API mode show the authenticated customer's real orders; otherwise the demo data.
  const displayOrders = isAuthenticated ? myOrders : orders;

  // Filter digital items from orders
  const digitalOrders = displayOrders.filter((o) => o.items.some((i) => i.is_digital));

  const wishlistedProducts = products.filter((p) => wishlist.includes(p.id));

  // Refund Form State
  const [refundOrderId, setRefundOrderId] = useState('');
  const [refundReason, setRefundReason] = useState('');
  const [refundSubmitted, setRefundSubmitted] = useState(false);
  const [refundMethod, setRefundMethod] = useState('original_payment');
  const [refundAccount, setRefundAccount] = useState('');
  const [refundError, setRefundError] = useState<string | null>(null);
  const [refundSubmitting, setRefundSubmitting] = useState(false);
  const [lastRefundId, setLastRefundId] = useState<string | null>(null);

  const handleRefundSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setRefundError(null);

    // API mode: submit a real refund request to the Laravel backend.
    if (isAuthenticated) {
      const orderIdNum = Number(refundOrderId.trim());
      if (!refundOrderId.trim() || !refundReason.trim() || !refundAccount.trim()) {
        setRefundError('অর্ডার আইডি, সমস্যার বিবরণ ও রিফান্ড অ্যাকাউন্ট দিন।');
        return;
      }
      setRefundSubmitting(true);
      const res = await submitRefund({
        order_id: orderIdNum,
        reason: refundReason.trim(),
        refund_method: refundMethod,
        refund_account: refundAccount.trim(),
      });
      setRefundSubmitting(false);
      if (res.success) {
        setLastRefundId(res.refundId ?? null);
        setRefundSubmitted(true);
        showToast(`রিফান্ড আবেদন জমা হয়েছে! ${res.refundId ? 'টোকেন: ' + res.refundId : ''}`, 'success');
      } else {
        setRefundError(res.message);
      }
      return;
    }

    // Demo mode fallback.
    if (!refundOrderId.trim() || !refundReason.trim()) return;
    setRefundSubmitted(true);
    showToast('রিটার্ন/রিফান্ড আবেদন সফলভাবে জমা হয়েছে। কাস্টমার সাপোর্ট আপনাকে কল করবে।', 'success');
  };

  const handleDownload = (filename?: string) => {
    showToast(`ডাউনলোড শুরু হচ্ছে: ${filename || 'digital_product.pdf'}`, 'success');
  };

  // Profile form state
  const [profileName, setProfileName] = useState(authUser?.name ?? '');
  const [profilePhone, setProfilePhone] = useState(authUser?.phone ?? '');
  const [profileEmail, setProfileEmail] = useState(authUser?.email ?? '');
  const [profileAddress, setProfileAddress] = useState(authUser?.address ?? '');
  const [profileSaving, setProfileSaving] = useState(false);
  const [profileError, setProfileError] = useState<string | null>(null);

  // Sync the form once the session is restored (authUser may hydrate late).
  useEffect(() => {
    if (authUser) {
      setProfileName(authUser.name ?? '');
      setProfilePhone(authUser.phone ?? '');
      setProfileEmail(authUser.email ?? '');
      setProfileAddress(authUser.address ?? '');
    }
  }, [authUser]);

  // Password form state
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passSaving, setPassSaving] = useState(false);
  const [passError, setPassError] = useState<string | null>(null);

  const handleProfileSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setProfileError(null);
    if (!profileName.trim() || !profilePhone.trim()) {
      setProfileError('নাম ও ফোন নম্বর আবশ্যক।');
      return;
    }
    setProfileSaving(true);
    const res = await updateProfile({
      name: profileName.trim(),
      phone: profilePhone.trim(),
      email: profileEmail.trim() || undefined,
      address: profileAddress.trim() || undefined,
    });
    setProfileSaving(false);
    if (!res.success) setProfileError(res.message);
  };

  const handlePasswordSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setPassError(null);
    if (!oldPassword || newPassword.length < 6) {
      setPassError('পুরনো পাসওয়ার্ড ও কমপক্ষে ৬ অক্ষরের নতুন পাসওয়ার্ড দিন।');
      return;
    }
    if (newPassword !== confirmPassword) {
      setPassError('নতুন পাসওয়ার্ড দুটি মিলছে না।');
      return;
    }
    setPassSaving(true);
    const res = await changePassword({
      old_password: oldPassword,
      new_password: newPassword,
      confirm_password: confirmPassword,
    });
    setPassSaving(false);
    if (res.success) {
      setOldPassword('');
      setNewPassword('');
      setConfirmPassword('');
    } else {
      setPassError(res.message);
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6 animate-in fade-in duration-200">
      {/* Account Header */}
      <div className="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 rounded-2xl bg-red-600 text-white flex items-center justify-center font-bold text-xl">
            <User className="w-6 h-6" />
          </div>
          <div>
            <h1 className="text-xl font-extrabold text-gray-950">কাস্টমার ড্যাশবোর্ড</h1>
            <p className="text-xs text-gray-500">আপনার অর্ডার ও ডিজিটাল পণ্যের হিসাব</p>
          </div>
        </div>

        {/* Quick Nav Tabs */}
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => setActiveTab('orders')}
            className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer ${
              activeTab === 'orders'
                ? 'bg-gray-900 text-white shadow-xs'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            <Package className="w-3.5 h-3.5" />
            <span>অর্ডার হিস্ট্রি ({displayOrders.length})</span>
          </button>

          <button
            onClick={() => setActiveTab('downloads')}
            className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer ${
              activeTab === 'downloads'
                ? 'bg-gray-900 text-white shadow-xs'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            <Download className="w-3.5 h-3.5" />
            <span>ডিজিটাল ডাউনলোড</span>
          </button>

          <button
            onClick={() => setActiveTab('wishlist')}
            className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer ${
              activeTab === 'wishlist'
                ? 'bg-gray-900 text-white shadow-xs'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            <Heart className="w-3.5 h-3.5" />
            <span>উইশলিস্ট ({wishlist.length})</span>
          </button>

          <button
            onClick={() => setActiveTab('refund')}
            className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer ${
              activeTab === 'refund'
                ? 'bg-gray-900 text-white shadow-xs'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            <RotateCcw className="w-3.5 h-3.5" />
            <span>রিটার্ন ও রিফান্ড</span>
          </button>

          {isAuthenticated && (
            <button
              onClick={() => setActiveTab('profile')}
              className={`px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer ${
                activeTab === 'profile'
                  ? 'bg-gray-900 text-white shadow-xs'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              <User className="w-3.5 h-3.5" />
              <span>প্রোফাইল</span>
            </button>
          )}
        </div>
      </div>

      {/* Orders Tab */}
      {activeTab === 'orders' && (
        <div className="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
          {apiEnabled && !isAuthenticated && (
            <div className="p-4 bg-red-50 border border-red-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div className="text-xs text-red-800 font-semibold">
                আপনার অর্ডার দেখতে প্রথমে লগইন করুন।
              </div>
              <button
                onClick={() => openAuthModal('login')}
                className="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded-xl cursor-pointer shrink-0"
              >
                লগইন / রেজিস্টার
              </button>
            </div>
          )}
          <h2 className="text-base font-extrabold text-gray-900">সাম্প্রতিক অর্ডারসমূহ</h2>
          {displayOrders.length > 0 ? (
            <div className="divide-y divide-gray-100">
              {displayOrders.map((order) => (
                <div key={order.id} className="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <span className="font-mono font-bold text-sm text-gray-900">{order.id}</span>
                      <span
                        className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                          order.status === 'delivered'
                            ? 'bg-emerald-100 text-emerald-800'
                            : 'bg-amber-100 text-amber-800'
                        }`}
                      >
                        {order.status}
                      </span>
                    </div>
                    <p className="text-xs text-gray-500">
                      তারিখ: {order.created_at} | পণ্য: {order.items.length} টি
                    </p>
                    <div className="text-xs font-bold text-red-600">
                      মোট বিল: {settings.currency}{order.total.toLocaleString()}
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => navigate('order_track', { orderId: order.id, phone: order.customer_phone })}
                      className="bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold px-3.5 py-2 rounded-xl transition-colors cursor-pointer"
                    >
                      ট্র্যাক করুন
                    </button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-10 text-gray-400 text-xs">কোনো অর্ডার পাওয়া যায়নি।</div>
          )}
        </div>
      )}

      {/* Digital Downloads Tab */}
      {activeTab === 'downloads' && (
        <div className="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
          {apiEnabled && !isAuthenticated && (
            <div className="p-4 bg-red-50 border border-red-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div className="text-xs text-red-800 font-semibold">
                আপনার ডিজিটাল ডাউনলোড দেখতে লগইন করুন।
              </div>
              <button
                onClick={() => openAuthModal('login')}
                className="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded-xl cursor-pointer shrink-0"
              >
                লগইন / রেজিস্টার
              </button>
            </div>
          )}
          <h2 className="text-base font-extrabold text-gray-900">ডিজিটাল প্রডাক্ট ডাউনলোডস</h2>

          {isAuthenticated ? (
            downloads.length > 0 ? (
              <div className="space-y-3">
                {downloads.map((d) => (
                  <div
                    key={d.id}
                    className="p-4 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                        <FileText className="w-5 h-5" />
                      </div>
                      <div>
                        <h4 className="text-xs font-bold text-gray-900">{d.product_name || 'ডিজিটাল প্রোডাক্ট'}</h4>
                        <span className="text-[11px] text-gray-500 font-mono block">
                          ফাইল: {d.file_name || 'download'}
                        </span>
                        <span className="text-[10px] text-gray-400">
                          বাকি ডাউনলোড: {d.remaining_downloads}
                          {d.expires_at ? ` | মেয়াদ: ${d.expires_at.substring(0, 10)}` : ' | মেয়াদহীন'}
                        </span>
                      </div>
                    </div>
                    <a
                      href={d.download_url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl flex items-center justify-center gap-1.5 shadow-xs transition-colors shrink-0"
                    >
                      <Download className="w-3.5 h-3.5" />
                      <span>ডাউনলোড</span>
                    </a>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-10 text-gray-400 text-xs">
                আপনার একাউন্টে কোনো ডিজিটাল ফাইল বা সফটওয়্যার লাইসেন্স নেই।
              </div>
            )
          ) : digitalOrders.length > 0 ? (
            <div className="space-y-3">
              {digitalOrders.map((order) =>
                order.items
                  .filter((i) => i.is_digital)
                  .map((item, idx) => (
                    <div
                      key={idx}
                      className="p-4 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-between gap-4"
                    >
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                          <FileText className="w-5 h-5" />
                        </div>
                        <div>
                          <h4 className="text-xs font-bold text-gray-900">{item.product_name}</h4>
                          <span className="text-[11px] text-gray-500 font-mono">
                            ফাইল: {item.digital_file || 'activation_instructions.pdf'}
                          </span>
                        </div>
                      </div>
                      <button
                        onClick={() => handleDownload(item.digital_file)}
                        className="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl flex items-center gap-1.5 shadow-xs transition-colors"
                      >
                        <Download className="w-3.5 h-3.5" />
                        <span>ডাউনলোড</span>
                      </button>
                    </div>
                  ))
              )}
            </div>
          ) : (
            <div className="text-center py-10 text-gray-400 text-xs">
              আপনার একাউন্টে কোনো ডিজিটাল ফাইল বা সফটওয়্যার লাইসেন্স নেই।
            </div>
          )}
        </div>
      )}

      {/* Wishlist Tab */}
      {activeTab === 'wishlist' && (
        <div className="space-y-4">
          <h2 className="text-base font-extrabold text-gray-900">আপনার সংরক্ষিত উইশলিস্ট</h2>
          {wishlistedProducts.length > 0 ? (
            <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              {wishlistedProducts.map((prod) => (
                <ProductCard key={prod.id} product={prod} />
              ))}
            </div>
          ) : (
            <div className="bg-white rounded-3xl p-12 text-center text-gray-500 border border-gray-100 space-y-3">
              <Heart className="w-12 h-12 text-gray-300 mx-auto" />
              <p className="text-sm font-bold">আপনার উইশলিস্ট খালি!</p>
              <button
                onClick={() => navigate('home')}
                className="bg-red-600 text-white text-xs font-bold px-4 py-2 rounded-xl"
              >
                পণ্য পছন্দ করুন
              </button>
            </div>
          )}
        </div>
      )}

      {/* Profile Tab */}
      {activeTab === 'profile' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Edit Profile */}
          <div className="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
            <h2 className="text-base font-extrabold text-gray-900 flex items-center gap-2">
              <User className="w-4 h-4 text-red-600" /> প্রোফাইল এডিট
            </h2>
            <form onSubmit={handleProfileSave} className="space-y-3.5">
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">নাম *</label>
                <input
                  type="text"
                  value={profileName}
                  onChange={(e) => setProfileName(e.target.value)}
                  className="w-full bg-gray-50 text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">ফোন নম্বর *</label>
                <input
                  type="tel"
                  value={profilePhone}
                  onChange={(e) => setProfilePhone(e.target.value)}
                  className="w-full bg-gray-50 text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white font-mono"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">ইমেইল (ঐচ্ছিক)</label>
                <input
                  type="email"
                  value={profileEmail}
                  onChange={(e) => setProfileEmail(e.target.value)}
                  className="w-full bg-gray-50 text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">ঠিকানা</label>
                <textarea
                  rows={2}
                  value={profileAddress}
                  onChange={(e) => setProfileAddress(e.target.value)}
                  className="w-full bg-gray-50 text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white resize-none"
                />
              </div>
              {profileError && (
                <div className="p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold">
                  {profileError}
                </div>
              )}
              <button
                type="submit"
                disabled={profileSaving}
                className="w-full bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white text-xs font-bold py-3 rounded-xl shadow-sm flex items-center justify-center gap-2 cursor-pointer"
              >
                {profileSaving && <Loader2 className="w-4 h-4 animate-spin" />}
                প্রোফাইল সেভ করুন
              </button>
            </form>
          </div>

          {/* Change Password */}
          <div className="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
            <h2 className="text-base font-extrabold text-gray-900 flex items-center gap-2">
              <Lock className="w-4 h-4 text-red-600" /> পাসওয়ার্ড পরিবর্তন
            </h2>
            <form onSubmit={handlePasswordSave} className="space-y-3.5">
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">পুরনো পাসওয়ার্ড *</label>
                <input
                  type="password"
                  value={oldPassword}
                  onChange={(e) => setOldPassword(e.target.value)}
                  className="w-full bg-gray-50 text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">নতুন পাসওয়ার্ড *</label>
                <input
                  type="password"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  className="w-full bg-gray-50 text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">নতুন পাসওয়ার্ড (আবার) *</label>
                <input
                  type="password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  className="w-full bg-gray-50 text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white"
                />
              </div>
              {passError && (
                <div className="p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold">
                  {passError}
                </div>
              )}
              <button
                type="submit"
                disabled={passSaving}
                className="w-full bg-gray-900 hover:bg-black disabled:opacity-60 text-white text-xs font-bold py-3 rounded-xl shadow-sm flex items-center justify-center gap-2 cursor-pointer"
              >
                {passSaving && <Loader2 className="w-4 h-4 animate-spin" />}
                পাসওয়ার্ড আপডেট করুন
              </button>
            </form>
          </div>
        </div>
      )}

      {/* Refund Request Tab */}
      {activeTab === 'refund' && (
        <div className="space-y-6 max-w-xl mx-auto">
          {apiEnabled && !isAuthenticated && (
            <div className="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div className="text-xs text-gray-700 font-semibold">
                রিফান্ড আবেদন করতে লগইন করুন।
              </div>
              <button
                onClick={() => openAuthModal('login')}
                className="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded-xl cursor-pointer shrink-0"
              >
                লগইন / রেজিস্টার
              </button>
            </div>
          )}

          <div className="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-5">
            <div className="text-center space-y-1">
              <h2 className="text-lg font-extrabold text-gray-950">রিটার্ন ও রিফান্ড আবেদন</h2>
              <p className="text-xs text-gray-500">
                পণ্যে কোনো ত্রুটি বা সমস্যা থাকলে ডেলিভারির ৩ দিনের মধ্যে আবেদন করুন
              </p>
            </div>

            {!refundSubmitted ? (
              <form onSubmit={handleRefundSubmit} className="space-y-4">
                {isAuthenticated ? (
                  <>
                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1">অর্ডার আইডি *</label>
                      <input
                        type="text"
                        required
                        placeholder="যেমন: 9841 (ইনভয়েস নম্বর)"
                        value={refundOrderId}
                        onChange={(e) => setRefundOrderId(e.target.value)}
                        className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1">সমস্যার বিবরণ *</label>
                      <textarea
                        rows={3}
                        required
                        placeholder="পণ্যের কি সমস্যা বা কেন ফেরত দিতে চাচ্ছেন বিস্তারিত লিখুন..."
                        value={refundReason}
                        onChange={(e) => setRefundReason(e.target.value)}
                        className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 resize-none"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1">রিফান্ড পদ্ধতি *</label>
                      <select
                        value={refundMethod}
                        onChange={(e) => setRefundMethod(e.target.value)}
                        className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500"
                      >
                        <option value="original_payment">মূল পেমেন্ট পদ্ধতি</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="bank">ব্যাংক</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1">রিফান্ড অ্যাকাউন্ট নম্বর *</label>
                      <input
                        type="text"
                        required
                        placeholder="যেমন: 017xxxxxxxx (bKash/Nagad) বা ব্যাংক একাউন্ট নম্বর"
                        value={refundAccount}
                        onChange={(e) => setRefundAccount(e.target.value)}
                        className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
                      />
                    </div>
                  </>
                ) : (
                  <>
                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1">অর্ডার আইডি *</label>
                      <input
                        type="text"
                        required
                        placeholder="যেমন: ORD-9841"
                        value={refundOrderId}
                        onChange={(e) => setRefundOrderId(e.target.value)}
                        className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 uppercase font-mono outline-hidden focus:border-red-500"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1">সমস্যার বিবরণ *</label>
                      <textarea
                        rows={3}
                        required
                        placeholder="পণ্যের কি সমস্যা বা কেন ফেরত দিতে চাচ্ছেন বিস্তারিত লিখুন..."
                        value={refundReason}
                        onChange={(e) => setRefundReason(e.target.value)}
                        className="w-full bg-gray-50 text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 resize-none"
                      />
                    </div>
                  </>
                )}

                {refundError && (
                  <div className="p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold">
                    {refundError}
                  </div>
                )}

                <button
                  type="submit"
                  disabled={refundSubmitting}
                  className="w-full bg-gray-900 hover:bg-black disabled:opacity-60 text-white text-xs font-bold py-3 rounded-xl shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                >
                  {refundSubmitting && <Loader2 className="w-4 h-4 animate-spin" />}
                  আবেদন জমা দিন
                </button>
              </form>
            ) : (
              <div className="p-5 bg-emerald-50 rounded-2xl border border-emerald-200 text-center space-y-2">
                <CheckCircle2 className="w-8 h-8 text-emerald-600 mx-auto" />
                <p className="text-xs font-bold text-emerald-950">
                  আপনার আবেদনটি গ্রহণ করা হয়েছে।
                </p>
                {lastRefundId && (
                  <p className="text-[11px] font-mono font-bold text-emerald-800">
                    টোকেন: {lastRefundId}
                  </p>
                )}
                <p className="text-[11px] text-emerald-800">
                  আমাদের রিটার্ন এক্সিকিউটিভ ২৪ ঘন্টার মধ্যে আপনার সাথে ফোনে যোগাযোগ করবে।
                </p>
              </div>
            )}
          </div>

          {/* My refund requests (API mode) */}
          {isAuthenticated && refunds.length > 0 && (
            <div className="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-3">
              <h3 className="text-sm font-extrabold text-gray-900">আমার রিফান্ড আবেদনসমূহ</h3>
              {refunds.map((r) => (
                <div key={r.id} className="p-4 rounded-2xl bg-gray-50 border border-gray-100 space-y-1.5">
                  <div className="flex items-center justify-between">
                    <span className="font-mono text-xs font-bold text-gray-900">{r.refund_id}</span>
                    <span
                      className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                        r.status === 'approved' || r.status === 'processed'
                          ? 'bg-emerald-100 text-emerald-800'
                          : r.status === 'rejected'
                          ? 'bg-red-100 text-red-800'
                          : 'bg-amber-100 text-amber-800'
                      }`}
                    >
                      {r.status}
                    </span>
                  </div>
                  <p className="text-xs text-gray-600">
                    অর্ডার: {r.order_invoice || r.order_id} | পরিমাণ: {settings.currency}
                    {(r.amount + r.shipping_charge).toLocaleString()}
                  </p>
                  <p className="text-[11px] text-gray-500">{r.reason}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
};
