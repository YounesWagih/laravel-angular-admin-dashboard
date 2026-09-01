export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  links: PaginationLink[];
  path: string;
  per_page: number;
  to: number | null;
  total: number;
}

export const EMPTY_PAGINATION_META: PaginationMeta = {
  current_page: 1,
  from: null,
  last_page: 1,
  links: [],
  path: '',
  per_page: 0,
  to: null,
  total: 0
};

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: PaginationMeta;
}
