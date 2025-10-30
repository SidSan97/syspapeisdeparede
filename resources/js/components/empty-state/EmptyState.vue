<template>
  <div class="blankslate">
    <i v-if="icon" :class="iconClass" class="fa-3x text-muted m-4"></i>
    <div class="blankslate-body">
      <h4 v-if="heading">{{ heading }}</h4>
      <p>
        <slot></slot>
      </p>
    </div>
    <div v-if="hasActionsSlot" class="blankslate-actions">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue'

const slots = useSlots()

// Props
const props = defineProps({
  icon: String,
  iconStyle: String,
  heading: String,
})

// Computed
const iconStyleClass = computed(() => {
  switch (props.iconStyle) {
    case 'solid':
      return 'fas'
    case 'regular':
      return 'far'
    case 'brands':
      return 'fab'
    default:
      return 'fa'
  }
})

const hasActionsSlot = computed(() => !!slots.actions)

const iconClass = computed(() => `${iconStyleClass.value} fa-${props.icon}`)
</script>
