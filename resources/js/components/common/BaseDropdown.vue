<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { onClickOutside } from '@vueuse/core';

const props = defineProps({
  align: {
    type: String,
    default: 'start',
    validator: (v) => ['start', 'end'].includes(v),
  },
  direction: {
    type: String,
    default: 'down',
    validator: (v) => ['down', 'up', 'start', 'end'].includes(v),
  },
});

const emit = defineEmits(['open', 'close']);

defineOptions({
  inheritAttrs: false,
});

const isOpen = ref(false);

const triggerRef = ref(null);
const menuRef = ref(null);
const dropdownRef = ref(null);

const styles = ref({
  top: '0px',
  left: '0px',
});

onClickOutside(dropdownRef, () => close(), {
  ignore: [menuRef],
});

function open() {
  if (!isOpen.value) {
    isOpen.value = true;
    emit('open');

    nextTick(() => {
      requestAnimationFrame(() => positionMenu());
    });
  }
}

function close() {
  if (isOpen.value) {
    isOpen.value = false;
    emit('close');
  }
}

function toggle() {
  isOpen.value ? close() : open();
}

function positionMenu() {
  const trigger = triggerRef.value;
  const menu = menuRef.value;

  if (!trigger || !menu) return;

  const triggerRect = trigger.getBoundingClientRect();
  const menuRect = menu.getBoundingClientRect();

  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  const margin = 8;

  let top = 0;
  let left = 0;

  switch (props.direction) {
    case 'up':
      top = triggerRect.top - menuRect.height;

      left = props.align === 'end' ? triggerRect.right - menuRect.width : triggerRect.left;

      // Flip para baixo
      if (top < margin) {
        top = triggerRect.bottom;
      }
      break;

    case 'start':
      top = triggerRect.top;

      left = triggerRect.left - menuRect.width;

      // Flip para direita
      if (left < margin) {
        left = triggerRect.right;
      }
      break;

    case 'end':
      top = triggerRect.top;

      left = triggerRect.right;

      // Flip para esquerda
      if (left + menuRect.width > viewportWidth - margin) {
        left = triggerRect.left - menuRect.width;
      }
      break;

    case 'down':
    default:
      top = triggerRect.bottom;

      left = props.align === 'end' ? triggerRect.right - menuRect.width : triggerRect.left;

      // Flip para cima
      if (top + menuRect.height > viewportHeight - margin) {
        top = triggerRect.top - menuRect.height;
      }
      break;
  }

  // Mantém dentro da viewport horizontalmente
  if (left < margin) {
    left = margin;
  }

  if (left + menuRect.width > viewportWidth - margin) {
    left = viewportWidth - menuRect.width - margin;
  }

  // Mantém dentro da viewport verticalmente
  if (top < margin) {
    top = margin;
  }

  if (top + menuRect.height > viewportHeight - margin) {
    top = viewportHeight - menuRect.height - margin;
  }

  styles.value = {
    top: `${top + window.scrollY}px`,
    left: `${left + window.scrollX}px`,
  };
}

function handleResize() {
  if (isOpen.value) {
    positionMenu();
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize);
  window.addEventListener('scroll', handleResize, true);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize);
  window.removeEventListener('scroll', handleResize, true);
});
</script>

<template>
  <div ref="dropdownRef" class="dropdown">
    <div ref="triggerRef">
      <slot name="trigger" :open="isOpen" :toggle="toggle" />
    </div>

    <Teleport to="body">
      <ul
        v-show="isOpen"
        ref="menuRef"
        class="dropdown-menu show"
        v-bind="$attrs"
        :style="{
          ...$attrs.style,
          position: 'absolute',
          zIndex: 9999,
          ...styles,
          visibility: isOpen ? 'visible' : 'hidden',
        }"
      >
        <slot :close="close" :open="isOpen" />
      </ul>
    </Teleport>
  </div>
</template>
