import { Routes } from '@angular/router';

import { authGuard } from './core/guards/auth.guard';
import { adminGuard } from './core/guards/admin.guard';
import { adminDashboardMatch } from './core/guards/admin-dashboard-match.guard';
import { guestGuard } from './core/guards/guest.guard';
import { permissionGuard } from './core/guards/permission.guard';

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
        canMatch: [adminDashboardMatch],
        loadComponent: () =>
          import(
            './features/dashboard/components/admin-dashboard-page/admin-dashboard-page.component'
          ).then((component) => component.AdminDashboardPageComponent)
      },
      {
        path: 'dashboard',
        loadComponent: () =>
          import(
            './features/dashboard/components/user-dashboard-page/user-dashboard-page.component'
          ).then(
            (component) => component.UserDashboardPageComponent
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
        path: 'categories',
        canActivate: [permissionGuard('categories.read')],
        loadComponent: () =>
          import(
            './features/categories/components/categories-page/categories-page.component'
          ).then((component) => component.CategoriesPageComponent)
      },
      {
        path: 'categories/new',
        canActivate: [permissionGuard('categories.create')],
        loadComponent: () =>
          import(
            './features/categories/components/category-form-page/category-form-page.component'
          ).then((component) => component.CategoryFormPageComponent)
      },
      {
        path: 'categories/:id/edit',
        canActivate: [permissionGuard('categories.update')],
        loadComponent: () =>
          import(
            './features/categories/components/category-form-page/category-form-page.component'
          ).then((component) => component.CategoryFormPageComponent)
      },
      {
        path: 'categories/:id',
        canActivate: [permissionGuard('categories.read')],
        loadComponent: () =>
          import(
            './features/categories/components/category-details-page/category-details-page.component'
          ).then((component) => component.CategoryDetailsPageComponent)
      },
      {
        path: 'products',
        canActivate: [permissionGuard('products.read')],
        loadComponent: () =>
          import('./features/products/components/products-page/products-page.component').then(
            (component) => component.ProductsPageComponent
          )
      },
      {
        path: 'products/new',
        canActivate: [permissionGuard('products.create')],
        loadComponent: () =>
          import(
            './features/products/components/product-form-page/product-form-page.component'
          ).then((component) => component.ProductFormPageComponent)
      },
      {
        path: 'products/:id/edit',
        canActivate: [permissionGuard('products.update')],
        loadComponent: () =>
          import(
            './features/products/components/product-form-page/product-form-page.component'
          ).then((component) => component.ProductFormPageComponent)
      },
      {
        path: 'products/:id',
        canActivate: [permissionGuard('products.read')],
        loadComponent: () =>
          import(
            './features/products/components/product-details-page/product-details-page.component'
          ).then((component) => component.ProductDetailsPageComponent)
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
