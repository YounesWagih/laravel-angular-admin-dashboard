import { HttpErrorResponse } from '@angular/common/http';

import type { ApiErrorResponse } from '../../core/models/api-response.model';

export function getApiErrorResponse(error: unknown): ApiErrorResponse | null {
  return error instanceof HttpErrorResponse && typeof error.error === 'object' && error.error
    ? (error.error as ApiErrorResponse)
    : null;
}

export function getApiErrorMessage(error: unknown, fallback: string): string {
  return getApiErrorResponse(error)?.message ?? fallback;
}
