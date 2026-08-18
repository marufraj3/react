import React from 'react';
import { Star, CheckCircle2, Trash2, XCircle, User } from 'lucide-react';
import { useStore } from '../../context/StoreContext';

export const AdminReviews: React.FC = () => {
  const { reviews, products, updateReviewStatus, deleteReview } = useStore();

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-gray-950">
            কাস্টমার রিভিউ ও রেটিং
          </h1>
          <p className="text-xs text-gray-500">
            গ্রাহকদের দেওয়া রিভিউ অনুমোদন, প্রত্যাখ্যান অথবা মুছে ফেলুন
          </p>
        </div>
      </div>

      <div className="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-gray-50 border-b border-gray-100 text-gray-500 font-bold">
              <tr>
                <th className="py-3.5 pl-4">প্রোডাক্ট</th>
                <th className="py-3.5">কাস্টমার</th>
                <th className="py-3.5">রেটিং</th>
                <th className="py-3.5">মন্তব্য (Review)</th>
                <th className="py-3.5">স্ট্যাটাস</th>
                <th className="py-3.5 text-right pr-4">অ্যাকশন</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 text-gray-700">
              {reviews.length > 0 ? (
                reviews.map((rev) => {
                  const product = products.find((p) => p.id === rev.product_id);
                  return (
                    <tr key={rev.id} className="hover:bg-gray-50/80 transition-colors">
                      <td className="py-3 pl-4">
                        <div className="flex items-center gap-2">
                          {product?.image && (
                            <img src={product.image} alt="" className="w-9 h-9 rounded-lg object-cover" />
                          )}
                          <span className="font-bold text-gray-900 line-clamp-1 max-w-[150px]">
                            {product?.name || `Product #${rev.product_id}`}
                          </span>
                        </div>
                      </td>

                      <td className="py-3 font-semibold text-gray-900">
                        {rev.customer_name}
                      </td>

                      <td className="py-3">
                        <div className="flex items-center gap-0.5">
                          {Array.from({ length: 5 }).map((_, i) => (
                            <Star
                              key={i}
                              className={`w-3.5 h-3.5 ${
                                i < rev.rating ? 'fill-amber-400 text-amber-400' : 'text-gray-200'
                              }`}
                            />
                          ))}
                        </div>
                      </td>

                      <td className="py-3">
                        <p className="text-gray-600 max-w-sm">{rev.comment}</p>
                      </td>

                      <td className="py-3">
                        <span
                          className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                            rev.status === 'approved'
                              ? 'bg-emerald-100 text-emerald-800'
                              : 'bg-amber-100 text-amber-800'
                          }`}
                        >
                          {rev.status}
                        </span>
                      </td>

                      <td className="py-3 text-right pr-4">
                        <div className="flex items-center justify-end gap-1.5">
                          {rev.status !== 'approved' ? (
                            <button
                              onClick={() => updateReviewStatus(rev.id, 'approved')}
                              className="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100"
                              title="অনুমোদন করুন"
                            >
                              <CheckCircle2 className="w-4 h-4" />
                            </button>
                          ) : (
                            <button
                              onClick={() => updateReviewStatus(rev.id, 'pending')}
                              className="p-1.5 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100"
                              title="পেন্ডিং করুন"
                            >
                              <XCircle className="w-4 h-4" />
                            </button>
                          )}
                          <button
                            onClick={() => {
                              if (confirm('আপনি কি রিভিউটি মুছে ফেলতে চান?')) {
                                deleteReview(rev.id);
                              }
                            }}
                            className="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50"
                            title="মুছুন"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              ) : (
                <tr>
                  <td colSpan={6} className="py-8 text-center text-gray-400 text-xs">
                    কোনো রিভিউ পাওয়া যায়নি।
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
