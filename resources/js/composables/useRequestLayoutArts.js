import { ref, watch } from 'vue';
import { useArtService } from '@/modules/card-modals/services/artService';
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

      const orderId = card.value.order_id
        || card.value.order?.id
        || (card.value.order && typeof card.value.order === 'object' ? card.value.order.id : null);

      const budgetId = card.value.budget_id
        || card.value.budget?.id
        || (card.value.budget && typeof card.value.budget === 'object' ? card.value.budget.id : null);

      const response = await artService.fetchRequestLayoutArts(null, orderId, budgetId);

      let artsData = [];
      if (Array.isArray(response)) {
        artsData = response;
      } else if (response?.success && Array.isArray(response.data)) {
        artsData = response.data;
      } else if (response?.data && Array.isArray(response.data)) {
        artsData = response.data;
      }

      if (artsData.length > 0) {
        requestLayoutArts.value = artsData.map((art) => {
          let imageUrl = art.image_url;
          if (!imageUrl && art.path_file) {
            imageUrl = resolveImageUrl(art.path_file);
          }
          
          return {
            id: art.id,
            comment: art.comment || null,
            approval_status: art.approval_status ?? 'pending',
            image_url: imageUrl,
            created_at: art.created_at || null,
            wall_info: art.wall_info || null,
            arts: [{
              id: art.id,
              comment: art.comment || null,
              approval_status: art.approval_status ?? 'pending',
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

