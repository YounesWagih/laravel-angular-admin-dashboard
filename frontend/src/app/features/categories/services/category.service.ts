import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../../environments/environment';
import type { ApiResponse } from '../../../core/models/api-response.model';
import { toHttpParams } from '../../../shared/utils/http-params.util';
import type {
  Category,
  CategoryDetails,
  CategoryFilters,
  CategoryPayload,
  PaginatedCategoriesResponse
} from '../models/category.model';

@Injectable({ providedIn: 'root' })
export class CategoryService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = `${environment.apiUrl}/categories`;

  index(filters: CategoryFilters = {}): Promise<PaginatedCategoriesResponse> {
    return firstValueFrom(
      this.http.get<PaginatedCategoriesResponse>(this.endpoint, { params: toHttpParams(filters) })
    );
  }

  async show(categoryId: number): Promise<CategoryDetails> {
    const response = await firstValueFrom(
      this.http.get<ApiResponse<CategoryDetails>>(`${this.endpoint}/${categoryId}`)
    );

    return response.data;
  }

  async create(payload: CategoryPayload): Promise<Category> {
    const response = await firstValueFrom(
      this.http.post<ApiResponse<Category>>(this.endpoint, payload)
    );

    return response.data;
  }

  async update(categoryId: number, payload: CategoryPayload): Promise<Category> {
    const response = await firstValueFrom(
      this.http.patch<ApiResponse<Category>>(`${this.endpoint}/${categoryId}`, payload)
    );

    return response.data;
  }

  async delete(categoryId: number): Promise<void> {
    await firstValueFrom(this.http.delete<void>(`${this.endpoint}/${categoryId}`));
  }
}
