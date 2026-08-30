import { computed, inject, Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import type {
  AuthenticatedUser,
  LoginCredentials,
  RegisterCredentials
} from '../models/authenticated-user.model';
import type { ApiResponse } from '../models/api-response.model';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly currentUser = signal<AuthenticatedUser | null>(null);
  private readonly authInitialized = signal(false);

  readonly user = this.currentUser.asReadonly();
  readonly initialized = this.authInitialized.asReadonly();
  readonly isAuthenticated = computed(() => this.currentUser() !== null);
  readonly isAdmin = computed(() => this.currentUser()?.is_admin === true);

  async initialize(): Promise<void> {
    if (this.authInitialized()) {
      return;
    }

    try {
      const response = await firstValueFrom(
        this.http.get<ApiResponse<AuthenticatedUser>>(`${environment.apiUrl}/auth/me`)
      );
      this.currentUser.set(response.data);
    } catch {
      this.currentUser.set(null);
    } finally {
      this.authInitialized.set(true);
    }
  }

  async login(credentials: LoginCredentials): Promise<AuthenticatedUser> {
    await this.prepareCsrfCookie();

    const response = await firstValueFrom(
      this.http.post<ApiResponse<AuthenticatedUser>>(
        `${environment.apiUrl}/auth/login`,
        credentials
      )
    );

    this.currentUser.set(response.data);

    return response.data;
  }

  async register(credentials: RegisterCredentials): Promise<AuthenticatedUser> {
    await this.prepareCsrfCookie();

    const response = await firstValueFrom(
      this.http.post<ApiResponse<AuthenticatedUser>>(
        `${environment.apiUrl}/auth/register`,
        credentials
      )
    );

    this.currentUser.set(response.data);

    return response.data;
  }

  async logout(): Promise<void> {
    await this.prepareCsrfCookie();
    await firstValueFrom(this.http.post<void>(`${environment.apiUrl}/auth/logout`, {}));
    this.currentUser.set(null);
  }

  hasPermission(permission: string): boolean {
    const user = this.currentUser();

    return user?.is_admin === true || user?.permissions.includes(permission) === true;
  }

  clearSession(): void {
    this.currentUser.set(null);
  }

  private async prepareCsrfCookie(): Promise<void> {
    await firstValueFrom(this.http.get<void>(`${environment.sanctumUrl}/csrf-cookie`));
  }
}
