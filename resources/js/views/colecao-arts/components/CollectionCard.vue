<script setup>
import { computed, ref } from 'vue';
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
const imageLoaded = ref(false);

const handleCoverClick = () => {
  emit('on-cover-click');
};
</script>

<template>
  <div class="card cursor-pointer mb-4 card-collection">
    <div class="ratio ratio-4x3">
      <span
        v-if="!imageLoaded"
        class="placeholder-glow position-absolute top-0 start-0 w-100 h-100"
        aria-hidden="true"
      >
        <span class="placeholder w-100 h-100 rounded" />
      </span>
      <img
        class="card-img-top rounded"
        :class="{ 'opacity-0': !imageLoaded }"
        :src="coverImage"
        loading="lazy"
        alt=""
        @load="imageLoaded = true"
      />
    </div>

    <div
      class="card-img-overlay d-flex flex-column justify-content-end text-white"
      @click="handleCoverClick"
    >
      <div class="position-absolute top-0 start-0 end-0 p-3">
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

.card-img-top {
  transition: opacity 0.3s ease;
}
</style>
