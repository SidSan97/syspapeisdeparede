<template>
  <div :class="containerClass">
    <div class="row g-0">
      <div class="col-12 mt-3">
        <PageHeader :title="title" :subtitle="subtitle" :back-to="backTo">
          <template #titleMetadata>
            <slot name="titleMetadata"></slot>
          </template>
          <template #actions>
            <slot name="actions"></slot>
          </template>
          <template v-if="hasSubtitleSlot" #subtitle>
            <slot name="subtitle"></slot>
          </template>
        </PageHeader>

        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue'
import PageHeader from './PageHeader.vue'

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
   * Largura total?
   */
  fullWidth: Boolean,

  /**
   * Voltar para.
   */
  backTo: [String, Object],
})

// Computed
const containerClass = computed(() =>
  props.fullWidth ? 'container-fluid' : 'container'
)

// Slots
const slots = useSlots()

const hasSubtitleSlot = computed(() => !!slots.subtitle)
</script>
