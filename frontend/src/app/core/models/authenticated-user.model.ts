export type UserType = 'admin' | 'user';
export type UserStatus = 'active' | 'inactive';

export interface AuthenticatedUserRole {
  id: number;
  name: string;
}

export interface AuthenticatedUser {
  id: number;
  name: string;
  email: string;
  type: UserType;
  status: UserStatus;
  is_admin: boolean;
  role: AuthenticatedUserRole | null;
  permissions: string[];
}

export interface LoginCredentials {
  email: string;
  password: string;
  remember: boolean;
}

export interface RegisterCredentials {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}
