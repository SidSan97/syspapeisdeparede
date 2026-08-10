const STRIP_WIDTH = 0.6;
const EXTRA = 0.07;
const MAX_H = 12.0;

// Mesma lista base da calculadora (HTML). Depois estende 5.60..12.00 de 0.10 em 0.10.
const BASE_STRIP_HEIGHTS = [
  1.0, 1.2, 1.5, 1.7, 2.0, 2.2, 2.5, 2.7, 3.0, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 4.0, 4.1,
  4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5.0, 5.1, 5.2, 5.3, 5.4, 5.5,
];

function buildAlturas() {
  const out = new Set(BASE_STRIP_HEIGHTS);

  for (let x = 5.6; x <= MAX_H + 1e-9; x += 0.1) {
    // idem HTML: Math.round(x*100)/100 para manter 2 casas
    const v = Math.round(x * 100) / 100;
    out.add(v);
  }

  return Array.from(out).sort((a, b) => a - b);
}

const ALTURAS = buildAlturas();

function getWallContinuations(wall) {
  // suportar nome camelCase (front) e snake_case (API)
  const enabled = !!(wall?.continueSameArt || wall?.continue_same_art);
  if (!enabled) return [];

  return Array.isArray(wall?.continuations) ? wall.continuations : [];
}

function getAlturaFaixa(alturaParede) {
  const hParede = Number(alturaParede);
  if (!isFinite(hParede) || hParede <= 0) return null;

  const target = hParede + EXTRA;
  if (target > MAX_H + 1e-9) return null;

  // menor h que cobre: v >= (altura + EXTRA)
  const h = ALTURAS.find((v) => v + 1e-9 >= target);
  return h ?? null;
}

function getSegments(wall) {
  const segments = [];

  const baseL = Number(wall?.width);
  const baseA = Number(wall?.height);
  if (isFinite(baseL) && isFinite(baseA) && baseL > 0 && baseA > 0) {
    segments.push({ L: baseL, A: baseA });
  }

  const continuations = getWallContinuations(wall);
  continuations.forEach((cont) => {
    const L = Number(cont?.width);
    const A = Number(cont?.height);
    if (isFinite(L) && isFinite(A) && L > 0 && A > 0) {
      segments.push({ L, A });
    }
  });

  return segments;
}


function computeSequenceFromSegments(segments) {
  if (!Array.isArray(segments) || segments.length === 0) {
    return { perPart: [], totalFaixas: 0, totalMetros: 0, groups: [], safetyExceeded: false };
  }

  const H = segments.map((seg) => getAlturaFaixa(seg.A));

  let carry = 0.0;
  const perPartBase = [];

  for (let i = 0; i < segments.length; i++) {
    const Li = Number(segments[i].L) || 0;
    const Hi = H[i];
    const nextH = i < segments.length - 1 ? H[i + 1] : null;

    if (!Hi || !(Li > 0)) {
      perPartBase.push({ q: 0, h: Hi ?? null });
      continue;
    }

    const LiEfetiva = Li - carry;
    if (LiEfetiva <= 1e-12) {
      carry = -LiEfetiva;
      perPartBase.push({ q: 0, h: Hi });
      continue;
    }

    const qBruto = Math.ceil(LiEfetiva / STRIP_WIDTH);
    const cobertura = qBruto * STRIP_WIDTH;
    const novoCarry = cobertura - LiEfetiva;

    if (nextH && nextH > Hi + 1e-9 && qBruto > 0) {
      const q = qBruto - 1;
      const cobAgora = q * STRIP_WIDTH;
      const faltou = LiEfetiva - cobAgora;
      carry = -faltou;
      perPartBase.push({ q, h: Hi });
    } else {
      carry = novoCarry;
      perPartBase.push({ q: qBruto, h: Hi });
    }
  }

  const groups0 = [];
  for (let i = 0; i < perPartBase.length; i++) {
    const { q, h } = perPartBase[i];
    if (!h || q <= 0) continue;

    if (groups0.length && Math.abs(groups0[groups0.length - 1].h - h) < 1e-9) {
      groups0[groups0.length - 1].q += q;
      groups0[groups0.length - 1].indices.push(i);
    } else {
      groups0.push({ h, q, indices: [i] });
    }
  }

  const Bres = rebalanceParityGroups(groups0);
  const groupsFinal = mergeConsecutiveGroups(Bres.groups);

  const partStrips = new Array(segments.length).fill(0);
  const partMeters = new Array(segments.length).fill(0);
  const partStripHeight = perPartBase.map((p) => p.h ?? null);

  groupsFinal.forEach((g) => {
    const idxs = g.indices;
    const qBaseSum = idxs.reduce((s, idx) => s + (perPartBase[idx]?.q ?? 0), 0);
    if (!qBaseSum) return;

    const exacts = idxs.map((idx) => {
      const q0 = perPartBase[idx]?.q ?? 0;
      return { idx, exact: (q0 / qBaseSum) * g.q, base: 0, frac: 0 };
    });

    let allocatedSum = 0;
    exacts.forEach((e) => {
      e.base = Math.floor(e.exact + 1e-9);
      e.frac = e.exact - e.base;
      allocatedSum += e.base;
    });

    const remainder = g.q - allocatedSum;
    if (remainder > 0) {
      exacts.sort((a, b) => b.frac - a.frac);
      for (let r = 0; r < remainder; r++) {
        exacts[r].base += 1;
      }
    } else if (remainder < 0) {
      exacts.sort((a, b) => a.frac - b.frac);
      for (let r = 0; r < Math.abs(remainder); r++) {
        if (exacts[r].base > 0) exacts[r].base -= 1;
      }
    }

    exacts.forEach((e) => {
      partStrips[e.idx] = e.base;
      const h = partStripHeight[e.idx];
      partMeters[e.idx] = h ? e.base * h : 0;
    });
  });

  const totalFaixas = partStrips.reduce((s, q) => s + q, 0);
  const totalMetros = partMeters.reduce((s, m) => s + m, 0);

  const perPart = segments.map((_, i) => ({
    strips: partStrips[i],
    meters: partMeters[i],
    stripHeight: partStripHeight[i],
  }));

  const groups = groupsFinal.map((g) => ({ q: g.q, h: g.h }));

  return { perPart, totalFaixas, totalMetros, groups, safetyExceeded: Bres.safetyExceeded };
}

function computeTotals(wall) {
  const r = computeSequenceFromSegments(getSegments(wall));
  return {
    totalFaixas: r.totalFaixas,
    totalMetros: r.totalMetros,
    groups: r.groups,
    safetyExceeded: r.safetyExceeded,
  };
}

export function getWallArea(wall) {
  return computeTotals(wall).totalMetros;
}

export function calculateStrips(wall) {
  return computeTotals(wall).totalFaixas;
}

export function calculateStripHeight(wall) {
  const { totalFaixas, totalMetros } = computeTotals(wall);
  if (!totalFaixas || totalFaixas <= 0) return null;

  // Nosso UI/assinatura espera 1 valor de altura; devolvemos a média ponderada
  // para manter consistente: getWallArea = strips * stripHeight.
  return totalMetros / totalFaixas;
}

export function calculateWallWithContinuations(wall) {
  return computeSequenceFromSegments(getSegments(wall));
}

export function calculatePartsTotalArea(rooms = []) {
  if (!Array.isArray(rooms)) {
    return 0;
  }

  let total = 0;

  rooms.forEach((room) => {
    if (!Array.isArray(room?.walls)) {
      return;
    }

    total += calculateWallsSequence(room.walls).totalMetros;
  });

  return total;
}

export function calculatePartMetrics(part) {
  const width = Number(part?.width) || 0;
  const height = Number(part?.height) || 0;

  if (width <= 0 || height <= 0) {
    return null;
  }

  const isolated = { width, height };
  const strips = calculateStrips(isolated);
  const stripHeight = calculateStripHeight(isolated);
  const meters = getWallArea(isolated);

  if (!strips || !stripHeight) {
    return null;
  }

  return { strips, stripHeight, meters };
}

function collapseWallForSequence(wall) {
  // Representa cada parede por 1 segmento para o cálculo sequencial entre paredes:
  // - largura = base + somatório das continuations (se existirem)
  // - altura = maior altura (base e continuations)
  const baseL = Number(wall?.width) || 0;
  const baseA = Number(wall?.height) || 0;

  const continuations = getWallContinuations(wall);
  const continuationWidth = continuations.reduce(
    (sum, cont) => sum + (Number(cont?.width) || 0),
    0,
  );

  const heights = [];
  if (baseA) heights.push(baseA);
  continuations.forEach((cont) => {
    const h = Number(cont?.height) || 0;
    if (h) heights.push(h);
  });

  const L = baseL + continuationWidth;
  const A = heights.length ? Math.max(...heights) : 0;

  return { L, A };
}

function mergeConsecutiveGroups(groups) {
  const out = [];
  for (const g of groups) {
    if (!g || g.q <= 0) continue;
    if (out.length && Math.abs(out[out.length - 1].h - g.h) < 1e-9) {
      out[out.length - 1].q += g.q;
      out[out.length - 1].indices.push(...g.indices);
    } else {
      out.push({ h: g.h, q: g.q, indices: [...g.indices] });
    }
  }
  return out;
}

function mustBeEvenGroups(groups, k) {
  const last = groups.length - 1;
  const middleRule = k !== 0 && k !== last;
  const heightRule = groups[k].h > 6.0 + 1e-9;
  return middleRule || heightRule;
}

function rebalanceParityGroups(groups) {
  let changed = true;
  let safety = 0;

  function findNextGE(k) {
    const hk = groups[k].h;
    for (let j = k + 1; j < groups.length; j++) {
      if (groups[j].h + 1e-9 >= hk) return j;
    }
    return -1;
  }

  while (changed && safety < 800) {
    safety++;
    changed = false;
    groups = mergeConsecutiveGroups(groups);

    for (let k = 0; k < groups.length; k++) {
      if (groups[k].q <= 0) continue;

      if (mustBeEvenGroups(groups, k) && groups[k].q % 2 !== 0) {
        const j = findNextGE(k);
        const last = groups.length - 1;

        // 1) tenta promover para o primeiro grupo à direita com altura >= hk
        if (j !== -1) {
          groups[k].q -= 1;
          groups[j].q += 1;
          groups = mergeConsecutiveGroups(groups);
          changed = true;
          break;
        }

        // 2) se não existir altura >= hk à direita, puxa do próximo (menor)
        if (k < last && groups[k + 1].q > 0) {
          groups[k + 1].q -= 1;
          groups[k].q += 1;
          groups = mergeConsecutiveGroups(groups);
          changed = true;
          break;
        }

        // 3) fallback (raro): adiciona 1 faixa
        groups[k].q += 1;
        groups = mergeConsecutiveGroups(groups);
        changed = true;
        break;
      }
    }
  }

  return { groups, safetyExceeded: safety >= 800 };
}

/**
 * Calcula de forma sequencial entre paredes (carry/sobra e reequilíbrio de paridade),
 * para bater com a lógica do arquivo HTML `Calculadora Revenda.html`.
 *
 * Cada parede é colapsada (parede + continuações → 1 segmento) antes da sequência.
 * Retorna métricas por parede na ordem original do array.
 */
export function calculateWallsSequence(walls) {
  if (!Array.isArray(walls) || walls.length === 0) {
    return { perWall: [], totalFaixas: 0, totalMetros: 0, safetyExceeded: false };
  }

  const collapsed = walls.map((w) => collapseWallForSequence(w));
  const r = computeSequenceFromSegments(collapsed);

  return {
    perWall: r.perPart,
    totalFaixas: r.totalFaixas,
    totalMetros: r.totalMetros,
    safetyExceeded: r.safetyExceeded,
  };
}
