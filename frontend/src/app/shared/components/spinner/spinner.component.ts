import { ChangeDetectionStrategy, Component, inject } from '@angular/core';

import { TranslatePipe } from '../../../core/i18n/translate.pipe';
import { LoadingService } from '../../../core/services/loading.service';

@Component({
  selector: 'app-spinner',
  imports: [TranslatePipe],
  templateUrl: './spinner.component.html',
  styleUrl: './spinner.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SpinnerComponent {
  loadingService = inject(LoadingService);
}
