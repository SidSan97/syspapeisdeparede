<template>
  <AppHeader></AppHeader>
  <!--div class="bd-layout">
    <AppSidebar ref="sidebarRef"></AppSidebar>
    <main class="bd-main-content">

    </main>
  </div-->
    <div class="d-flex">
        <AppSidebar ref="sidebarRef"></AppSidebar>

        <main>
            <router-view :key="route.fullPath"></router-view>
        </main>
    </div>
</template>

<script setup>
import { ref, provide } from 'vue';
import { useRoute } from 'vue-router';
import AppSidebar from '../components/AppSidebar.vue';
import AppHeader from '../components/AppHeader.vue';

const route = useRoute();
const sidebarRef = ref(null);

const toggleSidebar = () => {
  if (sidebarRef.value) {
    sidebarRef.value.toggleSidebar();
  }
};

// Expor toggleSidebar para componentes filhos via provide
provide('toggleSidebar', toggleSidebar);
</script>

<style lang="scss">
    main {
        padding-top: 15px;
        margin-left: 250px;
        width: 80%;
    }

    @media (max-width: 992px) {
        main {
            margin-left: 0px;
            width: 100%;
        }
    }
</style>
