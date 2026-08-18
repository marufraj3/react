export type ProductType = 'physical' | 'digital';

export interface ProductVariant {
  id: string;
  name: string;
  price?: number;
  stock?: number;
}

export interface Product {
  id: number;
  name: string;
  slug: string;
  category_id: number;
  subcategory_id?: number;
  childcategory_id?: number;
  brand_id?: number;
  product_code: string;
  purchase_price: number;
  old_price?: number;
  new_price: number;
  reseller_price?: number;
  stock: number;
  product_type: ProductType;
  is_digital: boolean;
  free_delivery: boolean;
  digital_file?: string;
  download_limit?: number;
  download_expire_days?: number;
  pro_unit?: string;
  pro_video?: string; // YouTube video ID or link
  description: string;
  meta_title?: string;
  meta_description?: string;
  image: string;
  gallery?: string[];
  colors?: string[];
  sizes?: string[];
  topsale?: boolean;
  flashsale?: boolean;
  feature_product?: boolean;
  ratting: number;
  reviews_count: number;
  status: 1 | 0;
  sold_count?: number;
  created_at: string;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  image: string;
  icon?: string;
  front_view?: number;
  status: 1 | 0;
  product_count?: number;
}

export interface Subcategory {
  id: number;
  category_id: number;
  name: string;
  slug: string;
  status: 1 | 0;
}

export interface Brand {
  id: number;
  name: string;
  slug: string;
  image?: string;
  status: 1 | 0;
}

export interface Banner {
  id: number;
  category_id: number;
  title?: string;
  subtitle?: string;
  image: string;
  link: string;
  status: 1 | 0;
  position: 'hero' | 'hero_bottom' | 'middle_ad' | 'footer_top' | 'hotdeals';
}

export interface CartItem {
  product: Product;
  quantity: number;
  selectedColor?: string;
  selectedSize?: string;
  unitPrice: number;
}

export type OrderStatus = 'pending' | 'confirmed' | 'processing' | 'courier' | 'delivered' | 'cancelled' | 'returned';

export interface OrderItem {
  product_id: number;
  product_name: string;
  product_code?: string;
  product_image: string;
  price: number;
  quantity: number;
  color?: string;
  size?: string;
  is_digital?: boolean;
  digital_file?: string;
}

export interface Order {
  id: string; // e.g. ORD-10928
  customer_name: string;
  customer_phone: string;
  customer_email?: string;
  customer_address: string;
  delivery_area: 'inside_dhaka' | 'outside_dhaka' | 'sub_dhaka';
  delivery_charge: number;
  payment_method: 'cod' | 'bkash' | 'nagad' | 'card';
  transaction_id?: string;
  order_note?: string;
  items: OrderItem[];
  subtotal: number;
  discount: number;
  total: number;
  advance_amount?: number;
  status: OrderStatus;
  courier_name?: string;
  courier_tracking_id?: string;
  created_at: string;
  ip_address?: string;
}

export interface IncompleteOrder {
  id: string;
  customer_name: string;
  customer_phone: string;
  customer_address: string;
  items: OrderItem[];
  total: number;
  created_at: string;
  status: 'abandoned' | 'recovered';
}

export interface Coupon {
  id: number;
  code: string;
  discount_type: 'fixed' | 'percent';
  discount_amount: number;
  min_purchase: number;
  max_discount?: number;
  expiry_date: string;
  status: 1 | 0;
  used_count: number;
}

export interface ShippingCharge {
  id: number;
  name: string;
  amount: number;
  description: string;
}

export interface GeneralSettings {
  name: string;
  white_logo?: string;
  dark_logo?: string;
  favicon?: string;
  hotline: string;
  email: string;
  address: string;
  top_headline: string;
  news_ticker_enabled: boolean;
  checkout_note: string;
  order_policy: string;
  primary_color: string;
  secondary_color: string;
  footer_about_text: string;
  facebook_page: string;
  whatsapp_number: string;
  youtube_link?: string;
  tiktok_link?: string;
  instagram_link?: string;
  hot_deal_end_date?: string;
  flash_sale_end_date?: string;
  currency: string;
}

export interface FraudSetting {
  order_limit_time: number; // hours
  order_limit_qty: number; // max orders allowed in that timeframe
  blocked_phones: string[];
  blocked_ips: string[];
  whitelist_phones: string[];
}

export interface Review {
  id: number;
  product_id: number;
  customer_name: string;
  customer_phone?: string;
  rating: number;
  comment: string;
  status: 'pending' | 'approved';
  created_at: string;
}

export interface BlogPost {
  id: number;
  title: string;
  slug: string;
  short_description: string;
  description: string;
  image: string;
  views: number;
  status: 1 | 0;
  created_at: string;
}

export interface CustomerComplaint {
  id: string;
  customer_name: string;
  customer_phone: string;
  order_id?: string;
  subject: string;
  message: string;
  status: 'open' | 'in_progress' | 'resolved';
  created_at: string;
}

export interface ContactMessage {
  id: number;
  name: string;
  phone: string;
  email?: string;
  subject: string;
  message: string;
  created_at: string;
  is_read: boolean;
}
