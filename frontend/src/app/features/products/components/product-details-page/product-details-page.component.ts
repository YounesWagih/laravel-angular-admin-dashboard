import { TitleCasePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { AuthService } from '../../../../core/services/auth.service';
import { ConfirmationDialogComponent } from '../../../../shared/components/confirmation-dialog/confirmation-dialog.component';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { formatDateTime } from '../../../../shared/utils/date.util';
import type { Product } from '../../models/product.model';
import { ProductService } from '../../services/product.service';

@Component({
  selector: 'app-product-details-page',
  imports: [ConfirmationDialogComponent, RouterLink, TitleCasePipe],
  templateUrl: './product-details-page.component.html',
  styleUrl: './product-details-page.component.scss'
})
export class ProductDetailsPageComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly productService = inject(ProductService);

  protected readonly product = signal<Product | null>(null);
  protected readonly loading = signal(true);
  protected readonly pageError = signal<string | null>(null);
  protected readonly actionError = signal<string | null>(null);
  protected readonly confirmingDelete = signal(false);
  protected readonly deleting = signal(false);
  protected readonly formatDate = formatDateTime;
  private readonly productId = Number(this.route.snapshot.paramMap.get('id'));

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
    return new Intl.NumberFormat('en-EG', {
      style: 'currency',
      currency: 'EGP'
    }).format(Number(value));
  }

  protected requestDelete(): void {
    this.actionError.set(null);
    this.confirmingDelete.set(true);
  }

  protected cancelDelete(): void {
    this.confirmingDelete.set(false);
  }

  protected async deleteProduct(product: Product): Promise<void> {
    this.deleting.set(true);

    try {
      await this.productService.delete(product.id);
      await this.router.navigateByUrl('/products');
    } catch (error) {
      this.confirmingDelete.set(false);
      this.actionError.set(
        getApiErrorMessage(
          error,
          'The product could not be deleted. Please try again.'
        )
      );
    } finally {
      this.deleting.set(false);
    }
  }

  private async loadProduct(): Promise<void> {
    if (!Number.isInteger(this.productId) || this.productId < 1) {
      this.pageError.set('The requested product could not be found.');
      this.loading.set(false);
      return;
    }

    try {
      this.product.set(await this.productService.show(this.productId));
    } catch (error) {
      this.pageError.set(getApiErrorMessage(error, 'The product could not be loaded.'));
    } finally {
      this.loading.set(false);
    }
  }
}
