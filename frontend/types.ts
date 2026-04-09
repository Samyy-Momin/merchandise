export type CategoryRef = {
  id: number;
  name: string;
};

export type Category = {
  id: number;
  name: string;
  slug: string;
};

export type Product = {
  id: number;
  name: string;
  description?: string | null;
  price: number;
  image_url?: string | null;
  stock?: number | null;
  // During transition, backend may send a string or an object
  category?: string | CategoryRef | null;
};

export type OrderItem = {
  id: number;
  order_id: number;
  product_id: number;
  qty_requested: number;
  qty_approved?: number | null;
  price: number;
  product?: Product;
};

export type Order = {
  id: number;
  user_id: string;
  status: string;
  total_amount: number;
  items?: OrderItem[];
  address_id?: number | null;
  address?: Address | null;
};

export type Address = {
  id: number;
  user_id: string;
  name: string;
  phone: string;
  address_line: string;
  city: string;
  state: string;
  pincode: string;
};
