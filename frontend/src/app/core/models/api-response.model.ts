export interface ApiResponse<T> {
  data: T;
}

export interface ApiErrorResponse {
  message?: string;
  errors?: Record<string, string[]>;
}
