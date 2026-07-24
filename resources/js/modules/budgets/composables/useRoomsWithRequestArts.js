/**
 * Agrupa solicitações de arte por ambiente/parede do orçamento.
 */

function normalizeKeyPart(value) {
  return String(value ?? '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

export function wallMatchKey(roomName, wallName) {
  return `${normalizeKeyPart(roomName)}::${normalizeKeyPart(wallName)}`;
}

/**
 * Normaliza um item da API (flat ou já com arts[]) para o formato de interação.
 */
export function normalizeRequestArtItem(art, resolveImageUrl = (path) => path) {
  let imageUrl = art.image_url;
  if (!imageUrl && art.path_file) {
    imageUrl = resolveImageUrl(art.path_file);
  }

  const designerName = art.designer?.name ?? art.designer_name ?? null;
  const dealerName = art.dealer?.name ?? art.dealer_name ?? null;

  const normalized = {
    id: art.id,
    order_id: art.order_id ?? null,
    order_budget_id: art.order_budget_id ?? null,
    dealer_id: art.dealer_id ?? art.dealer?.id ?? null,
    designer_id: art.designer_id ?? art.designer?.id ?? null,
    comment: art.comment ?? null,
    approval_status: art.approval_status ?? 'pending',
    path_file: art.path_file ?? null,
    image_url: imageUrl,
    created_at: art.created_at ?? null,
    designer_name: designerName,
    dealer_name: dealerName,
    wall_info: art.wall_info ?? null,
    wall_name: art.wall_name ?? art.wall_info?.wall_name ?? null,
    card_id: art.card_id ?? art.order_budget_id ?? null,
  };

  const arts =
    Array.isArray(art.arts) && art.arts.length > 0
      ? art.arts.map((item) => ({
          ...item,
          approval_status: item.approval_status ?? 'pending',
          designer_name: item.designer?.name ?? item.designer_name ?? designerName,
          dealer_name: item.dealer?.name ?? item.dealer_name ?? dealerName,
          image_url:
            item.image_url ||
            (item.path_file ? resolveImageUrl(item.path_file) : null) ||
            imageUrl,
          comment: item.comment ?? normalized.comment,
          created_at: item.created_at ?? normalized.created_at,
          wall_info: item.wall_info ?? normalized.wall_info,
        }))
      : [normalized];

  return {
    ...normalized,
    arts_count: art.arts_count ?? art.arts?.length ?? arts.length,
    arts,
  };
}

/**
 * Índice room+wall → interações.
 *
 * @param {Array} interactions
 * @returns {Map<string, Array>}
 */
export function indexInteractionsByWall(interactions) {
  const map = new Map();

  for (const interaction of interactions ?? []) {
    const roomName = interaction.wall_info?.room_name ?? '';
    const wallName =
      interaction.wall_info?.wall_name ?? interaction.wall_name ?? '';
    const key = wallMatchKey(roomName, wallName);

    if (!map.has(key)) {
      map.set(key, []);
    }
    map.get(key).push(interaction);
  }

  return map;
}

/**
 * Monta a árvore ambiente → parede → solicitações.
 *
 * @param {Object} budget
 * @param {Array} rawArts
 * @param {(path: string) => string} [resolveImageUrl]
 */
export function buildRoomsWithRequestArts(budget, rawArts = [], resolveImageUrl) {
  const interactions = (Array.isArray(rawArts) ? rawArts : []).map((art) =>
    normalizeRequestArtItem(art, resolveImageUrl),
  );
  const byWall = indexInteractionsByWall(interactions);
  const matchedKeys = new Set();

  const rooms = (budget?.rooms ?? []).map((room, roomIndex) => {
    const walls = (room.walls ?? []).map((wall, wallIndex) => {
      const key = wallMatchKey(room.name, wall.name);
      const requestArts = byWall.get(key) ?? [];
      if (requestArts.length) {
        matchedKeys.add(key);
      }

      return {
        ...wall,
        roomIndex,
        wallIndex,
        requestArts,
      };
    });

    return {
      ...room,
      roomIndex,
      walls,
      requestArtsCount: walls.reduce((sum, wall) => sum + wall.requestArts.length, 0),
    };
  });

  const unmatchedRequestArts = interactions.filter((interaction) => {
    const key = wallMatchKey(
      interaction.wall_info?.room_name,
      interaction.wall_info?.wall_name ?? interaction.wall_name,
    );
    return !matchedKeys.has(key);
  });

  return {
    rooms,
    unmatchedRequestArts,
    totalRequestArts: interactions.length,
  };
}
