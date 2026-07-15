import { computed } from 'vue';
import { calculateWallWithContinuations } from '@/utils/calculateStripsUtils.js';

function formatNumberBR(value, decimals = 2) {
  const n = Number(value);
  if (!Number.isFinite(n)) {
    return '0,00';
  }
  return n.toLocaleString('pt-BR', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });
}

function formatStripGroups(groups) {
  if (!Array.isArray(groups) || !groups.length) {
    return '';
  }

  return groups
    .filter((g) => g && g.q > 0 && Number.isFinite(g.h) && g.h > 0)
    .map((g) => `${g.q}F de ${formatNumberBR(g.h, 2)}m`)
    .join(' + ');
}

function normalizeWallForCalculation(wall) {
  return {
    width: wall?.width,
    height: wall?.height,
    continueSameArt: wall?.continueSameArt ?? wall?.continue_same_art ?? false,
    continuations: Array.isArray(wall?.continuations) ? wall.continuations : [],
  };
}

function getWallContinuations(wall) {
  const enabled = !!(wall?.continueSameArt || wall?.continue_same_art);
  if (!enabled) {
    return [];
  }
  return Array.isArray(wall?.continuations) ? wall.continuations : [];
}

/**
 * Composable para lógica de geração de PDF de orçamentos
 * @param {import('vue').Ref} budget - Referência reativa do orçamento
 */
export function useBudgetPdfGenerate(budget) {
  function formatWallPdfLine(wall, wallIndex = 0) {
    const sequence = calculateWallWithContinuations(normalizeWallForCalculation(wall));
    const stripSummary = formatStripGroups(sequence.groups);

    const label = wall?.name?.trim() || `Parede ${wallIndex + 1}`;
    const parts = [label];

    if (wall?.width && wall?.height) {
      parts.push(`${formatNumberBR(wall.width)}m x ${formatNumberBR(wall.height)}m`);
    }

    let line = parts.join(' | ');
    if (stripSummary) {
      line += ` — ${stripSummary}`;
    }

    const continuationLines = getWallContinuations(wall).map((continuation, contIndex) => {
      const contParts = [];
      const contName = continuation?.name?.trim();
      contParts.push(contName || `Continuação ${contIndex + 1}`);

      if (continuation?.width && continuation?.height) {
        contParts.push(
          `${formatNumberBR(continuation.width)}m x ${formatNumberBR(continuation.height)}m`,
        );
      }

      return `+ ${contParts.join(' | ')}`;
    });

    return {
      main: line,
      continuations: continuationLines,
    };
  }

  function getWallModelName(wall) {
    return wall?.collection_model?.name || wall?.collection_model_name || '—';
  }

  function getWallModelCost(wall) {
    const raw =
      wall?.collection_model?.value ??
      wall?.collection_model_value ??
      wall?.model_value ??
      0;
    const value = Number(raw);
    return Number.isFinite(value) ? value : 0;
  }

  function getRoomMeters(room) {
    if (!Array.isArray(room?.walls)) {
      return 0;
    }

    return room.walls.reduce((total, wall) => {
      const sequence = calculateWallWithContinuations(normalizeWallForCalculation(wall));
      return total + Number(sequence.totalMetros ?? 0);
    }, 0);
  }

  function getRoomModelCost(room) {
    if (!Array.isArray(room?.walls)) {
      return 0;
    }

    return room.walls.reduce((total, wall) => total + getWallModelCost(wall), 0);
  }

  /**
   * Extrai o nome da transportadora (remove descrição do serviço)
   */
  function getCarrierName(carrierName) {
    if (!carrierName) return null;
    if (carrierName.includes(' - ')) {
      return carrierName.split(' - ')[0].trim();
    }
    return carrierName.trim();
  }

  const totalRooms = computed(() => budget.value?.rooms?.length ?? 0);

  const totalItems = computed(() => {
    if (!budget.value?.rooms) return 0;
    return budget.value.rooms.reduce((count, room) => {
      return count + (Array.isArray(room.walls) ? room.walls.length : 0);
    }, 0);
  });

  const totalMeters = computed(() => {
    if (!budget.value?.rooms) return 0;
    return budget.value.rooms.reduce((total, room) => total + getRoomMeters(room), 0);
  });

  const totalModelsCost = computed(() => {
    if (!budget.value?.rooms) return 0;
    return budget.value.rooms.reduce((total, room) => total + getRoomModelCost(room), 0);
  });

  const freightCost = computed(() => Number(budget.value?.selected_carrier_price ?? 0));

  const wallpaperTotalCash = computed(() => {
    const total = Number(budget.value?.total_amount ?? 0);
    return Math.max(0, total - freightCost.value - totalModelsCost.value);
  });

  const pricePerMeterCash = computed(() => {
    if (totalMeters.value <= 0) {
      return 0;
    }
    return wallpaperTotalCash.value / totalMeters.value;
  });

  const roomSummaries = computed(() => {
    if (!budget.value?.rooms) {
      return [];
    }

    return budget.value.rooms.map((room, roomIndex) => {
      const meters = getRoomMeters(room);
      const modelCost = getRoomModelCost(room);
      const wallpaperCost = meters * pricePerMeterCash.value;

      return {
        name: room?.name?.trim() || `Ambiente ${roomIndex + 1}`,
        meters,
        modelCost,
        wallpaperCost,
        price: wallpaperCost + modelCost,
        walls: (room.walls ?? []).map((wall, wallIndex) => ({
          modelName: getWallModelName(wall),
          details: formatWallPdfLine(wall, wallIndex),
        })),
      };
    });
  });

  /**
   * @deprecated mantido para compatibilidade
   */
  function formatWallDetails(wall) {
    return formatWallPdfLine(wall).main;
  }

  const totalProducts = computed(() => {
    if (budget.value?.total_amount !== undefined && budget.value?.total_amount !== null) {
      return parseFloat(budget.value.total_amount);
    }

    return wallpaperTotalCash.value + totalModelsCost.value;
  });

  const totalOrder = computed(() => totalProducts.value + freightCost.value);

  const dropshippingData = computed(() => budget.value?.dropshipping_data || null);

  return {
    formatWallPdfLine,
    formatWallDetails,
    getCarrierName,
    getRoomMeters,
    getRoomModelCost,
    totalRooms,
    totalItems,
    totalMeters,
    totalModelsCost,
    totalProducts,
    totalOrder,
    roomSummaries,
    pricePerMeterCash,
    dropshippingData,
  };
}
