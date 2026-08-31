import { Component, effect, ElementRef, inject, OnInit, signal, viewChild } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';

import { AuthService } from '../../../../core/services/auth.service';
import { ConfirmationDialogComponent } from '../../../../shared/components/confirmation-dialog/confirmation-dialog.component';
import { FormFieldErrorComponent } from '../../../../shared/components/form-field-error/form-field-error.component';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { applyServerValidationErrors } from '../../../../shared/utils/form-error.util';
import type {
  Category,
  CategoryDetails,
  CategoryPayload,
  PaginatedCategoriesResponse
} from '../../models/category.model';
import { CategoryService } from '../../services/category.service';

type CategoryModalMode = 'view' | 'create' | 'edit';

@Component({
  selector: 'app-categories-page',
  imports: [ConfirmationDialogComponent, FormFieldErrorComponent, ReactiveFormsModule],
  templateUrl: './categories-page.component.html',
  styleUrl: './categories-page.component.scss'
})
export class CategoriesPageComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly categoryService = inject(CategoryService);
  private readonly formBuilder = inject(FormBuilder);
  private readonly categoryDialog = viewChild<ElementRef<HTMLDialogElement>>('categoryDialog');

  protected readonly categories = signal<Category[]>([]);
  protected readonly loading = signal(true);
  protected readonly pageError = signal<string | null>(null);
  protected readonly actionError = signal<string | null>(null);
  protected readonly currentPage = signal(1);
  protected readonly lastPage = signal(1);
  protected readonly totalCategories = signal(0);
  protected readonly fromCategory = signal<number | null>(null);
  protected readonly toCategory = signal<number | null>(null);
  protected readonly modalMode = signal<CategoryModalMode | null>(null);
  protected readonly selectedCategory = signal<CategoryDetails | null>(null);
  protected readonly loadingCategory = signal(false);
  protected readonly modalError = signal<string | null>(null);
  protected readonly savingCategory = signal(false);
  protected readonly confirmingDeleteId = signal<number | null>(null);
  protected readonly deletingCategoryId = signal<number | null>(null);

  protected readonly searchForm = this.formBuilder.nonNullable.group({
    search: ['', Validators.maxLength(255)]
  });
  protected readonly categoryForm = this.formBuilder.nonNullable.group({
    name_en: ['', [Validators.required, Validators.maxLength(255)]],
    name_ar: ['', [Validators.required, Validators.maxLength(255)]],
    description_en: ['', Validators.maxLength(1000)],
    description_ar: ['', Validators.maxLength(1000)]
  });

  private readonly showDialog = effect(() => {
    const dialog = this.categoryDialog()?.nativeElement;

    if (this.modalMode() && dialog && !dialog.open) {
      dialog.showModal();
    }
  });

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

  protected goToPage(page: number): void {
    if (page < 1 || page > this.lastPage() || page === this.currentPage()) {
      return;
    }

    void this.loadCategories(page);
  }

  protected openCreateModal(): void {
    this.selectedCategory.set(null);
    this.modalError.set(null);
    this.categoryForm.reset({
      name_en: '',
      name_ar: '',
      description_en: '',
      description_ar: ''
    });
    this.modalMode.set('create');
  }

  protected openViewModal(category: Category): void {
    void this.openDetailsModal(category.id, 'view');
  }

  protected openEditModal(category: Category): void {
    void this.openDetailsModal(category.id, 'edit');
  }

  protected closeModal(): void {
    if (this.savingCategory()) {
      return;
    }

    this.categoryDialog()?.nativeElement.close();
    this.modalMode.set(null);
    this.selectedCategory.set(null);
    this.modalError.set(null);
  }

  protected cancelDialog(event: Event): void {
    event.preventDefault();
    this.closeModal();
  }

  protected async saveCategory(): Promise<void> {
    if (this.categoryForm.invalid) {
      this.categoryForm.markAllAsTouched();
      return;
    }

    const mode = this.modalMode();
    const category = this.selectedCategory();

    if (mode !== 'create' && (mode !== 'edit' || !category)) {
      return;
    }

    this.savingCategory.set(true);
    this.modalError.set(null);
    const values = this.categoryForm.getRawValue();
    const payload: CategoryPayload = {
      name_en: values.name_en.trim(),
      name_ar: values.name_ar.trim(),
      description_en: values.description_en.trim() || null,
      description_ar: values.description_ar.trim() || null
    };

    try {
      if (mode === 'create') {
        await this.categoryService.create(payload);
        this.closeModalAfterSave();
        await this.loadCategories(1);
      } else if (category) {
        const updatedCategory = await this.categoryService.update(category.id, payload);
        this.categories.update((categories) =>
          categories.map((item) => (item.id === updatedCategory.id ? updatedCategory : item))
        );
        this.closeModalAfterSave();
      }
    } catch (error) {
      this.modalError.set(
        applyServerValidationErrors(this.categoryForm, error) ??
          getApiErrorMessage(error, 'The category could not be saved. Please try again.')
      );
    } finally {
      this.savingCategory.set(false);
    }
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
      const nextPage = this.categories().length === 1 ? Math.max(1, this.currentPage() - 1) : this.currentPage();
      await this.loadCategories(nextPage);
    } catch (error) {
      this.confirmingDeleteId.set(null);
      this.actionError.set(
        getApiErrorMessage(
          error,
          'The category could not be deleted. Remove its products first and try again.'
        )
      );
    } finally {
      this.deletingCategoryId.set(null);
    }
  }

  protected deleteCategoryById(): Category | undefined {
    const categoryId = this.confirmingDeleteId();
    return this.categories().find((category) => category.id === categoryId);
  }

  protected formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-EG', { dateStyle: 'medium' }).format(new Date(value));
  }

  protected async loadCategories(page = this.currentPage()): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);
    this.actionError.set(null);

    try {
      const response = await this.categoryService.index({
        search: this.searchForm.controls.search.value.trim() || undefined,
        page
      });
      this.applyResponse(response);
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(error, 'Categories could not be loaded. Please try again.')
      );
    } finally {
      this.loading.set(false);
    }
  }

  private async openDetailsModal(categoryId: number, mode: 'view' | 'edit'): Promise<void> {
    this.modalMode.set(mode);
    this.selectedCategory.set(null);
    this.modalError.set(null);
    this.loadingCategory.set(true);

    try {
      const category = await this.categoryService.show(categoryId);
      this.selectedCategory.set(category);

      if (mode === 'edit') {
        this.categoryForm.reset({
          name_en: category.name_en,
          name_ar: category.name_ar,
          description_en: category.description_en ?? '',
          description_ar: category.description_ar ?? ''
        });
      }
    } catch (error) {
      this.modalError.set(getApiErrorMessage(error, 'The category could not be loaded.'));
    } finally {
      this.loadingCategory.set(false);
    }
  }

  private applyResponse(response: PaginatedCategoriesResponse): void {
    this.categories.set(response.data);
    this.currentPage.set(response.meta.current_page);
    this.lastPage.set(response.meta.last_page);
    this.totalCategories.set(response.meta.total);
    this.fromCategory.set(response.meta.from);
    this.toCategory.set(response.meta.to);
  }

  private closeModalAfterSave(): void {
    this.categoryDialog()?.nativeElement.close();
    this.modalMode.set(null);
    this.selectedCategory.set(null);
    this.modalError.set(null);
  }
}
