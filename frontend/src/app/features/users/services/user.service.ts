import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../../environments/environment';
import type { ApiResponse } from '../../../core/models/api-response.model';
import { toHttpParams } from '../../../shared/utils/http-params.util';
import type {
  CreateUserPayload,
  PaginatedUsersResponse,
  User,
  UserFilters,
  UserRole,
  UpdateUserPayload,
} from '../models/user.model';

@Injectable({ providedIn: 'root' })
export class UserService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = `${environment.apiUrl}/users`;

  index(filters: UserFilters = {}): Promise<PaginatedUsersResponse> {
    return firstValueFrom(
      this.http.get<PaginatedUsersResponse>(this.endpoint, {
        params: toHttpParams(filters),
      }),
    );
  }

  async show(userId: number): Promise<User> {
    const response = await firstValueFrom(
      this.http.get<ApiResponse<User>>(`${this.endpoint}/${userId}`),
    );

    return response.data;
  }

  async create(payload: CreateUserPayload): Promise<User> {
    const response = await firstValueFrom(
      this.http.post<ApiResponse<User>>(this.endpoint, payload),
    );

    return response.data;
  }

  async update(userId: number, payload: UpdateUserPayload): Promise<User> {
    const response = await firstValueFrom(
      this.http.patch<ApiResponse<User>>(`${this.endpoint}/${userId}`, payload),
    );

    return response.data;
  }

  async updateStatus(userId: number, status: User['status']): Promise<User> {
    const response = await firstValueFrom(
      this.http.patch<ApiResponse<User>>(`${this.endpoint}/${userId}/status`, {
        status,
      }),
    );

    return response.data;
  }

  async roleOptions(): Promise<UserRole[]> {
    const response = await firstValueFrom(
      this.http.get<ApiResponse<UserRole[]>>(`${environment.apiUrl}/roles`),
    );

    return response.data;
  }
}
