import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { FormFieldErrorComponent } from '../../../../shared/components/form-field-error/form-field-error.component';
import { applyServerValidationErrors } from '../../../../shared/utils/form-error.util';
import { AuthLayoutComponent } from '../auth-layout/auth-layout.component';

@Component({
  selector: 'app-login-page',
  imports: [AuthLayoutComponent, FormFieldErrorComponent, ReactiveFormsModule, RouterLink],
  templateUrl: './login-page.component.html',
  styleUrl: './login-page.component.scss'
})
export class LoginPageComponent {
  private readonly auth = inject(AuthService);
  private readonly formBuilder = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected readonly submitting = signal(false);
  protected readonly requestError = signal<string | null>(null);
  protected readonly form = this.formBuilder.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
    remember: false
  });

  protected async submit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.requestError.set(null);

    try {
      await this.auth.login(this.form.getRawValue());
      await this.router.navigateByUrl(this.safeReturnUrl());
    } catch (error) {
      this.requestError.set(
        applyServerValidationErrors(this.form, error) ??
          'The request could not be completed. Please try again.'
      );
    } finally {
      this.submitting.set(false);
    }
  }

  private safeReturnUrl(): string {
    const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');

    return returnUrl?.startsWith('/') === true && !returnUrl.startsWith('//')
      ? returnUrl
      : '/dashboard';
  }
}
