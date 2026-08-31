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
        path: 'users',
        canActivate: [adminGuard],
        loadComponent: () =>
          import('./features/users/components/users-page/users-page.component').then(
            (component) => component.UsersPageComponent
          )
      },
      {
        path: 'users/new',
        canActivate: [adminGuard],
        loadComponent: () =>
          import('./features/users/components/user-form-page/user-form-page.component').then(
            (component) => component.UserFormPageComponent
          )
      },
      {
        path: 'users/:id/edit',
        canActivate: [adminGuard],
        loadComponent: () =>
          import('./features/users/components/user-form-page/user-form-page.component').then(
            (component) => component.UserFormPageComponent
          )
      },
      {
        path: 'users/:id',
        canActivate: [adminGuard],
        loadComponent: () =>
          import('./features/users/components/user-details-page/user-details-page.component').then(
            (component) => component.UserDetailsPageComponent
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
