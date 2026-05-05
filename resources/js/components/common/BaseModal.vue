<!-- Modal.vue -->
<template>
  <Teleport to="body">
    <!-- Backdrop -->
    <div
      v-if="isOpen"
      class="modal-backdrop fade"
      :class="{ show: isOpen, 'd-block': isOpen }"
      @click="handleBackdropClick"
    ></div>

    <!-- Modal -->
    <div
      v-if="isOpen"
      class="modal fade"
      :class="{ show: isOpen, 'd-block': isOpen }"
      :style="{ display: isOpen ? 'block' : 'none' }"
      tabindex="-1"
      role="dialog"
      @click.self="handleBackdropClick"
    >
      <div
        class="modal-dialog"
        :class="[
          size ? `modal-${size}` : '',
          centered ? 'modal-dialog-centered' : '',
          scrollable ? 'modal-dialog-scrollable' : '',
        ]"
      >
        <div class="modal-content">
          <!-- Header -->
          <div v-if="showHeader" class="modal-header">
            <slot name="header">
              <h5 class="modal-title">{{ title }}</h5>
            </slot>
            <button
              v-if="showCloseButton"
              type="button"
              class="btn-close"
              @click="close"
              :aria-label="closeButtonLabel"
            ></button>
          </div>

          <!-- Body -->
          <div class="modal-body">
            <slot name="body">
              <p>{{ content }}</p>
            </slot>
          </div>

          <!-- Footer -->
          <div v-if="showFooter" class="modal-footer">
            <slot name="footer">
              <button v-if="showCancelButton" type="button" class="btn btn-subtle" @click="close">
                {{ cancelButtonText }}
              </button>
              <button
                v-if="showConfirmButton"
                type="button"
                class="btn btn-primary"
                @click="confirm"
              >
                {{ confirmButtonText }}
              </button>
            </slot>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { watch, onUnmounted, computed } from 'vue';

// Props
const props = defineProps({
  // Controle de visibilidade
  modelValue: {
    type: Boolean,
    default: false,
  },

  // Conteúdo simples
  title: {
    type: String,
    default: '',
  },
  content: {
    type: String,
    default: '',
  },

  // Tamanhos: 'sm', 'lg', 'xl'
  size: {
    type: String,
    default: null,
    validator: (value) => ['sm', 'lg', 'xl'].includes(value),
  },

  // Comportamento
  centered: {
    type: Boolean,
    default: false,
  },
  scrollable: {
    type: Boolean,
    default: false,
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true,
  },
  closeOnEscape: {
    type: Boolean,
    default: true,
  },

  // Botões padrão
  showHeader: {
    type: Boolean,
    default: true,
  },
  showFooter: {
    type: Boolean,
    default: true,
  },
  showCloseButton: {
    type: Boolean,
    default: true,
  },
  showCancelButton: {
    type: Boolean,
    default: true,
  },
  showConfirmButton: {
    type: Boolean,
    default: true,
  },
  cancelButtonText: {
    type: String,
    default: 'Cancelar',
  },
  confirmButtonText: {
    type: String,
    default: 'Confirmar',
  },
  closeButtonLabel: {
    type: String,
    default: 'Fechar',
  },
});

// Emits
const emit = defineEmits(['update:modelValue', 'close', 'confirm']);

// Computed para controle interno
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

// Funções
const close = () => {
  isOpen.value = false;
  emit('close');
};

const confirm = () => {
  emit('confirm');
  close();
};

const handleBackdropClick = () => {
  if (props.closeOnBackdrop) {
    close();
  }
};

// Gerenciar evento de tecla ESC
const handleEscapeKey = (event) => {
  if (props.closeOnEscape && event.key === 'Escape' && isOpen.value) {
    close();
  }
};

// Prevenir scroll do body quando modal está aberto
watch(isOpen, (newValue) => {
  if (newValue) {
    document.body.classList.add('modal-open');
    document.addEventListener('keydown', handleEscapeKey);
  } else {
    document.body.classList.remove('modal-open');
    document.removeEventListener('keydown', handleEscapeKey);
  }
});

// Cleanup
onUnmounted(() => {
  document.body.classList.remove('modal-open');
  document.removeEventListener('keydown', handleEscapeKey);
});
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1040;
  width: 100vw;
  height: 100vh;
  background-color: #000;
}

.modal-backdrop.fade {
  opacity: 0;
  transition: opacity 0.15s linear;
}

.modal-backdrop.show {
  opacity: 0.5;
}

.modal.fade {
  transition: opacity 0.15s linear;
}

.modal.show {
  overflow-x: hidden;
  overflow-y: auto;
}
</style>
