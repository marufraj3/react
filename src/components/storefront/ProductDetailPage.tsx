import React, { useState } from 'react';
import {
  Star,
  Zap,
  ShoppingBag,
  Truck,
  ShieldCheck,
  RefreshCw,
  Share2,
  Heart,
  ChevronRight,
  Sparkles,
  CheckCircle2,
  Play,
  MessageSquare,
  User,
  Send
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { ProductCard } from './ProductCard';

interface ProductDetailPageProps {
  productId: number;
}

export const ProductDetailPage: React.FC<ProductDetailPageProps> = ({ productId }) => {
  const {
    products,
    categories,
    reviews,
    addReview,
    addToCart,
    openQuickOrder,
    wishlist,
    toggleWishlist,
    navigate,
    settings,
    showToast
  } = useStore();

  const product = products.find((p) => p.id === productId);

  // States
  const [selectedImg, setSelectedImg] = useState<string>(product?.image || '');
  const [selectedColor, setSelectedColor] = useState<string>(product?.colors?.[0] || '');
  const [selectedSize, setSelectedSize] = useState<string>(product?.sizes?.[0] || '');
  const [quantity, setQuantity] = useState<number>(1);
  const [activeTab, setActiveTab] = useState<'description' | 'video' | 'reviews'>('description');

  // Review Form
  const [reviewName, setReviewName] = useState('');
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewComment, setReviewComment] = useState('');

  if (!product) {
    return (
      <div className="max-w-7xl mx-auto px-4 py-16 text-center">
        <h2 className="text-xl font-bold text-gray-800">প্রোডাক্ট খুঁজে পাওয়া যায়নি!</h2>
        <button
          onClick={() => navigate('home')}
          className="mt-4 bg-red-600 text-white text-sm font-bold px-4 py-2 rounded-xl"
        >
          হোমপেজে ফিরে যান
        </button>
      </div>
    );
  }

  const category = categories.find((c) => c.id === product.category_id);
  const productReviews = reviews.filter((r) => r.product_id === product.id && r.status === 'approved');
  const relatedProducts = products.filter(
    (p) => p.category_id === product.category_id && p.id !== product.id
  ).slice(0, 4);

  const isWishlisted = wishlist.includes(product.id);
  const discountPercent =
    product.old_price && product.old_price > product.new_price
      ? Math.round(((product.old_price - product.new_price) / product.old_price) * 100)
      : null;

  const handleReviewSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!reviewName.trim() || !reviewComment.trim()) {
      showToast('অনুগ্রহ করে নাম এবং মতামত লিখুন', 'error');
      return;
    }
    addReview({
      product_id: product.id,
      customer_name: reviewName.trim(),
      rating: reviewRating,
      comment: reviewComment.trim(),
    });
    setReviewName('');
    setReviewComment('');
  };

  const handleShare = () => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(window.location.href);
      showToast('লিঙ্ক কপি করা হয়েছে!', 'success');
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-8 animate-in fade-in duration-200">
      {/* Breadcrumbs */}
      <nav className="flex items-center gap-2 text-xs text-gray-500">
        <button onClick={() => navigate('home')} className="hover:text-red-600">হোম</button>
        <ChevronRight className="w-3.5 h-3.5" />
        {category && (
          <>
            <button
              onClick={() => {
                navigate('home');
              }}
              className="hover:text-red-600"
            >
              {category.name}
            </button>
            <ChevronRight className="w-3.5 h-3.5" />
          </>
        )}
        <span className="text-gray-900 font-semibold truncate max-w-xs">{product.name}</span>
      </nav>

      {/* Main Product Showcase Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 bg-white p-4 sm:p-8 rounded-3xl border border-gray-100 shadow-xs">
        {/* Image Gallery (5 cols on lg) */}
        <div className="lg:col-span-5 space-y-4">
          <div className="relative aspect-square rounded-2xl overflow-hidden bg-gray-50 border border-gray-100 shadow-xs">
            <img
              src={selectedImg || product.image}
              alt={product.name}
              className="w-full h-full object-cover"
            />
            {discountPercent && (
              <span className="absolute top-3 left-3 bg-red-600 text-white text-xs font-extrabold px-2.5 py-1 rounded-lg shadow-sm">
                -{discountPercent}% ছাড়
              </span>
            )}
            {product.free_delivery && (
              <span className="absolute top-3 right-3 bg-emerald-600 text-white text-xs font-bold px-2 py-1 rounded-lg shadow-xs flex items-center gap-1">
                <Sparkles className="w-3 h-3" /> ফ্রি ডেলিভারি
              </span>
            )}
          </div>

          {/* Thumbnails */}
          {product.gallery && product.gallery.length > 0 && (
            <div className="flex gap-2.5 overflow-x-auto pb-2">
              <button
                onClick={() => setSelectedImg(product.image)}
                className={`w-16 h-16 rounded-xl overflow-hidden border-2 shrink-0 transition-all ${
                  (selectedImg || product.image) === product.image ? 'border-red-600 ring-2 ring-red-500/20' : 'border-gray-200'
                }`}
              >
                <img src={product.image} alt="main" className="w-full h-full object-cover" />
              </button>
              {product.gallery.map((img, i) => (
                <button
                  key={i}
                  onClick={() => setSelectedImg(img)}
                  className={`w-16 h-16 rounded-xl overflow-hidden border-2 shrink-0 transition-all ${
                    selectedImg === img ? 'border-red-600 ring-2 ring-red-500/20' : 'border-gray-200'
                  }`}
                >
                  <img src={img} alt={`thumb-${i}`} className="w-full h-full object-cover" />
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Product Details & Purchase Controls (7 cols on lg) */}
        <div className="lg:col-span-7 flex flex-col justify-between space-y-6">
          <div className="space-y-4">
            {/* Title & SKU */}
            <div>
              <div className="flex items-center justify-between gap-2 mb-1.5">
                <span className="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-mono font-bold">
                  SKU: {product.product_code}
                </span>
                <div className="flex items-center gap-2">
                  <button
                    onClick={handleShare}
                    className="p-2 text-gray-500 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors"
                    title="লিঙ্ক শেয়ার করুন"
                  >
                    <Share2 className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => toggleWishlist(product.id)}
                    className={`p-2 rounded-lg transition-colors ${
                      isWishlisted ? 'text-red-600 bg-red-50' : 'text-gray-500 hover:bg-gray-100'
                    }`}
                    title="উইশলিস্ট"
                  >
                    <Heart className={`w-4 h-4 ${isWishlisted ? 'fill-red-600' : ''}`} />
                  </button>
                </div>
              </div>

              <h1 className="text-xl sm:text-2xl lg:text-3xl font-extrabold text-gray-950 leading-tight">
                {product.name}
              </h1>

              {/* Ratings and reviews */}
              <div className="flex items-center gap-3 mt-2 text-xs">
                <div className="flex items-center gap-1 text-amber-500 font-bold">
                  <Star className="w-4 h-4 fill-amber-400 text-amber-400" />
                  <span>{product.ratting.toFixed(1)}</span>
                </div>
                <span className="text-gray-300">|</span>
                <span className="text-gray-500 font-medium">
                  {productReviews.length} টি ভেরিফাইড রিভিউ
                </span>
                <span className="text-gray-300">|</span>
                <span className="text-emerald-700 font-semibold flex items-center gap-1">
                  <CheckCircle2 className="w-3.5 h-3.5" /> স্টক এভেলেবল ({product.stock} টি)
                </span>
              </div>
            </div>

            {/* Pricing */}
            <div className="p-4 bg-red-50/60 rounded-2xl border border-red-100 flex items-baseline gap-3">
              <span className="text-2xl sm:text-3xl font-black text-red-600">
                {settings.currency}{product.new_price.toLocaleString()}
              </span>
              {product.old_price && (
                <span className="text-base text-gray-400 line-through">
                  {settings.currency}{product.old_price.toLocaleString()}
                </span>
              )}
              {discountPercent && (
                <span className="text-xs font-bold text-red-700 bg-white px-2.5 py-1 rounded-md border border-red-200 shadow-2xs">
                  আপনি সাশ্রয় করছেন {settings.currency}{(product.old_price! - product.new_price).toLocaleString()}
                </span>
              )}
            </div>

            {/* Color Swatches */}
            {product.colors && product.colors.length > 0 && (
              <div>
                <label className="block text-xs font-bold text-gray-800 mb-2">
                  কালার পছন্দ করুন: <span className="text-red-600">{selectedColor}</span>
                </label>
                <div className="flex flex-wrap gap-2">
                  {product.colors.map((c) => (
                    <button
                      key={c}
                      onClick={() => setSelectedColor(c)}
                      className={`px-4 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
                        selectedColor === c
                          ? 'bg-red-600 text-white border-red-600 shadow-sm'
                          : 'bg-gray-50 text-gray-800 border-gray-200 hover:bg-gray-100'
                      }`}
                    >
                      {c}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {/* Size Swatches */}
            {product.sizes && product.sizes.length > 0 && (
              <div>
                <label className="block text-xs font-bold text-gray-800 mb-2">
                  সাইজ / ভ্যারিয়েন্ট: <span className="text-red-600">{selectedSize}</span>
                </label>
                <div className="flex flex-wrap gap-2">
                  {product.sizes.map((s) => (
                    <button
                      key={s}
                      onClick={() => setSelectedSize(s)}
                      className={`px-4 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
                        selectedSize === s
                          ? 'bg-red-600 text-white border-red-600 shadow-sm'
                          : 'bg-gray-50 text-gray-800 border-gray-200 hover:bg-gray-100'
                      }`}
                    >
                      {s}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {/* Quantity Stepper */}
            <div>
              <label className="block text-xs font-bold text-gray-800 mb-2">পরিমাণ (Quantity):</label>
              <div className="flex items-center border border-gray-200 rounded-xl bg-gray-50 w-36 overflow-hidden">
                <button
                  onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                  className="w-10 h-10 flex items-center justify-center text-gray-700 hover:bg-gray-200 text-lg font-bold"
                >
                  -
                </button>
                <span className="flex-1 text-center font-bold text-sm text-gray-900">{quantity}</span>
                <button
                  onClick={() => setQuantity((q) => Math.min(product.stock, q + 1))}
                  className="w-10 h-10 flex items-center justify-center text-gray-700 hover:bg-gray-200 text-lg font-bold"
                >
                  +
                </button>
              </div>
            </div>

            {/* Primary Action Buttons */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
              <button
                onClick={() => openQuickOrder(product)}
                disabled={product.stock <= 0}
                className="w-full bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-extrabold text-sm sm:text-base py-3.5 rounded-2xl shadow-lg shadow-red-500/25 flex items-center justify-center gap-2 transition-transform active:scale-98 cursor-pointer disabled:opacity-50"
              >
                <Zap className="w-5 h-5 fill-white" />
                <span>সরাসরি অর্ডার করুন</span>
              </button>

              <button
                onClick={() => addToCart(product, quantity, selectedColor, selectedSize)}
                disabled={product.stock <= 0}
                className="w-full bg-gray-950 hover:bg-black text-white font-bold text-sm sm:text-base py-3.5 rounded-2xl shadow-sm flex items-center justify-center gap-2 transition-transform active:scale-98 cursor-pointer disabled:opacity-50"
              >
                <ShoppingBag className="w-5 h-5 text-amber-400" />
                <span>কার্টে যোগ করুন</span>
              </button>
            </div>
          </div>

          {/* Delivery & Assurance Box */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-200/70 text-xs text-gray-700">
            <div className="flex items-center gap-2.5">
              <Truck className="w-5 h-5 text-emerald-600 shrink-0" />
              <div>
                <div className="font-bold text-gray-900">ক্যাশ অন ডেলিভারি</div>
                <div className="text-[11px] text-gray-500">হাতে পেয়ে টাকা দিন</div>
              </div>
            </div>
            <div className="flex items-center gap-2.5">
              <ShieldCheck className="w-5 h-5 text-blue-600 shrink-0" />
              <div>
                <div className="font-bold text-gray-900">১০০% আসল প্রোডাক্ট</div>
                <div className="text-[11px] text-gray-500">গুণগত মানের নিশ্চয়তা</div>
              </div>
            </div>
            <div className="flex items-center gap-2.5">
              <RefreshCw className="w-5 h-5 text-amber-600 shrink-0" />
              <div>
                <div className="font-bold text-gray-900">৩ দিনে রিটার্ন</div>
                <div className="text-[11px] text-gray-500">ত্রুটিযুক্ত পণ্যে পরিবর্তন</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Tabs: Description, Video, Reviews */}
      <div className="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs">
        <div className="flex border-b border-gray-200 gap-4 sm:gap-8 text-sm font-extrabold mb-6">
          <button
            onClick={() => setActiveTab('description')}
            className={`pb-3.5 border-b-2 transition-colors cursor-pointer ${
              activeTab === 'description'
                ? 'border-red-600 text-red-600'
                : 'border-transparent text-gray-500 hover:text-gray-900'
            }`}
          >
            প্রোডাক্ট বিবরণ (Description)
          </button>
          {product.pro_video && (
            <button
              onClick={() => setActiveTab('video')}
              className={`pb-3.5 border-b-2 transition-colors cursor-pointer flex items-center gap-1.5 ${
                activeTab === 'video'
                  ? 'border-red-600 text-red-600'
                  : 'border-transparent text-gray-500 hover:text-gray-900'
              }`}
            >
              <Play className="w-4 h-4" />
              <span>ভিডিও প্রিভিউ</span>
            </button>
          )}
          <button
            onClick={() => setActiveTab('reviews')}
            className={`pb-3.5 border-b-2 transition-colors cursor-pointer flex items-center gap-1.5 ${
              activeTab === 'reviews'
                ? 'border-red-600 text-red-600'
                : 'border-transparent text-gray-500 hover:text-gray-900'
            }`}
          >
            <MessageSquare className="w-4 h-4" />
            <span>কাস্টমার রিভিউ ({productReviews.length})</span>
          </button>
        </div>

        {/* Tab Contents */}
        {activeTab === 'description' && (
          <div
            className="prose prose-sm max-w-none text-gray-700 leading-relaxed"
            dangerouslySetInnerHTML={{ __html: product.description }}
          />
        )}

        {activeTab === 'video' && product.pro_video && (
          <div className="max-w-2xl mx-auto aspect-video rounded-2xl overflow-hidden shadow-lg">
            <iframe
              src={`https://www.youtube.com/embed/${product.pro_video}`}
              title="Product Video Preview"
              className="w-full h-full"
              allowFullScreen
            />
          </div>
        )}

        {activeTab === 'reviews' && (
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
            {/* Review Submission Form */}
            <div className="lg:col-span-5 bg-gray-50 p-5 rounded-2xl border border-gray-200/80">
              <h3 className="font-extrabold text-base text-gray-900 mb-3">
                আপনার মতামত বা রিভিউ দিন
              </h3>
              <form onSubmit={handleReviewSubmit} className="space-y-3.5">
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">রেটিং:</label>
                  <div className="flex gap-1.5">
                    {[1, 2, 3, 4, 5].map((star) => (
                      <button
                        key={star}
                        type="button"
                        onClick={() => setReviewRating(star)}
                        className="p-1 hover:scale-110 transition-transform"
                      >
                        <Star
                          className={`w-6 h-6 ${
                            star <= reviewRating
                              ? 'fill-amber-400 text-amber-400'
                              : 'text-gray-300'
                          }`}
                        />
                      </button>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">আপনার নাম *</label>
                  <input
                    type="text"
                    required
                    placeholder="যেমন: সাকিব আল হাসান"
                    value={reviewName}
                    onChange={(e) => setReviewName(e.target.value)}
                    className="w-full bg-white text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">আপনার মন্তব্য *</label>
                  <textarea
                    required
                    rows={3}
                    placeholder="প্রোডাক্টটি কেমন লেগেছে লিখুন..."
                    value={reviewComment}
                    onChange={(e) => setReviewComment(e.target.value)}
                    className="w-full bg-white text-xs rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden resize-none"
                  />
                </div>

                <button
                  type="submit"
                  className="w-full bg-gray-900 hover:bg-black text-white text-xs font-bold py-2.5 rounded-xl shadow-xs flex items-center justify-center gap-2"
                >
                  <Send className="w-3.5 h-3.5" />
                  <span>রিভিউ জমা দিন</span>
                </button>
              </form>
            </div>

            {/* Reviews List */}
            <div className="lg:col-span-7 space-y-4">
              <h3 className="font-extrabold text-base text-gray-900">
                গ্রাহকদের রিভিউ ({productReviews.length})
              </h3>
              {productReviews.length > 0 ? (
                <div className="space-y-3">
                  {productReviews.map((rev) => (
                    <div
                      key={rev.id}
                      className="p-4 rounded-2xl bg-white border border-gray-100 shadow-2xs space-y-1.5"
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2 font-bold text-xs text-gray-900">
                          <div className="w-6 h-6 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-[10px]">
                            <User className="w-3.5 h-3.5" />
                          </div>
                          <span>{rev.customer_name}</span>
                        </div>
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
                      </div>
                      <p className="text-xs text-gray-600 pl-8">{rev.comment}</p>
                      <div className="text-[10px] text-gray-400 pl-8 font-mono">{rev.created_at}</div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="p-8 text-center text-gray-500 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                  <p className="text-xs font-semibold">এখনো কোনো রিভিউ দেওয়া হয়নি।</p>
                  <p className="text-[11px] text-gray-400 mt-1">প্রথম রিভিউটি আপনি দিন!</p>
                </div>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Related Products */}
      {relatedProducts.length > 0 && (
        <div className="space-y-4 pt-4">
          <h2 className="text-lg sm:text-xl font-extrabold text-gray-900">
            সম্পর্কিত পণ্যসমূহ (Related Products)
          </h2>
          <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
            {relatedProducts.map((p) => (
              <ProductCard key={p.id} product={p} />
            ))}
          </div>
        </div>
      )}
    </div>
  );
};
