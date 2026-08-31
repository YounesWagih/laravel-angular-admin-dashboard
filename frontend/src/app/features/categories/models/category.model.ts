import type { PaginatedResponse } from '../../../shared/models/pagination.model';

export interface Category {
  id: number;
  name: string;
  description: string | null;
  products_count: number;
  created_at: string;
  updated_at: string;
}

export interface CategoryDetails extends Category {
  name_en: string;
  name_ar: string;
  description_en: string | null;
  description_ar: string | null;
}

export interface CategoryPayload {
  name_en: string;
  name_ar: string;
  description_en: string | null;
  description_ar: string | null;
}

export interface CategoryFilters {
  search?: string;
  page?: number;
  per_page?: number;
}

export type PaginatedCategoriesResponse = PaginatedResponse<Category>;

