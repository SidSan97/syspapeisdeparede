<script setup>
import { useSidebar } from '@/composables/useSidebar';
import AppSidebar from '../components/AppSidebar.vue';
import AppHeader from '../components/AppHeader.vue';

const { isOpen, close } = useSidebar();
</script>

<template>
  <AppHeader />
  <div class="bd-layout">
    <AppSidebar :is-open="isOpen" @close="close" />
    <main class="bd-main">
      <router-view />
    </main>
  </div>
</template>

<style scoped lang="scss">
.bd-layout {
  display: flex;
  flex-direction: column;

  @media (min-width: 992px) {
    display: grid;
    grid-template-areas: 'sidebar main';
    grid-template-columns: min-content 1fr;
    gap: 0;
  }
}

.bd-main {
  grid-area: main;
  display: grid;
  grid-template-areas:
    'intro'
    'content'
    'footer';
  grid-template-rows: auto 1fr auto;
}
</style>
