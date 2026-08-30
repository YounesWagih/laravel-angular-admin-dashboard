export interface Role {
  id: number;
  name: string;
  description: string | null;
  is_default: boolean;
  users_count: number;
  permissions: string[];
  created_at: string;
  updated_at: string;
}

export interface PermissionDefinition {
  name: string;
  action: string;
}

export interface PermissionEntity {
  name: string;
  permissions: PermissionDefinition[];
}

export interface RoleIndexResponse {
  data: Role[];
  meta: {
    permission_entities: PermissionEntity[];
  };
}

export interface RolePayload {
  name: string;
  description: string | null;
}
