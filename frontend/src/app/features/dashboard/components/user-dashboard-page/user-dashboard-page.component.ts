import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { TranslatePipe } from '../../../../core/i18n/translate.pipe';

@Component({
  selector: 'app-user-dashboard-page',
  imports: [RouterLink, TranslatePipe],
  templateUrl: './user-dashboard-page.component.html',
  styleUrl: '../../styles/dashboard-page.scss',
})
export class UserDashboardPageComponent {
  auth = inject(AuthService);
}
