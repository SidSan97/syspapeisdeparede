<script setup>
import { computed } from 'vue';
import { useAvatar } from '@/composables/useAvatar';

const props = defineProps({
  src: String,

  alt: {
    type: String,
    default: 'avatar',
  },

  name: String,

  icon: Object, // Tabler icon component

  size: {
    type: String,
    default: 'md',
  },

  variant: {
    type: String,
    default: 'secondary',
  },

  rounded: {
    type: String,
    default: 'circle',
  },
});

const { initials, avatarType, onImageError } = useAvatar(props);

const sizeClass = computed(() => {
  const sizes = {
    xs: 'avatar-xs',
    sm: 'avatar-sm',
    md: '',
    lg: 'avatar-lg',
    xl: 'avatar-xl',
    xxl: 'avatar-xxl',
  };

  return sizes[props.size] || '';
});

const roundedClass = computed(() => {
  if (props.rounded === 'circle') return 'rounded-circle';

  if (props.rounded === 'square') return 'rounded-0';

  return `rounded-${props.rounded}`;
});

const variantClass = computed(() => {
  if (avatarType.value === 'image') return '';

  return `text-bg-${props.variant}`;
});
</script>

<template>
  <div class="avatar" :class="[sizeClass, roundedClass, variantClass]">
    <img v-if="avatarType === 'image'" :src="src" :alt="alt" @error="onImageError" />
    <component v-else-if="avatarType === 'icon'" :is="icon" class="avatar-icon" />
    <span v-else class="avatar-text">{{ initials }}</span>
    <slot />
  </div>
</template>

<style scoped>
.avatar-icon {
  display: unset;
  height: unset;
  width: unset;
  object-fit: unset;
  border-radius: unset;
}
</style>
