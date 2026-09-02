import { ChangeDetectionStrategy, Component, inject } from '@angular/core';

import { LoadingService } from '../../../core/services/loading.service';

@Component({
  selector: 'app-spinner',
  templateUrl: './spinner.component.html',
  styleUrl: './spinner.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SpinnerComponent {
  loadingService = inject(LoadingService);
}
