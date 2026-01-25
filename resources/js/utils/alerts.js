/**
 * Utilitários para alertas usando SweetAlert2
 */

/**
 * Exibe um diálogo de confirmação
 * @param {string} title - Título do diálogo
 * @param {string} text - Texto do diálogo
 * @param {string} icon - Ícone (warning, error, success, info, question)
 * @param {string} confirmButtonText - Texto do botão de confirmação
 * @param {string} cancelButtonText - Texto do botão de cancelamento
 * @returns {Promise<Object>} Resultado do SweetAlert2
 */
export async function swalConfirmation(
    title = 'Confirmar ação?',
    text = 'Essa ação não pode ser desfeita.',
    icon = 'warning',
    confirmButtonText = 'Confirmar',
    cancelButtonText = 'Cancelar'
) {
    return await window.Swal.fire({
        title,
        html: text,
        icon,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText,
        reverseButtons: true,
    });
}

/**
 * Exibe um alerta de sucesso
 * @param {string} title - Título do alerta
 * @param {string} text - Texto do alerta
 * @returns {Promise<Object>} Resultado do SweetAlert2
 */
export async function swalSuccess(title = 'Sucesso!', text = 'Operação realizada com sucesso.') {
    return await window.Swal.fire({
        title,
        text,
        icon: 'success',
        confirmButtonText: 'Entendi!',
    });
}

/**
 * Exibe um alerta de erro
 * @param {string} title - Título do alerta
 * @param {string} text - Texto do alerta
 * @returns {Promise<Object>} Resultado do SweetAlert2
 */
export async function swalError(title = 'Erro!', text = 'Ocorreu um erro ao processar a solicitação.') {
    return await window.Swal.fire({
        title,
        text,
        icon: 'error',
        confirmButtonText: 'Entendi!',
    });
}

