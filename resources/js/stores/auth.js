import { defineStore } from 'pinia';
import { USER_TYPES } from '@/constants/userTypes';

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

    async fetchUser() {
      try {
        const response = await window.axios.get('/api/user');
        if (response.data && response.data.user) {
            console.log(response.data);
          this.setUser({
            user: response.data.user,
            roles: response.data.roles || [],
            permissions: response.data.permissions || [],
            direct_permissions: response.data.direct_permissions || [],
          });
          return true;
        }
      } catch (error) {
        console.error('Erro ao buscar dados do usuário:', error);
        // Se falhar, tenta usar os dados do window.LaravelApp
        if (window.LaravelApp?.user) {
          this.setUser({
            user: window.LaravelApp.user,
            roles: window.LaravelApp.roles || [],
            permissions: window.LaravelApp.permissions || [],
            direct_permissions: window.LaravelApp.direct_permissions || [],
          });
        }
        return false;
      }
    },

    isAdmin() {
      return this.user?.user_type_id === USER_TYPES.ADMIN || this.roles.includes('super admin');
    },

    hasPermission(name) {
      if (!name) return false;

      // Verificar se é admin (super admin ou user_type_id === 1)
      if (this.isAdmin()) return true;

      if (Array.isArray(name)) {
        return name.some(n => this.permissions.includes(n));
      }

      return this.permissions.includes(name);
    },

    hasAllPermissions(names) {
      if (!Array.isArray(names)) return this.hasPermission(names);

      // Verificar se é admin (super admin ou user_type_id === 1)
      if (this.isAdmin()) return true;

      return names.every(n => this.permissions.includes(n));
    },

    hasRole(name) {
      if (!name) return false;
      if (Array.isArray(name)) return name.some(n => this.roles.includes(n));
      return this.roles.includes(name);
    }
  }
});
