<template>
  <div class="modal fade" id="avatar-modal" tabindex="-1" aria-labelledby="avatar-modal-label" ref="crop-modal"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="avatar-modal-label">Atualizar foto de perfil</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="file" accept="image/*" class="form-control mb-3" @change="onFileChange" />
          <div v-if="imageUrl" class="text-center">
            <img ref="image" :src="imageUrl" class="img-fluid" />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-subtle" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" @click="cropAndUpload()">Carregar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, useTemplateRef } from 'vue'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.min.css'

const avatarPreview = ref(null)
const imageUrl = ref(null)
const cropper = ref(null)
const cropModal = ref(null)
const avatarTriggers = ref([])

const cropModalRef = useTemplateRef('crop-modal')
const imageRef = useTemplateRef('image')

const openModal = () => {
  if (!cropModal.value) {
    cropModal.value = new window.bootstrap.Modal(cropModalRef.value)
  }

  cropModal.value.show()
}

const handleAvatarClick = (event) => {
  event?.preventDefault()
  openModal()
}

const bindAvatarTriggers = () => {
  avatarTriggers.value = Array.from(document.querySelectorAll('.avatar-box'))
  avatarTriggers.value.forEach((el) => el.addEventListener('click', handleAvatarClick))
}

const unbindAvatarTriggers = () => {
  avatarTriggers.value.forEach((el) => el.removeEventListener('click', handleAvatarClick))
  avatarTriggers.value = []
}

function closeModal() {
  cropModal.value?.hide()
  imageUrl.value = null

  if (cropper.value) {
    cropper.value.destroy()
    cropper.value = null
  }
}

function onFileChange(event) {
  const file = event.target.files[0]

  if (file && file.type.startsWith('image/')) {
    imageUrl.value = URL.createObjectURL(file)

    nextTick(() => {
      const imgEl = imageRef.value
      if (!imgEl) return

      imgEl.onload = () => {
        if (cropper.value) {
          cropper.value.destroy()
        }

        cropper.value = new Cropper(imgEl, {
          aspectRatio: 1,
          viewMode: 1
        })

        console.log('dfgljgfoig');

      }
    })
  }
}

function cropAndUpload() {
  if (!cropper.value) return

  const canvas = cropper.value.getCroppedCanvas({
    width: 300,
    height: 300,
    imageSmoothingEnabled: true,
    imageSmoothingQuality: 'high'
  })

  const base64Image = canvas.toDataURL('image/jpeg', 0.9)

  axios.post('/v1/profile/avatar', { avatar: base64Image })
    .then(() => {
      avatarPreview.value = base64Image
      closeModal()
      window.location.reload()
    })
    .catch(error => {
      console.error('Erro ao enviar avatar:', error)
    })
}

onMounted(() => {
  cropModal.value = new window.bootstrap.Modal(cropModalRef.value)
  bindAvatarTriggers()
})

onBeforeUnmount(() => {
  unbindAvatarTriggers()
})
</script>
