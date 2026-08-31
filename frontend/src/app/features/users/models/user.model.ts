import type { UserStatus, UserType } from '../../../core/models/authenticated-user.model';

export interface UserRole {
  id: number;
  name: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  type: UserType;
  status: UserStatus;
  role: UserRole | null;
  created_at: string;
  updated_at: string;
}

export interface UserFilters {
  search?: string;
  type?: UserType;
  role_id?: number;
  status?: UserStatus;
  page?: number;
  per_page?: number;
}

export interface UpdateUserPayload {
  name: string;
  email: string;
  type: UserType;
  role_id: number;
}

export interface CreateUserPayload extends UpdateUserPayload {
  password: string;
  status: UserStatus;
}

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

export interface PaginatedUsersResponse {
  data: User[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: PaginationMeta;
}
