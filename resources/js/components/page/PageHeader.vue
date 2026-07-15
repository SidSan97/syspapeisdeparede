<script setup>
import { computed } from 'vue';

import { IconArrowLeft } from '@tabler/icons-vue';

import PageTitle from './PageTitle.vue';

const props = defineProps({
  title: {
    type: String,
    default: '',
  },

  subtitle: {
    type: String,
    default: '',
  },

  backTo: {
    type: [String, Object],
    default: '',
  },

  breadcrumbs: {
    type: Array,
    default: () => [],
  },
});

const hasBreadcrumbs = computed(
  () => Array.isArray(props.breadcrumbs) && props.breadcrumbs.length > 0,
);
</script>

<template>
  <div class="mb-3">
    <!-- Breadcrumbs -->
    <nav v-if="hasBreadcrumbs" class="mb-1" aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li
          v-for="(crumb, index) in breadcrumbs"
          :key="crumb.path || index"
          class="breadcrumb-item"
          :class="{ active: index === breadcrumbs.length - 1 }"
        >
          <router-link v-if="index < breadcrumbs.length - 1 && crumb.path" :to="crumb.path">
            {{ crumb.breadcrumbName }}
          </router-link>

          <span v-else>
            {{ crumb.breadcrumbName }}
          </span>
        </li>
      </ol>
    </nav>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center flex-grow-1">
        <router-link v-if="backTo" class="btn btn-subtle me-2 btn-back" :to="backTo" title="Voltar">
          <IconArrowLeft :size="18" />
        </router-link>

        <PageTitle :title="title" :subtitle="subtitle">
          <template v-if="$slots.subtitle" #subtitle>
            <slot name="subtitle"></slot>
          </template>
        </PageTitle>
      </div>

      <!-- Actions -->
      <div v-if="$slots.extra" class="d-flex align-items-center gap-2">
        <slot name="extra"></slot>
      </div>
    </div>
  </div>
</template>

<style scoped>
.btn-back {
  --bs-btn-padding-x: var(--bs-btn-padding-y);
  min-width: 2.125rem;
}
</style>
