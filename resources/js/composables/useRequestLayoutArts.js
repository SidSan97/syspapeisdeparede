import { ref, watch } from 'vue';
import { useArtService } from '@/modules/cardModals/services/artService';
import { useAuthStore } from '@/stores/auth';

/**
 * Composable para gerenciar solicitações de artes de layout
 */
export function useRequestLayoutArts(card) {
  const auth = useAuthStore();
  const artService = useArtService();

  const requestLayoutArts = ref([]);
  const loadingRequestArts = ref(false);

  function resolveImageUrl(path) {
    if (!path) {
      return '';
    }
    if (/^https?:\/\//i.test(path)) {
      return path;
    }
    const baseUrl = window.location.origin.replace(/\/$/, '');
    return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
  }

  async function fetchRequestLayoutArts() {
    if (!card.value?.id || !auth.user?.id) {
      requestLayoutArts.value = [];
      loadingRequestArts.value = false;
      return;
    }

    try {
      loadingRequestArts.value = true;

      const orderBudgetId = card.value.id;

      const orderId = card.value.order_id
        || card.value.order?.id
        || (card.value.order && typeof card.value.order === 'object' ? card.value.order.id : null);

      const data = await artService.fetchRequestLayoutArts(orderBudgetId, orderId);

      if (data?.success && Array.isArray(data.data)) {
        requestLayoutArts.value = data.data.map((art) => {
          let imageUrl = art.image_url;
          if (!imageUrl && art.path_file) {
            imageUrl = resolveImageUrl(art.path_file);
          }
          
          return {
            id: art.id,
            comment: art.comment || null,
            image_url: imageUrl,
            created_at: art.created_at || null,
            wall_info: art.wall_info || null,
            arts: [{
              id: art.id,
              comment: art.comment || null,
              path_file: art.path_file || null,
              image_url: imageUrl,
              created_at: art.created_at || null,
              designer_name: art.designer?.name || art.designer_name || null,
              dealer_name: art.dealer?.name || art.dealer_name || null,
            }],
            arts_count: 1,
          };
        });
      } else {
        requestLayoutArts.value = [];
      }
    } catch (error) {
      console.error('Erro ao buscar solicitações de artes:', error);
      console.error('Erro completo:', error.response?.data || error.message);
      requestLayoutArts.value = [];
    } finally {
      loadingRequestArts.value = false;
    }
  }

  // Buscar quando o card mudar
  watch(() => card.value?.id, (newCardId) => {
    if (newCardId) {
      fetchRequestLayoutArts();
    }
  }, { immediate: true });

  return {
    requestLayoutArts,
    loadingRequestArts,
    fetchRequestLayoutArts,
  };
}

