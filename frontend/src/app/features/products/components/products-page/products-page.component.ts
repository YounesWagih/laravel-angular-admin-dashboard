import { TitleCasePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { ConfirmationDialogComponent } from '../../../../shared/components/confirmation-dialog/confirmation-dialog.component';
import { PaginationComponent } from '../../../../shared/components/pagination/pagination.component';
import {
  EMPTY_PAGINATION_META,
  type PaginationMeta,
} from '../../../../shared/models/pagination.model';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import type {
  Product,
  ProductCategory,
  ProductStatus,
} from '../../models/product.model';
import { ProductService } from '../../services/product.service';

@Component({
  selector: 'app-products-page',
  imports: [
    ConfirmationDialogComponent,
    PaginationComponent,
    ReactiveFormsModule,
    RouterLink,
    TitleCasePipe,
  ],
  templateUrl: './products-page.component.html',
  styleUrl: './products-page.component.scss',
})
export class ProductsPageComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly formBuilder = inject(FormBuilder);
  private readonly productService = inject(ProductService);

  products = signal<Product[]>([]);
  categories = signal<ProductCategory[]>([]);
  loading = signal(true);
  pageError = signal<string | null>(null);
  actionError = signal<string | null>(null);
  pagination = signal<PaginationMeta>(EMPTY_PAGINATION_META);
  confirmingDeleteId = signal<number | null>(null);
  deletingProductId = signal<number | null>(null);

  filtersForm = this.formBuilder.nonNullable.group({
    search: ['', Validators.maxLength(255)],
    category_id: [0],
    status: this.formBuilder.nonNullable.control<ProductStatus | ''>(''),
  });

  ngOnInit(): void {
    void this.loadPage();
  }

  protected canCreate(): boolean {
    return this.auth.hasPermission('products.create');
  }

  protected canUpdate(): boolean {
    return this.auth.hasPermission('products.update');
  }

  protected canDelete(): boolean {
    return this.auth.hasPermission('products.delete');
  }

  protected applyFilters(): void {
    if (this.filtersForm.invalid) {
      this.filtersForm.markAllAsTouched();
      return;
    }

    void this.loadProducts(1);
  }

  protected clearFilters(): void {
    this.filtersForm.reset({ search: '', category_id: 0, status: '' });
    void this.loadProducts(1);
  }

  protected hasFilters(): boolean {
    const filters = this.filtersForm.getRawValue();

    return (
      filters.search.trim() !== '' ||
      filters.category_id > 0 ||
      filters.status !== ''
    );
  }

  protected requestDelete(product: Product): void {
    this.actionError.set(null);
    this.confirmingDeleteId.set(product.id);
  }

  protected cancelDelete(): void {
    this.confirmingDeleteId.set(null);
  }

  protected async deleteProduct(product: Product): Promise<void> {
    this.deletingProductId.set(product.id);
    this.actionError.set(null);

    try {
      await this.productService.delete(product.id);
      this.confirmingDeleteId.set(null);
      const nextPage =
        this.products().length === 1
          ? Math.max(1, this.pagination().current_page - 1)
          : this.pagination().current_page;
      await this.loadProducts(nextPage);
    } catch (error) {
      this.confirmingDeleteId.set(null);
      this.actionError.set(
        getApiErrorMessage(
          error,
          'The product could not be deleted. Please try again.',
        ),
      );
    } finally {
      this.deletingProductId.set(null);
    }
  }

  protected deleteProductById(): Product | undefined {
    const productId = this.confirmingDeleteId();
    return this.products().find((product) => product.id === productId);
  }

  protected formatPrice(value: string): string {
    return new Intl.NumberFormat('en-EG', {
      style: 'currency',
      currency: 'EGP',
    }).format(Number(value));
  }

  private async loadPage(): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);

    try {
      const [products, options] = await Promise.all([
        this.productService.index(),
        this.productService.options(),
      ]);
      this.categories.set(options.categories);
      this.products.set(products.data);
      this.pagination.set(products.meta);
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(
          error,
          'Products could not be loaded. Please try again.',
        ),
      );
    } finally {
      this.loading.set(false);
    }
  }

  protected async loadProducts(
    page = this.pagination().current_page,
  ): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);
    this.actionError.set(null);
    const filters = this.filtersForm.getRawValue();

    try {
      const response = await this.productService.index({
        search: filters.search.trim() || undefined,
        category_id: filters.category_id || undefined,
        status: filters.status || undefined,
        page,
      });
      this.products.set(response.data);
      this.pagination.set(response.meta);
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(
          error,
          'Products could not be loaded. Please try again.',
        ),
      );
    } finally {
      this.loading.set(false);
    }
  }
}
