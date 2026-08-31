import { Component, inject, OnDestroy, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { FormFieldErrorComponent } from '../../../../shared/components/form-field-error/form-field-error.component';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { applyServerValidationErrors } from '../../../../shared/utils/form-error.util';
import type {
  ProductCategory,
  ProductDetails,
  ProductPayload,
  ProductStatus,
} from '../../models/product.model';
import { ProductService } from '../../services/product.service';

const MAX_IMAGE_SIZE = 2 * 1024 * 1024;
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

@Component({
  selector: 'app-product-form-page',
  imports: [FormFieldErrorComponent, ReactiveFormsModule, RouterLink],
  templateUrl: './product-form-page.component.html',
  styleUrl: './product-form-page.component.scss',
})
export class ProductFormPageComponent implements OnInit, OnDestroy {
  private readonly formBuilder = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly productService = inject(ProductService);

  protected readonly productId = this.readProductId();
  protected readonly editing = this.route.snapshot.paramMap.has('id');
  protected readonly categories = signal<ProductCategory[]>([]);
  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly pageError = signal<string | null>(null);
  protected readonly formError = signal<string | null>(null);
  protected readonly imageError = signal<string | null>(null);
  protected readonly imagePreview = signal<string | null>(null);
  private objectUrl: string | null = null;

  protected readonly productForm = this.formBuilder.group({
    name_en: this.formBuilder.nonNullable.control('', [
      Validators.required,
      Validators.maxLength(255),
    ]),
    name_ar: this.formBuilder.nonNullable.control('', [
      Validators.required,
      Validators.maxLength(255),
    ]),
    description_en: this.formBuilder.nonNullable.control(
      '',
      Validators.maxLength(5000),
    ),
    description_ar: this.formBuilder.nonNullable.control(
      '',
      Validators.maxLength(5000),
    ),
    category_id: this.formBuilder.nonNullable.control(0, [
      Validators.required,
      Validators.min(1),
    ]),
    price: this.formBuilder.nonNullable.control(0, [
      Validators.required,
      Validators.min(0),
    ]),
    stock: this.formBuilder.nonNullable.control(0, [
      Validators.required,
      Validators.min(0),
    ]),
    status: this.formBuilder.nonNullable.control<ProductStatus>(
      'active',
      Validators.required,
    ),
    image: this.formBuilder.control<File | null>(null),
  });

  ngOnInit(): void {
    void this.loadPage();
  }

  ngOnDestroy(): void {
    this.revokeObjectUrl();
  }

  protected selectImage(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    this.productForm.controls.image.setValue(null);
    this.productForm.controls.image.setErrors(null);
    this.imageError.set(null);

    if (!file) {
      return;
    }

    if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
      this.rejectImage(input, 'Choose a JPG, PNG, or WebP image.');
      return;
    }

    if (file.size > MAX_IMAGE_SIZE) {
      this.rejectImage(input, 'The image must be 2 MB or smaller.');
      return;
    }

    this.productForm.controls.image.setValue(file);
    this.productForm.controls.image.markAsDirty();
    this.revokeObjectUrl();
    this.objectUrl = URL.createObjectURL(file);
    this.imagePreview.set(this.objectUrl);
  }

  protected async saveProduct(): Promise<void> {
    if (this.productForm.invalid) {
      this.productForm.markAllAsTouched();
      return;
    }

    if (this.editing && this.productId === null) {
      return;
    }

    this.saving.set(true);
    this.formError.set(null);
    const values = this.productForm.getRawValue();
    const payload: ProductPayload = {
      name_en: values.name_en.trim(),
      name_ar: values.name_ar.trim(),
      description_en: values.description_en.trim() || null,
      description_ar: values.description_ar.trim() || null,
      category_id: values.category_id,
      price: values.price,
      stock: values.stock,
      status: values.status,
      image: values.image,
    };

    try {
      const product =
        this.productId === null
          ? await this.productService.create(payload)
          : await this.productService.update(this.productId, payload);
      await this.router.navigate(['/products', product.id]);
    } catch (error) {
      this.formError.set(
        applyServerValidationErrors(this.productForm, error) ??
          getApiErrorMessage(
            error,
            'The product could not be saved. Please try again.',
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
        const options = await this.productService.options();
        this.categories.set(options.categories);
        return;
      }

      if (this.productId === null) {
        this.pageError.set('The requested product could not be found.');
        return;
      }

      const [product, options] = await Promise.all([
        this.productService.show(this.productId),
        this.productService.options(),
      ]);
      this.categories.set(options.categories);
      this.fillForm(product);
    } catch (error) {
      this.pageError.set(
        getApiErrorMessage(error, 'The product form could not be loaded.'),
      );
    } finally {
      this.loading.set(false);
    }
  }

  private fillForm(product: ProductDetails): void {
    this.productForm.reset({
      name_en: product.name_en,
      name_ar: product.name_ar,
      description_en: product.description_en ?? '',
      description_ar: product.description_ar ?? '',
      category_id: product.category.id,
      price: Number(product.price),
      stock: product.stock,
      status: product.status,
      image: null,
    });
    this.imagePreview.set(product.image_url);
  }

  private rejectImage(input: HTMLInputElement, message: string): void {
    input.value = '';
    this.productForm.controls.image.setErrors({ invalidFile: true });
    this.productForm.controls.image.markAsTouched();
    this.imageError.set(message);
  }

  private revokeObjectUrl(): void {
    if (this.objectUrl) {
      URL.revokeObjectURL(this.objectUrl);
      this.objectUrl = null;
    }
  }

  private readProductId(): number | null {
    const value = this.route.snapshot.paramMap.get('id');
    const productId = value ? Number(value) : null;

    return productId !== null && Number.isInteger(productId) && productId > 0
      ? productId
      : null;
  }
}
