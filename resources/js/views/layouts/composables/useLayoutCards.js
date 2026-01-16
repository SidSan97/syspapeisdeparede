import { ref, computed } from 'vue';
import { useLayoutService } from '../services/layoutService';
import { getCardDisplayName } from '@/utils/cardUtils';

/**
 * Composable para gerenciar cards de layout
 */
export function useLayoutCards(columnsRef) {
    const layoutService = useLayoutService();

    const cards = ref([]);
    const loading = ref(false);
    const selectedCard = ref(null);
    const draggedCard = ref(null);

    function getCardsByColumn(columnId) {
        return cards.value.filter(card => card.column === columnId);
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

    function openCardModal(card) {
        selectedCard.value = card;
    }

    function closeCardModal() {
        selectedCard.value = null;
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
