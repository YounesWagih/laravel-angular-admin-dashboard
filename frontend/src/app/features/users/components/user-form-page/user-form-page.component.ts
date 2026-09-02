import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import type {
  UserStatus,
  UserType,
} from '../../../../core/models/authenticated-user.model';
import { AuthService } from '../../../../core/services/auth.service';
import { FormFieldErrorComponent } from '../../../../shared/components/form-field-error/form-field-error.component';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { applyServerValidationErrors } from '../../../../shared/utils/form-error.util';
import type {
  CreateUserPayload,
  UpdateUserPayload,
  User,
  UserRole,
} from '../../models/user.model';
import { UserService } from '../../services/user.service';

@Component({
  selector: 'app-user-form-page',
  imports: [FormFieldErrorComponent, ReactiveFormsModule, RouterLink],
  templateUrl: './user-form-page.component.html',
  styleUrl: './user-form-page.component.scss',
})
export class UserFormPageComponent implements OnInit {
  private readonly formBuilder = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly auth = inject(AuthService);
  private readonly userService = inject(UserService);

  userId = this.readUserId();
  editing = this.route.snapshot.paramMap.has('id');
  roles = signal<UserRole[]>([]);
  loading = signal(true);
  saving = signal(false);
  pageError = signal<string | null>(null);
  formError = signal<string | null>(null);
  private loadedUser: User | null = null;

  userForm = this.formBuilder.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(255)]],
    email: [
      '',
      [Validators.required, Validators.email, Validators.maxLength(255)],
    ],
    password: [''],
    type: this.formBuilder.nonNullable.control<UserType>(
      'user',
      Validators.required,
    ),
    role_id: [0, [Validators.required, Validators.min(1)]],
    status: this.formBuilder.nonNullable.control<UserStatus>(
      'active',
      Validators.required,
    ),
  });

  ngOnInit(): void {
    if (!this.editing) {
      this.userForm.controls.password.setValidators([
        Validators.required,
        Validators.minLength(8),
      ]);
      this.userForm.controls.password.updateValueAndValidity();
    }

    void this.loadPage();
  }

  protected async saveUser(): Promise<void> {
    if (this.userForm.invalid) {
      this.userForm.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    this.formError.set(null);
    const values = this.userForm.getRawValue();
    const commonPayload: UpdateUserPayload = {
      name: values.name.trim(),
      email: values.email.trim().toLowerCase(),
      type: values.type,
      role_id: values.role_id,
    };

    try {
      let savedUser: User;

      if (this.userId === null) {
        const payload: CreateUserPayload = {
          ...commonPayload,
          password: values.password,
          status: values.status,
        };
        savedUser = await this.userService.create(payload);
      } else {
        savedUser = await this.userService.update(this.userId, commonPayload);

        if (this.loadedUser?.status !== values.status) {
          try {
            savedUser = await this.userService.updateStatus(
              this.userId,
              values.status,
            );
          } catch (error) {
            this.loadedUser = savedUser;
            this.formError.set(
              getApiErrorMessage(
                error,
                'The account details were saved, but its status could not be changed.',
              ),
            );
            return;
          }
        }
      }

      await this.router.navigate(['/users', savedUser.id]);
    } catch (error) {
      this.formError.set(
        applyServerValidationErrors(this.userForm, error) ??
          getApiErrorMessage(
            error,
            'The user could not be saved. Please try again.',
          ),
      );
    } finally {
      this.saving.set(false);
    }
  }

  protected editingCurrentUser(): boolean {
    return this.editing && this.auth.user()?.id === this.userId;
  }

  private async loadPage(): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);

    try {
      if (!this.editing) {
        this.roles.set(await this.userService.roleOptions());
        return;
      }

      if (this.userId === null) {
        this.pageError.set('The requested user could not be found.');
        return;
      }

      const [user, roles] = await Promise.all([
        this.userService.show(this.userId),
        this.userService.roleOptions(),
      ]);
      this.loadedUser = user;
      this.roles.set(roles);
      this.userForm.reset({
        name: user.name,
        email: user.email,
        password: '',
        type: user.type,
        role_id: user.role?.id ?? 0,
        status: user.status,
      });
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(error, 'The user could not be loaded.'),
      );
    } finally {
      this.loading.set(false);
    }
  }

  private readUserId(): number | null {
    const value = this.route.snapshot.paramMap.get('id');
    const userId = value ? Number(value) : null;

    return userId !== null && Number.isInteger(userId) && userId > 0
      ? userId
      : null;
  }
}
