/**
 * Somatório dos "dias úteis de nova arte" baseado nos modelos selecionados.
 *
 * Regra:
 * - percorre budget.rooms[].walls[]
 * - se wall.model existir, pega o modelo via getModelById(wall.model)
 * - soma model.deadline (>= 0)
 *
 * @param {object} budget
 * @param {(id: number|string) => (object|null|undefined)} getModelById
 * @returns {number}
 */
export function sumArtworkDays(budget, getModelById) {
  const rooms = budget?.rooms;
  if (!Array.isArray(rooms) || typeof getModelById !== 'function') {
    return 0;
  }

  let totalArtworkDays = 0;

  rooms.forEach((room) => {
    const walls = room?.walls;
    if (!Array.isArray(walls)) return;

    walls.forEach((wall) => {
      if (!wall?.model) return;

      const model = getModelById(wall.model);
      if (!model) return;

      const days = Math.max(0, Number(model.deadline ?? 0));
      totalArtworkDays += days;
    });
  });

  return totalArtworkDays;
}
