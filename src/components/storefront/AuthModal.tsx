import React, { useState } from 'react';
import { X, Phone, Lock, User, Mail, LogIn, UserPlus, Loader2 } from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const AuthModal: React.FC = () => {
  const { authModalOpen, authModalMode, closeAuthModal, openAuthModal, login, registerUser } = useStore();

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (!authModalOpen) return null;

  const reset = () => {
    setError(null);
    setSubmitting(false);
  };

  const handleClose = () => {
    reset();
    closeAuthModal();
  };

  const switchMode = (mode: 'login' | 'register') => {
    setError(null);
    setPassword('');
    openAuthModal(mode);
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!phone.trim() || !password) {
      setError('ফোন নম্বর/ইমেইল ও পাসওয়ার্ড দিন।');
      return;
    }
    setSubmitting(true);
    setError(null);
    const res = await login(phone.trim(), password);
    setSubmitting(false);
    if (!res.success) setError(res.message);
  };

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim() || !phone.trim() || password.length < 6) {
      setError('নাম, সঠিক ফোন নম্বর ও কমপক্ষে ৬ অক্ষরের পাসওয়ার্ড দিন।');
      return;
    }
    setSubmitting(true);
    setError(null);
    const res = await registerUser({
      name: name.trim(),
      phone: phone.trim(),
      email: email.trim() || undefined,
      password,
    });
    setSubmitting(false);
    if (!res.success) setError(res.message);
  };

  return (
    <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150">
      <div className="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
        {/* Header */}
        <div className="bg-gradient-to-r from-gray-900 to-red-600 px-6 py-5 flex items-center justify-between">
          <div>
            <h2 className="text-white font-black text-lg leading-tight">
              {authModalMode === 'login' ? 'কাস্টমার লগইন' : 'নতুন অ্যাকাউন্ট খুলুন'}
            </h2>
            <p className="text-white/70 text-[11px] mt-0.5">
              {authModalMode === 'login'
                ? 'আপনার অর্ডার ও ডিজিটাল ডাউনলোড দেখতে লগইন করুন'
                : 'কয়েক সেকেন্ডেই ফ্রি অ্যাকাউন্ট তৈরি করুন'}
            </p>
          </div>
          <button onClick={handleClose} className="text-white/80 hover:text-white p-1" aria-label="Close">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Mode switch */}
        <div className="flex px-6 pt-4 gap-2">
          <button
            onClick={() => switchMode('login')}
            className={`flex-1 py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer ${
              authModalMode === 'login' ? 'bg-red-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            <LogIn className="w-3.5 h-3.5" /> লগইন
          </button>
          <button
            onClick={() => switchMode('register')}
            className={`flex-1 py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer ${
              authModalMode === 'register' ? 'bg-red-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            <UserPlus className="w-3.5 h-3.5" /> রেজিস্টার
          </button>
        </div>

        {/* Form */}
        <form onSubmit={authModalMode === 'login' ? handleLogin : handleRegister} className="p-6 space-y-3.5">
          {authModalMode === 'register' && (
            <div className="relative">
              <User className="w-4 h-4 text-gray-400 absolute left-3.5 top-3" />
              <input
                type="text"
                placeholder="আপনার নাম"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full bg-gray-50 text-sm rounded-xl pl-10 pr-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white"
              />
            </div>
          )}

          <div className="relative">
            <Phone className="w-4 h-4 text-gray-400 absolute left-3.5 top-3" />
            <input
              type="tel"
              placeholder={authModalMode === 'login' ? 'ফোন নম্বর বা ইমেইল' : 'ফোন নম্বর (যেমন 017xxxxxxxx)'}
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              className="w-full bg-gray-50 text-sm rounded-xl pl-10 pr-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white font-mono"
            />
          </div>

          {authModalMode === 'register' && (
            <div className="relative">
              <Mail className="w-4 h-4 text-gray-400 absolute left-3.5 top-3" />
              <input
                type="email"
                placeholder="ইমেইল (ঐচ্ছিক)"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full bg-gray-50 text-sm rounded-xl pl-10 pr-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white"
              />
            </div>
          )}

          <div className="relative">
            <Lock className="w-4 h-4 text-gray-400 absolute left-3.5 top-3" />
            <input
              type="password"
              placeholder="পাসওয়ার্ড"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full bg-gray-50 text-sm rounded-xl pl-10 pr-3.5 py-2.5 border border-gray-200 outline-hidden focus:border-red-500 focus:bg-white"
            />
          </div>

          {error && (
            <div className="p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold">
              {error}
            </div>
          )}

          <button
            type="submit"
            disabled={submitting}
            className="w-full bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold text-sm py-3 rounded-xl shadow-sm flex items-center justify-center gap-2 cursor-pointer"
          >
            {submitting && <Loader2 className="w-4 h-4 animate-spin" />}
            {authModalMode === 'login' ? 'লগইন করুন' : 'অ্যাকাউন্ট তৈরি করুন'}
          </button>
        </form>
      </div>
    </div>
  );
};
