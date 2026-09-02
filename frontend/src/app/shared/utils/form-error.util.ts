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

export function clearServerValidationErrors(form: FormGroup): void {
  for (const control of Object.values(form.controls)) {
    const errors = control.errors;

    if (!errors?.['server']) {
      continue;
    }

    const remainingErrors = { ...errors };
    delete remainingErrors['server'];
    control.setErrors(Object.keys(remainingErrors).length > 0 ? remainingErrors : null);
  }
}
