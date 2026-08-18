import React from 'react';
import {
  Phone,
  Mail,
  MapPin,
  Heart,
  Truck,
  ShieldCheck,
  RotateCcw,
  Facebook,
  Youtube,
  Instagram
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const Footer: React.FC = () => {
  const { settings, categories, navigate, setSelectedCategory } = useStore();

  return (
    <footer className="bg-gray-950 text-gray-400 text-xs mt-12 border-t border-gray-800">
      {/* Top Features Bar */}
      <div className="border-b border-gray-800/80 py-6">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-white">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-gray-900 text-emerald-400 flex items-center justify-center shrink-0 border border-gray-800">
              <Truck className="w-5 h-5" />
            </div>
            <div>
              <h4 className="font-bold text-xs">সারা দেশে ডেলিভারি</h4>
              <p className="text-[11px] text-gray-400">ক্যাশ অন ডেলিভারি সুবিধা</p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-gray-900 text-blue-400 flex items-center justify-center shrink-0 border border-gray-800">
              <ShieldCheck className="w-5 h-5" />
            </div>
            <div>
              <h4 className="font-bold text-xs">১০০% আসল প্রোডাক্ট</h4>
              <p className="text-[11px] text-gray-400">কোয়ালিটি চেক ও ওয়ারেন্টি</p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-gray-900 text-amber-400 flex items-center justify-center shrink-0 border border-gray-800">
              <RotateCcw className="w-5 h-5" />
            </div>
            <div>
              <h4 className="font-bold text-xs">সহজ রিটার্ন পলিসি</h4>
              <p className="text-[11px] text-gray-400">৩ দিনের রিপ্লেসমেন্ট</p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-gray-900 text-red-400 flex items-center justify-center shrink-0 border border-gray-800">
              <Phone className="w-5 h-5" />
            </div>
            <div>
              <h4 className="font-bold text-xs">২৪/৭ হটলাইন সাপোর্ট</h4>
              <p className="text-[11px] text-gray-400 font-mono">{settings.hotline}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Main Footer Links */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
          {/* Col 1 & 2: About Store */}
          <div className="lg:col-span-2 space-y-4">
            <div
              onClick={() => {
                setSelectedCategory(null);
                navigate('home');
              }}
              className="cursor-pointer flex items-center gap-2"
            >
              <div className="w-9 h-9 rounded-xl bg-red-600 flex items-center justify-center text-white font-bold text-lg">
                G
              </div>
              <span className="font-extrabold text-xl tracking-tight text-white font-['Plus_Jakarta_Sans']">
                Shop<span className="text-red-500">Genie</span>
              </span>
            </div>

            <p className="text-xs text-gray-400 leading-relaxed pr-6">
              {settings.footer_about_text}
            </p>

            <div className="space-y-2 pt-1 text-xs">
              <div className="flex items-start gap-2.5">
                <MapPin className="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
                <span>{settings.address}</span>
              </div>
              <div className="flex items-center gap-2.5">
                <Phone className="w-4 h-4 text-emerald-500 shrink-0" />
                <span className="font-mono text-white font-bold">{settings.hotline}</span>
              </div>
              <div className="flex items-center gap-2.5">
                <Mail className="w-4 h-4 text-blue-400 shrink-0" />
                <span>{settings.email}</span>
              </div>
            </div>
          </div>

          {/* Col 3: Popular Categories */}
          <div className="space-y-3">
            <h4 className="font-bold text-white text-sm">টপ ক্যাটাগরি</h4>
            <ul className="space-y-2">
              {categories.slice(0, 5).map((cat) => (
                <li key={cat.id}>
                  <button
                    onClick={() => {
                      setSelectedCategory(cat.id);
                      navigate('home');
                    }}
                    className="hover:text-white transition-colors"
                  >
                    {cat.name}
                  </button>
                </li>
              ))}
            </ul>
          </div>

          {/* Col 4: Customer Services */}
          <div className="space-y-3">
            <h4 className="font-bold text-white text-sm">গ্রাহক সেবা</h4>
            <ul className="space-y-2">
              <li>
                <button onClick={() => navigate('order_track')} className="hover:text-white">
                  অর্ডার ট্র্যাকিং (Live Status)
                </button>
              </li>
              <li>
                <button
                  onClick={() => navigate('customer_account', { tab: 'orders' })}
                  className="hover:text-white"
                >
                  আমার অ্যাকাউন্ট
                </button>
              </li>
              <li>
                <button
                  onClick={() => navigate('customer_account', { tab: 'downloads' })}
                  className="hover:text-white"
                >
                  ডিজিটাল পণ্য ডাউনলোড
                </button>
              </li>
              <li>
                <button onClick={() => navigate('complaint')} className="hover:text-white">
                  অভিযোগ ও সাপোর্ট
                </button>
              </li>
              <li>
                <button onClick={() => navigate('contact')} className="hover:text-white">
                  যোগাযোগ
                </button>
              </li>
            </ul>
          </div>

          {/* Col 5: Policies & Social */}
          <div className="space-y-3">
            <h4 className="font-bold text-white text-sm">শর্তাবলী ও পলিসি</h4>
            <ul className="space-y-2">
              <li>
                <button onClick={() => navigate('policy', { type: 'return' })} className="hover:text-white">
                  রিটার্ন ও রিফান্ড পলিসি
                </button>
              </li>
              <li>
                <button onClick={() => navigate('policy', { type: 'delivery' })} className="hover:text-white">
                  ডেলিভারি তথ্য
                </button>
              </li>
              <li>
                <button onClick={() => navigate('policy', { type: 'privacy' })} className="hover:text-white">
                  গোপনীয়তা নীতি
                </button>
              </li>
              <li>
                <button onClick={() => navigate('policy', { type: 'terms' })} className="hover:text-white">
                  ব্যবহারের শর্তাবলী
                </button>
              </li>
            </ul>

            <div className="pt-3">
              <h5 className="font-bold text-white text-xs mb-2">সোশ্যাল মিডিয়া</h5>
              <div className="flex gap-2">
                <a
                  href={settings.facebook_page}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-8 h-8 rounded-lg bg-gray-900 hover:bg-red-600 text-white flex items-center justify-center transition-colors"
                >
                  <Facebook className="w-4 h-4" />
                </a>
                <a
                  href={settings.youtube_link || '#'}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-8 h-8 rounded-lg bg-gray-900 hover:bg-red-600 text-white flex items-center justify-center transition-colors"
                >
                  <Youtube className="w-4 h-4" />
                </a>
                <a
                  href={settings.instagram_link || '#'}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-8 h-8 rounded-lg bg-gray-900 hover:bg-red-600 text-white flex items-center justify-center transition-colors"
                >
                  <Instagram className="w-4 h-4" />
                </a>
              </div>
            </div>
          </div>
        </div>

        {/* Payment Methods & Bottom Line */}
        <div className="mt-12 pt-6 border-t border-gray-900 flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-gray-500">
          <div>
            © {new Date().getFullYear()} <span className="text-gray-300 font-bold">Shop Genie</span>. সর্বস্বত্ব সংরক্ষিত।
          </div>

          {/* Payment Methods Badges */}
          <div className="flex items-center gap-2 font-bold text-gray-400">
            <span className="bg-gray-900 px-2.5 py-1 rounded text-pink-400 border border-gray-800">
              bKash
            </span>
            <span className="bg-gray-900 px-2.5 py-1 rounded text-orange-400 border border-gray-800">
              Nagad
            </span>
            <span className="bg-gray-900 px-2.5 py-1 rounded text-purple-400 border border-gray-800">
              Rocket
            </span>
            <span className="bg-gray-900 px-2.5 py-1 rounded text-emerald-400 border border-gray-800">
              Cash on Delivery
            </span>
          </div>
        </div>
      </div>
    </footer>
  );
};
