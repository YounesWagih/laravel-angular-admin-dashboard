import { Component, inject } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';

@Component({
  selector: 'app-error-page',
  imports: [RouterLink],
  templateUrl: './error-page.component.html',
  styleUrl: './error-page.component.scss'
})
export class ErrorPageComponent {
  protected readonly code = inject(ActivatedRoute).snapshot.data['code'] === 403 ? 403 : 404;
  protected readonly title = this.code === 403 ? 'Access denied' : 'Page not found';
  protected readonly message =
    this.code === 403
      ? 'You do not have permission to view this page.'
      : 'The page you requested does not exist.';
}
