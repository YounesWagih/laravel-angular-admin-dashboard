import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../../environments/environment';
import type { ApiResponse } from '../../../core/models/api-response.model';
import { toHttpParams } from '../../../shared/utils/http-params.util';
import type {
  PaginatedProductsResponse,
  Product,
  ProductDetails,
  ProductFilters,
  ProductOptions,
  ProductPayload
} from '../models/product.model';

@Injectable({ providedIn: 'root' })
export class ProductService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = `${environment.apiUrl}/products`;

  index(filters: ProductFilters = {}): Promise<PaginatedProductsResponse> {
    return firstValueFrom(
      this.http.get<PaginatedProductsResponse>(this.endpoint, { params: toHttpParams(filters) })
    );
  }

  async options(): Promise<ProductOptions> {
    const response = await firstValueFrom(
      this.http.get<ApiResponse<ProductOptions>>(`${this.endpoint}/options`)
    );

    return response.data;
  }

  async show(productId: number): Promise<ProductDetails> {
    const response = await firstValueFrom(
      this.http.get<ApiResponse<ProductDetails>>(`${this.endpoint}/${productId}`)
    );

    return response.data;
  }

  async create(payload: ProductPayload): Promise<Product> {
    const response = await firstValueFrom(
      this.http.post<ApiResponse<Product>>(this.endpoint, this.toFormData(payload))
    );

    return response.data;
  }

  async update(productId: number, payload: ProductPayload): Promise<Product> {
    const formData = this.toFormData(payload);
    formData.append('_method', 'PATCH');

    const response = await firstValueFrom(
      this.http.post<ApiResponse<Product>>(`${this.endpoint}/${productId}`, formData)
    );

    return response.data;
  }

  async delete(productId: number): Promise<void> {
    await firstValueFrom(this.http.delete<void>(`${this.endpoint}/${productId}`));
  }

  private toFormData(payload: ProductPayload): FormData {
    const formData = new FormData();

    formData.append('name_en', payload.name_en);
    formData.append('name_ar', payload.name_ar);
    formData.append('description_en', payload.description_en ?? '');
    formData.append('description_ar', payload.description_ar ?? '');
    formData.append('category_id', String(payload.category_id));
    formData.append('price', String(payload.price));
    formData.append('stock', String(payload.stock));
    formData.append('status', payload.status);

    for (const image of payload.new_images) {
      formData.append('new_images[]', image);
    }

    if (payload.primary_image) {
      formData.append('primary_image', payload.primary_image);
    }

    formData.append('removed_image_ids', JSON.stringify(payload.removed_image_ids));

    return formData;
  }
}
