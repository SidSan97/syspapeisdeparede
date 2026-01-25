import { defineStore } from 'pinia';

/**
 * Mapeamento padrão de roles (em inglês) para suas traduções em português
 */
const DEFAULT_ROLE_TRANSLATIONS = {
  'admin': 'Administrador',
  'super admin': 'Super Administrador',
  'reseller': 'Revendedor',
  'designer': 'Designer',
  'production': 'Produção',
  'commercial': 'Comercial',
  'expedition': 'Expedição',
  'representatives': 'Representantes',
  'architects': 'Arquitetos',
};

export const useRoleTranslationsStore = defineStore('roleTranslations', {
  state: () => ({
    translations: { ...DEFAULT_ROLE_TRANSLATIONS },
  }),

  getters: {
    /**
     * Obtém todas as traduções disponíveis
     */
    getTranslations: (state) => {
      return { ...state.translations };
    },

    /**
     * Traduz o nome de uma role para português
     */
    translateRole: (state) => {
      return (roleName) => {
        if (!roleName) return '—';
        
        // Normalizar o nome da role (lowercase, trim)
        const normalizedRole = roleName.toLowerCase().trim();
        
        // Retornar a tradução se existir, caso contrário retornar o nome original capitalizado
        return state.translations[normalizedRole] || capitalizeFirst(roleName);
      };
    },
  },

  actions: {
    /**
     * Atualiza as traduções de roles
     * @param {Object} newTranslations - Novo objeto com traduções
     */
    updateTranslations(newTranslations) {
      this.translations = { ...DEFAULT_ROLE_TRANSLATIONS, ...newTranslations };
    },

    /**
     * Limpa o cache e restaura as traduções padrão
     */
    clearCache() {
      this.translations = { ...DEFAULT_ROLE_TRANSLATIONS };
    },
  },
});

/**
 * Capitaliza a primeira letra de uma string
 * @param {string} str - String a ser capitalizada
 * @returns {string} - String capitalizada
 */
function capitalizeFirst(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

