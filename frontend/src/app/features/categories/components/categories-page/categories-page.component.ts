import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { ConfirmationDialogComponent } from '../../../../shared/components/confirmation-dialog/confirmation-dialog.component';
import { FormFieldErrorComponent } from '../../../../shared/components/form-field-error/form-field-error.component';
import { PaginationComponent } from '../../../../shared/components/pagination/pagination.component';
import {
  EMPTY_PAGINATION_META,
  type PaginationMeta,
} from '../../../../shared/models/pagination.model';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { formatDate } from '../../../../shared/utils/date.util';
import type { Category } from '../../models/category.model';
import { CategoryService } from '../../services/category.service';

@Component({
  selector: 'app-categories-page',
  imports: [
    ConfirmationDialogComponent,
    FormFieldErrorComponent,
    PaginationComponent,
    ReactiveFormsModule,
    RouterLink,
  ],
  templateUrl: './categories-page.component.html',
  styleUrl: './categories-page.component.scss',
})
export class CategoriesPageComponent implements OnInit {
  auth = inject(AuthService);
  categoryService = inject(CategoryService);
  formBuilder = inject(FormBuilder);

  categories = signal<Category[]>([]);
  loading = signal(true);
  pageError = signal<string | null>(null);
  actionError = signal<string | null>(null);
  pagination = signal<PaginationMeta>(EMPTY_PAGINATION_META);
  confirmingDeleteId = signal<number | null>(null);
  deletingCategoryId = signal<number | null>(null);

  searchForm = this.formBuilder.nonNullable.group({
    search: ['', Validators.maxLength(255)],
  });
  formatDate = formatDate;

  ngOnInit(): void {
    void this.loadCategories();
  }

  protected canCreate(): boolean {
    return this.auth.hasPermission('categories.create');
  }

  protected canUpdate(): boolean {
    return this.auth.hasPermission('categories.update');
  }

  protected canDelete(): boolean {
    return this.auth.hasPermission('categories.delete');
  }

  protected applySearch(): void {
    if (this.searchForm.invalid) {
      this.searchForm.markAllAsTouched();
      return;
    }

    void this.loadCategories(1);
  }

  protected clearSearch(): void {
    this.searchForm.reset({ search: '' });
    void this.loadCategories(1);
  }

  protected hasSearch(): boolean {
    return this.searchForm.controls.search.value.trim() !== '';
  }

  protected requestDelete(category: Category): void {
    this.actionError.set(null);
    this.confirmingDeleteId.set(category.id);
  }

  protected cancelDelete(): void {
    this.confirmingDeleteId.set(null);
  }

  protected async deleteCategory(category: Category): Promise<void> {
    this.deletingCategoryId.set(category.id);
    this.actionError.set(null);

    try {
      await this.categoryService.delete(category.id);
      this.confirmingDeleteId.set(null);
      const currentPage = this.pagination().current_page;
      const nextPage =
        this.categories().length === 1
          ? Math.max(1, currentPage - 1)
          : currentPage;
      await this.loadCategories(nextPage);
    } catch (error) {
      this.confirmingDeleteId.set(null);
      this.actionError.set(
        getApiErrorMessage(
          error,
          'The category could not be deleted. Remove its products first and try again.',
        ),
      );
    } finally {
      this.deletingCategoryId.set(null);
    }
  }

  protected deleteCategoryById(): Category | undefined {
    const categoryId = this.confirmingDeleteId();
    return this.categories().find((category) => category.id === categoryId);
  }

  protected async loadCategories(
    page = this.pagination().current_page,
  ): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);
    this.actionError.set(null);

    try {
      const response = await this.categoryService.index({
        search: this.searchForm.controls.search.value.trim() || undefined,
        page,
      });
      this.categories.set(response.data);
      this.pagination.set(response.meta);
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(
          error,
          'Categories could not be loaded. Please try again.',
        ),
      );
    } finally {
      this.loading.set(false);
    }
  }
}
