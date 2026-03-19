const STRIP_WIDTH = 0.6;
const EXTRA = 0.07;
const MAX_H = 12.0;

// Mesma lista base da calculadora (HTML). Depois estende 5.60..12.00 de 0.10 em 0.10.
const BASE_STRIP_HEIGHTS = [
    1.0, 1.2, 1.5, 1.7, 2.0, 2.2, 2.5, 2.7, 3.0, 3.2, 3.3, 3.4, 3.5, 3.6,
    3.7, 3.8, 3.9, 4.0, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5.0,
    5.1, 5.2, 5.3, 5.4, 5.5
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

function mergeConsecutive(groups) {
    const out = [];
    for (const g of groups) {
        if (!g || g.q <= 0) continue;
        if (out.length && Math.abs(out[out.length - 1].h - g.h) < 1e-9) {
            out[out.length - 1].q += g.q;
        } else {
            out.push({ h: g.h, q: g.q });
        }
    }
    return out;
}

function mustBeEven(groups, k) {
    const last = groups.length - 1;
    const middleRule = k !== 0 && k !== last;
    const heightRule = groups[k].h > 6.0 + 1e-9;
    return middleRule || heightRule;
}

function rebalanceParity(groups) {
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
        groups = mergeConsecutive(groups);

        for (let k = 0; k < groups.length; k++) {
            if (groups[k].q <= 0) continue;

            if (mustBeEven(groups, k) && groups[k].q % 2 !== 0) {
                const hk = groups[k].h;
                const last = groups.length - 1;

                // 1) promover para um grupo à direita com altura >= hk
                const j = findNextGE(k);
                if (j !== -1) {
                    groups[k].q -= 1;
                    groups[j].q += 1;
                    groups = mergeConsecutive(groups);
                    changed = true;
                    break;
                }

                // 2) se só houver alturas menores à direita: puxar do próximo (menor)
                if (k < last && groups[k + 1].q > 0) {
                    groups[k + 1].q -= 1;
                    groups[k].q += 1;
                    groups = mergeConsecutive(groups);
                    changed = true;
                    break;
                }

                // 3) fallback (raro): adiciona 1 faixa para garantir paridade
                groups[k].q += 1;
                groups = mergeConsecutive(groups);
                changed = true;
                break;
            }
        }
    }

    return { groups, safetyExceeded: safety >= 800 };
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

function computeTotals(wall) {
    const segments = getSegments(wall);
    if (!segments.length) {
        return { totalFaixas: 0, totalMetros: 0, groups: [] };
    }

    // Alturas de faixa (h) para cada segmento.
    const H = [];
    for (let i = 0; i < segments.length; i++) {
        const h = getAlturaFaixa(segments[i].A);
        if (h === null) {
            return { totalFaixas: 0, totalMetros: 0, groups: [] };
        }
        H.push(h);
    }

    let carry = 0.0;
    const perWall = [];

    for (let i = 0; i < segments.length; i++) {
        const Li = segments[i].L;
        const Hi = H[i];
        const nextH = i < segments.length - 1 ? H[i + 1] : null;

        const LiEfetiva = Li - carry;

        if (LiEfetiva <= 1e-12) {
            // garante progresso mesmo com carry excessivo
            carry = -LiEfetiva;
            perWall.push({ q: 0, h: Hi });
            continue;
        }

        const qBruto = Math.ceil(LiEfetiva / STRIP_WIDTH);
        const cobertura = qBruto * STRIP_WIDTH;
        const novoCarry = cobertura - LiEfetiva;

        // subida de altura: próximo segmento tem faixa maior
        if (i < segments.length - 1 && nextH > Hi + 1e-9 && qBruto > 0) {
            const q = qBruto - 1;
            const cobAgora = q * STRIP_WIDTH;
            const faltou = LiEfetiva - cobAgora; // > 0
            carry = -faltou;
            perWall.push({ q, h: Hi });
        } else {
            carry = novoCarry;
            perWall.push({ q: qBruto, h: Hi });
        }
    }

    let groups = mergeConsecutive(perWall);
    const Bres = rebalanceParity(groups);
    groups = Bres.groups;
    groups = mergeConsecutive(groups);

    const totalFaixas = groups.reduce((s, g) => s + g.q, 0);
    const totalMetros = groups.reduce((s, g) => s + g.q * g.h, 0);

    return { totalFaixas, totalMetros, groups, safetyExceeded: Bres.safetyExceeded };
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

function collapseWallForSequence(wall) {
    // Representa cada parede por 1 segmento para o cálculo sequencial entre paredes:
    // - largura = base + somatório das continuations (se existirem)
    // - altura = maior altura (base e continuations)
    const baseL = Number(wall?.width) || 0;
    const baseA = Number(wall?.height) || 0;

    const continuations = getWallContinuations(wall);
    const continuationWidth = continuations.reduce((sum, cont) => sum + (Number(cont?.width) || 0), 0);

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
 * para bater com a lógica do arquivo HTML `2026-03-04-Calculadora Revenda.html`.
 *
 * Retorna métricas por parede na ordem original do array.
 */
export function calculateWallsSequence(walls) {
    if (!Array.isArray(walls) || walls.length === 0) {
        return { perWall: [], totalFaixas: 0, totalMetros: 0, safetyExceeded: false };
    }

    const collapsed = walls.map((w) => collapseWallForSequence(w));

    // Alturas de faixa h[i] (uma para cada parede colapsada)
    const H = collapsed.map((seg) => getAlturaFaixa(seg.A));

    // Passo A: paredes -> (q,h) por parede, com carry e subida de altura
    let carry = 0.0;
    const perWallBase = [];
    for (let i = 0; i < collapsed.length; i++) {
        const Li = collapsed[i].L;
        const Hi = H[i];
        const nextH = i < collapsed.length - 1 ? H[i + 1] : null;

        // Se não há largura e/ou não existe faixa de altura (ex.: A inválida),
        // tratamos como 0 e não contribuímos para grupos.
        if (!Hi || !(Li > 0)) {
            perWallBase.push({ q: 0, h: Hi ?? null });
            continue;
        }

        const LiEfetiva = Li - carry;
        if (LiEfetiva <= 1e-12) {
            carry = -LiEfetiva;
            perWallBase.push({ q: 0, h: Hi });
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
            perWallBase.push({ q, h: Hi });
        } else {
            carry = novoCarry;
            perWallBase.push({ q: qBruto, h: Hi });
        }
    }

    // Passo B: merge -> grupos com índices das paredes que contribuíram
    const groups0 = [];
    for (let i = 0; i < perWallBase.length; i++) {
        const { q, h } = perWallBase[i];
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

    // Distribuir q ajustado de volta para cada parede do grupo (proporcional ao q base)
    const wallStrips = new Array(walls.length).fill(0);
    const wallMeters = new Array(walls.length).fill(0);
    const wallStripHeight = perWallBase.map((p) => p.h ?? null);

    groupsFinal.forEach((g) => {
        const idxs = g.indices;
        const qBaseSum = idxs.reduce((s, idx) => s + (perWallBase[idx]?.q ?? 0), 0);
        if (!qBaseSum) return;

        const exacts = idxs.map((idx) => {
            const q0 = perWallBase[idx]?.q ?? 0;
            return {
                idx,
                exact: (q0 / qBaseSum) * g.q,
                base: 0,
                frac: 0,
            };
        });

        let allocatedSum = 0;
        exacts.forEach((e) => {
            e.base = Math.floor(e.exact + 1e-9);
            e.frac = e.exact - e.base;
            allocatedSum += e.base;
        });

        let remainder = g.q - allocatedSum;
        if (remainder > 0) {
            exacts.sort((a, b) => b.frac - a.frac);
            for (let r = 0; r < remainder; r++) {
                exacts[r].base += 1;
            }
        } else if (remainder < 0) {
            // Ajuste defensivo: reduz nos menores frac
            exacts.sort((a, b) => a.frac - b.frac);
            for (let r = 0; r < Math.abs(remainder); r++) {
                if (exacts[r].base > 0) exacts[r].base -= 1;
            }
        }

        exacts.forEach((e) => {
            wallStrips[e.idx] = e.base;
            const h = wallStripHeight[e.idx];
            wallMeters[e.idx] = h ? e.base * h : 0;
        });
    });

    const totalFaixas = wallStrips.reduce((s, q) => s + q, 0);
    const totalMetros = wallMeters.reduce((s, m) => s + m, 0);

    const perWall = walls.map((_, i) => ({
        strips: wallStrips[i],
        meters: wallMeters[i],
        stripHeight: wallStripHeight[i],
    }));

    return {
        perWall,
        totalFaixas,
        totalMetros,
        safetyExceeded: Bres.safetyExceeded,
    };
}
