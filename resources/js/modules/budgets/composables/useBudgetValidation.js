/**
 * Composable para validações de orçamento
 */

/**
 * Valida um orçamento
 */
export function validateBudget(budget, modelsLoading, productModels, showWarning) {
    if (!budget.name) {
        showWarning('Por favor, informe o nome do orçamento');
        return false;
    }

    // Validar se há pelo menos um ambiente com pelo menos uma parede
    if (budget.rooms.length === 0) {
        showWarning('Por favor, adicione pelo menos um ambiente');
        return false;
    }

    const isValidDimension = (value) =>
        typeof value === 'number' && !Number.isNaN(value) && value > 0;

    for (let roomIndex = 0; roomIndex < budget.rooms.length; roomIndex++) {
        const room = budget.rooms[roomIndex];
        const roomLabel = room.name?.trim() || `Ambiente ${roomIndex + 1}`;

        if (!room.name || !room.name.toString().trim()) {
            showWarning(`Informe o nome do ${roomLabel}.`);
            return false;
        }

        if (!room.walls.length) {
            showWarning(`Adicione pelo menos uma parede em ${roomLabel}.`);
            return false;
        }

        for (let wallIndex = 0; wallIndex < room.walls.length; wallIndex++) {
            const wall = room.walls[wallIndex];
            const wallLabel = wall.name?.trim() || `Parede ${wallIndex + 1}`;

            if (!wall.name || !wall.name.toString().trim()) {
                showWarning(`Informe o nome da ${wallLabel} em ${roomLabel}.`);
                return false;
            }

            if (!isValidDimension(wall.width) || !isValidDimension(wall.height)) {
                showWarning(
                    `Informe largura e altura válidas para ${wallLabel} em ${roomLabel}.`
                );
                return false;
            }

            if (wall.continueSameArt && Array.isArray(wall.continuations)) {
                for (
                    let continuationIndex = 0;
                    continuationIndex < wall.continuations.length;
                    continuationIndex++
                ) {
                    const continuation = wall.continuations[continuationIndex];
                    if (!isValidDimension(continuation.width)) {
                        showWarning(
                            `Informe a largura da continuação ${continuationIndex + 1} em ${wallLabel} (${roomLabel}).`
                        );
                        return false;
                    }
                    if (!isValidDimension(continuation.height)) {
                        showWarning(
                            `Informe a altura da continuação ${continuationIndex + 1} em ${wallLabel} (${roomLabel}).`
                        );
                        return false;
                    }
                }
            }

            if (!wall.model) {
                showWarning(`Por favor, selecione um modelo para ${wallLabel} em ${roomLabel}`);
                return false;
            }
        }
    }

    if (modelsLoading.value) {
        showWarning('Aguarde o carregamento dos modelos antes de salvar.');
        return false;
    }

    if (!productModels.value.length) {
        showWarning('Nenhum modelo disponível no momento.');
        return false;
    }

    if (budget.cep && budget.cep.length >= 8 && budget.selectedCarrier === null) {
        showWarning('Por favor, selecione uma transportadora ou remova o CEP');
        return false;
    }

    if (!budget.paymentMethod) {
        showWarning('Por favor, selecione uma forma de pagamento');
        return false;
    }

    return true;
}
