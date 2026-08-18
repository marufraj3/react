import React from 'react';
import { ShieldCheck, RotateCcw, FileText, Truck, ChevronRight } from 'lucide-react';
import { useStore } from '../../context/StoreContext';

interface PolicyPageProps {
  type?: 'privacy' | 'terms' | 'return' | 'delivery';
}

export const PolicyPage: React.FC<PolicyPageProps> = ({ type = 'return' }) => {
  const { navigate, settings } = useStore();

  const renderContent = () => {
    switch (type) {
      case 'return':
        return (
          <div className="space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
            <h2 className="text-base font-extrabold text-gray-900">রিটার্ন ও রিফান্ড পলিসি</h2>
            <p>
              Shop Genie-তে গ্রাহক সন্তুষ্টি আমাদের শীর্ষ অগ্রাধিকার। আপনি যদি কোনো কারণে ক্রুটিযুক্ত বা ক্ষতিগ্রস্ত পণ্য পেয়ে থাকেন, তবে নিম্নলিখিত শর্তাবলী অনুযায়ী পণ্য ফেরত বা পরিবর্তন করতে পারবেন:
            </p>
            <ul className="list-disc pl-5 space-y-2">
              <li><strong>ডেলিভারি চেকিং:</strong> ডেলিভারি ম্যানের সামনে প্যাকেট খুলে পণ্য যাচাই করবেন। কোনো অমিল বা সমস্যা দেখা দিলে সাথে সাথে ডেলিভারি ম্যানকে ফেরত দিন অথবা আমাদের হটলাইনে কল করুন।</li>
              <li><strong>রিটার্ন সময়সীমা:</strong> পণ্য গ্রহণের ৩ দিনের মধ্যে রিটার্ন রিকোয়েস্ট জমা দিতে হবে।</li>
              <li><strong>পণ্য অবস্থা:</strong> পণ্যটি আসল প্যাকেজিং, আনুষঙ্গিক যন্ত্রাংশ ও ক্যাশ মেমো সহ অবিকৃত অবস্থায় থাকতে হবে।</li>
              <li><strong>রিফান্ড পদ্ধতি:</strong> রিফান্ড অনুমোদন হওয়ার ৩ থেকে ৫ কার্যদিবসের মধ্যে বিকাশ/নগদ এর মাধ্যমে অর্থ ফেরত দেওয়া হবে।</li>
            </ul>
          </div>
        );
      case 'delivery':
        return (
          <div className="space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
            <h2 className="text-base font-extrabold text-gray-900">ডেলিভারি ও শিপিং সংক্রান্ত তথ্য</h2>
            <p>
              আমরা সারা বাংলাদেশে বিশ্বস্ত কুরিয়ার সার্ভিসের মাধ্যমে দ্রুত ও নিরাপদে পণ্য পৌঁছে দেই।
            </p>
            <ul className="list-disc pl-5 space-y-2">
              <li><strong>ঢাকা সিটি কর্পোরেশন:</strong> ডেলিভারি চার্জ ৳৬০ (২৪ থেকে ৪৮ ঘন্টার মধ্যে ডেলিভারি)।</li>
              <li><strong>ঢাকা সাব-এরিয়া (সাভার, কেরানীগঞ্জ, গাজীপুর):</strong> ডেলিভারি চার্জ ৳১০০ (৪৮ ঘন্টা)।</li>
              <li><strong>ঢাকার বাইরে সমগ্র বাংলাদেশ:</strong> ডেলিভারি চার্জ ৳১২০ (২ থেকে ৩ কার্যদিবস)।</li>
            </ul>
          </div>
        );
      case 'privacy':
        return (
          <div className="space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
            <h2 className="text-base font-extrabold text-gray-900">প্রাইভেসি ও গোপনীয়তা নীতি</h2>
            <p>
              আমরা আপনার ব্যক্তিগত তথ্যের সর্বোচ্চ সুরক্ষা নিশ্চিত করি। অর্ডার সম্পাদন ও গ্রাহক সেবা ব্যতীত অন্য কোনো উদ্দেশ্যে তথ্য কারো সাথে শেয়ার করা হয় না।
            </p>
          </div>
        );
      default:
        return (
          <div className="space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
            <h2 className="text-base font-extrabold text-gray-900">শর্তাবলী (Terms & Conditions)</h2>
            <p>
              Shop Genie প্ল্যাটফর্ম ব্যবহারের মাধ্যমে আপনি আমাদের সকল নিয়ম ও শর্তাবলী মেনে নিতে সম্মত হচ্ছেন।
            </p>
          </div>
        );
    }
  };

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-6 animate-in fade-in duration-200">
      <nav className="flex items-center gap-2 text-xs text-gray-500">
        <button onClick={() => navigate('home')} className="hover:text-red-600">হোম</button>
        <ChevronRight className="w-3.5 h-3.5" />
        <span className="text-gray-900 font-semibold uppercase">{type} Policy</span>
      </nav>

      <div className="bg-white rounded-3xl p-6 sm:p-10 border border-gray-100 shadow-xs">
        {renderContent()}
      </div>
    </div>
  );
};
