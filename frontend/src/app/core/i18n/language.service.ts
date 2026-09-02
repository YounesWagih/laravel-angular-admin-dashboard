import { DOCUMENT } from '@angular/common';
import { computed, inject, Injectable, signal } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { Observable, tap } from 'rxjs';

export type Locale = 'en' | 'ar';
export type TranslationReplacements = Record<string, string | number>;

const STORAGE_KEY = 'app.locale';

@Injectable({ providedIn: 'root' })
export class LanguageService {
  private readonly document = inject(DOCUMENT);
  private readonly translateService = inject(TranslateService);
  private readonly currentLocale = signal<Locale>(this.storedLocale());

  readonly locale = this.currentLocale.asReadonly();
  readonly isRtl = computed(() => this.currentLocale() === 'ar');

  initialize(): Observable<unknown> {
    return this.apply(this.currentLocale());
  }

  switchLocale(): void {
    this.use(this.currentLocale() === 'en' ? 'ar' : 'en');
  }

  use(locale: Locale): void {
    if (locale === this.currentLocale()) {
      return;
    }

    this.currentLocale.set(locale);
    this.document.defaultView?.localStorage.setItem(STORAGE_KEY, locale);
    this.apply(locale).subscribe();
  }

  translate(key: string, replacements: TranslationReplacements = {}): string {
    return this.translateService.instant(key, replacements);
  }

  private storedLocale(): Locale {
    return this.document.defaultView?.localStorage.getItem(STORAGE_KEY) === 'ar'
      ? 'ar'
      : 'en';
  }

  private apply(locale: Locale): Observable<unknown> {
    this.document.documentElement.lang = locale;
    this.document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';

    return this.translateService.use(locale).pipe(
      tap(() => {
        this.document.title = this.translate('Product Management');
      }),
    );
  }
}
