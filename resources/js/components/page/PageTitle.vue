<template>
    <div>
        <div class="d-flex align-items-center">
            <h1 v-if="title" class="o-page-title h4 mb-0">{{ title }}</h1>
            <slot name="titleMetadata"></slot>
        </div>
        <p v-if="subtitle || hasSubtitleSlot" class="text-muted mb-0">
            {{ subtitle }}
            <slot name="subtitle"></slot>
        </p>
    </div>
</template>

<script setup>
import { computed, useSlots } from 'vue'

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
})

// Slots
const slots = useSlots()

const hasSubtitleSlot = computed(() => !!slots.subtitle)
</script>
