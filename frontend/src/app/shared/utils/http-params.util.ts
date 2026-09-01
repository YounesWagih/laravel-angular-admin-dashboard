import { HttpParams } from '@angular/common/http';

export function toHttpParams<T extends object>(values: T): HttpParams {
  let params = new HttpParams();

  for (const [key, value] of Object.entries(values)) {
    if (value !== undefined && value !== '') {
      params = params.set(key, String(value));
    }
  }

  return params;
}
