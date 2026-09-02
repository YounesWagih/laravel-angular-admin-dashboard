import { Component, inject } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';

import { TranslatePipe } from '../../../core/i18n/translate.pipe';

@Component({
  selector: 'app-error-page',
  imports: [RouterLink, TranslatePipe],
  templateUrl: './error-page.component.html',
  styleUrl: './error-page.component.scss',
})
export class ErrorPageComponent {
  code = inject(ActivatedRoute).snapshot.data['code'] === 403 ? 403 : 404;
  title = this.code === 403 ? 'Access denied' : 'Page not found';
  message =
    this.code === 403
      ? 'You do not have permission to view this page.'
      : 'The page you requested does not exist.';
}
