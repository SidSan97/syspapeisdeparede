<template>
  <BaseModal v-model="isOpen" title="Atualizar foto">
    <template #body>
      <input type="file" accept="image/*" class="form-control mb-3" @change="onFileChange" />

      <div v-if="imageUrl" class="text-center">
        <img ref="imageEl" :src="imageUrl" class="img-fluid" />
      </div>
    </template>

    <template #footer>
      <button class="btn btn-subtle" @click="close">Cancelar</button>

      <button class="btn btn-primary" @click="cropAndUpload">Carregar</button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.min.css';
import BaseModal from '@/components/common/BaseModal.vue';
import { http } from '@/lib/http';

const props = defineProps({
  userId: {
    type: [Number, String],
    required: true,
  },
});

const emit = defineEmits(['uploaded']);

const isOpen = ref(false);

const imageUrl = ref(null);
const imageEl = ref(null);
const cropper = ref(null);

function open() {
  isOpen.value = true;
}

function close() {
  isOpen.value = false;
  imageUrl.value = null;
}

defineExpose({ open, close });

function onFileChange(event) {
  const file = event.target.files[0];

  if (!file || !file.type.startsWith('image/')) return;

  imageUrl.value = URL.createObjectURL(file);
}

watch(imageUrl, async () => {
  if (!imageUrl.value) return;

  await nextTick();

  if (!imageEl.value) return;

  // recria cropper sempre que trocar imagem
  if (cropper.value) {
    cropper.value.destroy();
  }

  cropper.value = new Cropper(imageEl.value, {
    aspectRatio: 1,
    viewMode: 1,
  });
});

function cropAndUpload() {
  if (!cropper.value) return;

  const canvas = cropper.value.getCroppedCanvas({
    width: 300,
    height: 300,
    imageSmoothingEnabled: true,
    imageSmoothingQuality: 'high',
  });

  const base64Image = canvas.toDataURL('image/jpeg', 0.9);

  http
    .put(`v1/users/${props.userId}/avatar`, { image: base64Image })
    .then(({ data }) => {
      emit('uploaded', data.url);
      close();
    })
    .catch((err) => {
      console.error('Erro ao enviar avatar:', err);
    });
}
</script>
