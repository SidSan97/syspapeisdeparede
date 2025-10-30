<template>
  <section class="content">
    <Page title="Meu perfil">
      <ProfileAccountCard></ProfileAccountCard>
      <ProfilePasswordCard></ProfilePasswordCard>
      <ProfileAvatarModal></ProfileAvatarModal>
    </Page>
  </section>
</template>

<script setup>
import { onMounted } from 'vue'
import Page from '@/components/page/Page.vue'
import ProfileAccountCard from './components/ProfileAccountCard.vue'
import ProfilePasswordCard from './components/ProfilePasswordCard.vue'
import ProfileAvatarModal from './components/ProfileAvatarModal.vue'

onMounted(() => {
  document.title = 'Meu perfil | ' + window.APP_NAME
})
</script>
