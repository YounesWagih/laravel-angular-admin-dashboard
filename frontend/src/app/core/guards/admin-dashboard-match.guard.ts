import { inject } from '@angular/core';
import type { CanMatchFn } from '@angular/router';

import { AuthService } from '../services/auth.service';

export const adminDashboardMatch: CanMatchFn = () => {
  return inject(AuthService).isAdmin();
};
