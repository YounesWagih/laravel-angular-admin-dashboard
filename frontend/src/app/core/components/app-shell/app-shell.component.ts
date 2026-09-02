import { Component, computed, inject, signal } from '@angular/core';
import {
  Router,
  RouterLink,
  RouterLinkActive,
  RouterOutlet,
} from '@angular/router';

import { AuthService } from '../../services/auth.service';

interface NavigationItem {
  label: string;
  route: string;
  visible: () => boolean;
}

@Component({
  selector: 'app-shell',
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  templateUrl: './app-shell.component.html',
  styleUrl: './app-shell.component.scss',
})
export class AppShellComponent {
  auth = inject(AuthService);
  private readonly router = inject(Router);

  menuOpen = signal(false);
  loggingOut = signal(false);
  logoutError = signal<string | null>(null);
  readonly userInitial = computed(() =>
    this.auth.user()?.name.trim().charAt(0).toUpperCase() || '?',
  );
  navigationItems: NavigationItem[] = [
    { label: 'Dashboard', route: '/dashboard', visible: () => true },
    {
      label: 'Products',
      route: '/products',
      visible: () => this.auth.hasPermission('products.read'),
    },
    {
      label: 'Categories',
      route: '/categories',
      visible: () => this.auth.hasPermission('categories.read'),
    },
    { label: 'Users', route: '/users', visible: () => this.auth.isAdmin() },
    {
      label: 'Roles & Permissions',
      route: '/roles',
      visible: () => this.auth.isAdmin(),
    },
  ];

  protected closeMenu(): void {
    this.menuOpen.set(false);
  }

  protected toggleMenu(): void {
    this.menuOpen.update((isOpen) => !isOpen);
  }

  protected async logout(): Promise<void> {
    this.loggingOut.set(true);
    this.logoutError.set(null);

    try {
      await this.auth.logout();
      await this.router.navigateByUrl('/login');
    } catch {
      this.logoutError.set('Logout could not be completed. Please try again.');
    } finally {
      this.loggingOut.set(false);
    }
  }
}
