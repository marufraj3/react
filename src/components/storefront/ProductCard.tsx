import React from 'react';
import { ShoppingBag, Zap, Heart, Star, Sparkles, CheckCircle2 } from 'lucide-react';
import { Product } from '../../types';
import { useStore } from '../../context/StoreContext';

interface ProductCardProps {
  product: Product;
}

export const ProductCard: React.FC<ProductCardProps> = ({ product }) => {
  const { settings, addToCart, openQuickOrder, wishlist, toggleWishlist, navigate } = useStore();

  const isWishlisted = wishlist.includes(product.id);

  // Discount percentage calculation
  const discountPercent =
    product.old_price && product.old_price > product.new_price
      ? Math.round(((product.old_price - product.new_price) / product.old_price) * 100)
      : null;

  return (
    <div className="bg-white rounded-2xl border border-gray-100/90 shadow-xs hover:shadow-xl hover:border-red-100 transition-all duration-300 flex flex-col justify-between overflow-hidden group">
      {/* Top Image Container */}
      <div className="relative aspect-square overflow-hidden bg-gray-50">
        <img
          src={product.image}
          alt={product.name}
          onClick={() => navigate('product_details', { productId: product.id })}
          className="w-full h-full object-cover cursor-pointer group-hover:scale-105 transition-transform duration-500"
          loading="lazy"
        />

        {/* Badges Overlay */}
        <div className="absolute top-2.5 left-2.5 flex flex-col gap-1 z-10 pointer-events-none">
          {discountPercent && (
            <span className="bg-red-600 text-white text-[11px] font-extrabold px-2 py-0.5 rounded-md shadow-sm">
              -{discountPercent}%
            </span>
          )}
          {product.free_delivery && (
            <span className="bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-md shadow-xs flex items-center gap-1">
              <Sparkles className="w-2.5 h-2.5" /> ফ্রি ডেলিভারি
            </span>
          )}
          {product.is_digital && (
            <span className="bg-purple-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-md shadow-xs">
              ডিজিটাল
            </span>
          )}
        </div>

        {/* Wishlist Button */}
        <button
          onClick={(e) => {
            e.stopPropagation();
            toggleWishlist(product.id);
          }}
          className={`absolute top-2.5 right-2.5 w-8 h-8 rounded-full flex items-center justify-center transition-transform active:scale-90 shadow-md ${
            isWishlisted ? 'bg-red-50 text-red-600' : 'bg-white/80 hover:bg-white text-gray-600'
          }`}
          aria-label="Wishlist"
        >
          <Heart className={`w-4 h-4 ${isWishlisted ? 'fill-red-600 text-red-600' : ''}`} />
        </button>

        {/* Quick View Hover Prompt on Desktop */}
        <button
          onClick={() => navigate('product_details', { productId: product.id })}
          className="absolute inset-x-3 bottom-2.5 bg-gray-950/80 hover:bg-gray-950 text-white text-xs font-semibold py-1.5 rounded-xl text-center opacity-0 group-hover:opacity-100 transition-opacity hidden sm:block backdrop-blur-xs cursor-pointer"
        >
          বিস্তারিত দেখুন
        </button>
      </div>

      {/* Product Content Details */}
      <div className="p-3 sm:p-4 flex-1 flex flex-col justify-between">
        <div>
          {/* Star Rating & SKU */}
          <div className="flex items-center justify-between text-xs text-gray-400 mb-1.5">
            <div className="flex items-center gap-1 text-amber-500 font-semibold text-[11px]">
              <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
              <span>{product.ratting.toFixed(1)}</span>
              <span className="text-gray-400">({product.reviews_count || 12})</span>
            </div>
            <span className="text-[10px] text-gray-400 font-mono">
              {product.product_code}
            </span>
          </div>

          {/* Product Title */}
          <h3
            onClick={() => navigate('product_details', { productId: product.id })}
            className="text-xs sm:text-sm font-bold text-gray-900 line-clamp-2 hover:text-red-600 cursor-pointer transition-colors leading-snug min-h-[2.5rem]"
          >
            {product.name}
          </h3>

          {/* Stock Indicator */}
          <div className="mt-1.5 flex items-center gap-1.5 text-[11px]">
            {product.stock > 0 ? (
              <span className="text-emerald-700 font-medium flex items-center gap-1">
                <CheckCircle2 className="w-3 h-3 text-emerald-500" /> ইন স্টক ({product.stock})
              </span>
            ) : (
              <span className="text-red-500 font-medium">স্টক শেষ</span>
            )}
          </div>
        </div>

        {/* Pricing & CTA Buttons */}
        <div className="mt-3 pt-2.5 border-t border-gray-100">
          <div className="flex items-baseline gap-2 mb-2.5">
            <span className="text-base sm:text-lg font-extrabold text-red-600">
              {settings.currency}{product.new_price.toLocaleString()}
            </span>
            {product.old_price && (
              <span className="text-xs sm:text-sm text-gray-400 line-through">
                {settings.currency}{product.old_price.toLocaleString()}
              </span>
            )}
          </div>

          {/* Action Buttons: Quick Order & Add to Cart */}
          <div className="grid grid-cols-5 gap-1.5">
            {/* Quick Order Button (Direct 1-step checkout) */}
            <button
              onClick={() => openQuickOrder(product)}
              disabled={product.stock <= 0}
              className="col-span-3 bg-red-600 hover:bg-red-700 disabled:bg-gray-300 text-white font-extrabold text-xs sm:text-sm py-2 rounded-xl flex items-center justify-center gap-1 transition-transform active:scale-95 shadow-sm shadow-red-500/20 cursor-pointer"
            >
              <Zap className="w-3.5 h-3.5 fill-white" />
              <span>অর্ডার করুন</span>
            </button>

            {/* Add to Cart Button */}
            <button
              onClick={() => addToCart(product, 1)}
              disabled={product.stock <= 0}
              className="col-span-2 bg-gray-100 hover:bg-gray-200 disabled:bg-gray-100 text-gray-800 font-bold text-xs py-2 rounded-xl flex items-center justify-center gap-1 transition-colors cursor-pointer"
              title="কার্টে যোগ করুন"
            >
              <ShoppingBag className="w-3.5 h-3.5 text-gray-600" />
              <span className="hidden sm:inline">কার্ট</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
