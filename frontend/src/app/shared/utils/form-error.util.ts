import { HttpErrorResponse } from '@angular/common/http';
import type { FormGroup } from '@angular/forms';

import type { ApiErrorResponse } from '../../core/models/api-response.model';

export function applyServerValidationErrors(form: FormGroup, error: unknown): string | null {
  if (!(error instanceof HttpErrorResponse) || typeof error.error !== 'object' || !error.error) {
    return null;
  }

  const response = error.error as ApiErrorResponse;

  for (const [field, messages] of Object.entries(response.errors ?? {})) {
    const control = form.get(field);

    if (control && messages[0]) {
      control.setErrors({ ...control.errors, server: messages[0] });
    }
  }

  return response.message ?? null;
}
