import { computed, ref } from 'vue';

export function useAvatar(props) {
  const imageError = ref(false);

  const initials = computed(() => {
    if (!props.name) return '';

    return props.name
      .split(' ')
      .slice(0, 2)
      .map((p) => p[0])
      .join('')
      .toUpperCase();
  });

  const hasImage = computed(() => {
    return props.src && !imageError.value;
  });

  const avatarType = computed(() => {
    if (hasImage.value) return 'image';

    if (props.icon) return 'icon';

    return 'initials';
  });

  function onImageError() {
    imageError.value = true;
  }

  return {
    initials,
    avatarType,
    onImageError,
  };
}
