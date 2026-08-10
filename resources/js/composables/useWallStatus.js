import { computed, toValue } from 'vue';

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
    const value = String(rawStatus || '')
      .trim()
      .toLowerCase();

    if (value.includes('aguardando pagamento')) return 'Aguardando pagamento';
    if (value.includes('aguardando arte')) return 'Aguardando Arte';
    if (value.includes('arte recebida')) return 'Arte Recebida';
    if (value.includes('layout aprovado')) return 'Layout Aprovado';
    if (
      value.includes('em produção') ||
      value.includes('em producao') ||
      value.includes('liberado para produção') ||
      value.includes('liberado para producao')
    ) {
      return 'Em Produção';
    }
    if (value.includes('enviado')) return 'Enviado';
    if (value.includes('entregue')) return 'Entregue';

    return null;
  }

  function hasPendingArtPayment() {
    const remainingArt = Number(data.value?.payment_breakdown?.remaining?.ARTES ?? 0);
    return remainingArt > 0;
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
    if (orderStatus === 'Enviado' || orderStatus === 'Entregue') {
      return orderStatus;
    }

    if (hasPendingArtPayment()) {
      return 'Aguardando pagamento';
    }

    const card = resolveCardByWall(wall, roomIndex, wallIndex);

    return normalizeStatus(card?.status) ?? 'Aguardando Arte';
  }

  function getWallStatusClass(wall, roomIndex, wallIndex) {
    const status = getWallStatusLabel(wall, roomIndex, wallIndex);

    if (status === 'Aguardando pagamento') return 'text-bg-warning';
    if (status === 'Arte Recebida') return 'text-bg-info';
    if (status === 'Layout Aprovado') return 'text-bg-primary';
    if (status === 'Em Produção') return 'text-bg-dark';
    if (status === 'Enviado' || status === 'Entregue') return 'text-bg-success';

    return 'text-bg-secondary';
  }

  return {
    getWallStatusLabel,
    getWallStatusClass,
  };
}
