import Swal from 'sweetalert2'
import type { SweetAlertOptions, SweetAlertIcon, SweetAlertResult } from 'sweetalert2'

export function simpleAlert(title: string, icon: any | string, footer: string) {
  Swal.fire({
    title: title,
    icon: icon,
    showConfirmButton: false,
    toast: true,
    position: 'top-end',
    footer: footer,
    timer: 1500
  })
}

export function swalSuccess(message: string) {
  Swal.fire({
    title: 'Feito!',
    text: message,
    icon: 'success'
  })
}

export function swalError(message: string) {
  Swal.fire({
    title: 'Erro!',
    text: message,
    icon: 'error'
  })
}
export function swalErrorHtml(message: string) {
  Swal.fire({
    title: 'Erro!',
    html: message,
    icon: 'error'
  })
}

export function swalConfirmation(
  title = 'Você tem certeza?',
  message = 'Essa ação é <strong>irreversível!</strong>',
  icon: SweetAlertIcon = 'warning',
  confirmButtonText = 'Sim',
  cancelButtonText = 'Não'
): Promise<SweetAlertResult> {
  const options: SweetAlertOptions = {
    title,
    html: message,
    icon,
    showCancelButton: true,
    confirmButtonColor: '#3085D6',
    confirmButtonText,
    cancelButtonColor: '#D33',
    cancelButtonText
  }
  return Swal.fire(options)
}