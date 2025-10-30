export function useConfirm() {
  const modal = async ({
    title = 'Excluir item?',
    text = 'Se você excluir o item, não será possível recuperá-lo.',
    acceptLabel = 'Excluir item',
    rejectLabel = 'Cancelar',
    onAccept = null,
  }) => {
    const result = await Swal.fire({
      title,
      text,
      showCancelButton: true,
      showCloseButton: true,
      reverseButtons: true,
      customClass: {
        popup: 'bg-body',
        title: 'h5 text-start text-body',
        htmlContainer: 'fs-6 text-body text-start',
        actions: 'justify-content-end w-100 px-5',
        cancelButton: 'btn btn-subtle',
        confirmButton: 'btn btn-danger',
      },
      cancelButtonText: rejectLabel,
      confirmButtonText: acceptLabel,
    });

    if (result.isConfirmed && onAccept) {
      await onAccept()
    }
  }

  return { modal }
}