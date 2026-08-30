import { Component, inject } from '@angular/core';

import { AuthService } from '../../../../core/services/auth.service';

@Component({
  selector: 'app-dashboard-page',
  templateUrl: './dashboard-page.component.html',
  styleUrl: './dashboard-page.component.scss'
})
export class DashboardPageComponent {
  protected readonly auth = inject(AuthService);
}
