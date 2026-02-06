import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAuthStore = defineStore('auth', () => {
  // state
  const user = ref(null);
  const ready = ref(false);

  // getters
  const isAdmin = () => hasRole(['admin', 'super admin']);

  const can = computed(() => (permission) => {
    return hasPermission(permission);
  });

  const roles = computed(() => {
    return user.value?.roles || [];
  });

  const permissions = computed(() => {
    return user.value?.permissions || [];
  });

  // actions
  const setUser = (payload) => {
    user.value = payload?.user || null;
    ready.value = !!payload;
  };

  const clearUser = () => {
    user.value = null;
    ready.value = false;
  };

  const init = async () => {
    if (window.LaravelApp?.user) {
      setUser({
        user: window.LaravelApp.user,
      });
      ready.value = true;
    }
  };

  const hasPermission = (name) => {
    if (!name) return false;

    // Admin tem todas as permissões
    if (isAdmin()) return true;

    if (Array.isArray(name)) {
      return name.some((n) => user.value.permissions.includes(n));
    }

    return user.value.permissions.includes(name);
  };

  const hasAllPermissions = (names) => {
    if (!Array.isArray(names)) return hasPermission(names);

    // Admin tem todas as permissões
    if (isAdmin()) return true;

    return names.every((n) => user.value.permissions.includes(n));
  };

  const hasRole = (name) => {
    if (!name) return false;

    if (Array.isArray(name)) {
      return name.some((n) => user.value.roles.includes(n));
    }

    return user.value.roles.includes(name);
  };

  /**
   * @param {string | string[]} requiredRoles
   * @returns {boolean}
   */
  const hasAnyRole = (requiredRoles) => {
    if (!requiredRoles) return false;

    const rolesToCheck = Array.isArray(requiredRoles) ? requiredRoles : [requiredRoles];

    return rolesToCheck.some((role) => user.value.roles.includes(role));
  };

  return {
    // state
    user,
    roles,
    permissions,
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
