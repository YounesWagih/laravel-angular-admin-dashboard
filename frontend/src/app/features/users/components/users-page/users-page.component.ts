import { TitleCasePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { ConfirmationDialogComponent } from '../../../../shared/components/confirmation-dialog/confirmation-dialog.component';
import { PaginationComponent } from '../../../../shared/components/pagination/pagination.component';
import {
  EMPTY_PAGINATION_META,
  type PaginationMeta
} from '../../../../shared/models/pagination.model';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { formatDate } from '../../../../shared/utils/date.util';
import type { User, UserFilters, UserRole } from '../../models/user.model';
import { UserService } from '../../services/user.service';

@Component({
  selector: 'app-users-page',
  imports: [ConfirmationDialogComponent, FormsModule, PaginationComponent, RouterLink, TitleCasePipe],
  templateUrl: './users-page.component.html',
  styleUrl: './users-page.component.scss'
})
export class UsersPageComponent implements OnInit {
  protected readonly auth = inject(AuthService);
  private readonly userService = inject(UserService);

  protected readonly users = signal<User[]>([]);
  protected readonly roles = signal<UserRole[]>([]);
  protected readonly loading = signal(true);
  protected readonly pageError = signal<string | null>(null);
  protected readonly actionError = signal<string | null>(null);
  protected readonly pagination = signal<PaginationMeta>(EMPTY_PAGINATION_META);
  protected readonly confirmingStatusUserId = signal<number | null>(null);
  protected readonly changingStatusUserId = signal<number | null>(null);

  protected search = '';
  protected type = '';
  protected roleId = '';
  protected status = '';
  protected readonly formatDate = formatDate;

  ngOnInit(): void {
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
      const updatedUser = await this.userService.updateStatus(user.id, nextStatus);
      this.users.update((users) =>
        users.map((item) => (item.id === updatedUser.id ? updatedUser : item))
      );
      this.confirmingStatusUserId.set(null);
    } catch (error) {
      this.confirmingStatusUserId.set(null);
      this.actionError.set(
        getApiErrorMessage(error, 'The user status could not be changed. Please try again.')
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
        this.userService.roleOptions()
      ]);
      this.roles.set(roles);
      this.users.set(usersResponse.data);
      this.pagination.set(usersResponse.meta);
    } catch (error) {
      this.pageError.set(getApiErrorMessage(error, 'Users could not be loaded. Please try again.'));
    } finally {
      this.loading.set(false);
    }
  }

  protected async loadUsers(page = this.pagination().current_page): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);
    this.actionError.set(null);

    const filters: UserFilters = {
      search: this.search.trim() || undefined,
      type: this.type === 'admin' || this.type === 'user' ? this.type : undefined,
      role_id: this.roleId ? Number(this.roleId) : undefined,
      status:
        this.status === 'active' || this.status === 'inactive' ? this.status : undefined,
      page
    };

    try {
      const response = await this.userService.index(filters);
      this.users.set(response.data);
      this.pagination.set(response.meta);
    } catch (error) {
      this.pageError.set(getApiErrorMessage(error, 'Users could not be loaded. Please try again.'));
    } finally {
      this.loading.set(false);
    }
  }

}
