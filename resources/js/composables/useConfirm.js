export function useConfirm() {
  const modal = async ({
    title = 'Excluir item?',
    text = 'Se você excluir o item, não será possível recuperá-lo.',
    acceptLabel = 'Excluir item',
    rejectLabel = 'Cancelar',
    onAccept = null,
  }) => {
    const result = await window.Swal.fire({
      title,
      text,
      showCancelButton: true,
      showCloseButton: true,
      reverseButtons: true,
      cancelButtonText: rejectLabel,
      confirmButtonText: acceptLabel,
    });

    if (result.isConfirmed && onAccept) {
      await onAccept()
    }
  }

  return { modal }
}