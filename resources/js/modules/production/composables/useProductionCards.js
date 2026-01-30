import { ref } from 'vue';
import { useProductionService } from '../services/productionService';
import { getCardDisplayName, getProductionTimerText, getProductionTimerClass } from '@/utils/cardUtils';
import {
    isImageFile,
    getImageUrl,
    getCoverImage,
    getCommentsCount,
    getActivitiesCount,
    useCardModal,
} from '@/modules/card-modals/composables/useCardUtils';

/**
 * Composable para gerenciar cards de produção
 */
export function useProductionCards(columnsRef) {
    const productionService = useProductionService();

    const cards = ref([]);
    const loading = ref(false);
    const draggedCard = ref(null);
    const currentTime = ref(new Date());
    const { selectedCard, openCardModal, closeCardModal } = useCardModal();

    function getCardsByColumn(columnId) {
        return cards.value.filter(card => card.column === columnId);
    }

    function getTotalMetragem(columnId) {
        const columnCards = getCardsByColumn(columnId);
        const total = columnCards.reduce((sum, card) => {
            const area = card?.wall?.total_area || 0;
            return sum + Number(area);
        }, 0);
        return total.toFixed(2);
    }

    function formatProductionDate(dateString) {
        if (!dateString) {
            return '';
        }
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return dateString;
            }
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        } catch (error) {
            return dateString;
        }
    }

    function getProductionTimerTextForCard(card) {
        return getProductionTimerText(card, currentTime.value);
    }

    function getProductionTimerClassForCard(card) {
        return getProductionTimerClass(card, currentTime.value, 'production-card-timer');
    }

    function isCardFullyProduced(card) {
        return card.production_percentage === 100 || Number(card.production_percentage) === 100;
    }

    async function fetchLayouts() {
        try {
            loading.value = true;
            const payload = await productionService.getLayouts();

            const firstColumnId = columnsRef.value.length > 0 ? columnsRef.value[0].id : null;
            cards.value = payload.map(card => ({
                ...card,
                column: card.production_column_names_id || firstColumnId,
            }));
        } catch (error) {
            console.error('Erro ao carregar layouts de produção:', error);
            cards.value = [];
        } finally {
            loading.value = false;
        }
    }

    function handleDragStart(event, card) {
        const isFullyProduced = isCardFullyProduced(card);

        if (isFullyProduced) {
            event.preventDefault();
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Card 100% produzido',
                    text: 'Não é possível mover um card que está 100% produzido.',
                    confirmButtonText: 'Entendi!',
                });
            } else {
                alert('Não é possível mover um card que está 100% produzido.');
            }
            return false;
        }

        draggedCard.value = card;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/html', event.target.outerHTML);
    }

    async function handleDrop(event, columnId) {
        event.preventDefault();
        if (!draggedCard.value) {
            return;
        }

        const isFullyProduced = isCardFullyProduced(draggedCard.value);

        if (isFullyProduced) {
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Card 100% produzido',
                    text: 'Não é possível mover um card que está 100% produzido.',
                    confirmButtonText: 'Entendi!',
                });
            } else {
                alert('Não é possível mover um card que está 100% produzido.');
            }
            draggedCard.value = null;
            return;
        }

        const cardIndex = cards.value.findIndex(c => c.id === draggedCard.value.id);
        if (cardIndex !== -1) {
            const oldColumnId = cards.value[cardIndex].column;
            cards.value[cardIndex].column = columnId;

            try {
                await productionService.updateCardColumn(draggedCard.value.id, columnId);
            } catch (error) {
                console.error('Erro ao atualizar coluna do card:', error);
                cards.value[cardIndex].column = oldColumnId;
                throw error;
            }
        }
        draggedCard.value = null;
    }

    async function moveCardsToColumn(cardsToMove, targetColumnId) {
        for (const card of cardsToMove) {
            try {
                await productionService.updateCardColumn(card.id, targetColumnId);
                card.column = targetColumnId;
            } catch (error) {
                console.error(`Erro ao mover card ${card.id}:`, error);
            }
        }
    }

    function updateCurrentTime() {
        currentTime.value = new Date();
    }

    return {
        cards,
        loading,
        selectedCard,
        draggedCard,
        currentTime,
        getCardsByColumn,
        getTotalMetragem,
        getCoverImage,
        getCommentsCount,
        getActivitiesCount,
        getCardDisplayName,
        formatProductionDate,
        getProductionTimerTextForCard,
        getProductionTimerClassForCard,
        isCardFullyProduced,
        fetchLayouts,
        handleDragStart,
        handleDrop,
        moveCardsToColumn,
        openCardModal,
        closeCardModal,
        updateCurrentTime,
    };
}


