<template>
  <section class="content">
    <Page :title="`Olá! ${user.name}`"></Page>
  </section>
</template>

<script setup>
import { computed } from 'vue';

import Page from '@/components/page/Page.vue';

const user = computed(() => window.LaravelApp.user);
</script>
