<script setup>
import { computed } from 'vue';
import placeholderImage from '@assets/img/no-image.svg';

const emit = defineEmits(['on-cover-click']);

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  src: {
    type: String,
    default: null,
  },
});

const coverImage = computed(() => props.src || placeholderImage);

const handleCoverClick = () => {
  emit('on-cover-click');
};
</script>

<template>
  <div class="card cursor-pointer mb-4 card-collection">
    <div class="ratio ratio-4x3">
      <img class="card-img-top rounded" :src="coverImage" alt="" />
    </div>

    <div
      class="card-img-overlay d-flex flex-column justify-content-end text-white"
      @click="handleCoverClick"
    >
      <div class="position-absolute top-0 end-0 p-3">
        <slot name="actions"></slot>
      </div>

      <h5 class="mb-0 fw-semibold">{{ title }}</h5>
    </div>
  </div>
</template>

<style scoped>
.card-collection .card-img-overlay {
  background: linear-gradient(0deg, rgba(0, 0, 0, 1) 0%, rgba(0, 0, 0, 0) 100%);
}
</style>
