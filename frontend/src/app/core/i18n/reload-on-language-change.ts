import { effect, inject } from '@angular/core';

import { LanguageService } from './language.service';


export function reloadOnLanguageChange(callback: () => void): void {
  const language = inject(LanguageService);
  let previousLocale = language.locale();

  effect(() => {
    const locale = language.locale();

    if (locale !== previousLocale) {
      previousLocale = locale;
      callback();
    }
  });
}
