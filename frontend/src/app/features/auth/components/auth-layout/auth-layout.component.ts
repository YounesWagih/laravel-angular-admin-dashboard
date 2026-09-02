import { Component, input } from '@angular/core';

import { LanguageSwitcherComponent } from '../../../../core/components/language-switcher/language-switcher.component';
import { TranslatePipe } from '../../../../core/i18n/translate.pipe';

@Component({
  selector: 'app-auth-layout',
  imports: [LanguageSwitcherComponent, TranslatePipe],
  templateUrl: './auth-layout.component.html',
  styleUrl: './auth-layout.component.scss'
})
export class AuthLayoutComponent {
  readonly title = input.required<string>();
  readonly subtitle = input.required<string>();
}
