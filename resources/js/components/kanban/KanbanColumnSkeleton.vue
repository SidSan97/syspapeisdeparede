<script setup></script>

<template>
  <div v-for="n in 3" :key="n" class="card-placeholder mb-2">
    <div class="placeholder-glow">
      <div class="placeholder placeholder-lg w-100 mb-2"></div>
    </div>
  </div>
</template>

<style scoped>
.placeholder-lg {
  height: 120px;
  border-radius: 0.375rem;
}
</style>
