<template>
  <div class="accordion-item bg-transparent">
    <h2 class="accordion-header">
      <button
        class="accordion-button bg-transparent"
        :class="{ collapsed: !isOpen }"
        type="button"
        data-bs-toggle="collapse"
        :data-bs-target="`#${id}`"
        :aria-expanded="isOpen ? 'true' : 'false'"
        :aria-controls="id"
        @click="emit('toggle')"
        :disabled="disabled"
      >
        <i class="fa me-2" :class="iconClass"></i>
        {{ label }}
      </button>
    </h2>
    <div class="accordion-collapse collapse" :id="id" :data-bs-parent="`#${parentId}`" :class="{ show: isOpen }">
      <div class="accordion-body">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  id: String,
  parentId: String,
  label: String,
  isOpen: Boolean,
  disabled: Boolean,
  iconUnlocked: {
    type: String,
    default: 'fa-lock-open'
  },
  iconLocked: {
    type: String,
    default: 'fa-lock text-muted'
  },
  isUnlocked: Boolean
});

const emit = defineEmits(['toggle']);

const iconClass = computed(() => (props.isUnlocked ? props.iconUnlocked : props.iconLocked));
</script>
