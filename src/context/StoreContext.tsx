import React, { createContext, useContext, useState, useEffect } from 'react';
import confetti from 'canvas-confetti';
import {
  Product,
  Category,
  Subcategory,
  Brand,
  Banner,
  CartItem,
  Order,
  OrderStatus,
  IncompleteOrder,
  Coupon,
  ShippingCharge,
  GeneralSettings,
  FraudSetting,
  Review,
  BlogPost,
  CustomerComplaint,
  ContactMessage,
} from '../types';
import {
  initialSettings,
  initialCategories,
  initialSubcategories,
  initialBrands,
  initialBanners,
  initialProducts,
  initialShippingCharges,
  initialCoupons,
  initialFraudSettings,
  initialOrders,
  initialBlogs,
  initialReviews,
  initialComplaints,
  initialContactMessages,
} from '../data/initialData';
import {
  API_ENABLED,
  fetchBootstrap,
  placeOrder,
  trackOrderApi,
  submitReviewApi,
  submitComplaintApi,
  submitContactApi,
} from '../api/client';

export type ViewType =
  | 'home'
  | 'product_details'
  | 'cart'
  | 'checkout'
  | 'order_success'
  | 'order_track'
  | 'customer_account'
  | 'blogs'
  | 'blog_details'
  | 'contact'
  | 'complaint'
  | 'policy'
  | 'admin';

export interface ToastNotification {
  id: string;
  message: string;
  type: 'success' | 'error' | 'info';
}

interface StoreContextType {
  // Store Data
  products: Product[];
  categories: Category[];
  subcategories: Subcategory[];
  brands: Brand[];
  banners: Banner[];
  coupons: Coupon[];
  shippingCharges: ShippingCharge[];
  orders: Order[];
  incompleteOrders: IncompleteOrder[];
  settings: GeneralSettings;
  fraudSettings: FraudSetting;
  reviews: Review[];
  blogs: BlogPost[];
  complaints: CustomerComplaint[];
  contactMessages: ContactMessage[];

  // Navigation & View
  currentView: ViewType;
  viewParams: Record<string, any>;
  navigate: (view: ViewType, params?: Record<string, any>) => void;
  selectedCategory: number | null;
  setSelectedCategory: (catId: number | null) => void;
  searchQuery: string;
  setSearchQuery: (query: string) => void;
  selectedSort: string;
  setSelectedSort: (sort: string) => void;

  // Cart
  cart: CartItem[];
  addToCart: (product: Product, quantity?: number, color?: string, size?: string) => void;
  removeFromCart: (productId: number, color?: string, size?: string) => void;
  updateCartQuantity: (productId: number, quantity: number, color?: string, size?: string) => void;
  clearCart: () => void;
  cartSubtotal: number;
  cartTotalItems: number;
  cartDiscount: number;
  appliedCoupon: Coupon | null;
  applyCoupon: (code: string) => { success: boolean; message: string };
  removeCoupon: () => void;
  isCartDrawerOpen: boolean;
  setIsCartDrawerOpen: (open: boolean) => void;

  // Quick Order
  quickOrderProduct: Product | null;
  openQuickOrder: (product: Product) => void;
  closeQuickOrder: () => void;

  // Wishlist
  wishlist: number[];
  toggleWishlist: (productId: number) => void;

  // Order Operations
  lastCreatedOrder: Order | null;
  createOrder: (orderData: Omit<Order, 'id' | 'created_at' | 'status'>) => Promise<{ success: boolean; orderId?: string; message: string }>;
  trackOrder: (orderId: string, phone: string) => Order | null;
  /** Server-backed variant — hits the Laravel API when VITE_API_URL is set. */
  trackOrderAsync: (orderId: string, phone: string) => Promise<Order | null>;

  // Admin CRUD Operations
  updateOrderStatus: (orderId: string, status: OrderStatus, courierName?: string, trackingId?: string) => void;
  updateOrderCourier: (orderId: string, courierName: string, trackingId: string) => void;
  deleteOrder: (orderId: string) => void;
  addProduct: (product: Omit<Product, 'id' | 'created_at' | 'ratting' | 'reviews_count'>) => void;
  updateProduct: (id: number, product: Partial<Product>) => void;
  deleteProduct: (id: number) => void;
  addCategory: (category: Omit<Category, 'id'>) => void;
  updateCategory: (id: number, category: Partial<Category>) => void;
  deleteCategory: (id: number) => void;
  updateSettings: (newSettings: Partial<GeneralSettings>) => void;
  addBanner: (banner: Omit<Banner, 'id'>) => void;
  deleteBanner: (id: number) => void;
  addCoupon: (coupon: Omit<Coupon, 'id' | 'used_count'>) => void;
  updateCoupon: (coupon: Partial<Coupon> & { id: number }) => void;
  deleteCoupon: (id: number) => void;
  approveReview: (id: number) => void;
  updateReviewStatus: (id: number, status: 'approved' | 'pending') => void;
  deleteReview: (id: number) => void;
  addReview: (review: Omit<Review, 'id' | 'created_at' | 'status'>) => void;
  submitComplaint: (complaint: Omit<CustomerComplaint, 'id' | 'created_at' | 'status'>) => string;
  submitContactMessage: (msg: Omit<ContactMessage, 'id' | 'created_at' | 'is_read'>) => void;
  updateComplaintStatus: (id: string, status: 'open' | 'in_progress' | 'resolved') => void;
  markContactMessageRead: (id: number) => void;
  updateFraudSettings: (settings: Partial<FraudSetting>) => void;

  // Toasts
  toasts: ToastNotification[];
  showToast: (message: string, type?: 'success' | 'error' | 'info') => void;
  removeToast: (id: string) => void;
}

const StoreContext = createContext<StoreContextType | undefined>(undefined);

const LOCAL_STORAGE_KEY_PREFIX = 'shopgenie_ecom_';

function getStoredItem<T>(key: string, fallback: T): T {
  try {
    const item = localStorage.getItem(LOCAL_STORAGE_KEY_PREFIX + key);
    return item ? JSON.parse(item) : fallback;
  } catch {
    return fallback;
  }
}

function setStoredItem<T>(key: string, value: T): void {
  try {
    localStorage.setItem(LOCAL_STORAGE_KEY_PREFIX + key, JSON.stringify(value));
  } catch {
    // ignore
  }
}

export const StoreProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  // Persistence states with fallback to initialData
  const [products, setProducts] = useState<Product[]>(() => getStoredItem('products', initialProducts));
  const [categories, setCategories] = useState<Category[]>(() => getStoredItem('categories', initialCategories));
  const [subcategories, setSubcategories] = useState<Subcategory[]>(() => getStoredItem('subcategories', initialSubcategories));
  const [brands, setBrands] = useState<Brand[]>(() => getStoredItem('brands', initialBrands));
  const [banners, setBanners] = useState<Banner[]>(() => getStoredItem('banners', initialBanners));
  const [coupons, setCoupons] = useState<Coupon[]>(() => getStoredItem('coupons', initialCoupons));
  const [shippingCharges, setShippingCharges] = useState<ShippingCharge[]>(initialShippingCharges);
  const [orders, setOrders] = useState<Order[]>(() => getStoredItem('orders', initialOrders));
  const [incompleteOrders, setIncompleteOrders] = useState<IncompleteOrder[]>(() => getStoredItem('incomplete_orders', []));
  const [settings, setSettings] = useState<GeneralSettings>(() => getStoredItem('settings', initialSettings));
  const [fraudSettings, setFraudSettings] = useState<FraudSetting>(() => getStoredItem('fraud_settings', initialFraudSettings));
  const [reviews, setReviews] = useState<Review[]>(() => getStoredItem('reviews', initialReviews));
  const [blogs, setBlogs] = useState<BlogPost[]>(() => getStoredItem('blogs', initialBlogs));
  const [complaints, setComplaints] = useState<CustomerComplaint[]>(() => getStoredItem('complaints', initialComplaints));
  const [contactMessages, setContactMessages] = useState<ContactMessage[]>(() => getStoredItem('contact_messages', initialContactMessages));

  // Sync to local storage
  useEffect(() => setStoredItem('products', products), [products]);
  useEffect(() => setStoredItem('categories', categories), [categories]);
  useEffect(() => setStoredItem('subcategories', subcategories), [subcategories]);
  useEffect(() => setStoredItem('banners', banners), [banners]);
  useEffect(() => setStoredItem('coupons', coupons), [coupons]);
  useEffect(() => setStoredItem('orders', orders), [orders]);
  useEffect(() => setStoredItem('incomplete_orders', incompleteOrders), [incompleteOrders]);
  useEffect(() => setStoredItem('settings', settings), [settings]);
  useEffect(() => setStoredItem('fraud_settings', fraudSettings), [fraudSettings]);
  useEffect(() => setStoredItem('reviews', reviews), [reviews]);
  useEffect(() => setStoredItem('complaints', complaints), [complaints]);
  useEffect(() => setStoredItem('contact_messages', contactMessages), [contactMessages]);

  // Hydrate the store from the Laravel REST API when configured (VITE_API_URL).
  // Falls back to the localStorage demo data when the API is unreachable.
  const [apiHydrated, setApiHydrated] = useState(false);
  useEffect(() => {
    if (!API_ENABLED || apiHydrated) return;
    let cancelled = false;

    (async () => {
      try {
        const data = await fetchBootstrap();
        if (cancelled) return;

        if (data.settings && Object.keys(data.settings).length > 0) {
          setSettings((prev) => ({ ...prev, ...data.settings }));
        }
        if (data.categories?.length) setCategories(data.categories);
        if (data.subcategories?.length) setSubcategories(data.subcategories);
        if (data.banners?.length) setBanners(data.banners);
        if (data.shipping_charges?.length) {
          setShippingCharges(data.shipping_charges);
        }
        if (data.coupons?.length) setCoupons(data.coupons);
        if (data.products?.length) setProducts(data.products);
        if (data.blogs?.length) setBlogs(data.blogs);

        setApiHydrated(true);
      } catch {
        // API unreachable — keep running on the demo data.
      }
    })();

    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [API_ENABLED, apiHydrated]);

  // Views & Navigation
  const [currentView, setCurrentView] = useState<ViewType>('home');
  const [viewParams, setViewParams] = useState<Record<string, any>>({});
  const [selectedCategory, setSelectedCategory] = useState<number | null>(null);
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [selectedSort, setSelectedSort] = useState<string>('default');

  const navigate = (view: ViewType, params: Record<string, any> = {}) => {
    setCurrentView(view);
    setViewParams(params);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Cart State
  const [cart, setCart] = useState<CartItem[]>(() => getStoredItem('cart', []));
  const [appliedCoupon, setAppliedCoupon] = useState<Coupon | null>(null);
  const [isCartDrawerOpen, setIsCartDrawerOpen] = useState(false);
  const [wishlist, setWishlist] = useState<number[]>(() => getStoredItem('wishlist', []));
  const [quickOrderProduct, setQuickOrderProduct] = useState<Product | null>(null);
  const [lastCreatedOrder, setLastCreatedOrder] = useState<Order | null>(null);

  useEffect(() => setStoredItem('cart', cart), [cart]);
  useEffect(() => setStoredItem('wishlist', wishlist), [wishlist]);

  // Toasts
  const [toasts, setToasts] = useState<ToastNotification[]>([]);

  const showToast = (message: string, type: 'success' | 'error' | 'info' = 'success') => {
    const id = Date.now().toString() + Math.random().toString(36).substring(2, 5);
    setToasts((prev) => [...prev, { id, message, type }]);
    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id));
    }, 4000);
  };

  const removeToast = (id: string) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  };

  // Cart Calculations
  const cartSubtotal = cart.reduce((acc, item) => acc + item.unitPrice * item.quantity, 0);
  const cartTotalItems = cart.reduce((acc, item) => acc + item.quantity, 0);

  let cartDiscount = 0;
  if (appliedCoupon) {
    if (appliedCoupon.discount_type === 'fixed') {
      cartDiscount = appliedCoupon.discount_amount;
    } else {
      cartDiscount = Math.round((cartSubtotal * appliedCoupon.discount_amount) / 100);
      if (appliedCoupon.max_discount && cartDiscount > appliedCoupon.max_discount) {
        cartDiscount = appliedCoupon.max_discount;
      }
    }
  }

  const addToCart = (product: Product, quantity = 1, color?: string, size?: string) => {
    const defaultColor = color || (product.colors && product.colors.length > 0 ? product.colors[0] : undefined);
    const defaultSize = size || (product.sizes && product.sizes.length > 0 ? product.sizes[0] : undefined);

    setCart((prevCart) => {
      const existingIndex = prevCart.findIndex(
        (item) => item.product.id === product.id && item.selectedColor === defaultColor && item.selectedSize === defaultSize
      );

      if (existingIndex > -1) {
        const updated = [...prevCart];
        updated[existingIndex].quantity += quantity;
        return updated;
      } else {
        return [
          ...prevCart,
          {
            product,
            quantity,
            selectedColor: defaultColor,
            selectedSize: defaultSize,
            unitPrice: product.new_price,
          },
        ];
      }
    });

    showToast(`✅ "${product.name.substring(0, 32)}..." কার্টে যুক্ত হয়েছে!`, 'success');
  };

  const removeFromCart = (productId: number, color?: string, size?: string) => {
    setCart((prevCart) =>
      prevCart.filter(
        (item) => !(item.product.id === productId && item.selectedColor === color && item.selectedSize === size)
      )
    );
    showToast('কার্ট থেকে পণ্য সরানো হয়েছে', 'info');
  };

  const updateCartQuantity = (productId: number, quantity: number, color?: string, size?: string) => {
    if (quantity <= 0) {
      removeFromCart(productId, color, size);
      return;
    }
    setCart((prevCart) =>
      prevCart.map((item) => {
        if (item.product.id === productId && item.selectedColor === color && item.selectedSize === size) {
          return { ...item, quantity };
        }
        return item;
      })
    );
  };

  const clearCart = () => {
    setCart([]);
    setAppliedCoupon(null);
  };

  const applyCoupon = (code: string) => {
    const cleanCode = code.trim().toUpperCase();
    const coupon = coupons.find((c) => c.code.toUpperCase() === cleanCode && c.status === 1);

    if (!coupon) {
      return { success: false, message: 'অকার্যকর কুপন কোড! দয়া করে সঠিক কোড দিন।' };
    }

    if (cartSubtotal < coupon.min_purchase) {
      return {
        success: false,
        message: `এই কুপন ব্যবহার করতে ন্যূনতম ৳${coupon.min_purchase} টাকার পণ্য কিনতে হবে।`,
      };
    }

    setAppliedCoupon(coupon);
    showToast(`🎉 কুপন "${coupon.code}" সফলভাবে যুক্ত হয়েছে!`, 'success');
    return { success: true, message: 'কুপন সফলভাবে প্রয়োগ হয়েছে!' };
  };

  const removeCoupon = () => {
    setAppliedCoupon(null);
    showToast('কুপন বাতিল করা হয়েছে', 'info');
  };

  const toggleWishlist = (productId: number) => {
    setWishlist((prev) => {
      if (prev.includes(productId)) {
        showToast('উইশলিস্ট থেকে সরানো হয়েছে', 'info');
        return prev.filter((id) => id !== productId);
      } else {
        showToast('❤️ উইশলিস্টে যুক্ত করা হয়েছে!', 'success');
        return [...prev, productId];
      }
    });
  };

  const openQuickOrder = (product: Product) => {
    setQuickOrderProduct(product);
  };

  const closeQuickOrder = () => {
    setQuickOrderProduct(null);
  };

  // Create Order with Fraud / Duplicate Detection
  const createOrder = async (orderData: Omit<Order, 'id' | 'created_at' | 'status'>) => {
    // API mode: forward the order to the Laravel backend.
    if (API_ENABLED) {
      try {
        const areaMap: Record<Order['delivery_area'], number> = {
          inside_dhaka: 1,
          sub_dhaka: 2,
          outside_dhaka: 3,
        };

        const result = await placeOrder({
          name: orderData.customer_name,
          phone: orderData.customer_phone,
          address: orderData.customer_address,
          area: areaMap[orderData.delivery_area] ?? 3,
          payment_method: orderData.payment_method,
          order_note: orderData.order_note,
          coupon_code: appliedCoupon?.code,
          items: orderData.items.map((i) => ({
            product_id: i.product_id,
            qty: i.quantity,
            price: i.price,
            color: i.color,
            size: i.size,
          })),
        });

        const synthOrder: Order = {
          ...orderData,
          id: result.order_id,
          status: 'pending',
          created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
        };

        setOrders((prev) => [synthOrder, ...prev]);
        setLastCreatedOrder(synthOrder);
        clearCart();
        setQuickOrderProduct(null);

        try {
          confetti({ particleCount: 80, spread: 70, origin: { y: 0.6 } });
        } catch {
          // ignore
        }

        showToast(`🎉 আপনার অর্ডারটি সফলভাবে গৃহীত হয়েছে! অর্ডার আইডি: ${result.order_id}`, 'success');
        navigate('order_success', { orderId: result.order_id });

        return { success: true, orderId: result.order_id, message: 'অর্ডার সফলভাবে সম্পন্ন হয়েছে!' };
      } catch (err) {
        return {
          success: false,
          message: err instanceof Error ? err.message : 'অর্ডার প্রক্রিয়া করার সময় ত্রুটি হয়েছে।',
        };
      }
    }

    const cleanPhone = orderData.customer_phone.trim().replace(/[-+\s]/g, '');

    // Check blocked phones
    if (fraudSettings.blocked_phones.includes(cleanPhone)) {
      return {
        success: false,
        message: 'দুঃখিত, এই নম্বরটি আমাদের সিস্টেমে ব্লক রয়েছে। সহায়তার জন্য হটলাইনে কল করুন।',
      };
    }

    // Check duplicate frequency if not in whitelist
    if (!fraudSettings.whitelist_phones.includes(cleanPhone)) {
      const recentOrdersFromPhone = orders.filter((o) => {
        const orderTime = new Date(o.created_at).getTime();
        const now = new Date().getTime();
        const diffHours = (now - orderTime) / (1000 * 60 * 60);
        return o.customer_phone.replace(/[-+\s]/g, '') === cleanPhone && diffHours <= fraudSettings.order_limit_time;
      });

      if (recentOrdersFromPhone.length >= fraudSettings.order_limit_qty) {
        return {
          success: false,
          message: `আপনি গত ${fraudSettings.order_limit_time} ঘন্টায় ইতিমধ্যে অর্ডার করেছেন। অনুগ্রহ করে আগের অর্ডারটি ডেলিভারির অপেক্ষা করুন অথবা হটলাইনে যোগাযোগ করুন।`,
        };
      }
    }

    const orderId = 'ORD-' + Math.floor(100000 + Math.random() * 900000);
    const nowStr = new Date().toISOString().replace('T', ' ').substring(0, 19);

    const newOrder: Order = {
      ...orderData,
      id: orderId,
      status: 'pending',
      created_at: nowStr,
      ip_address: '103.245.' + Math.floor(Math.random() * 250) + '.' + Math.floor(Math.random() * 250),
    };

    setOrders((prev) => [newOrder, ...prev]);
    setLastCreatedOrder(newOrder);

    // Update Product stocks & sold counts
    setProducts((prev) =>
      prev.map((prod) => {
        const orderedItem = orderData.items.find((item) => item.product_id === prod.id);
        if (orderedItem) {
          return {
            ...prod,
            stock: Math.max(0, prod.stock - orderedItem.quantity),
            sold_count: (prod.sold_count || 0) + orderedItem.quantity,
          };
        }
        return prod;
      })
    );

    // Clear cart & close quick order
    clearCart();
    setQuickOrderProduct(null);

    // Trigger celebration confetti
    try {
      confetti({
        particleCount: 80,
        spread: 70,
        origin: { y: 0.6 },
      });
    } catch {
      // ignore
    }

    showToast(`🎉 আপনার অর্ডারটি সফলভাবে গৃহীত হয়েছে! অর্ডার আইডি: ${orderId}`, 'success');
    navigate('order_success', { orderId });

    return { success: true, orderId, message: 'অর্ডার সফলভাবে সম্পন্ন হয়েছে!' };
  };

  const trackOrder = (orderId: string, phone: string) => {
    const cleanId = orderId.trim().toUpperCase();
    const cleanPhone = phone.trim().replace(/[-+\s]/g, '');

    const found = orders.find(
      (o) =>
        o.id.toUpperCase() === cleanId &&
        o.customer_phone.replace(/[-+\s]/g, '').endsWith(cleanPhone.slice(-6))
    );

    return found || null;
  };

  const trackOrderAsync = async (orderId: string, phone: string): Promise<Order | null> => {
    if (!API_ENABLED) {
      return trackOrder(orderId, phone);
    }

    const found = await trackOrderApi(orderId, phone);
    if (found) {
      // Merge server order into the local list so the rest of the UI can read it.
      setOrders((prev) => (prev.some((o) => o.id === found.id) ? prev : [found, ...prev]));
    }
    return found;
  };

  // Admin Actions
  const updateOrderStatus = (orderId: string, status: OrderStatus, courierName?: string, trackingId?: string) => {
    setOrders((prev) =>
      prev.map((o) => {
        if (o.id === orderId) {
          return {
            ...o,
            status,
            courier_name: courierName || o.courier_name,
            courier_tracking_id: trackingId || o.courier_tracking_id,
          };
        }
        return o;
      })
    );
    showToast(`অর্ডার ${orderId} এর স্ট্যাটাস '${status}' আপডেট করা হয়েছে`, 'success');
  };

  const updateOrderCourier = (orderId: string, courierName: string, trackingId: string) => {
    setOrders((prev) =>
      prev.map((o) =>
        o.id === orderId
          ? { ...o, courier_name: courierName, courier_tracking_id: trackingId, status: 'courier' }
          : o
      )
    );
    showToast(`অর্ডার ${orderId} কুরিয়ারে পাঠানো হয়েছে!`, 'success');
  };

  const deleteOrder = (orderId: string) => {
    setOrders((prev) => prev.filter((o) => o.id !== orderId));
    showToast(`অর্ডার ${orderId} সফলভাবে মুছে ফেলা হয়েছে`, 'info');
  };

  const addProduct = (newProd: Omit<Product, 'id' | 'created_at' | 'ratting' | 'reviews_count'>) => {
    const id = Date.now();
    const product: Product = {
      ...newProd,
      id,
      ratting: 5.0,
      reviews_count: 0,
      created_at: new Date().toISOString().substring(0, 10),
    };
    setProducts((prev) => [product, ...prev]);
    showToast('✅ নতুন প্রোডাক্ট সফলভাবে যোগ করা হয়েছে!', 'success');
  };

  const updateProduct = (id: number, updated: Partial<Product>) => {
    setProducts((prev) => prev.map((p) => (p.id === id ? { ...p, ...updated } : p)));
    showToast('প্রোডাক্ট আপডেট সম্পন্ন হয়েছে', 'success');
  };

  const deleteProduct = (id: number) => {
    setProducts((prev) => prev.filter((p) => p.id !== id));
    showToast('প্রোডাক্ট ডিলিট করা হয়েছে', 'info');
  };

  const addCategory = (category: Omit<Category, 'id'>) => {
    const newCat: Category = { ...category, id: Date.now() };
    setCategories((prev) => [...prev, newCat]);
    showToast('নতুন ক্যাটাগরি যোগ হয়েছে', 'success');
  };

  const updateCategory = (id: number, updated: Partial<Category>) => {
    setCategories((prev) => prev.map((c) => (c.id === id ? { ...c, ...updated } : c)));
    showToast('ক্যাটাগরি আপডেট সম্পন্ন হয়েছে', 'success');
  };

  const deleteCategory = (id: number) => {
    setCategories((prev) => prev.filter((c) => c.id !== id));
    showToast('ক্যাটাগরি ডিলিট করা হয়েছে', 'info');
  };

  const updateSettings = (newSettings: Partial<GeneralSettings>) => {
    setSettings((prev) => ({ ...prev, ...newSettings }));
    showToast('দোকানের সেটিংস সংরক্ষণ করা হয়েছে', 'success');
  };

  const addBanner = (banner: Omit<Banner, 'id'>) => {
    const newBan: Banner = { ...banner, id: Date.now() };
    setBanners((prev) => [...prev, newBan]);
    showToast('নতুন ব্যানার যোগ করা হয়েছে', 'success');
  };

  const deleteBanner = (id: number) => {
    setBanners((prev) => prev.filter((b) => b.id !== id));
    showToast('ব্যানার মুছে ফেলা হয়েছে', 'info');
  };

  const addCoupon = (coupon: Omit<Coupon, 'id' | 'used_count'>) => {
    const newC: Coupon = { ...coupon, id: Date.now(), used_count: 0 };
    setCoupons((prev) => [...prev, newC]);
    showToast('কুপন কোড তৈরি হয়েছে', 'success');
  };

  const updateCoupon = (coupon: Partial<Coupon> & { id: number }) => {
    setCoupons((prev) => prev.map((c) => (c.id === coupon.id ? { ...c, ...coupon } : c)));
    showToast('কুপন আপডেট সম্পন্ন হয়েছে', 'success');
  };

  const deleteCoupon = (id: number) => {
    setCoupons((prev) => prev.filter((c) => c.id !== id));
    showToast('কুপন মুছে ফেলা হয়েছে', 'info');
  };

  const approveReview = (id: number) => {
    setReviews((prev) => prev.map((r) => (r.id === id ? { ...r, status: 'approved' } : r)));
    showToast('রিভিউ অনুমোদন দেওয়া হয়েছে', 'success');
  };

  const updateReviewStatus = (id: number, status: 'approved' | 'pending') => {
    setReviews((prev) => prev.map((r) => (r.id === id ? { ...r, status } : r)));
    showToast('রিভিউ স্ট্যাটাস আপডেট হয়েছে', 'success');
  };

  const deleteReview = (id: number) => {
    setReviews((prev) => prev.filter((r) => r.id !== id));
    showToast('রিভিউ ডিলিট করা হয়েছে', 'info');
  };

  const addReview = (review: Omit<Review, 'id' | 'created_at' | 'status'>) => {
    if (API_ENABLED) {
      submitReviewApi({
        product_id: review.product_id,
        rating: review.rating,
        comment: review.comment,
        customer_name: review.customer_name,
        customer_email: review.customer_phone,
      }).catch(() => {
        // Keep the local review even if the API call fails.
      });
    }

    const newRev: Review = {
      ...review,
      id: Date.now(),
      created_at: new Date().toISOString().substring(0, 10),
      status: 'approved',
    };
    setReviews((prev) => [newRev, ...prev]);
    showToast('আপনার মতামতের জন্য ধন্যবাদ!', 'success');
  };

  const submitComplaint = (complaint: Omit<CustomerComplaint, 'id' | 'created_at' | 'status'>) => {
    if (API_ENABLED) {
      submitComplaintApi({
        name: complaint.customer_name,
        phone: complaint.customer_phone,
        order_id: complaint.order_id,
        message: complaint.subject ? `${complaint.subject} — ${complaint.message}` : complaint.message,
      }).catch(() => {
        // Ignore — the local record still stands.
      });
    }

    const id = 'CMP-' + Math.floor(1000 + Math.random() * 9000);
    const newComp: CustomerComplaint = {
      ...complaint,
      id,
      created_at: new Date().toISOString().substring(0, 10),
      status: 'open',
    };
    setComplaints((prev) => [newComp, ...prev]);
    showToast(`আপনার অভিযোগটি গৃহীত হয়েছে। ট্র্যাকিং টোকেন: ${id}`, 'success');
    return id;
  };

  const submitContactMessage = (msg: Omit<ContactMessage, 'id' | 'created_at' | 'is_read'>) => {
    if (API_ENABLED) {
      submitContactApi({
        name: msg.name,
        phone: msg.phone,
        email: msg.email,
        subject: msg.subject,
        message: msg.message,
      }).catch(() => {
        // Ignore — the local record still stands.
      });
    }

    const newMsg: ContactMessage = {
      ...msg,
      id: Date.now(),
      created_at: new Date().toISOString().substring(0, 10),
      is_read: false,
    };
    setContactMessages((prev) => [newMsg, ...prev]);
    showToast('ধন্যবাদ! আপনার বার্তাটি আমাদের কাছে পৌঁছেছে। দ্রুত যোগাযোগ করা হবে।', 'success');
  };

  const updateComplaintStatus = (id: string, status: 'open' | 'in_progress' | 'resolved') => {
    setComplaints((prev) => prev.map((c) => (c.id === id ? { ...c, status } : c)));
    showToast(`অভিযোগের স্ট্যাটাস '${status}' এ পরিবর্তন হয়েছে`, 'success');
  };

  const markContactMessageRead = (id: number) => {
    setContactMessages((prev) => prev.map((m) => (m.id === id ? { ...m, is_read: true } : m)));
  };

  const updateFraudSettings = (updated: Partial<FraudSetting>) => {
    setFraudSettings((prev) => ({ ...prev, ...updated }));
    showToast('ফ্রড প্রতিরোধ সেটিংস আপডেট করা হয়েছে', 'success');
  };

  return (
    <StoreContext.Provider
      value={{
        products,
        categories,
        subcategories,
        brands,
        banners,
        coupons,
        shippingCharges,
        orders,
        incompleteOrders,
        settings,
        fraudSettings,
        reviews,
        blogs,
        complaints,
        contactMessages,
        currentView,
        viewParams,
        navigate,
        selectedCategory,
        setSelectedCategory,
        searchQuery,
        setSearchQuery,
        selectedSort,
        setSelectedSort,
        cart,
        addToCart,
        removeFromCart,
        updateCartQuantity,
        clearCart,
        cartSubtotal,
        cartTotalItems,
        cartDiscount,
        appliedCoupon,
        applyCoupon,
        removeCoupon,
        isCartDrawerOpen,
        setIsCartDrawerOpen,
        quickOrderProduct,
        openQuickOrder,
        closeQuickOrder,
        wishlist,
        toggleWishlist,
        lastCreatedOrder,
        createOrder,
        trackOrder,
        trackOrderAsync,
        updateOrderStatus,
        updateOrderCourier,
        deleteOrder,
        addProduct,
        updateProduct,
        deleteProduct,
        addCategory,
        updateCategory,
        deleteCategory,
        updateSettings,
        addBanner,
        deleteBanner,
        addCoupon,
        updateCoupon,
        deleteCoupon,
        approveReview,
        updateReviewStatus,
        deleteReview,
        addReview,
        submitComplaint,
        submitContactMessage,
        updateComplaintStatus,
        markContactMessageRead,
        updateFraudSettings,
        toasts,
        showToast,
        removeToast,
      }}
    >
      {children}
    </StoreContext.Provider>
  );
};

export const useStore = (): StoreContextType => {
  const context = useContext(StoreContext);
  if (!context) {
    throw new Error('useStore must be used within a StoreProvider');
  }
  return context;
};
