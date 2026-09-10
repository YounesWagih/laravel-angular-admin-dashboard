import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import type {
  AuthenticatedUser,
  AuthenticatedSession,
  LoginCredentials,
  RegisterCredentials
} from '../models/authenticated-user.model';
import type { ApiResponse } from '../models/api-response.model';
import { AuthStateService } from './auth-state.service';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly authState = inject(AuthStateService);

  readonly user = this.authState.user;
  readonly initialized = this.authState.initialized;
  readonly isAuthenticated = this.authState.isAuthenticated;
  readonly isAdmin = this.authState.isAdmin;

  async initialize(): Promise<void> {
    if (this.authState.initialized()) {
      return;
    }

    if (!this.authState.accessToken()) {
      this.authState.markInitialized();

      return;
    }

    try {
      const response = await firstValueFrom(
        this.http.get<ApiResponse<AuthenticatedUser>>(`${environment.apiUrl}/auth/me`)
      );
      this.authState.setUser(response.data);
    } catch {
      this.authState.setUser(null);
    } finally {
      this.authState.markInitialized();
    }
  }

  async login(credentials: LoginCredentials): Promise<AuthenticatedUser> {
    const response = await firstValueFrom(
      this.http.post<ApiResponse<AuthenticatedSession>>(
        `${environment.apiUrl}/auth/login`,
        credentials
      )
    );

    this.authState.storeSession(
      response.data.user,
      response.data.access_token,
      credentials.remember,
    );

    return response.data.user;
  }

  async register(credentials: RegisterCredentials): Promise<AuthenticatedUser> {
    const response = await firstValueFrom(
      this.http.post<ApiResponse<AuthenticatedSession>>(
        `${environment.apiUrl}/auth/register`,
        credentials
      )
    );

    this.authState.storeSession(
      response.data.user,
      response.data.access_token,
      false,
    );

    return response.data.user;
  }

  async logout(): Promise<void> {
    try {
      await firstValueFrom(this.http.post<void>(`${environment.apiUrl}/auth/logout`, {}));
    } catch (error) {
      if (!(error instanceof HttpErrorResponse) || error.status !== 401) {
        throw error;
      }
    }

    this.authState.clearSession();
  }

  hasPermission(permission: string): boolean {
    const user = this.authState.user();

    return user?.is_admin === true || user?.permissions.includes(permission) === true;
  }

  clearSession(): void {
    this.authState.clearSession();
  }
}
