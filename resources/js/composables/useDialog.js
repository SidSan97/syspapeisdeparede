import Swal from 'sweetalert2';

export function useDialog() {
  const classes = {
    popup: 'bg-body',
    title: 'h5 text-start text-body',
    htmlContainer: 'fs-6 text-body text-start',
    actions: 'justify-content-end w-100 px-5',
    cancelButton: 'btn btn-subtle',
    confirmButton: 'btn btn-primary',
  };

  function confirm({
    title = 'Tem certeza?',
    text = '',
    confirmText = 'Sim',
    cancelText = 'Cancelar',
    ...options
  } = {}) {
    return Swal.fire({
      title,
      text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      reverseButtons: true,
      focusCancel: true,
      customClass: classes,
      ...options,
    }).then((result) => result.isConfirmed);
  }

  function confirmDelete({
    title = 'Tem certeza?',
    text = '',
    confirmText = 'Excluir',
    cancelText = 'Cancelar',
    ...options
  } = {}) {
    return Swal.fire({
      title,
      text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      reverseButtons: true,
      focusCancel: true,
      customClass: {
        ...classes,
        confirmButton: 'btn btn-danger',
      },
      ...options,
    }).then((result) => result.isConfirmed);
  }

  function alert({ title = 'Atenção', text = '', confirmText = 'OK', ...options } = {}) {
    return Swal.fire({
      title,
      text,
      icon: 'info',
      confirmButtonText: confirmText,
      customClass: classes,
      ...options,
    });
  }

  function success({ title = 'Sucesso', text = '', confirmText = 'OK', ...options } = {}) {
    return Swal.fire({
      title,
      text,
      icon: 'success',
      confirmButtonText: confirmText,
      customClass: classes,
      ...options,
    });
  }

  function error({ title = 'Erro', text = '', confirmText = 'OK', ...options } = {}) {
    return Swal.fire({
      title,
      text,
      icon: 'error',
      confirmButtonText: confirmText,
      customClass: classes,
      ...options,
    });
  }

  return {
    confirm,
    confirmDelete,
    alert,
    success,
    error,
  };
}
