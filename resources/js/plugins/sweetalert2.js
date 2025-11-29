import Swal from 'sweetalert2'

// Classes customizadas padrão para todos os SweetAlert
const defaultCustomClass = {
  popup: 'bg-body',
  title: 'h5 text-start text-body',
  htmlContainer: 'fs-6 text-body text-start',
  actions: 'justify-content-end w-100 px-5',
  cancelButton: 'btn btn-subtle',
  confirmButton: 'btn btn-primary',
}

// Criar uma instância do Swal com as classes customizadas aplicadas por padrão
const SwalWithCustomClass = Swal.mixin({
  customClass: defaultCustomClass
})

// Substituir window.Swal para usar a versão com classes customizadas
window.Swal = SwalWithCustomClass

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: toast => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
})
window.Toast = Toast
