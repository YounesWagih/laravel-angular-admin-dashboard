import { Component, inject, input } from '@angular/core';
import type { AbstractControl } from '@angular/forms';

import { LanguageService } from '../../../core/i18n/language.service';

@Component({
  selector: 'app-form-field-error',
  templateUrl: './form-field-error.component.html',
  styleUrl: './form-field-error.component.scss'
})
export class FormFieldErrorComponent {
  private readonly language = inject(LanguageService);
  readonly control = input.required<AbstractControl>();

  protected message(): string | null {
    const control = this.control();

    if (!control.invalid || (!control.touched && !control.dirty)) {
      return null;
    }

    const serverMessage = control.getError('server') as string | undefined;

    if (serverMessage) {
      return this.language.translate(serverMessage);
    }

    if (control.hasError('required')) {
      return this.language.translate('This field is required.');
    }

    if (control.hasError('email')) {
      return this.language.translate('Enter a valid email address.');
    }

    const minimumLength = control.getError('minlength') as { requiredLength: number } | null;

    if (minimumLength) {
      return this.language.translate('Use at least :count characters.', {
        count: minimumLength.requiredLength,
      });
    }

    const maximumLength = control.getError('maxlength') as { requiredLength: number } | null;

    if (maximumLength) {
      return this.language.translate('Use no more than :count characters.', {
        count: maximumLength.requiredLength,
      });
    }

    const minimum = control.getError('min') as { min: number } | null;

    if (minimum) {
      return this.language.translate('Use a value of :value or greater.', {
        value: minimum.min,
      });
    }

    const maximum = control.getError('max') as { max: number } | null;

    if (maximum) {
      return this.language.translate('Use a value of :value or less.', {
        value: maximum.max,
      });
    }

    if (control.hasError('pattern')) {
      return this.language.translate('Enter a valid number.');
    }

    return null;
  }
}
