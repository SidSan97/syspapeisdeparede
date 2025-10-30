<template>
  <div class="accordion" :id="id">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  id: {
    type: String,
    default: 'accordion'
  }
});
</script>
