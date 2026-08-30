import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';

import { FormFieldErrorComponent } from '../../../../shared/components/form-field-error/form-field-error.component';
import { ConfirmationDialogComponent } from '../../../../shared/components/confirmation-dialog/confirmation-dialog.component';
import { getApiErrorMessage } from '../../../../shared/utils/api-error.util';
import { applyServerValidationErrors } from '../../../../shared/utils/form-error.util';
import type { PermissionEntity, Role, RolePayload } from '../../models/role.model';
import { RoleService } from '../../services/role.service';

@Component({
  selector: 'app-roles-page',
  imports: [ConfirmationDialogComponent, FormFieldErrorComponent, ReactiveFormsModule],
  templateUrl: './roles-page.component.html',
  styleUrl: './roles-page.component.scss'
})
export class RolesPageComponent implements OnInit {
  formBuilder = inject(FormBuilder);
  roleService = inject(RoleService);

  roles = signal<Role[]>([]);
  permissionEntities = signal<PermissionEntity[]>([]);
  loading = signal(true);
  pageError = signal<string | null>(null);
  expandedRoleId = signal<number | null>(null);
  formOpen = signal(false);
  editingRoleId = signal<number | null>(null);
  savingRole = signal(false);
  formError = signal<string | null>(null);
  deletingRoleId = signal<number | null>(null);
  confirmingDeleteRoleId = signal<number | null>(null);
  settingDefaultRoleId = signal<number | null>(null);
  confirmingDefaultRoleId = signal<number | null>(null);
  syncingRoleIds = signal<ReadonlySet<number>>(new Set());
  roleErrors = signal<Record<number, string>>({});
  entityPickerRoleId = signal<number | null>(null);
  availableEntities = signal<PermissionEntity[]>([]);
  loadingAvailableEntities = signal(false);
  addedEntityNames = signal<Record<number, string[]>>({});
  roleForm = this.formBuilder.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(255)]],
    description: ['', Validators.maxLength(1000)]
  });

  ngOnInit(): void {
    void this.loadRoles();
  }

  protected async loadRoles(): Promise<void> {
    this.loading.set(true);
    this.pageError.set(null);

    try {
      const response = await this.roleService.index();
      this.roles.set(response.data);
      this.permissionEntities.set(response.meta.permission_entities);
    } catch (error) {
      this.pageError.set(getApiErrorMessage(error, 'Roles could not be loaded. Please try again.'));
    } finally {
      this.loading.set(false);
    }
  }

  protected toggleExpanded(roleId: number): void {
    this.expandedRoleId.update((expandedId) => (expandedId === roleId ? null : roleId));
    this.closeEntityPicker();
  }

  protected openCreateForm(): void {
    this.editingRoleId.set(null);
    this.formError.set(null);
    this.roleForm.reset({ name: '', description: '' });
    this.formOpen.set(true);
  }

  protected openEditForm(role: Role): void {
    this.editingRoleId.set(role.id);
    this.formError.set(null);
    this.roleForm.reset({
      name: role.name,
      description: role.description ?? ''
    });
    this.formOpen.set(true);
  }

  protected closeRoleForm(): void {
    this.formOpen.set(false);
    this.formError.set(null);
    this.editingRoleId.set(null);
  }

  protected async saveRole(): Promise<void> {
    if (this.roleForm.invalid) {
      this.roleForm.markAllAsTouched();
      return;
    }

    this.savingRole.set(true);
    this.formError.set(null);

    const values = this.roleForm.getRawValue();
    const payload: RolePayload = {
      name: values.name.trim(),
      description: values.description.trim() || null
    };

    try {
      const roleId = this.editingRoleId();
      const savedRole = roleId
        ? await this.roleService.update(roleId, payload)
        : await this.roleService.create(payload);

      this.replaceRole(savedRole);
      this.closeRoleForm();

      if (!roleId) {
        this.expandedRoleId.set(savedRole.id);
      }
    } catch (error) {
      this.formError.set(
        applyServerValidationErrors(this.roleForm, error) ??
          getApiErrorMessage(error, 'The role could not be saved. Please try again.')
      );
    } finally {
      this.savingRole.set(false);
    }
  }

  protected requestDelete(role: Role): void {
    if (role.is_default || role.users_count > 0) {
      return;
    }

    this.confirmingDefaultRoleId.set(null);
    this.confirmingDeleteRoleId.set(role.id);
  }

  protected cancelDelete(): void {
    this.confirmingDeleteRoleId.set(null);
  }

  protected requestMakeDefault(role: Role): void {
    if (role.is_default) {
      return;
    }

    this.confirmingDeleteRoleId.set(null);
    this.confirmingDefaultRoleId.set(role.id);
  }

  protected cancelMakeDefault(): void {
    this.confirmingDefaultRoleId.set(null);
  }

  protected async makeDefault(role: Role): Promise<void> {
    this.settingDefaultRoleId.set(role.id);
    this.setRoleError(role.id, null);

    try {
      const defaultRole = await this.roleService.makeDefault(role.id);

      this.roles.update((roles) =>
        roles
          .map((item) =>
            item.id === defaultRole.id ? defaultRole : { ...item, is_default: false }
          )
          .sort(
            (left, right) =>
              Number(right.is_default) - Number(left.is_default) ||
              left.name.localeCompare(right.name)
          )
      );
      this.confirmingDefaultRoleId.set(null);
    } catch (error) {
      this.confirmingDefaultRoleId.set(null);
      this.setRoleError(
        role.id,
        getApiErrorMessage(error, 'The default role could not be changed. Please try again.')
      );
    } finally {
      this.settingDefaultRoleId.set(null);
    }
  }

  protected async deleteRole(role: Role): Promise<void> {
    this.deletingRoleId.set(role.id);
    this.setRoleError(role.id, null);

    try {
      await this.roleService.delete(role.id);
      this.roles.update((roles) => roles.filter((item) => item.id !== role.id));
      this.confirmingDeleteRoleId.set(null);

      if (this.expandedRoleId() === role.id) {
        this.expandedRoleId.set(null);
      }
    } catch (error) {
      this.confirmingDeleteRoleId.set(null);
      this.setRoleError(
        role.id,
        getApiErrorMessage(error, 'The role could not be deleted. Please try again.')
      );
    } finally {
      this.deletingRoleId.set(null);
    }
  }

  protected async togglePermission(role: Role, permission: string, event: Event): Promise<void> {
    const checked = (event.target as HTMLInputElement).checked;
    const previousPermissions = [...role.permissions];
    const permissions = checked
      ? [...new Set([...previousPermissions, permission])]
      : previousPermissions.filter((name) => name !== permission);

    this.updateRolePermissions(role.id, permissions);
    this.setRoleSyncing(role.id, true);
    this.setRoleError(role.id, null);

    try {
      const updatedRole = await this.roleService.syncPermissions(role.id, permissions);
      this.replaceRole(updatedRole);
      this.removeAddedEntity(role.id, this.permissionEntity(permission));
    } catch (error) {
      this.updateRolePermissions(role.id, previousPermissions);
      this.setRoleError(
        role.id,
        getApiErrorMessage(error, 'Permissions could not be updated. The change was reverted.')
      );
    } finally {
      this.setRoleSyncing(role.id, false);
    }
  }

  protected isRoleSyncing(roleId: number): boolean {
    return this.syncingRoleIds().has(roleId);
  }

  protected displayedEntities(role: Role): PermissionEntity[] {
    const addedEntities = this.addedEntityNames()[role.id] ?? [];

    return this.permissionEntities().filter(
      (entity) =>
        addedEntities.includes(entity.name) ||
        entity.permissions.some((permission) => role.permissions.includes(permission.name))
    );
  }

  protected async toggleEntityPicker(role: Role): Promise<void> {
    if (this.entityPickerRoleId() === role.id) {
      this.closeEntityPicker();
      return;
    }

    this.entityPickerRoleId.set(role.id);
    this.availableEntities.set([]);
    this.loadingAvailableEntities.set(true);
    this.setRoleError(role.id, null);

    try {
      const entities = await this.roleService.availableEntities(role.id);
      const alreadyAdded = this.addedEntityNames()[role.id] ?? [];

      if (this.entityPickerRoleId() === role.id) {
        this.availableEntities.set(
          entities.filter((entity) => !alreadyAdded.includes(entity.name))
        );
      }
    } catch (error) {
      if (this.entityPickerRoleId() === role.id) {
        this.closeEntityPicker();
        this.setRoleError(
          role.id,
          getApiErrorMessage(error, 'Available permission entities could not be loaded.')
        );
      }
    } finally {
      if (this.entityPickerRoleId() === role.id) {
        this.loadingAvailableEntities.set(false);
      }
    }
  }

  protected addEntity(roleId: number, entityName: string): void {
    this.addedEntityNames.update((entitiesByRole) => ({
      ...entitiesByRole,
      [roleId]: [...new Set([...(entitiesByRole[roleId] ?? []), entityName])]
    }));
    this.availableEntities.update((entities) =>
      entities.filter((entity) => entity.name !== entityName)
    );
  }

  protected closeEntityPicker(): void {
    this.entityPickerRoleId.set(null);
    this.availableEntities.set([]);
    this.loadingAvailableEntities.set(false);
  }

  protected formatLabel(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
  }

  protected deleteDisabledReason(role: Role): string {
    if (role.is_default) {
      return 'The default role cannot be deleted.';
    }

    return role.users_count > 0 ? 'Roles assigned to users cannot be deleted.' : '';
  }

  protected roleById(roleId: number): Role | undefined {
    return this.roles().find((role) => role.id === roleId);
  }

  private replaceRole(savedRole: Role): void {
    this.roles.update((roles) => {
      const exists = roles.some((role) => role.id === savedRole.id);
      const updatedRoles = exists
        ? roles.map((role) => (role.id === savedRole.id ? savedRole : role))
        : [...roles, savedRole];

      return updatedRoles.sort(
        (left, right) =>
          Number(right.is_default) - Number(left.is_default) || left.name.localeCompare(right.name)
      );
    });
  }

  private updateRolePermissions(roleId: number, permissions: string[]): void {
    this.roles.update((roles) =>
      roles.map((role) => (role.id === roleId ? { ...role, permissions } : role))
    );
  }

  private setRoleSyncing(roleId: number, syncing: boolean): void {
    this.syncingRoleIds.update((roleIds) => {
      const updatedRoleIds = new Set(roleIds);

      if (syncing) {
        updatedRoleIds.add(roleId);
      } else {
        updatedRoleIds.delete(roleId);
      }

      return updatedRoleIds;
    });
  }

  private setRoleError(roleId: number, message: string | null): void {
    this.roleErrors.update((errors) => {
      const updatedErrors = { ...errors };

      if (message) {
        updatedErrors[roleId] = message;
      } else {
        delete updatedErrors[roleId];
      }

      return updatedErrors;
    });
  }

  private removeAddedEntity(roleId: number, entityName: string): void {
    this.addedEntityNames.update((entitiesByRole) => ({
      ...entitiesByRole,
      [roleId]: (entitiesByRole[roleId] ?? []).filter((name) => name !== entityName)
    }));
  }

  private permissionEntity(permission: string): string {
    return permission.split('.', 1)[0];
  }
}
