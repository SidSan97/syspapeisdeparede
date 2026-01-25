import { defineStore } from 'pinia';
import { useProductionService } from '@/modules/production/services/productionService';

/**
 * Store para gerenciar relatórios de produção e ações relacionadas
 */
export const useProductionReportsStore = defineStore('productionReports', {
  state: () => ({
    // Relatórios de produção por card (key: cardId, value: array de relatórios)
    reports: {},
    // Estado de loading por card (key: cardId, value: boolean)
    loading: {},
    // Estado de "marcando como produzido" por card (key: cardId, value: boolean)
    markingAsProduced: {},
  }),

  getters: {
    /**
     * Obtém os relatórios de produção de um card específico
     * @param {number|string} cardId - ID do card
     * @returns {Array} Array de relatórios de produção
     */
    getReportsByCardId: (state) => (cardId) => {
      return state.reports[cardId] || [];
    },

    /**
     * Verifica se está carregando relatórios de um card específico
     * @param {number|string} cardId - ID do card
     * @returns {boolean}
     */
    isLoadingByCardId: (state) => (cardId) => {
      return state.loading[cardId] || false;
    },

    /**
     * Verifica se está marcando como produzido um card específico
     * @param {number|string} cardId - ID do card
     * @returns {boolean}
     */
    isMarkingAsProducedByCardId: (state) => (cardId) => {
      return state.markingAsProduced[cardId] || false;
    },
  },

  actions: {
    /**
     * Busca os relatórios de produção de um card
     * @param {number|string} cardId - ID do card
     */
    async fetchProductionReports(cardId) {
      if (!cardId) {
        this.setReports(cardId, []);
        this.setLoading(cardId, false);
        return;
      }

      try {
        this.setLoading(cardId, true);
        const productionService = useProductionService();
        const reports = await productionService.getProductionReports(cardId);
        this.setReports(cardId, reports);
      } catch (error) {
        console.error('Erro ao buscar relatórios de produção:', error);
        this.setReports(cardId, []);
      } finally {
        this.setLoading(cardId, false);
      }
    },

    /**
     * Marca um card como produzido
     * @param {number|string} cardId - ID do card
     * @returns {Promise<Object|null>} Dados atualizados do card ou null em caso de erro
     */
    async markAsProduced(cardId) {
      if (!cardId || this.markingAsProduced[cardId]) {
        return null;
      }

      this.setMarkingAsProduced(cardId, true);

      try {
        const productionService = useProductionService();
        const updated = await productionService.markAsProduced(cardId);
        return updated;
      } catch (error) {
        console.error('Erro ao marcar como produzido:', error);
        throw error;
      } finally {
        this.setMarkingAsProduced(cardId, false);
      }
    },

    /**
     * Define os relatórios de produção de um card
     * @param {number|string} cardId - ID do card
     * @param {Array} reports - Array de relatórios
     */
    setReports(cardId, reports) {
      if (cardId) {
        this.reports = { ...this.reports, [cardId]: reports };
      }
    },

    /**
     * Define o estado de loading de um card
     * @param {number|string} cardId - ID do card
     * @param {boolean} loading - Estado de loading
     */
    setLoading(cardId, loading) {
      if (cardId !== undefined && cardId !== null) {
        this.loading = { ...this.loading, [cardId]: loading };
      }
    },

    /**
     * Define o estado de "marcando como produzido" de um card
     * @param {number|string} cardId - ID do card
     * @param {boolean} marking - Estado de marking
     */
    setMarkingAsProduced(cardId, marking) {
      if (cardId !== undefined && cardId !== null) {
        this.markingAsProduced = { ...this.markingAsProduced, [cardId]: marking };
      }
    },

    /**
     * Limpa os dados de um card específico
     * @param {number|string} cardId - ID do card
     */
    clearCardData(cardId) {
      if (cardId) {
        const newReports = { ...this.reports };
        const newLoading = { ...this.loading };
        const newMarkingAsProduced = { ...this.markingAsProduced };
        
        delete newReports[cardId];
        delete newLoading[cardId];
        delete newMarkingAsProduced[cardId];
        
        this.reports = newReports;
        this.loading = newLoading;
        this.markingAsProduced = newMarkingAsProduced;
      }
    },
  },
});

