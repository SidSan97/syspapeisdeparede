import { ref } from 'vue';

/**
 * Funções utilitárias compartilhadas para gerenciamento de cards
 * Usado por useLayoutCards e useProductionCards
 */

/**
 * Verifica se um arquivo é uma imagem
 * @param {Object} file - Objeto do arquivo
 * @returns {boolean}
 */
export function isImageFile(file) {
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

/**
 * Obtém a URL de uma imagem
 * @param {Object} file - Objeto do arquivo
 * @returns {string}
 */
export function getImageUrl(file) {
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

/**
 * Obtém a imagem de capa de um card
 * @param {Object} card - Objeto do card
 * @returns {string}
 */
export function getCoverImage(card) {
    if (!card) {
        return '';
    }
    if (card.image) {
        return card.image;
    }
    const imageAttachment = card.uploaded_files?.find(file => isImageFile(file));
    return imageAttachment ? getImageUrl(imageAttachment) : '';
}

/**
 * Conta o número de comentários de um card
 * @param {Object} card - Objeto do card
 * @returns {number}
 */
export function getCommentsCount(card) {
    if (!card || !Array.isArray(card.comments)) {
        return 0;
    }
    return card.comments.length;
}

/**
 * Conta o número de atividades de um card
 * @param {Object} card - Objeto do card
 * @returns {number}
 */
export function getActivitiesCount(card) {
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

/**
 * Composable para gerenciar o modal de card
 * @returns {Object} Funções e estado do modal
 */
export function useCardModal() {
    const selectedCard = ref(null);

    function openCardModal(card) {
        selectedCard.value = card;
    }

    function closeCardModal() {
        selectedCard.value = null;
    }

    return {
        selectedCard,
        openCardModal,
        closeCardModal,
    };
}

