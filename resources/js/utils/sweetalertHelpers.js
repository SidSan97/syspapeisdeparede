import Swal from 'sweetalert2';

const copyIconSVG = `
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>
`;

async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text);
    return true;
  } catch {
    return false;
  }
}

export async function showCopyLinkAlert({
  title,
  linkValue,
  message = null,
  confirmButtonText = 'Abrir link',
  cancelButtonText = 'Fechar',
  icon = 'success',
}) {
  const result = await Swal.fire({
    title,

    html: `
      ${message ? `<p>${message}</p>` : ''}

      <div class="mt-3">
        <div class="input-group">
          <input
            type="text"
            class="form-control swal-link-input"
            readonly
            value="${linkValue}"
          />

          <button
            type="button"
            class="btn btn-outline-default swal-copy-btn"
            title="Copiar link"
          >${copyIconSVG}</button>
        </div>
      </div>
    `,

    icon,

    showCancelButton: true,

    confirmButtonText,
    cancelButtonText,

    didRender: () => {
      const container = Swal.getHtmlContainer();

      const copyButton = container.querySelector('.swal-copy-btn');

      const input = container.querySelector('.swal-link-input');

      copyButton?.addEventListener('click', async () => {
        const success = await copyToClipboard(linkValue);

        if (success) {
          Swal.fire({
            title: 'Link copiado!',
            text: 'O link foi copiado para a área de transferência.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
          });
        } else {
          input?.select();
        }
      });
    },
  });

  return result;
}
