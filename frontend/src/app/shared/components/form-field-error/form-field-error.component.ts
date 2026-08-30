import { Component, input } from '@angular/core';
import type { AbstractControl } from '@angular/forms';

@Component({
  selector: 'app-form-field-error',
  templateUrl: './form-field-error.component.html',
  styleUrl: './form-field-error.component.scss'
})
export class FormFieldErrorComponent {
  readonly control = input.required<AbstractControl>();

  protected message(): string | null {
    const control = this.control();

    if (!control.invalid || (!control.touched && !control.dirty)) {
      return null;
    }

    const serverMessage = control.getError('server') as string | undefined;

    if (serverMessage) {
      return serverMessage;
    }

    if (control.hasError('required')) {
      return 'This field is required.';
    }

    if (control.hasError('email')) {
      return 'Enter a valid email address.';
    }

    const minimumLength = control.getError('minlength') as { requiredLength: number } | null;

    if (minimumLength) {
      return `Use at least ${minimumLength.requiredLength} characters.`;
    }

    const maximumLength = control.getError('maxlength') as { requiredLength: number } | null;

    return maximumLength ? `Use no more than ${maximumLength.requiredLength} characters.` : null;
  }
}
