import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../../environments/environment';
import type { ApiResponse } from '../../../core/models/api-response.model';
import type { AdminDashboardSummary } from '../models/dashboard.model';

@Injectable({ providedIn: 'root' })
export class DashboardService {
  private readonly http = inject(HttpClient);

  async adminSummary(): Promise<AdminDashboardSummary> {
    const response = await firstValueFrom(
      this.http.get<ApiResponse<AdminDashboardSummary>>(`${environment.apiUrl}/dashboard`)
    );

    return response.data;
  }
}
