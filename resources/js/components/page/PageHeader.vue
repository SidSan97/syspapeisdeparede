<template>
  <div class="d-flex align-items-center mb-3">
    <router-link v-if="backTo" :to="backTo" class="btn btn-subtle me-2" title="Voltar">
      <i class="fa fa-chevron-left"></i>
    </router-link>

    <!-- Page Title -->
    <PageTitle :title="title" :subtitle="subtitle">
      <template #titleMetadata>
        <slot name="titleMetadata"></slot>
      </template>
      <template v-if="hasSubtitleSlot" #subtitle>
        <slot name="subtitle"></slot>
      </template>
    </PageTitle>

    <!-- Actions -->
    <div v-if="hasActionsSlot" class="ms-auto">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue'
import PageTitle from './PageTitle.vue'

// Props
const props = defineProps({
  /**
   * Título da página.
   */
  title: String,

  /**
   * Subtítulo da página.
   */
  subtitle: String,

  /**
   * Voltar para.
   */
  backTo: [String, Object],
})

// Slots
const slots = useSlots()

const hasActionsSlot = computed(() => !!slots.actions)
const hasSubtitleSlot = computed(() => !!slots.subtitle)
</script>
