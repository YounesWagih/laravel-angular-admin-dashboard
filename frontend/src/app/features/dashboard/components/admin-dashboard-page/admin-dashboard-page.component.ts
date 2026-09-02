import { Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import type { AdminDashboardSummary } from '../../models/dashboard.model';
import { DashboardService } from '../../services/dashboard.service';

@Component({
  selector: 'app-admin-dashboard-page',
  imports: [RouterLink],
  templateUrl: './admin-dashboard-page.component.html',
  styleUrl: '../../styles/dashboard-page.scss'
})
export class AdminDashboardPageComponent implements OnInit {
  protected readonly auth = inject(AuthService);
  private readonly dashboardService = inject(DashboardService);

  protected readonly summary = signal<AdminDashboardSummary>({
    users_count: 0,
    products_count: 0,
    categories_count: 0,
    roles_count: 0
  });
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);

  ngOnInit(): void {
    void this.loadSummary();
  }

  protected async loadSummary(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    try {
      this.summary.set(await this.dashboardService.adminSummary());
    } catch (error) {
      this.error.set(
        getApiErrorMessage(error, 'Dashboard summary could not be loaded. Please try again.')
      );
    } finally {
      this.loading.set(false);
    }
  }
}
