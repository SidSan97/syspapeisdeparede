<script setup>
import { computed } from 'vue';

import PageHeader from './PageHeader.vue';

const props = defineProps({
  title: {
    type: String,
    default: null,
  },

  subtitle: {
    type: String,
    default: '',
  },

  fullWidth: {
    type: Boolean,
    default: false,
  },

  backTo: {
    type: [String, Object],
    default: null,
  },

  breadcrumbs: {
    type: Array,
    default: () => [],
  },
});

const containerClass = computed(() => (props.fullWidth ? 'container-fluid' : 'container'));
</script>

<template>
  <div class="" :class="containerClass">
    <div class="row g-0">
      <div class="col-12 mt-3">
        <PageHeader
          :title="title"
          :subtitle="subtitle"
          :back-to="backTo"
          :breadcrumbs="breadcrumbs"
        >
          <template #extra>
            <slot name="extra"></slot>
          </template>

          <template v-if="$slots.subtitle" #subtitle>
            <slot name="subtitle"></slot>
          </template>
        </PageHeader>

        <slot></slot>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
