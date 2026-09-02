import { Component, inject } from '@angular/core';

import { LanguageService } from '../../i18n/language.service';

@Component({
  selector: 'app-language-switcher',
  templateUrl: './language-switcher.component.html',
  styleUrl: './language-switcher.component.scss',
})
export class LanguageSwitcherComponent {
  protected readonly language = inject(LanguageService);
}
