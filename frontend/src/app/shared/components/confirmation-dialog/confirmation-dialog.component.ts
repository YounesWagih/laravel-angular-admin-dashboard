import { AfterViewInit, Component, ElementRef, input, output, viewChild } from '@angular/core';

@Component({
  selector: 'app-confirmation-dialog',
  templateUrl: './confirmation-dialog.component.html',
  styleUrl: './confirmation-dialog.component.scss'
})
export class ConfirmationDialogComponent implements AfterViewInit {
  private readonly dialog = viewChild.required<ElementRef<HTMLDialogElement>>('dialog');

  readonly title = input.required<string>();
  readonly message = input.required<string>();
  readonly confirmLabel = input('Confirm');
  readonly busy = input(false);
  readonly danger = input(false);

  readonly confirmed = output<void>();
  readonly cancelled = output<void>();

  ngAfterViewInit(): void {
    this.dialog().nativeElement.showModal();
  }

  protected cancel(event: Event): void {
    event.preventDefault();

    if (!this.busy()) {
      this.cancelled.emit();
    }
  }
}
