import Swal from 'sweetalert2'

const alert = Swal.mixin({
  customClass: {
    popup: 'growhub-alert-popup',
    title: 'growhub-alert-title',
    confirmButton: 'growhub-alert-confirm',
    cancelButton: 'growhub-alert-cancel',
  },
  buttonsStyling: false,
  reverseButtons: true,
})

export const showSuccess = (title: string, message: string) => {
  return alert.fire({
    icon: 'success',
    title,
    text: message,
    confirmButtonText: 'Continue',
  })
}

export const showError = (message: string, title = 'Something went wrong') => {
  return alert.fire({
    icon: 'error',
    title,
    text: message,
    confirmButtonText: 'Okay',
  })
}

export const showWarning = (title: string, message: string) => {
  return alert.fire({
    icon: 'warning',
    title,
    text: message,
    confirmButtonText: 'Okay',
  })
}

export const confirmAction = async (
  title: string,
  message: string,
  confirmText = 'Yes, continue',
  icon: 'warning' | 'question' | 'error' = 'warning',
) => {
  const result = await alert.fire({
    icon,
    title,
    text: message,
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: 'Cancel',
    focusCancel: true,
  })

  return result.isConfirmed
}

export default alert
