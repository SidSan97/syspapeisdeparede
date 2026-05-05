import Swal from 'sweetalert2';

let toastInstance = null;

function getToast() {
  if (!toastInstance) {
    toastInstance = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
      },
    });
  }

  return toastInstance;
}

export function useToast() {
  const toast = getToast();

  function show(options) {
    return toast.fire(options);
  }

  function success(message, options = {}) {
    return show({
      icon: 'success',
      title: message,
      ...options,
    });
  }

  function error(message, options = {}) {
    return show({
      icon: 'error',
      title: message,
      ...options,
    });
  }

  function warning(message, options = {}) {
    return show({
      icon: 'warning',
      title: message,
      ...options,
    });
  }

  function info(message, options = {}) {
    return show({
      icon: 'info',
      title: message,
      ...options,
    });
  }

  function loading(message = 'Carregando...', options = {}) {
    return Swal.fire({
      title: message,
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
      ...options,
    });
  }

  function close() {
    Swal.close();
  }

  return {
    show,
    success,
    error,
    warning,
    info,
    loading,
    close,
  };
}
