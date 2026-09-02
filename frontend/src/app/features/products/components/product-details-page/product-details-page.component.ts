import { TitleCasePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { reloadOnLanguageChange } from '../../../../core/i18n/reload-on-language-change';
import { TranslatePipe } from '../../../../core/i18n/translate.pipe';
import { ConfirmationDialogComponent } from '../../../../shared/components/confirmation-dialog/confirmation-dialog.component';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { formatCurrency, formatDateTime } from '../../../../shared/utils/date.util';
import type { ProductDetails, ProductImage } from '../../models/product.model';
import { ProductService } from '../../services/product.service';

@Component({
  selector: 'app-product-details-page',
  imports: [ConfirmationDialogComponent, RouterLink, TitleCasePipe, TranslatePipe],
  templateUrl: './product-details-page.component.html',
  styleUrl: './product-details-page.component.scss',
})
export class ProductDetailsPageComponent implements OnInit {
  auth = inject(AuthService);
  route = inject(ActivatedRoute);
  router = inject(Router);
  productService = inject(ProductService);

  product = signal<ProductDetails | null>(null);
  selectedImage = signal<ProductImage | null>(null);
  loading = signal(true);
  pageError = signal<string | null>(null);
  actionError = signal<string | null>(null);
  confirmingDelete = signal(false);
  deleting = signal(false);
  formatDate = formatDateTime;
  productId = Number(this.route.snapshot.paramMap.get('id'));

  constructor() {
    reloadOnLanguageChange(() => void this.loadProduct());
  }

  ngOnInit(): void {
    void this.loadProduct();
  }

  protected canUpdate(): boolean {
    return this.auth.hasPermission('products.update');
  }

  protected canDelete(): boolean {
    return this.auth.hasPermission('products.delete');
  }

  protected formatPrice(value: string): string {
    return formatCurrency(value);
  }

  protected requestDelete(): void {
    this.actionError.set(null);
    this.confirmingDelete.set(true);
  }

  protected cancelDelete(): void {
    this.confirmingDelete.set(false);
  }

  protected selectImage(image: ProductImage): void {
    this.selectedImage.set(image);
  }

  protected async deleteProduct(product: ProductDetails): Promise<void> {
    this.deleting.set(true);

    try {
      await this.productService.delete(product.id);
      await this.router.navigateByUrl('/products');
    } catch (error) {
      this.confirmingDelete.set(false);
      this.actionError.set(
        getApiErrorMessage(
          error,
          'The product could not be deleted. Please try again.',
        ),
      );
    } finally {
      this.deleting.set(false);
    }
  }

  private async loadProduct(): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);
    this.actionError.set(null);

    if (!Number.isInteger(this.productId) || this.productId < 1) {
      this.pageError.set('The requested product could not be found.');
      this.loading.set(false);
      return;
    }

    try {
      const product = await this.productService.show(this.productId);
      this.product.set(product);
      this.selectedImage.set(
        product.images.find((image) => image.is_primary) ?? product.images[0] ?? null,
      );
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(error, 'The product could not be loaded.'),
      );
    } finally {
      this.loading.set(false);
    }
  }
}
