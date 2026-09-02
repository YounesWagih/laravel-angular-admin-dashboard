import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { reloadOnLanguageChange } from '../../../../core/i18n/reload-on-language-change';
import { TranslatePipe } from '../../../../core/i18n/translate.pipe';
import { FormFieldErrorComponent } from '../../../../shared/components/form-field-error/form-field-error.component';
import {
  applyServerValidationErrors,
  clearServerValidationErrors,
} from '../../../../shared/utils/form-error.util';
import { AuthLayoutComponent } from '../auth-layout/auth-layout.component';

@Component({
  selector: 'app-login-page',
  imports: [
    AuthLayoutComponent,
    FormFieldErrorComponent,
    ReactiveFormsModule,
    RouterLink,
    TranslatePipe,
  ],
  templateUrl: './login-page.component.html',
  styleUrl: './login-page.component.scss',
})
export class LoginPageComponent {
  private readonly auth = inject(AuthService);
  private readonly formBuilder = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  submitting = signal(false);
  requestError = signal<string | null>(null);
  form = this.formBuilder.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
    remember: false,
  });

  constructor() {
    reloadOnLanguageChange(() => {
      this.requestError.set(null);
      clearServerValidationErrors(this.form);
    });
  }

  protected async submit(): Promise<void> {
    clearServerValidationErrors(this.form);

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.requestError.set(null);

    try {
      await this.auth.login(this.form.getRawValue());
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
