import { DOCUMENT } from '@angular/common';
import { computed, inject, Injectable, signal } from '@angular/core';

import type { AuthenticatedUser } from '../models/authenticated-user.model';

const TOKEN_STORAGE_KEY = 'app.access_token';

@Injectable({ providedIn: 'root' })
export class AuthStateService {
  private readonly document = inject(DOCUMENT);
  private readonly currentUser = signal<AuthenticatedUser | null>(null);
  private readonly authInitialized = signal(false);

  readonly user = this.currentUser.asReadonly();
  readonly initialized = this.authInitialized.asReadonly();
  readonly isAuthenticated = computed(() => this.currentUser() !== null);
  readonly isAdmin = computed(() => this.currentUser()?.is_admin === true);

  accessToken(): string | null {
    const window = this.document.defaultView;

    return window?.sessionStorage.getItem(TOKEN_STORAGE_KEY)
      ?? window?.localStorage.getItem(TOKEN_STORAGE_KEY)
      ?? null;
  }

  storeSession(user: AuthenticatedUser, accessToken: string, remember: boolean): void {
    const window = this.document.defaultView;

    this.clearStoredToken();

    const storage = remember ? window?.localStorage : window?.sessionStorage;
    storage?.setItem(TOKEN_STORAGE_KEY, accessToken);
    this.currentUser.set(user);
  }

  setUser(user: AuthenticatedUser | null): void {
    this.currentUser.set(user);
  }

  markInitialized(): void {
    this.authInitialized.set(true);
  }

  clearSession(): void {
    this.clearStoredToken();
    this.currentUser.set(null);
  }

  private clearStoredToken(): void {
    const window = this.document.defaultView;

    window?.sessionStorage.removeItem(TOKEN_STORAGE_KEY);
    window?.localStorage.removeItem(TOKEN_STORAGE_KEY);
  }
}
