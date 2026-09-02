import { TitleCasePipe } from '@angular/common';
import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { debounceTime, distinctUntilChanged, map, Subject } from 'rxjs';

import { AuthService } from '../../../../core/services/auth.service';
import { reloadOnLanguageChange } from '../../../../core/i18n/reload-on-language-change';
import { TranslatePipe } from '../../../../core/i18n/translate.pipe';
import { ConfirmationDialogComponent } from '../../../../shared/components/confirmation-dialog/confirmation-dialog.component';
import { PaginationComponent } from '../../../../shared/components/pagination/pagination.component';
import {
  EMPTY_PAGINATION_META,
  type PaginationMeta,
} from '../../../../shared/models/pagination.model';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { formatDate } from '../../../../shared/utils/date.util';
import type { User, UserFilters, UserRole } from '../../models/user.model';
import { UserService } from '../../services/user.service';

@Component({
  selector: 'app-users-page',
  imports: [
    ConfirmationDialogComponent,
    FormsModule,
    PaginationComponent,
    RouterLink,
    TitleCasePipe,
    TranslatePipe,
  ],
  templateUrl: './users-page.component.html',
  styleUrl: './users-page.component.scss',
})
export class UsersPageComponent implements OnInit {
  auth = inject(AuthService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly userService = inject(UserService);

  users = signal<User[]>([]);
  roles = signal<UserRole[]>([]);
  loading = signal(true);
  pageError = signal<string | null>(null);
  actionError = signal<string | null>(null);
  pagination = signal<PaginationMeta>(EMPTY_PAGINATION_META);
  confirmingStatusUserId = signal<number | null>(null);
  changingStatusUserId = signal<number | null>(null);

  protected search = '';
  protected readonly searchChanges = new Subject<string>();
  protected type = '';
  protected roleId = '';
  protected status = '';
  formatDate = formatDate;

  constructor() {
    reloadOnLanguageChange(() => void this.loadUsers());
  }

  ngOnInit(): void {
    this.searchChanges
      .pipe(
        map((search) => search.trim()),
        debounceTime(300),
        distinctUntilChanged(),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe(() => void this.loadUsers(1));

    void this.loadInitialData();
  }

  protected applyFilters(): void {
    void this.loadUsers(1);
  }

  protected clearFilters(): void {
    this.search = '';
    this.type = '';
    this.roleId = '';
    this.status = '';
    void this.loadUsers(1);
  }

  protected requestStatusChange(user: User): void {
    if (this.isCurrentUser(user)) {
      return;
    }

    this.actionError.set(null);
    this.confirmingStatusUserId.set(user.id);
  }

  protected cancelStatusChange(): void {
    this.confirmingStatusUserId.set(null);
  }

  protected async changeStatus(user: User): Promise<void> {
    const nextStatus = user.status === 'active' ? 'inactive' : 'active';
    this.changingStatusUserId.set(user.id);
    this.actionError.set(null);

    try {
      const updatedUser = await this.userService.updateStatus(
        user.id,
        nextStatus,
      );
      this.users.update((users) =>
        users.map((item) => (item.id === updatedUser.id ? updatedUser : item)),
      );
      this.confirmingStatusUserId.set(null);
    } catch (error) {
      this.confirmingStatusUserId.set(null);
      this.actionError.set(
        getApiErrorMessage(
          error,
          'The user status could not be changed. Please try again.',
        ),
      );
    } finally {
      this.changingStatusUserId.set(null);
    }
  }

  protected statusUser(): User | undefined {
    const userId = this.confirmingStatusUserId();
    return this.users().find((user) => user.id === userId);
  }

  protected isCurrentUser(user: User): boolean {
    return this.auth.user()?.id === user.id;
  }

  private async loadInitialData(): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);

    try {
      const [usersResponse, roles] = await Promise.all([
        this.userService.index({ page: 1 }),
        this.userService.roleOptions(),
      ]);
      this.roles.set(roles);
      this.users.set(usersResponse.data);
      this.pagination.set(usersResponse.meta);
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(
          error,
          'Users could not be loaded. Please try again.',
        ),
      );
    } finally {
      this.loading.set(false);
    }
  }

  protected async loadUsers(
    page = this.pagination().current_page,
  ): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);
    this.actionError.set(null);

    const filters: UserFilters = {
      search: this.search.trim() || undefined,
      type:
        this.type === 'admin' || this.type === 'user' ? this.type : undefined,
      role_id: this.roleId ? Number(this.roleId) : undefined,
      status:
        this.status === 'active' || this.status === 'inactive'
          ? this.status
          : undefined,
      page,
    };

    try {
      const response = await this.userService.index(filters);
      this.users.set(response.data);
      this.pagination.set(response.meta);
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(
          error,
          'Users could not be loaded. Please try again.',
        ),
      );
    } finally {
      this.loading.set(false);
    }
  }
}
