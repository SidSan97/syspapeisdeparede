import { computed, toValue } from 'vue';
import { ORDER_BUDGET_STATUS, canonicalizeOrderBudgetStatus } from '@/constants/orderBudgetStatuses';

/**
 * Resolve o status exibido em cada parede a partir dos cards (order_budgets) do pedido.
 *
 * @param {import('vue').MaybeRefOrGetter<object|null|undefined>} source Pedido ou orçamento.
 */
export function useWallStatus(source) {
  const data = computed(() => toValue(source));

  const orderBudgetCards = computed(() => {
    if (!Array.isArray(data.value?.order_budgets)) {
      return [];
    }

    return [...data.value.order_budgets].sort(
      (a, b) => Number(a?.order_index || 0) - Number(b?.order_index || 0),
    );
  });

  function normalizeStatus(rawStatus) {
    const canonical = canonicalizeOrderBudgetStatus(rawStatus);

    if (canonical === ORDER_BUDGET_STATUS.WAITING_ART) {
      return ORDER_BUDGET_STATUS.WAITING_ART;
    }
    if (canonical === ORDER_BUDGET_STATUS.WAITING_PAYMENT) {
      return ORDER_BUDGET_STATUS.WAITING_PAYMENT;
    }
    if (canonical === ORDER_BUDGET_STATUS.ART_RECEIVED) {
      return ORDER_BUDGET_STATUS.ART_RECEIVED;
    }
    if (canonical === ORDER_BUDGET_STATUS.LAYOUT_APPROVED) {
      return ORDER_BUDGET_STATUS.LAYOUT_APPROVED;
    }
    if (
      canonical === ORDER_BUDGET_STATUS.RELEASED_FOR_PRODUCTION ||
      canonical === ORDER_BUDGET_STATUS.IN_PRODUCTION
    ) {
      return ORDER_BUDGET_STATUS.IN_PRODUCTION;
    }
    if (canonical === ORDER_BUDGET_STATUS.SENT) {
      return ORDER_BUDGET_STATUS.SENT;
    }
    if (canonical === ORDER_BUDGET_STATUS.DELIVERED) {
      return ORDER_BUDGET_STATUS.DELIVERED;
    }

    return null;
  }

  function getWallGlobalIndex(roomIndex, wallIndex) {
    let index = 0;
    const rooms = data.value?.rooms || [];

    for (let i = 0; i < roomIndex; i += 1) {
      index += Array.isArray(rooms[i]?.walls) ? rooms[i].walls.length : 0;
    }

    return index + wallIndex;
  }

  function resolveCardByWall(wall, roomIndex, wallIndex) {
    const cards = orderBudgetCards.value;
    if (!cards.length) {
      return null;
    }

    // Amarração preferencial por budget_wall_id
    if (wall?.id != null) {
      const byWallId = cards.find((card) => Number(card?.budget_wall_id) === Number(wall.id));
      if (byWallId) {
        return byWallId;
      }
    }

    // Fallback pela ordem da tabela order_budgets
    return cards[getWallGlobalIndex(roomIndex, wallIndex)] || null;
  }

  function getWallStatusLabel(wall, roomIndex, wallIndex) {
    const orderStatus = normalizeStatus(data.value?.status);
    if (orderStatus === ORDER_BUDGET_STATUS.SENT || orderStatus === ORDER_BUDGET_STATUS.DELIVERED) {
      return orderStatus;
    }

    const card = resolveCardByWall(wall, roomIndex, wallIndex);

    return normalizeStatus(card?.status) ?? ORDER_BUDGET_STATUS.WAITING_ART;
  }

  function getWallStatusClass(wall, roomIndex, wallIndex) {
    const status = getWallStatusLabel(wall, roomIndex, wallIndex);

    if (status === ORDER_BUDGET_STATUS.WAITING_PAYMENT) return 'text-bg-warning';
    if (status === ORDER_BUDGET_STATUS.ART_RECEIVED) return 'text-bg-info';
    if (status === ORDER_BUDGET_STATUS.LAYOUT_APPROVED) return 'text-bg-primary';
    if (status === ORDER_BUDGET_STATUS.IN_PRODUCTION) return 'text-bg-dark';
    if (status === ORDER_BUDGET_STATUS.SENT || status === ORDER_BUDGET_STATUS.DELIVERED) {
      return 'text-bg-success';
    }

    return 'text-bg-secondary';
  }

  return {
    getWallStatusLabel,
    getWallStatusClass,
  };
}
