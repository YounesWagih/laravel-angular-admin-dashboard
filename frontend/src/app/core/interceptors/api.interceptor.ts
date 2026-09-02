import type { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';

import { LanguageService } from '../i18n/language.service';

export const apiInterceptor: HttpInterceptorFn = (request, next) => {
  const language = inject(LanguageService);

  return next(
    request.clone({
      setHeaders: { 'Accept-Language': language.locale() },
      withCredentials: true,
    })
  );
};
