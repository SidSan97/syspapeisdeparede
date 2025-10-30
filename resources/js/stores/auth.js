import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    roles: [],
    permissions: [],
    directPermissions: [],
    ready: false,
  }),
  actions: {

    setUser(payload) {
      this.user = payload?.user || null;
      this.roles = payload?.roles || [];
      this.permissions = payload?.permissions || [];
      this.directPermissions = payload?.direct_permissions || [];
      this.ready = !!payload;
    },

    hasPermission(name) {
      if (!name) return false;

      if (this.roles.includes('super admin')) return true;

      if (Array.isArray(name)) {
        return name.some(n => this.permissions.includes(n));
      }

      return this.permissions.includes(name);
    },

    hasAllPermissions(names) {
      if (!Array.isArray(names)) return this.hasPermission(names);

      if (this.roles.includes('super admin')) return true;

      return names.every(n => this.permissions.includes(n));
    },

    hasRole(name) {
      if (!name) return false;
      if (Array.isArray(name)) return name.some(n => this.roles.includes(n));
      return this.roles.includes(name);
    }
  }
});
