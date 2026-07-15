import Swal from 'sweetalert2';

window.Swal = Swal.mixin({
  customClass: {
    popup: 'bg-body',
    title: 'h5 text-start text-body',
    htmlContainer: 'fs-6 text-body text-start',
    actions: 'justify-content-end w-100 px-5',
    cancelButton: 'btn btn-subtle',
    confirmButton: 'btn btn-primary',
  },
  allowOutsideClick: false,
});

window.Toast = Swal.mixin({
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
