<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { onClickOutside } from '@vueuse/core';

const props = defineProps({
  align: {
    type: String,
    default: 'start',
    validator: (v) => ['start', 'end'].includes(v),
  },
});

const emit = defineEmits(['open', 'close']);

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

  let top = triggerRect.bottom;
  let left;

  if (props.align === 'end') {
    left = triggerRect.right - menuRect.width;
  } else {
    left = triggerRect.left;
  }

  if (top + menuRect.height > viewportHeight) {
    top = triggerRect.top - menuRect.height;
  }

  if (left + menuRect.width > viewportWidth) {
    left = viewportWidth - menuRect.width - 8;
  }

  if (left < 0) {
    left = 8;
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
        :style="{
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
