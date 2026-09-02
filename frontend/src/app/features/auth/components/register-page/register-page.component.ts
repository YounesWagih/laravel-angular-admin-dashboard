import { Component, inject, signal } from '@angular/core';
import {
  type AbstractControl,
  FormBuilder,
  ReactiveFormsModule,
  type ValidationErrors,
  type ValidatorFn,
  Validators,
} from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { FormFieldErrorComponent } from '../../../../shared/components/form-field-error/form-field-error.component';
import { applyServerValidationErrors } from '../../../../shared/utils/form-error.util';
import { AuthLayoutComponent } from '../auth-layout/auth-layout.component';

const passwordsMatch: ValidatorFn = (
  control: AbstractControl,
): ValidationErrors | null =>
  control.get('password')?.value === control.get('password_confirmation')?.value
    ? null
    : { passwordMismatch: true };

@Component({
  selector: 'app-register-page',
  imports: [
    AuthLayoutComponent,
    FormFieldErrorComponent,
    ReactiveFormsModule,
    RouterLink,
  ],
  templateUrl: './register-page.component.html',
  styleUrl: './register-page.component.scss',
})
export class RegisterPageComponent {
  private readonly auth = inject(AuthService);
  private readonly formBuilder = inject(FormBuilder);
  private readonly router = inject(Router);

  submitting = signal(false);
  requestError = signal<string | null>(null);
  form = this.formBuilder.nonNullable.group(
    {
      name: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(8)]],
      password_confirmation: ['', Validators.required],
    },
    { validators: passwordsMatch },
  );

  protected async submit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.requestError.set(null);

    try {
      await this.auth.register(this.form.getRawValue());
      await this.router.navigate(['/dashboard']);
    } catch (error) {
      this.requestError.set(
        applyServerValidationErrors(this.form, error) ??
          'The request could not be completed. Please try again.',
      );
    } finally {
      this.submitting.set(false);
    }
  }
}
