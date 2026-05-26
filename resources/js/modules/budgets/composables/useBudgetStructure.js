import { createDefaultWall } from './useBudgetUtils';

/**
 * Composable para manipulação da estrutura de orçamento (rooms, walls, continuations)
 */

const createDefaultContinuation = ({ initial = false } = {}) => ({
  name: '',
  width: null,
  height: null,
  sameArt: false,
  fit: initial ? 'Inicial' : 'Central',
});

/**
 * Composable para gerenciar a estrutura do orçamento
 */
export function useBudgetStructure(budget) {
  function addRoom() {
    budget.rooms.push({
      name: '',
      walls: [createDefaultWall()],
    });
  }

  function removeRoom(index) {
    budget.rooms.splice(index, 1);
  }

  function addWall(roomIndex) {
    budget.rooms[roomIndex].walls.push(createDefaultWall());
  }

  function removeWall(roomIndex, wallIndex) {
    budget.rooms[roomIndex].walls.splice(wallIndex, 1);
  }

  function addContinuation(roomIndex, wallIndex) {
    const wall = budget.rooms[roomIndex].walls[wallIndex];
    if (!Array.isArray(wall.continuations)) {
      wall.continuations = [];
    }
    if (!wall.continueSameArt) {
      wall.continueSameArt = true;
    }
    wall.continuations.push(createDefaultContinuation({ initial: false }));
  }

  function removeContinuation(roomIndex, wallIndex, continuationIndex) {
    const wall = budget.rooms[roomIndex].walls[wallIndex];
    if (!Array.isArray(wall.continuations)) {
      return;
    }
    wall.continuations.splice(continuationIndex, 1);
  }

  function handleContinuationToggle(roomIndex, wallIndex) {
    const wall = budget.rooms[roomIndex].walls[wallIndex];

    if (!wall.continueSameArt) {
      wall.continuations = [];
      return;
    }

    if (!wall.width || !wall.height) {
      wall.continueSameArt = false;
      wall.continuations = [];
      return;
    }

    if (!Array.isArray(wall.continuations) || !wall.continuations.length) {
      wall.continuations = [createDefaultContinuation({ initial: true })];
    }
  }

  return {
    addRoom,
    removeRoom,
    addWall,
    removeWall,
    addContinuation,
    removeContinuation,
    handleContinuationToggle,
  };
}
