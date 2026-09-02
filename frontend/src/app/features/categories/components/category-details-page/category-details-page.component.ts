import { Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { ConfirmationDialogComponent } from '../../../../shared/components/confirmation-dialog/confirmation-dialog.component';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { formatDateTime } from '../../../../shared/utils/date.util';
import type { CategoryDetails } from '../../models/category.model';
import { CategoryService } from '../../services/category.service';

@Component({
  selector: 'app-category-details-page',
  imports: [ConfirmationDialogComponent, RouterLink],
  templateUrl: './category-details-page.component.html',
  styleUrl: './category-details-page.component.scss',
})
export class CategoryDetailsPageComponent implements OnInit {
  auth = inject(AuthService);
  route = inject(ActivatedRoute);
  router = inject(Router);
  categoryService = inject(CategoryService);

  category = signal<CategoryDetails | null>(null);
  loading = signal(true);
  pageError = signal<string | null>(null);
  actionError = signal<string | null>(null);
  confirmingDelete = signal(false);
  deleting = signal(false);
  categoryId = Number(this.route.snapshot.paramMap.get('id'));
  formatDate = formatDateTime;

  ngOnInit(): void {
    void this.loadCategory();
  }

  protected canUpdate(): boolean {
    return this.auth.hasPermission('categories.update');
  }

  protected canDelete(): boolean {
    return this.auth.hasPermission('categories.delete');
  }

  protected requestDelete(): void {
    this.actionError.set(null);
    this.confirmingDelete.set(true);
  }

  protected cancelDelete(): void {
    this.confirmingDelete.set(false);
  }

  protected async deleteCategory(category: CategoryDetails): Promise<void> {
    this.deleting.set(true);
    this.actionError.set(null);

    try {
      await this.categoryService.delete(category.id);
      await this.router.navigateByUrl('/categories');
    } catch (error) {
      this.confirmingDelete.set(false);
      this.actionError.set(
        getApiErrorMessage(
          error,
          'The category could not be deleted. Remove its products first and try again.',
        ),
      );
    } finally {
      this.deleting.set(false);
    }
  }

  private async loadCategory(): Promise<void> {
    if (!Number.isInteger(this.categoryId) || this.categoryId < 1) {
      this.pageError.set('The requested category could not be found.');
      this.loading.set(false);
      return;
    }

    try {
      this.category.set(await this.categoryService.show(this.categoryId));
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(error, 'The category could not be loaded.'),
      );
    } finally {
      this.loading.set(false);
    }
  }
}
