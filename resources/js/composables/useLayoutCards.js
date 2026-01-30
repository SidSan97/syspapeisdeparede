import { ref } from 'vue';
import { useLayoutService } from '@/services/layoutService';
import { getCardDisplayName } from '@/utils/cardUtils';
import {
    isImageFile,
    getImageUrl,
    getCoverImage,
    getCommentsCount,
    getActivitiesCount,
    useCardModal,
} from '@/modules/card-modals/composables/useCardUtils';

/**
 * Composable para gerenciar cards de layout
 */
export function useLayoutCards(columnsRef) {
    const layoutService = useLayoutService();

    const cards = ref([]);
    const loading = ref(false);
    const draggedCard = ref(null);
    const { selectedCard, openCardModal, closeCardModal } = useCardModal();

    function getCardsByColumn(columnId) {
        return cards.value.filter(card => card.column === columnId);
    }

    async function fetchLayouts() {
        try {
            loading.value = true;
            const payload = await layoutService.getLayouts();

            const firstColumnId = columnsRef.value.length > 0 ? columnsRef.value[0].id : null;
            cards.value = payload.map(card => ({
                ...card,
                column: card.layout_column_names_id || firstColumnId,
            }));
        } catch (error) {
            console.error('Erro ao carregar layouts:', error);
            cards.value = [];
        } finally {
            loading.value = false;
        }
    }

    function handleDragStart(event, card) {
        draggedCard.value = card;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/html', event.target.outerHTML);
    }

    async function handleDrop(event, columnId) {
        event.preventDefault();
        if (draggedCard.value) {
            const cardIndex = cards.value.findIndex(c => c.id === draggedCard.value.id);
            if (cardIndex !== -1) {
                const oldColumnId = cards.value[cardIndex].column;
                cards.value[cardIndex].column = columnId;

                try {
                    await layoutService.updateCardColumn(draggedCard.value.id, columnId);
                } catch (error) {
                    console.error('Erro ao atualizar coluna do card:', error);
                    cards.value[cardIndex].column = oldColumnId;
                    throw error;
                }
            }
            draggedCard.value = null;
        }
    }

    async function moveCardsToColumn(cardsToMove, targetColumnId) {
        for (const card of cardsToMove) {
            try {
                await layoutService.updateCardColumn(card.id, targetColumnId);
                card.column = targetColumnId;
            } catch (error) {
                console.error(`Erro ao mover card ${card.id}:`, error);
            }
        }
    }

    return {
        cards,
        loading,
        selectedCard,
        draggedCard,
        getCardsByColumn,
        getCoverImage,
        getCommentsCount,
        getActivitiesCount,
        getCardDisplayName,
        fetchLayouts,
        handleDragStart,
        handleDrop,
        moveCardsToColumn,
        openCardModal,
        closeCardModal,
    };
}

