<script setup>
import { computed } from 'vue';

const props = defineProps({
  count: {
    type: Number,
    required: true,
    validator: (value) => value >= 0,
  },
  selectedLabel: {
    type: String,
    default: 'item selecionado',
  },
  selectedPluralLabel: {
    type: String,
    default: 'itens selecionados',
  },
  position: {
    type: String,
    default: 'bottom-center',
    validator: (value) => ['bottom-center', 'bottom-left', 'bottom-right'].includes(value),
  },
  offset: {
    type: Object,
    default: () => ({ bottom: '1rem', left: 'auto', right: 'auto' }),
  },
  showCloseButton: {
    type: Boolean,
    default: true,
  },
  autoHideDelay: {
    type: Number,
    default: 0, // 0 = never auto hide
  },
});

const emit = defineEmits(['close', 'clear']);

// Computed properties
const selectedText = computed(() => {
  const label = props.count === 1 ? props.selectedLabel : props.selectedPluralLabel;
  return `${props.count} ${label}`;
});

const shouldAutoHide = computed(() => props.autoHideDelay > 0 && props.count > 0);

// Auto-hide logic
let autoHideTimer = null;

function startAutoHideTimer() {
  if (!shouldAutoHide.value) return;

  if (autoHideTimer) clearTimeout(autoHideTimer);
  autoHideTimer = setTimeout(() => {
    emit('close');
  }, props.autoHideDelay);
}

function stopAutoHideTimer() {
  if (autoHideTimer) {
    clearTimeout(autoHideTimer);
    autoHideTimer = null;
  }
}

// Watch for count changes to reset timer
import { watch } from 'vue';
watch(
  () => props.count,
  (newCount) => {
    if (newCount > 0 && shouldAutoHide.value) {
      startAutoHideTimer();
    }
  },
);

// Position classes
const positionClasses = computed(() => ({
  'bottom-0 end-0 start-0': props.position === 'bottom-center',
  'bottom-0 start-0': props.position === 'bottom-left',
  'bottom-0 end-0': props.position === 'bottom-right',
}));

// Style overrides
const containerStyle = computed(() => ({
  bottom: props.offset.bottom,
  left:
    props.offset.left !== 'auto'
      ? props.offset.left
      : props.position === 'bottom-left'
        ? '1rem'
        : 'auto',
  right:
    props.offset.right !== 'auto'
      ? props.offset.right
      : props.position === 'bottom-right'
        ? '1rem'
        : 'auto',
}));

// Handlers
function handleClose() {
  emit('close');
  emit('clear');
  stopAutoHideTimer();
}

// Lifecycle
import { onBeforeUnmount } from 'vue';
onBeforeUnmount(() => {
  if (autoHideTimer) clearTimeout(autoHideTimer);
});
</script>

<template>
  <Transition name="slide-up">
    <div
      v-if="count > 0"
      class="selection-bar position-fixed"
      :class="positionClasses"
      :style="containerStyle"
      role="status"
      aria-live="polite"
      @mouseenter="stopAutoHideTimer"
      @mouseleave="startAutoHideTimer"
    >
      <div class="container-fluid px-3">
        <div class="row justify-content-center">
          <div class="col-md-auto">
            <div class="selection-bar__content">
              <button
                v-if="showCloseButton"
                type="button"
                class="btn-close btn-close-sm"
                aria-label="Fechar barra de seleção"
                @click="handleClose"
              ></button>

              <div class="selection-bar__info">
                <span>{{ selectedText }}</span>
              </div>

              <div class="selection-bar__actions">
                <slot name="actions"></slot>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.selection-bar {
  z-index: 1050;
  margin: 0 0 1rem 0;
  pointer-events: none;
}

.selection-bar__content {
  background-color: var(--ds-surface, #fff);
  border: 1px solid var(--ds-border, #e0e0e0);
  border-radius: 0.75rem;
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  pointer-events: auto;
}

.selection-bar__info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  color: var(--ds-text, #212529);
}

.selection-bar__actions {
  display: flex;
  gap: 0.5rem;
}

/* Animations */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .selection-bar {
    margin-bottom: 0.5rem;
  }

  .selection-bar__content {
    padding: 0.5rem 0.75rem;
    gap: 0.75rem;
    font-size: 0.875rem;
  }
}

/* Dark mode support */
[data-bs-theme='dark'] .selection-bar__content {
  background-color: var(--ds-surface-dark, #2c2c2c);
  border-color: var(--ds-border-dark, #404040);
}
</style>
