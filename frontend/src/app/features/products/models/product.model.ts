import type { PaginatedResponse } from '../../../shared/models/pagination.model';

export type ProductStatus = 'active' | 'inactive';

export interface ProductCategory {
  id: number;
  name: string;
}

export interface Product {
  id: number;
  name: string;
  description: string | null;
  image_url: string | null;
  price: string;
  stock: number;
  status: ProductStatus;
  category: ProductCategory;
  created_at: string;
  updated_at: string;
}

export interface ProductDetails extends Product {
  name_en: string;
  name_ar: string;
  description_en: string | null;
  description_ar: string | null;
}

export interface ProductPayload {
  name_en: string;
  name_ar: string;
  description_en: string | null;
  description_ar: string | null;
  category_id: number;
  price: number;
  stock: number;
  status: ProductStatus;
  image: File | null;
}

export interface ProductFilters {
  search?: string;
  category_id?: number;
  status?: ProductStatus;
  page?: number;
  per_page?: number;
}

export interface ProductOptions {
  categories: ProductCategory[];
  statuses: ProductStatus[];
}

export type PaginatedProductsResponse = PaginatedResponse<Product>;
