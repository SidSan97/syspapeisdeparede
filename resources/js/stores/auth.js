import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAuthStore = defineStore('auth', () => {
  // state
  const user = ref(null);

  /** @type {import('vue').Ref<string[]>} */
  const roles = ref([]);
  const permissions = ref([]);
  const directPermissions = ref([]);
  const ready = ref(false);

  // getters
  const isAdmin = () => hasRole(['admin', 'super admin']);

  const can = computed(() => (permission) => {
    return hasPermission(permission);
  });

  // actions
  const setUser = (payload) => {
    user.value = payload?.user || null;
    roles.value = payload?.roles || [];
    permissions.value = payload?.permissions || [];
    directPermissions.value = payload?.direct_permissions || [];
    ready.value = !!payload;
  };

  const clearUser = () => {
    user.value = null;
    roles.value = [];
    permissions.value = [];
    directPermissions.value = [];
    ready.value = false;
  };

  const init = async () => {
    if (window.LaravelApp?.user) {
      setUser({
        user: window.LaravelApp.user,
        roles: window.LaravelApp.roles || [],
        permissions: window.LaravelApp.permissions || [],
        direct_permissions: window.LaravelApp.direct_permissions || [],
      });
      ready.value = true;
    }
  };

  const hasPermission = (name) => {
    if (!name) return false;

    // Admin tem todas as permissões
    if (isAdmin()) return true;

    if (Array.isArray(name)) {
      return name.some((n) => permissions.value.includes(n));
    }

    return permissions.value.includes(name);
  };

  const hasAllPermissions = (names) => {
    if (!Array.isArray(names)) return hasPermission(names);

    // Admin tem todas as permissões
    if (isAdmin()) return true;

    return names.every((n) => permissions.value.includes(n));
  };

  const hasRole = (name) => {
    if (!name) return false;

    if (Array.isArray(name)) {
      return name.some((n) => roles.value.includes(n));
    }

    return roles.value.includes(name);
  };

  /**
   * @param {string | string[]} requiredRoles
   * @returns {boolean}
   */
  const hasAnyRole = (requiredRoles) => {
    if (!requiredRoles) return false;

    const rolesToCheck = Array.isArray(requiredRoles) ? requiredRoles : [requiredRoles];

    return rolesToCheck.some((role) => roles.value.includes(role));
  };

  return {
    // state
    user,
    roles,
    permissions,
    directPermissions,
    ready,

    // getters / helpers
    can,
    isAdmin,
    hasRole,
    hasAnyRole,
    hasPermission,
    hasAllPermissions,

    // actions
    setUser,
    clearUser,
    init,
  };
});
