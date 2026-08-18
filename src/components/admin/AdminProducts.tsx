import React, { useState } from 'react';
import {
  Package,
  Plus,
  Search,
  Edit2,
  Trash2,
  Sparkles,
  Zap,
  CheckCircle2,
  X,
  Image as ImageIcon,
  DollarSign,
  Layers,
  Flame,
  Star
} from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { Product } from '../../types';

export const AdminProducts: React.FC = () => {
  const { products, categories, addProduct, updateProduct, deleteProduct, settings, showToast } = useStore();

  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCatFilter, setSelectedCatFilter] = useState<number | 'all'>('all');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);

  // Form Fields
  const [formData, setFormData] = useState<Partial<Product>>({
    name: '',
    category_id: categories[0]?.id || 1,
    product_code: '',
    purchase_price: 500,
    old_price: 1200,
    new_price: 950,
    stock: 50,
    product_type: 'physical',
    image: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80',
    description: '<p>উচ্চমানের প্রিমিয়াম প্রোডাক্ট। কোয়ালিটি এবং ডিউরেবিলিটির নিশ্চয়তা।</p>',
    colors: ['Black', 'White'],
    sizes: ['M', 'L', 'XL'],
    status: 1,
    topsale: false,
    flashsale: false,
    feature_product: false,
    free_delivery: false,
    is_digital: false,
    digital_file: '',
    pro_video: '',
  });

  const [colorInput, setColorInput] = useState('');
  const [sizeInput, setSizeInput] = useState('');

  const filteredProducts = products.filter((p) => {
    if (selectedCatFilter !== 'all' && p.category_id !== selectedCatFilter) return false;
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase().trim();
      const matchName = p.name.toLowerCase().includes(q);
      const matchCode = p.product_code.toLowerCase().includes(q);
      if (!matchName && !matchCode) return false;
    }
    return true;
  });

  const handleOpenAdd = () => {
    setEditingProduct(null);
    setFormData({
      name: '',
      category_id: categories[0]?.id || 1,
      product_code: `PRD-${Math.floor(1000 + Math.random() * 9000)}`,
      purchase_price: 500,
      old_price: 1200,
      new_price: 950,
      stock: 50,
      product_type: 'physical',
      image: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80',
      description: '<p>উচ্চমানের প্রিমিয়াম গ্যাজেট। ১ বছরের ওয়ারেন্টি সহ।</p>',
      colors: ['Black', 'Silver'],
      sizes: ['Standard'],
      status: 1,
      topsale: false,
      flashsale: false,
      feature_product: false,
      free_delivery: false,
      is_digital: false,
      digital_file: '',
      pro_video: '',
    });
    setIsModalOpen(true);
  };

  const handleOpenEdit = (product: Product) => {
    setEditingProduct(product);
    setFormData({ ...product });
    setIsModalOpen(true);
  };

  const handleSaveProduct = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name?.trim() || !formData.new_price) {
      showToast('অনুগ্রহ করে পণ্যের নাম ও দাম লিখুন', 'error');
      return;
    }

    if (editingProduct) {
      updateProduct(editingProduct.id, formData);
      showToast('প্রোডাক্ট সফলভাবে আপডেট করা হয়েছে!', 'success');
    } else {
      addProduct({
        ...formData,
        name: formData.name || '',
        slug: formData.name?.toLowerCase().replace(/\s+/g, '-') || 'product-item',
        category_id: formData.category_id || categories[0]?.id || 1,
        product_code: formData.product_code || `PRD-${Math.floor(1000 + Math.random() * 9000)}`,
        purchase_price: formData.purchase_price || 0,
        new_price: formData.new_price || 0,
        stock: formData.stock || 0,
        product_type: formData.is_digital ? 'digital' : 'physical',
        is_digital: !!formData.is_digital,
        free_delivery: !!formData.free_delivery,
        description: formData.description || '',
        image: formData.image || '',
        status: (formData.status as 1 | 0) || 1,
      } as any);
      showToast('নতুন প্রোডাক্ট সফলভাবে যুক্ত হয়েছে!', 'success');
    }
    setIsModalOpen(false);
  };

  const handleAddColor = () => {
    if (colorInput.trim() && !formData.colors?.includes(colorInput.trim())) {
      setFormData((prev) => ({
        ...prev,
        colors: [...(prev.colors || []), colorInput.trim()],
      }));
      setColorInput('');
    }
  };

  const handleRemoveColor = (col: string) => {
    setFormData((prev) => ({
      ...prev,
      colors: prev.colors?.filter((c) => c !== col),
    }));
  };

  const handleAddSize = () => {
    if (sizeInput.trim() && !formData.sizes?.includes(sizeInput.trim())) {
      setFormData((prev) => ({
        ...prev,
        sizes: [...(prev.sizes || []), sizeInput.trim()],
      }));
      setSizeInput('');
    }
  };

  const handleRemoveSize = (sz: string) => {
    setFormData((prev) => ({
      ...prev,
      sizes: prev.sizes?.filter((s) => s !== sz),
    }));
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-black text-gray-950">
            প্রোডাক্টস ও স্টক ইনভেন্টরি
          </h1>
          <p className="text-xs text-gray-500">
            স্টোরের সকল প্রোডাক্ট যোগ, এডিট, ভ্যারিয়েন্ট এবং স্টক নিয়ন্ত্রণ করুন
          </p>
        </div>

        <button
          onClick={handleOpenAdd}
          className="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer self-start sm:self-auto"
        >
          <Plus className="w-4 h-4" />
          <span>+ নতুন প্রোডাক্ট যোগ করুন</span>
        </button>
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-col sm:flex-row items-center gap-3">
        <div className="flex-1 flex items-center gap-2 bg-gray-50 px-3.5 py-2 rounded-xl w-full">
          <Search className="w-4 h-4 text-gray-400 shrink-0" />
          <input
            type="text"
            placeholder="প্রোডাক্ট নাম অথবা কোড দিয়ে খুঁজুন..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full bg-transparent text-xs outline-hidden"
          />
        </div>

        <select
          value={selectedCatFilter}
          onChange={(e) =>
            setSelectedCatFilter(e.target.value === 'all' ? 'all' : Number(e.target.value))
          }
          className="bg-gray-50 border border-gray-200 text-xs font-bold rounded-xl px-3 py-2 outline-hidden w-full sm:w-auto"
        >
          <option value="all">সকল ক্যাটাগরি ({products.length})</option>
          {categories.map((cat) => (
            <option key={cat.id} value={cat.id}>
              {cat.name}
            </option>
          ))}
        </select>
      </div>

      {/* Product List Table */}
      <div className="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-gray-50 border-b border-gray-100 text-gray-500 font-bold">
              <tr>
                <th className="py-3.5 pl-4">ছবি ও নাম</th>
                <th className="py-3.5">ক্যাটাগরি</th>
                <th className="py-3.5">মূল্য (নতুন / পুরাতন)</th>
                <th className="py-3.5">স্টক</th>
                <th className="py-3.5">ফিচার ট্যাগ</th>
                <th className="py-3.5">স্ট্যাটাস</th>
                <th className="py-3.5 text-right pr-4">অ্যাকশন</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 text-gray-700">
              {filteredProducts.length > 0 ? (
                filteredProducts.map((p) => {
                  const cat = categories.find((c) => c.id === p.category_id);
                  return (
                    <tr key={p.id} className="hover:bg-gray-50/80 transition-colors">
                      <td className="py-3 pl-4">
                        <div className="flex items-center gap-3">
                          <img
                            src={p.image}
                            alt={p.name}
                            className="w-12 h-12 rounded-xl object-cover border border-gray-100 shrink-0"
                          />
                          <div>
                            <div className="font-bold text-gray-950 text-xs line-clamp-1">
                              {p.name}
                            </div>
                            <div className="text-[10px] text-gray-400 font-mono">
                              SKU: {p.product_code} {p.is_digital && '• (ডিজিটাল ফাইল)'}
                            </div>
                          </div>
                        </div>
                      </td>

                      <td className="py-3">
                        <span className="font-semibold text-gray-800">{cat?.name || 'Gen'}</span>
                      </td>

                      <td className="py-3">
                        <div className="font-black text-gray-950 text-xs">
                          {settings.currency}{p.new_price.toLocaleString()}
                        </div>
                        {p.old_price && (
                          <div className="text-[10px] text-gray-400 line-through">
                            {settings.currency}{p.old_price.toLocaleString()}
                          </div>
                        )}
                      </td>

                      <td className="py-3">
                        <span
                          className={`font-mono font-bold text-xs px-2 py-0.5 rounded-md ${
                            p.stock <= 10
                              ? 'bg-red-50 text-red-700 border border-red-200'
                              : 'bg-emerald-50 text-emerald-700'
                          }`}
                        >
                          {p.stock} টি
                        </span>
                      </td>

                      <td className="py-3">
                        <div className="flex flex-wrap gap-1">
                          {p.flashsale && (
                            <span className="bg-red-100 text-red-800 text-[9px] font-bold px-1.5 py-0.5 rounded">
                              Flash
                            </span>
                          )}
                          {p.topsale && (
                            <span className="bg-amber-100 text-amber-800 text-[9px] font-bold px-1.5 py-0.5 rounded">
                              Top Sale
                            </span>
                          )}
                          {p.feature_product && (
                            <span className="bg-purple-100 text-purple-800 text-[9px] font-bold px-1.5 py-0.5 rounded">
                              Hot Deal
                            </span>
                          )}
                        </div>
                      </td>

                      <td className="py-3">
                        <button
                          onClick={() =>
                            updateProduct(p.id, { status: p.status === 1 ? 0 : 1 })
                          }
                          className={`px-2.5 py-1 rounded-full text-[10px] font-bold ${
                            p.status === 1
                              ? 'bg-emerald-100 text-emerald-800'
                              : 'bg-gray-200 text-gray-600'
                          }`}
                        >
                          {p.status === 1 ? 'Active' : 'Inactive'}
                        </button>
                      </td>

                      <td className="py-3 text-right pr-4">
                        <div className="flex items-center justify-end gap-1.5">
                          <button
                            onClick={() => handleOpenEdit(p)}
                            className="p-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200"
                            title="এডিট"
                          >
                            <Edit2 className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => {
                              if (confirm(`আপনি কি "${p.name}" প্রোডাক্টটি ডিলিট করতে চান?`)) {
                                deleteProduct(p.id);
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
                  <td colSpan={7} className="py-12 text-center text-gray-400 text-xs">
                    কোনো প্রোডাক্ট পাওয়া যায়নি।
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Add / Edit Product Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150 overflow-y-auto">
          <div className="bg-white rounded-3xl p-6 sm:p-8 max-w-3xl w-full shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between border-b border-gray-100 pb-4">
              <h3 className="text-lg font-black text-gray-900 flex items-center gap-2">
                <Package className="w-5 h-5 text-red-600" />
                <span>{editingProduct ? 'প্রোডাক্ট এডিট করুন' : 'নতুন প্রোডাক্ট যুক্ত করুন'}</span>
              </h3>
              <button onClick={() => setIsModalOpen(false)} className="p-1.5 text-gray-400 hover:text-gray-700">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleSaveProduct} className="space-y-4 text-xs">
              {/* Product Name */}
              <div>
                <label className="block font-bold text-gray-800 mb-1">প্রোডাক্টের নাম *</label>
                <input
                  type="text"
                  required
                  placeholder="যেমন: T900 Ultra Smartwatch 2.09 HD"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full bg-gray-50 focus:bg-white rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-red-500 outline-hidden font-bold"
                />
              </div>

              {/* Category, Subcategory, Code */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label className="block font-bold text-gray-800 mb-1">ক্যাটাগরি *</label>
                  <select
                    value={formData.category_id}
                    onChange={(e) => setFormData({ ...formData, category_id: Number(e.target.value) })}
                    className="w-full bg-gray-50 rounded-xl px-3 py-2.5 border border-gray-200 font-bold outline-hidden focus:border-red-500"
                  >
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block font-bold text-gray-800 mb-1">প্রোডাক্ট কোড / SKU</label>
                  <input
                    type="text"
                    placeholder="PRD-1029"
                    value={formData.product_code}
                    onChange={(e) => setFormData({ ...formData, product_code: e.target.value })}
                    className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
                  />
                </div>

                <div>
                  <label className="block font-bold text-gray-800 mb-1">বর্তমান স্টক সংখ্যা *</label>
                  <input
                    type="number"
                    required
                    min={0}
                    value={formData.stock}
                    onChange={(e) => setFormData({ ...formData, stock: Number(e.target.value) })}
                    className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-bold outline-hidden focus:border-red-500"
                  />
                </div>
              </div>

              {/* Pricing Grid */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                <div>
                  <label className="block font-bold text-gray-800 mb-1">ক্রয়মূল্য (৳)</label>
                  <input
                    type="number"
                    value={formData.purchase_price}
                    onChange={(e) => setFormData({ ...formData, purchase_price: Number(e.target.value) })}
                    className="w-full bg-white rounded-xl px-3 py-2 border border-gray-200 font-bold outline-hidden focus:border-red-500"
                  />
                </div>
                <div>
                  <label className="block font-bold text-gray-800 mb-1">পূর্বের মূল্য (৳)</label>
                  <input
                    type="number"
                    value={formData.old_price}
                    onChange={(e) => setFormData({ ...formData, old_price: Number(e.target.value) })}
                    className="w-full bg-white rounded-xl px-3 py-2 border border-gray-200 font-bold outline-hidden focus:border-red-500"
                  />
                </div>
                <div>
                  <label className="block font-bold text-red-600 mb-1">বিক্রয় মূল্য * (৳)</label>
                  <input
                    type="number"
                    required
                    value={formData.new_price}
                    onChange={(e) => setFormData({ ...formData, new_price: Number(e.target.value) })}
                    className="w-full bg-white rounded-xl px-3 py-2 border border-red-300 font-black text-red-600 outline-hidden focus:border-red-600"
                  />
                </div>
              </div>

              {/* Image URL & YouTube Video */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-gray-800 mb-1">প্রধান ছবির লিঙ্ক (URL) *</label>
                  <input
                    type="text"
                    required
                    placeholder="https://..."
                    value={formData.image}
                    onChange={(e) => setFormData({ ...formData, image: e.target.value })}
                    className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
                  />
                </div>
                <div>
                  <label className="block font-bold text-gray-800 mb-1">ইউটিউব ভিডিও ID (যেমন: dQw4w9WgXcQ)</label>
                  <input
                    type="text"
                    placeholder="ভিডিও আইডি"
                    value={formData.pro_video}
                    onChange={(e) => setFormData({ ...formData, pro_video: e.target.value })}
                    className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono outline-hidden focus:border-red-500"
                  />
                </div>
              </div>

              {/* Colors & Sizes tags */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {/* Colors */}
                <div>
                  <label className="block font-bold text-gray-800 mb-1">কালার ভ্যারিয়েন্ট</label>
                  <div className="flex gap-1.5 mb-2">
                    <input
                      type="text"
                      placeholder="কালার নাম"
                      value={colorInput}
                      onChange={(e) => setColorInput(e.target.value)}
                      className="flex-1 bg-gray-50 rounded-xl px-3 py-1.5 border border-gray-200 outline-hidden"
                    />
                    <button
                      type="button"
                      onClick={handleAddColor}
                      className="bg-gray-900 text-white px-3 py-1.5 rounded-xl font-bold"
                    >
                      + যোগ
                    </button>
                  </div>
                  <div className="flex flex-wrap gap-1.5">
                    {formData.colors?.map((c) => (
                      <span
                        key={c}
                        className="bg-gray-200 text-gray-800 text-[11px] font-bold px-2 py-0.5 rounded-lg flex items-center gap-1"
                      >
                        {c}
                        <button type="button" onClick={() => handleRemoveColor(c)}>
                          <X className="w-3 h-3 text-gray-500" />
                        </button>
                      </span>
                    ))}
                  </div>
                </div>

                {/* Sizes */}
                <div>
                  <label className="block font-bold text-gray-800 mb-1">সাইজ ভ্যারিয়েন্ট</label>
                  <div className="flex gap-1.5 mb-2">
                    <input
                      type="text"
                      placeholder="সাইজ"
                      value={sizeInput}
                      onChange={(e) => setSizeInput(e.target.value)}
                      className="flex-1 bg-gray-50 rounded-xl px-3 py-1.5 border border-gray-200 outline-hidden"
                    />
                    <button
                      type="button"
                      onClick={handleAddSize}
                      className="bg-gray-900 text-white px-3 py-1.5 rounded-xl font-bold"
                    >
                      + যোগ
                    </button>
                  </div>
                  <div className="flex flex-wrap gap-1.5">
                    {formData.sizes?.map((s) => (
                      <span
                        key={s}
                        className="bg-gray-200 text-gray-800 text-[11px] font-bold px-2 py-0.5 rounded-lg flex items-center gap-1"
                      >
                        {s}
                        <button type="button" onClick={() => handleRemoveSize(s)}>
                          <X className="w-3 h-3 text-gray-500" />
                        </button>
                      </span>
                    ))}
                  </div>
                </div>
              </div>

              {/* Toggles: Flash Deal, Top Sale, Hot Deal, Free Delivery */}
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-2">
                <label className="p-2.5 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-between cursor-pointer">
                  <span className="font-bold">ফ্ল্যাশ সেল</span>
                  <input
                    type="checkbox"
                    checked={formData.flashsale}
                    onChange={(e) => setFormData({ ...formData, flashsale: e.target.checked })}
                  />
                </label>

                <label className="p-2.5 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-between cursor-pointer">
                  <span className="font-bold">টপ সেলস</span>
                  <input
                    type="checkbox"
                    checked={formData.topsale}
                    onChange={(e) => setFormData({ ...formData, topsale: e.target.checked })}
                  />
                </label>

                <label className="p-2.5 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-between cursor-pointer">
                  <span className="font-bold">হট ডিল</span>
                  <input
                    type="checkbox"
                    checked={formData.feature_product}
                    onChange={(e) => setFormData({ ...formData, feature_product: e.target.checked })}
                  />
                </label>

                <label className="p-2.5 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-between cursor-pointer">
                  <span className="font-bold">ফ্রি ডেলিভারি</span>
                  <input
                    type="checkbox"
                    checked={formData.free_delivery}
                    onChange={(e) => setFormData({ ...formData, free_delivery: e.target.checked })}
                  />
                </label>
              </div>

              {/* Digital Product Settings */}
              <div className="p-3 bg-purple-50 rounded-2xl border border-purple-200 space-y-2">
                <label className="flex items-center gap-2 font-bold text-purple-950 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={formData.is_digital}
                    onChange={(e) => setFormData({ ...formData, is_digital: e.target.checked })}
                  />
                  <span>এটি একটি ডিজিটাল প্রোডাক্ট (সফটওয়্যার, কোর্স বা লাইসেন্স কি)</span>
                </label>
                {formData.is_digital && (
                  <div>
                    <label className="block text-[11px] font-bold text-purple-900 mb-1">
                      ডাউনলোড ফাইল নাম বা লাইসেন্স ফাইল
                    </label>
                    <input
                      type="text"
                      placeholder="যেমন: canva_pro_invite.pdf"
                      value={formData.digital_file}
                      onChange={(e) => setFormData({ ...formData, digital_file: e.target.value })}
                      className="w-full bg-white rounded-xl px-3 py-2 border border-purple-300 outline-hidden font-mono"
                    />
                  </div>
                )}
              </div>

              {/* Description */}
              <div>
                <label className="block font-bold text-gray-800 mb-1">বিস্তারিত বিবরণ (HTML)</label>
                <textarea
                  rows={4}
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  className="w-full bg-gray-50 rounded-xl px-3.5 py-2.5 border border-gray-200 font-mono text-[11px] outline-hidden focus:border-red-500 resize-none"
                />
              </div>

              <div className="flex gap-2 pt-3 border-t border-gray-100">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-2xl"
                >
                  বাতিল
                </button>
                <button
                  type="submit"
                  className="flex-1 bg-red-600 hover:bg-red-700 text-white font-extrabold py-3 rounded-2xl shadow-md shadow-red-500/20"
                >
                  {editingProduct ? 'পরিবর্তন সেভ করুন' : 'প্রোডাক্ট পাবলিশ করুন'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
