import { useRoleTranslationsStore } from '@/stores/roleTranslations';

/**
 * Obtém a store de traduções de roles
 * @returns {Object} - Store do Pinia
 */
function getRoleTranslationsStore() {
  return useRoleTranslationsStore();
}

export function translateRole(roleName) {
  const store = getRoleTranslationsStore();
  return store.translateRole(roleName);
}

export function translateRoles(roles) {
  if (!roles || !Array.isArray(roles)) return [];
  
  return roles.map(role => {
    const roleName = typeof role === 'string' ? role : role.name;
    return translateRole(roleName);
  });
}

export function getRoleTranslations() {
  const store = getRoleTranslationsStore();
  return store.getTranslations;
}

export function updateRoleTranslationsCache(newTranslations) {
  const store = getRoleTranslationsStore();
  store.updateTranslations(newTranslations);
}

export function clearRoleTranslationsCache() {
  const store = getRoleTranslationsStore();
  store.clearCache();
}


