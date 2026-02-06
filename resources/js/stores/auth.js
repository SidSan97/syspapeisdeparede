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

    clearUser() {
      this.user = null;
      this.roles = [];
      this.permissions = [];
      this.directPermissions = [];
      this.ready = false;
    },

    async init() {
      if (window.LaravelApp?.user) {
        this.setUser({
          user: window.LaravelApp.user,
          roles: window.LaravelApp.roles || [],
          permissions: window.LaravelApp.permissions || [],
          direct_permissions: window.LaravelApp.direct_permissions || [],
        });
        this.ready = true;
      }
    },

    isAdmin() {
      return this.hasRole(['admin', 'super admin']);
    },

    hasPermission(name) {
      if (!name) return false;

      // Admin tem todas as permissões
      if (this.isAdmin()) return true;

      if (Array.isArray(name)) {
        return name.some((n) => this.permissions.includes(n));
      }

      return this.permissions.includes(name);
    },

    hasAllPermissions(names) {
      if (!Array.isArray(names)) return this.hasPermission(names);

      // Admin tem todas as permissões
      if (this.isAdmin()) return true;

      return names.every((n) => this.permissions.includes(n));
    },

    hasRole(name) {
      if (!name) return false;
      if (Array.isArray(name)) return name.some((n) => this.roles.includes(n));
      return this.roles.includes(name);
    },
  },
});
