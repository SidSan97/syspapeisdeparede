import { ref } from 'vue';
import { useProductionService } from '../services/productionService';
import { getCardDisplayName, getProductionTimerText, getProductionTimerClass } from '@/utils/cardUtils';

/**
 * Composable para gerenciar cards de produção
 */
export function useProductionCards(columnsRef) {
    const productionService = useProductionService();

    const cards = ref([]);
    const loading = ref(false);
    const selectedCard = ref(null);
    const draggedCard = ref(null);
    const currentTime = ref(new Date());

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

    function isImageFile(file) {
        if (!file) {
            return false;
        }
        const mime = (file.mime || file.mimetype || '').toLowerCase();
        if (mime.startsWith('image/')) {
            return true;
        }
        const name = (file.name || file.original_name || file.file_name || '').toLowerCase();
        return ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'].some(ext => name.endsWith(ext));
    }

    function getImageUrl(file) {
        if (file.url) {
            return file.url;
        }
        if (file.fileUrl) {
            return file.fileUrl;
        }
        if (file.file_path) {
            if (file.file_path.startsWith('http')) {
                return file.file_path;
            }
            return `/storage/${file.file_path}`;
        }
        return '';
    }

    function getCoverImage(card) {
        if (!card) {
            return '';
        }
        if (card.image) {
            return card.image;
        }
        const imageAttachment = card.uploaded_files?.find(file => isImageFile(file));
        return imageAttachment ? getImageUrl(imageAttachment) : '';
    }

    function getCommentsCount(card) {
        if (!card || !Array.isArray(card.comments)) {
            return 0;
        }
        return card.comments.length;
    }

    function getActivitiesCount(card) {
        if (!card) {
            return 0;
        }
        let count = 0;

        if (Array.isArray(card.activities)) {
            count += card.activities.length;
        }

        if (Array.isArray(card.history)) {
            count += card.history.length;
        }

        if (card.budget?.comment_referring_model) {
            count += 1;
        }

        return count;
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

    function openCardModal(card) {
        selectedCard.value = card;
    }

    function closeCardModal() {
        selectedCard.value = null;
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


