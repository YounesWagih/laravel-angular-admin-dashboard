import { TitleCasePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';

import { reloadOnLanguageChange } from '../../../../core/i18n/reload-on-language-change';
import { TranslatePipe } from '../../../../core/i18n/translate.pipe';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { formatDateTime } from '../../../../shared/utils/date.util';
import type { User } from '../../models/user.model';
import { UserService } from '../../services/user.service';

@Component({
  selector: 'app-user-details-page',
  imports: [RouterLink, TitleCasePipe, TranslatePipe],
  templateUrl: './user-details-page.component.html',
  styleUrl: './user-details-page.component.scss',
})
export class UserDetailsPageComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly userService = inject(UserService);

  user = signal<User | null>(null);
  loading = signal(true);
  pageError = signal<string | null>(null);
  formatDate = formatDateTime;
  private readonly userId = Number(this.route.snapshot.paramMap.get('id'));

  constructor() {
    reloadOnLanguageChange(() => void this.loadUser());
  }

  ngOnInit(): void {
    void this.loadUser();
  }

  private async loadUser(): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);

    if (!Number.isInteger(this.userId) || this.userId < 1) {
      this.pageError.set('The requested user could not be found.');
      this.loading.set(false);
      return;
    }

    try {
      this.user.set(await this.userService.show(this.userId));
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(error, 'The user could not be loaded.'),
      );
    } finally {
      this.loading.set(false);
    }
  }
}
