import { Component, input, output } from '@angular/core';

import type { PaginationMeta } from '../../models/pagination.model';

@Component({
  selector: 'app-pagination',
  templateUrl: './pagination.component.html',
  styleUrl: './pagination.component.scss'
})
export class PaginationComponent {
  readonly meta = input.required<PaginationMeta>();
  readonly itemLabel = input('items');
  readonly pageChange = output<number>();

  protected changePage(page: number): void {
    const { current_page: currentPage, last_page: lastPage } = this.meta();

    if (page >= 1 && page <= lastPage && page !== currentPage) {
      this.pageChange.emit(page);
    }
  }
}
