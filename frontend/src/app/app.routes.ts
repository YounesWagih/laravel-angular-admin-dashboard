import { Routes } from '@angular/router';

import { authGuard } from './core/guards/auth.guard';
import { adminGuard } from './core/guards/admin.guard';
import { guestGuard } from './core/guards/guest.guard';

export const routes: Routes = [
  {
    path: 'login',
    canActivate: [guestGuard],
    loadComponent: () =>
      import('./features/auth/components/login-page/login-page.component').then(
        (component) => component.LoginPageComponent
      )
  },
  {
    path: 'register',
    canActivate: [guestGuard],
    loadComponent: () =>
      import('./features/auth/components/register-page/register-page.component').then(
        (component) => component.RegisterPageComponent
      )
  },
  {
    path: '',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./core/components/app-shell/app-shell.component').then(
        (component) => component.AppShellComponent
      ),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      {
        path: 'dashboard',
        loadComponent: () =>
          import('./features/dashboard/components/dashboard-page/dashboard-page.component').then(
            (component) => component.DashboardPageComponent
          )
      },
      {
        path: '403',
        data: { code: 403 },
        loadComponent: () =>
          import('./shared/components/error-page/error-page.component').then(
            (component) => component.ErrorPageComponent
          )
      },
      {
        path: 'roles',
        canActivate: [adminGuard],
        loadComponent: () =>
          import('./features/roles/components/roles-page/roles-page.component').then(
            (component) => component.RolesPageComponent
          )
      },
      {
        path: '**',
        data: { code: 404 },
        loadComponent: () =>
          import('./shared/components/error-page/error-page.component').then(
            (component) => component.ErrorPageComponent
          )
      }
    ]
  }
];
