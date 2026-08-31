import { Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';

import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import type { User } from '../../models/user.model';
import { UserService } from '../../services/user.service';

@Component({
  selector: 'app-user-details-page',
  imports: [RouterLink],
  templateUrl: './user-details-page.component.html',
  styleUrl: './user-details-page.component.scss'
})
export class UserDetailsPageComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly userService = inject(UserService);

  protected readonly user = signal<User | null>(null);
  protected readonly loading = signal(true);
  protected readonly pageError = signal<string | null>(null);
  private readonly userId = Number(this.route.snapshot.paramMap.get('id'));

  ngOnInit(): void {
    void this.loadUser();
  }

  protected formatLabel(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
  }

  protected formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-EG', {
      dateStyle: 'medium',
      timeStyle: 'short'
    }).format(new Date(value));
  }

  private async loadUser(): Promise<void> {
    if (!Number.isInteger(this.userId) || this.userId < 1) {
      this.pageError.set('The requested user could not be found.');
      this.loading.set(false);
      return;
    }

    try {
      this.user.set(await this.userService.show(this.userId));
    } catch (error) {
      this.pageError.set(getApiErrorMessage(error, 'The user could not be loaded.'));
    } finally {
      this.loading.set(false);
    }
  }
}

