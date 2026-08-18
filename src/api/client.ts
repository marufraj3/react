/**
 * Thin typed client for the Laravel headless storefront REST API.
 *
 * Set VITE_API_URL (e.g. https://your-laravel-app.com) to point the React app
 * at the PHP backend. When VITE_API_URL is empty, the app runs in demo mode
 * using localStorage (see StoreContext).
 */

import type {
  AuthUser,
  Banner,
  BlogPost,
  Category,
  Coupon,
  DigitalDownloadItem,
  GeneralSettings,
  Order,
  Product,
  RefundItem,
  ShippingCharge,
  Subcategory,
} from '../types';

const rawBase = (import.meta.env.VITE_API_URL as string | undefined)?.trim().replace(/\/+$/, '') ?? '';

/** Whether the app should talk to the PHP backend instead of localStorage. */
export const API_ENABLED: boolean = rawBase.length > 0;

export const API_BASE: string = rawBase || '/api';

export interface BootstrapData {
  settings: Partial<GeneralSettings>;
  categories: Category[];
  subcategories: Subcategory[];
  banners: Banner[];
  shipping_charges: ShippingCharge[];
  coupons: Coupon[];
  products: Product[];
  blogs?: BlogPost[];
}

export interface PlaceOrderPayload {
  name: string;
  phone: string;
  address: string;
  area: number;
  payment_method: string;
  order_note?: string;
  coupon_code?: string;
  items: Array<{
    product_id: number;
    qty: number;
    price: number;
    color?: string;
    size?: string;
  }>;
}

export interface PlaceOrderResult {
  order_id: string;
  invoice_id: string;
  total: number;
  payment_method?: string;
  redirect_url?: string | null;
}

interface ApiEnvelope<T> {
  success?: boolean;
  message?: string;
  data?: T;
}

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(`${API_BASE}/v1/storefront${path}`, {
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    ...init,
  });

  const body = (await res.json().catch(() => null)) as ApiEnvelope<T> | null;

  if (!res.ok || !body || body.success === false) {
    throw new Error(body?.message || `API request failed (${res.status})`);
  }

  return body.data as T;
}

export async function fetchBootstrap(): Promise<BootstrapData> {
  return request<BootstrapData>('/bootstrap');
}

export async function placeOrder(payload: PlaceOrderPayload): Promise<PlaceOrderResult> {
  return request<PlaceOrderResult>('/orders', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function trackOrderApi(orderId: string, phone: string): Promise<Order | null> {
  try {
    const id = encodeURIComponent(orderId.trim());
    const ph = encodeURIComponent(phone.trim());
    return await request<Order>(`/orders/track/${id}?phone=${ph}`);
  } catch {
    return null;
  }
}

export async function applyCouponApi(
  code: string,
  subtotal: number,
  phone: string,
): Promise<{ code: string; type: 'fixed' | 'percent'; discount: number }> {
  return request('/coupons/apply', {
    method: 'POST',
    body: JSON.stringify({ code, subtotal, phone }),
  });
}

export async function submitReviewApi(payload: {
  product_id: number;
  rating: number;
  comment: string;
  customer_name?: string;
  customer_email?: string;
}): Promise<void> {
  await request('/reviews', { method: 'POST', body: JSON.stringify(payload) });
}

export async function submitComplaintApi(payload: {
  name: string;
  phone: string;
  order_id?: string;
  message: string;
}): Promise<{ token: string }> {
  return request('/complaints', { method: 'POST', body: JSON.stringify(payload) });
}

export async function submitContactApi(payload: {
  name: string;
  phone: string;
  email?: string;
  subject?: string;
  message: string;
}): Promise<void> {
  await request('/contact', { method: 'POST', body: JSON.stringify(payload) });
}

/* ------------------------------------------------------------------ *
 * Customer authentication
 * ------------------------------------------------------------------ */

export interface AuthResult {
  token: string;
  user: AuthUser;
}

export async function registerApi(payload: {
  name: string;
  phone: string;
  email?: string;
  password: string;
}): Promise<AuthResult> {
  return request<AuthResult>('/auth/register', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function loginApi(login: string, password: string): Promise<AuthResult> {
  return request<AuthResult>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ login, password }),
  });
}

export async function logoutApi(token: string): Promise<void> {
  await request('/auth/logout', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
}

export async function meApi(token: string): Promise<AuthUser> {
  return request<AuthUser>('/auth/me', {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
}

export async function myOrdersApi(token: string): Promise<Order[]> {
  return request<Order[]>('/auth/orders', {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
}

export async function myDownloadsApi(token: string): Promise<DigitalDownloadItem[]> {
  return request<DigitalDownloadItem[]>('/auth/downloads', {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
}

export async function myRefundsApi(token: string): Promise<RefundItem[]> {
  return request<RefundItem[]>('/auth/refunds', {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
}

export async function submitRefundApi(
  token: string,
  payload: {
    order_id: string | number;
    reason: string;
    refund_method: string;
    refund_account: string;
    refund_account_name?: string;
    amount?: number;
    shipping_charge?: number;
  },
): Promise<{ id: number; refund_id: string; status: string }> {
  return request('/refunds', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(payload),
  });
}

export async function updateProfileApi(
  token: string,
  payload: {
    name: string;
    phone: string;
    email?: string;
    address?: string;
    district?: string;
    area?: string;
  },
): Promise<AuthUser> {
  return request<AuthUser>('/auth/profile', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(payload),
  });
}

export async function changePasswordApi(
  token: string,
  payload: { old_password: string; new_password: string; confirm_password: string },
): Promise<void> {
  await request('/auth/password', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(payload),
  });
}
