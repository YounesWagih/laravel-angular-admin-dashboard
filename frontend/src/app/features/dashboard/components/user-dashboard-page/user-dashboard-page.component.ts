import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';

@Component({
  selector: 'app-user-dashboard-page',
  imports: [RouterLink],
  templateUrl: './user-dashboard-page.component.html',
  styleUrl: '../../styles/dashboard-page.scss'
})
export class UserDashboardPageComponent {
  protected readonly auth = inject(AuthService);
}
