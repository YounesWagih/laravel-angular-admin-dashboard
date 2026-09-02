import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { reloadOnLanguageChange } from '../../../../core/i18n/reload-on-language-change';
import { FormFieldErrorComponent } from '../../../../shared/components/form-field-error/form-field-error.component';
import { TranslatePipe } from '../../../../core/i18n/translate.pipe';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import {
  applyServerValidationErrors,
  clearServerValidationErrors,
} from '../../../../shared/utils/form-error.util';
import type { CategoryDetails, CategoryPayload } from '../../models/category.model';
import { CategoryService } from '../../services/category.service';

@Component({
  selector: 'app-category-form-page',
  imports: [FormFieldErrorComponent, ReactiveFormsModule, RouterLink, TranslatePipe],
  templateUrl: './category-form-page.component.html',
  styleUrl: './category-form-page.component.scss',
})
export class CategoryFormPageComponent implements OnInit {
  formBuilder = inject(FormBuilder);
  route = inject(ActivatedRoute);
  router = inject(Router);
  categoryService = inject(CategoryService);

  categoryId = this.readCategoryId();
  editing = this.route.snapshot.paramMap.has('id');
  loading = signal(true);
  saving = signal(false);
  pageError = signal<string | null>(null);
  formError = signal<string | null>(null);

  categoryForm = this.formBuilder.nonNullable.group({
    name_en: ['', [Validators.required, Validators.maxLength(255)]],
    name_ar: ['', [Validators.required, Validators.maxLength(255)]],
    description_en: ['', Validators.maxLength(1000)],
    description_ar: ['', Validators.maxLength(1000)],
  });

  constructor() {
    reloadOnLanguageChange(() => {
      const shouldReloadPage = this.pageError() !== null;

      this.pageError.set(null);
      this.formError.set(null);
      clearServerValidationErrors(this.categoryForm);

      if (shouldReloadPage) {
        void this.loadPage();
      }
    });
  }

  ngOnInit(): void {
    void this.loadPage();
  }

  protected async saveCategory(): Promise<void> {
    if (this.categoryForm.invalid) {
      this.categoryForm.markAllAsTouched();
      return;
    }

    if (this.editing && this.categoryId === null) {
      return;
    }

    this.saving.set(true);
    this.formError.set(null);
    const values = this.categoryForm.getRawValue();
    const payload: CategoryPayload = {
      name_en: values.name_en.trim(),
      name_ar: values.name_ar.trim(),
      description_en: values.description_en.trim() || null,
      description_ar: values.description_ar.trim() || null,
    };

    try {
      const category =
        this.categoryId === null
          ? await this.categoryService.create(payload)
          : await this.categoryService.update(this.categoryId, payload);
      await this.router.navigate(['/categories', category.id]);
    } catch (error) {
      this.formError.set(
        applyServerValidationErrors(this.categoryForm, error) ??
          getApiErrorMessage(
            error,
            'The category could not be saved. Please try again.',
          ),
      );
    } finally {
      this.saving.set(false);
    }
  }

  private async loadPage(): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);

    try {
      if (!this.editing) {
        return;
      }

      if (this.categoryId === null) {
        this.pageError.set('The requested category could not be found.');
        return;
      }

      const category = await this.categoryService.show(this.categoryId);
      this.fillForm(category);
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(error, 'The category form could not be loaded.'),
      );
    } finally {
      this.loading.set(false);
    }
  }

  private fillForm(category: CategoryDetails): void {
    this.categoryForm.reset({
      name_en: category.name_en,
      name_ar: category.name_ar,
      description_en: category.description_en ?? '',
      description_ar: category.description_ar ?? '',
    });
  }

  private readCategoryId(): number | null {
    const value = this.route.snapshot.paramMap.get('id');
    const categoryId = value ? Number(value) : null;

    return categoryId !== null && Number.isInteger(categoryId) && categoryId > 0
      ? categoryId
      : null;
  }
}
