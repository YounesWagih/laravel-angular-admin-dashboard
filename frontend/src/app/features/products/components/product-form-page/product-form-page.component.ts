import { Component, inject, OnDestroy, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { LanguageService } from '../../../../core/i18n/language.service';
import { reloadOnLanguageChange } from '../../../../core/i18n/reload-on-language-change';
import { FormFieldErrorComponent } from '../../../../shared/components/form-field-error/form-field-error.component';
import { TranslatePipe } from '../../../../core/i18n/translate.pipe';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import {
  applyServerValidationErrors,
  clearServerValidationErrors,
} from '../../../../shared/utils/form-error.util';
import type {
  ProductCategory,
  ProductDetails,
  ProductImage,
  ProductPayload,
  ProductStatus,
} from '../../models/product.model';
import { ProductService } from '../../services/product.service';

const MAX_IMAGE_SIZE = 2 * 1024 * 1024;
const MAX_PRODUCT_IMAGES = 5;
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

interface ImageItem {
  key: string;
  id: number | null;
  file: File | null;
  url: string;
  name: string;
}

@Component({
  selector: 'app-product-form-page',
  imports: [FormFieldErrorComponent, ReactiveFormsModule, RouterLink, TranslatePipe],
  templateUrl: './product-form-page.component.html',
  styleUrl: './product-form-page.component.scss',
})
export class ProductFormPageComponent implements OnInit, OnDestroy {
  private readonly language = inject(LanguageService);
  formBuilder = inject(FormBuilder);
  route = inject(ActivatedRoute);
  router = inject(Router);
  productService = inject(ProductService);

  productId = this.readProductId();
  editing = this.route.snapshot.paramMap.has('id');
  categories = signal<ProductCategory[]>([]);
  images = signal<ImageItem[]>([]);
  removedIds: number[] = [];
  primaryKey = signal<string | null>(null);
  loading = signal(true);
  saving = signal(false);
  pageError = signal<string | null>(null);
  formError = signal<string | null>(null);
  imageError = signal<string | null>(null);
  private nextImageNumber = 1;
  private readonly previewUrls = new Set<string>();

  productForm = this.formBuilder.group({
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
  });

  constructor() {
    reloadOnLanguageChange(() => {
      const shouldReloadPage = this.pageError() !== null;

      this.pageError.set(null);
      this.formError.set(null);
      this.imageError.set(null);
      clearServerValidationErrors(this.productForm);

      if (shouldReloadPage) {
        void this.loadPage();
      } else {
        void this.loadCategoryOptions();
      }
    });
  }

  ngOnInit(): void {
    void this.loadPage();
  }

  ngOnDestroy(): void {
    for (const url of this.previewUrls) {
      URL.revokeObjectURL(url);
    }
  }

  protected selectImages(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    input.value = '';
    this.imageError.set(null);

    if (files.length === 0) {
      return;
    }

    if (this.images().length + files.length > MAX_PRODUCT_IMAGES) {
      this.imageError.set(
        this.language.translate('A product can have at most :count images.', {
          count: MAX_PRODUCT_IMAGES,
        }),
      );
      return;
    }

    for (const file of files) {
      if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
        this.imageError.set(
          this.language.translate(':name must be a JPG, PNG, or WebP image.', {
            name: file.name,
          }),
        );
        return;
      }

      if (file.size > MAX_IMAGE_SIZE) {
        this.imageError.set(
          this.language.translate(':name must be 2 MB or smaller.', {
            name: file.name,
          }),
        );
        return;
      }
    }

    const newImages = files.map((file): ImageItem => {
      const url = URL.createObjectURL(file);
      const key = `new_${this.nextImageNumber++}`;
      this.previewUrls.add(url);

      return {
        key,
        id: null,
        file,
        url,
        name: file.name,
      };
    });

    this.images.update((images) => [...images, ...newImages]);

    if (this.primaryKey() === null) {
      this.primaryKey.set(newImages[0]?.key ?? null);
    }
  }

  protected removeImage(index: number): void {
    const images = [...this.images()];
    const [image] = images.splice(index, 1);

    if (!image) {
      return;
    }

    const imageId = image.id;

    if (imageId !== null) {
      this.removedIds.push(imageId);
    } else {
      URL.revokeObjectURL(image.url);
      this.previewUrls.delete(image.url);
    }

    this.images.set(images);
    if (this.primaryKey() === image.key) {
      this.primaryKey.set(images[0]?.key ?? null);
    }
    this.imageError.set(null);
  }

  protected setPrimary(imageKey: string): void {
    this.primaryKey.set(imageKey);
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
      new_images: this.newFiles(),
      primary_image: this.primaryValue(),
      removed_image_ids: this.removedIds,
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

  private async loadCategoryOptions(): Promise<void> {
    try {
      const options = await this.productService.options();
      this.categories.set(options.categories);
    } catch {
      // Keep the current form values intact if category labels cannot refresh.
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
    });
    this.images.set(
      [...product.images]
        .sort((first, second) => first.order - second.order)
        .map((image) => this.existingImage(image)),
    );
    const primaryImage = product.images.find((image) => image.is_primary) ?? product.images[0];
    this.primaryKey.set(primaryImage ? `existing_${primaryImage.id}` : null);
    this.removedIds = [];
  }

  private existingImage(image: ProductImage): ImageItem {
    return {
      key: `existing_${image.id}`,
      id: image.id,
      file: null,
      url: image.url,
      name: image.file_name,
    };
  }

  private newFiles(): File[] {
    return this.images().flatMap((image) => (image.file ? [image.file] : []));
  }

  private primaryValue(): string | null {
    const primaryImage = this.images().find((image) => image.key === this.primaryKey());

    if (!primaryImage) {
      return null;
    }

    if (primaryImage.id !== null) {
      return `existing:${primaryImage.id}`;
    }

    const newImages = this.images().filter((image) => image.file !== null);

    return `new:${newImages.findIndex((image) => image.key === primaryImage.key)}`;
  }

  private readProductId(): number | null {
    const value = this.route.snapshot.paramMap.get('id');
    const productId = value ? Number(value) : null;

    return productId !== null && Number.isInteger(productId) && productId > 0
      ? productId
      : null;
  }
}
