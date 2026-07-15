<template>
  <section class="content">
    <Page title="Configurações da conta">
      <h3 class="h5 mb-4">Detalhes do perfil</h3>

      <ProfileInfoCard />

      <ProfilePasswordCard />
    </Page>
  </section>
</template>

<script setup>
import Page from '@/components/page/Page.vue';
import ProfileInfoCard from './components/ProfileInfoCard.vue';
import ProfilePasswordCard from './components/ProfilePasswordCard.vue';
</script>
