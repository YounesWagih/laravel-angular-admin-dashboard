import type { HttpInterceptorFn } from '@angular/common/http';

export const apiInterceptor: HttpInterceptorFn = (request, next) => {
  return next(
    request.clone({
      withCredentials: true
    })
  );
};
