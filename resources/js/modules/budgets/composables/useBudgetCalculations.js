import { computed } from 'vue';
import { createDefaultWall } from './useBudgetUtils';
import {
  calculatePartsTotalArea,
  calculateWallsSequence,
  getWallArea as getWallAreaUtils,
  calculateStrips as calculateStripsUtils,
  calculateStripHeight as calculateStripHeightUtils,
} from '@/utils/calculateStripsUtils.js';

/**
 * Composable para cálculos relacionados a orçamentos
 */

const STRIP_WIDTH = 0.6;
const STRIP_HEIGHT_OPTIONS = [
  1.0, 1.2, 1.5, 1.7, 2.0, 2.2, 2.5, 2.7, 3.0, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 4.0, 4.1,
  4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5.0, 5.1, 5.2, 5.3, 5.4, 5.5, 6.0, 6.1, 6.2, 6.3, 6.4,
  6.5, 6.6, 6.7, 6.8, 6.9, 7.0, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 8.0,
];

/**
 * Obtém as continuações de uma parede
 */
export function getWallContinuations(wall) {
  if (!wall.continueSameArt) {
    return [];
  }
  return Array.isArray(wall.continuations) ? wall.continuations : [];
}

/**
 * Calcula o cálculo de strips para uma parede
 */
function getStripCalculation(wall) {
  const baseWidth = Number(wall.width) || 0;
  const continuationWidth = getWallContinuations(wall).reduce((sum, continuation) => {
    const width = Number(continuation.width) || 0;
    return sum + width;
  }, 0);
  const width = baseWidth + continuationWidth;

  const heights = [];
  const baseHeight = Number(wall.height) || 0;
  if (baseHeight) {
    heights.push(baseHeight);
  }

  getWallContinuations(wall).forEach((continuation) => {
    const continuationHeight = Number(continuation.height) || 0;
    if (continuationHeight) {
      heights.push(continuationHeight);
    }
  });

  const height = heights.length ? Math.max(...heights) : 0;

  if (!width || !height) {
    return {
      numberOfStrips: 0,
      stripHeight: null,
    };
  }

  let numberOfStrips = Math.ceil(width / STRIP_WIDTH);
  const stripHeight = STRIP_HEIGHT_OPTIONS.find((alt) => alt >= height + 0.09);

  if (stripHeight && stripHeight >= 6 && numberOfStrips % 2 !== 0) {
    numberOfStrips += 1;
  }

  return {
    numberOfStrips,
    stripHeight: stripHeight || null,
  };
}

/**
 * Calcula o número de strips de uma parede
 */
export function calculateStrips(wall) {
  return calculateStripsUtils(wall);
}

/**
 * Calcula a altura do strip de uma parede
 */
export function calculateStripHeight(wall) {
  return calculateStripHeightUtils(wall);
}

/**
 * Calcula a área de uma parede
 */
export function getWallArea(wall) {
  return getWallAreaUtils(wall);
}

/**
 * Calcula o tempo de entrega do orçamento
 */
export function calculateDeliveryTime(budget, getModelById) {
  // Encontrar o maior tempo de desenvolvimento de arte entre todas as paredes
  let maxDevelopmentTime = 0;

  budget.rooms.forEach((room) => {
    room.walls.forEach((wall) => {
      if (wall.model) {
        const model = getModelById(wall.model);
        if (model) {
          const days = Math.max(0, Number(model.deadline ?? 0));
          if (days > maxDevelopmentTime) {
            maxDevelopmentTime = days;
          }
        }
      }
    });
  });

  // Tempo de produção base (5 dias) + maior tempo de desenvolvimento + tempo de frete
  const productionTime = 5;
  const freightTime = budget.carriers[budget.selectedCarrier]?.deliveryTime || 0;

  return productionTime + maxDevelopmentTime + freightTime;
}

/**
 * Composable para cálculos de orçamento
 */
export function useBudgetCalculations(budget, getModelById, precoVista, precoPrazo, hasChanges) {
  const totalWalls = computed(() => {
    return budget.rooms.reduce((total, room) => total + room.walls.length, 0);
  });

  const wallMetricsMap = computed(() => {
    const map = new WeakMap();

    budget.rooms.forEach((room) => {
      const seq = calculateWallsSequence(room.walls);
      room.walls.forEach((wall, idx) => {
        const m = seq.perWall?.[idx] ?? { meters: 0, strips: 0, stripHeight: null };
        map.set(wall, {
          meters: m.meters ?? 0,
          strips: m.strips ?? 0,
          stripHeight: m.stripHeight ?? null,
        });
      });
    });

    return map;
  });

  const getWallAreaSeq = (wall) => {
    const m = wallMetricsMap.value.get(wall);
    return m?.meters ?? 0;
  };

  const calculateStripsSeq = (wall) => {
    const m = wallMetricsMap.value.get(wall);
    return m?.strips ?? 0;
  };

  const calculateStripHeightSeq = (wall) => {
    const m = wallMetricsMap.value.get(wall);
    return m?.stripHeight ?? calculateStripHeightUtils(wall);
  };

  const totalArea = computed(() => calculatePartsTotalArea(budget.rooms));

  const totalModelsCost = computed(() => {
    let total = 0;
    budget.rooms.forEach((room) => {
      room.walls.forEach((wall) => {
        if (wall.model) {
          const model = getModelById(wall.model);
          if (model) {
            total += model.value;
          }
        }
      });
    });
    return total;
  });

  const freightCost = computed(() => {
    if (budget.selectedCarrier !== null && budget.carriers[budget.selectedCarrier]) {
      return budget.carriers[budget.selectedCarrier].price;
    }
    return 0;
  });

  const calculatedTotalBudgetVista = computed(() => {
    return totalArea.value * precoVista.value + totalModelsCost.value + freightCost.value;
  });

  const calculatedTotalBudgetPrazo = computed(() => {
    return totalArea.value * precoPrazo.value + totalModelsCost.value + freightCost.value;
  });

  const totalBudgetVista = computed(() => {
    if (hasChanges.value) {
      return calculatedTotalBudgetVista.value;
    }
    return budget.total_amount || calculatedTotalBudgetVista.value;
  });

  const totalBudgetPrazo = computed(() => {
    if (hasChanges.value) {
      return calculatedTotalBudgetPrazo.value;
    }
    return budget.total_amount_installments || calculatedTotalBudgetPrazo.value;
  });

  const totalBudget = computed(() => {
    if (budget.paymentMethod === 'pix') {
      return totalBudgetVista.value;
    } else if (budget.paymentMethod === 'credit_card') {
      return totalBudgetPrazo.value;
    }
    return 0;
  });

  return {
    totalWalls,
    totalArea,
    totalModelsCost,
    freightCost,
    calculatedTotalBudgetVista,
    calculatedTotalBudgetPrazo,
    totalBudgetVista,
    totalBudgetPrazo,
    totalBudget,
    getWallArea: getWallAreaSeq,
    calculateStrips: calculateStripsSeq,
    calculateStripHeight: calculateStripHeightSeq,
    calculateDeliveryTime: () => calculateDeliveryTime(budget, getModelById),
  };
}
