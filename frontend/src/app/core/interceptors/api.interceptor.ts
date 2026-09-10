import { HttpErrorResponse, type HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';

import { environment } from '../../../environments/environment';
import { LanguageService } from '../i18n/language.service';
import { AuthStateService } from '../services/auth-state.service';

export const apiInterceptor: HttpInterceptorFn = (request, next) => {
  const authState = inject(AuthStateService);
  const language = inject(LanguageService);
  const router = inject(Router);
  const isApiRequest = request.url.startsWith(environment.apiUrl);
  const accessToken = authState.accessToken();
  const setHeaders: Record<string, string> = {
    'Accept-Language': language.locale(),
  };

  if (isApiRequest && accessToken) {
    setHeaders['Authorization'] = `Bearer ${accessToken}`;
  }

  return next(
    request.clone({
      setHeaders,
    })
  ).pipe(
    catchError((error: unknown) => {
      if (isApiRequest && error instanceof HttpErrorResponse && error.status === 401) {
        authState.clearSession();
        void router.navigateByUrl('/login');
      }

      return throwError(() => error);
    }),
  );
};
