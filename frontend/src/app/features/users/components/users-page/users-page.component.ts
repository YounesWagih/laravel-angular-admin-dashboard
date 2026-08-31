import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { ConfirmationDialogComponent } from '../../../../shared/components/confirmation-dialog/confirmation-dialog.component';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import type { User, UserFilters, UserRole } from '../../models/user.model';
import { UserService } from '../../services/user.service';

@Component({
  selector: 'app-users-page',
  imports: [ConfirmationDialogComponent, FormsModule, RouterLink],
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
  protected readonly currentPage = signal(1);
  protected readonly lastPage = signal(1);
  protected readonly totalUsers = signal(0);
  protected readonly fromUser = signal<number | null>(null);
  protected readonly toUser = signal<number | null>(null);
  protected readonly confirmingStatusUserId = signal<number | null>(null);
  protected readonly changingStatusUserId = signal<number | null>(null);

  protected search = '';
  protected type = '';
  protected roleId = '';
  protected status = '';

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

  protected goToPage(page: number): void {
    if (page < 1 || page > this.lastPage() || page === this.currentPage()) {
      return;
    }

    void this.loadUsers(page);
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

  protected formatLabel(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
  }

  protected formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-EG', { dateStyle: 'medium' }).format(new Date(value));
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
      this.applyResponse(usersResponse);
    } catch (error) {
      this.pageError.set(getApiErrorMessage(error, 'Users could not be loaded. Please try again.'));
    } finally {
      this.loading.set(false);
    }
  }

  protected async loadUsers(page = this.currentPage()): Promise<void> {
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
      this.applyResponse(await this.userService.index(filters));
    } catch (error) {
      this.pageError.set(getApiErrorMessage(error, 'Users could not be loaded. Please try again.'));
    } finally {
      this.loading.set(false);
    }
  }

  private applyResponse(response: Awaited<ReturnType<UserService['index']>>): void {
    this.users.set(response.data);
    this.currentPage.set(response.meta.current_page);
    this.lastPage.set(response.meta.last_page);
    this.totalUsers.set(response.meta.total);
    this.fromUser.set(response.meta.from);
    this.toUser.set(response.meta.to);
  }
}

