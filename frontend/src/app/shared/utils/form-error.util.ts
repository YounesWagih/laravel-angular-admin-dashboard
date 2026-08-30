import type { FormGroup } from '@angular/forms';

import { getApiErrorResponse } from './api-error.util';

export function applyServerValidationErrors(form: FormGroup, error: unknown): string | null {
  const response = getApiErrorResponse(error);

  if (!response) {
    return null;
  }

  for (const [field, messages] of Object.entries(response.errors ?? {})) {
    const control = form.get(field);

    if (control && messages[0]) {
      control.setErrors({ ...control.errors, server: messages[0] });
    }
  }

  return response.message ?? null;
}
