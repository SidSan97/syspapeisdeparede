/**
 * Utilitários para ajudar com SweetAlert2
 */

/**
 * Cria um HTML de input com botão de copiar para usar no SweetAlert2
 * @param {string} linkId - ID único para o input e botão
 * @param {string} linkValue - Valor do link a ser exibido
 * @param {string} message - Mensagem a ser exibida acima do input (opcional)
 * @returns {string} HTML formatado
 */
export function createLinkInputHTML(linkId, linkValue, message = null) {
  const messageHTML = message ? `<p>${message}</p>` : '';
  
  return `
    ${messageHTML}
    <div class="mt-3">
      <div class="input-group">
        <input
          type="text"
          id="${linkId}-input"
          class="form-control"
          value="${linkValue}"
          readonly
          style="font-size: 0.875rem;"
        />
        <button
          type="button"
          class="btn btn-outline-secondary"
          id="${linkId}-button"
          title="Copiar link"
        >
          <i class="fa fa-copy"></i>
        </button>
      </div>
    </div>
  `;
}

/**
 * Configura o evento de copiar para um link no SweetAlert2
 * @param {string} linkId - ID único usado no createLinkInputHTML
 * @param {string} linkValue - Valor do link a ser copiado
 * @param {string} successMessage - Mensagem de sucesso personalizada (opcional)
 */
export function setupCopyLinkHandler(linkId, linkValue, successMessage = null) {
  const defaultMessage = 'O link foi copiado para a área de transferência.';
  const message = successMessage || defaultMessage;
  
  return () => {
    const copyButton = document.getElementById(`${linkId}-button`);
    const linkInput = document.getElementById(`${linkId}-input`);
    
    if (copyButton) {
      copyButton.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(linkValue);
          window.Swal.fire({
            title: 'Link copiado!',
            text: message,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
          });
        } catch (err) {
          // Fallback para navegadores mais antigos
          if (linkInput) {
            linkInput.select();
            document.execCommand('copy');
            window.Swal.fire({
              title: 'Link copiado!',
              text: message,
              icon: 'success',
              timer: 2000,
              showConfirmButton: false
            });
          }
        }
      });
    }
  };
}

/**
 * Cria uma configuração completa para SweetAlert2 com input de link e botão copiar
 * @param {Object} options - Opções de configuração
 * @param {string} options.title - Título do SweetAlert
 * @param {string} options.linkId - ID único para o input e botão
 * @param {string} options.linkValue - Valor do link a ser exibido
 * @param {string} options.message - Mensagem a ser exibida acima do input (opcional)
 * @param {string} options.successMessage - Mensagem de sucesso ao copiar (opcional)
 * @param {string} options.confirmButtonText - Texto do botão de confirmação
 * @param {string} options.cancelButtonText - Texto do botão de cancelamento
 * @param {string} options.icon - Ícone do SweetAlert (default: 'success')
 * @returns {Object} Configuração do SweetAlert2
 */
export function createLinkAlertConfig({
  title,
  linkId,
  linkValue,
  message = null,
  successMessage = null,
  confirmButtonText = 'Abrir link',
  cancelButtonText = 'Fechar',
  icon = 'success'
}) {
  return {
    title,
    html: createLinkInputHTML(linkId, linkValue, message),
    icon,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText,
    didOpen: setupCopyLinkHandler(linkId, linkValue, successMessage)
  };
}

