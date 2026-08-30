import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../../environments/environment';
import type { ApiResponse } from '../../../core/models/api-response.model';
import type {
  PermissionEntity,
  Role,
  RoleIndexResponse,
  RolePayload
} from '../models/role.model';

@Injectable({ providedIn: 'root' })
export class RoleService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = `${environment.apiUrl}/roles`;

  index(): Promise<RoleIndexResponse> {
    return firstValueFrom(this.http.get<RoleIndexResponse>(this.endpoint));
  }

  async create(payload: RolePayload): Promise<Role> {
    const response = await firstValueFrom(
      this.http.post<ApiResponse<Role>>(this.endpoint, payload)
    );

    return response.data;
  }

  async update(roleId: number, payload: RolePayload): Promise<Role> {
    const response = await firstValueFrom(
      this.http.patch<ApiResponse<Role>>(`${this.endpoint}/${roleId}`, payload)
    );

    return response.data;
  }

  async delete(roleId: number): Promise<void> {
    await firstValueFrom(this.http.delete<void>(`${this.endpoint}/${roleId}`));
  }

  async makeDefault(roleId: number): Promise<Role> {
    const response = await firstValueFrom(
      this.http.patch<ApiResponse<Role>>(`${this.endpoint}/${roleId}/default`, {})
    );

    return response.data;
  }

  async availableEntities(roleId: number): Promise<PermissionEntity[]> {
    const response = await firstValueFrom(
      this.http.get<ApiResponse<PermissionEntity[]>>(
        `${this.endpoint}/${roleId}/available-entities`
      )
    );

    return response.data;
  }

  async syncPermissions(roleId: number, permissions: string[]): Promise<Role> {
    const response = await firstValueFrom(
      this.http.put<ApiResponse<Role>>(`${this.endpoint}/${roleId}/permissions`, {
        permissions
      })
    );

    return response.data;
  }
}
