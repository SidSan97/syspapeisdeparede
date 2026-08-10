<template>
  <header class="navbar navbar-expand-lg py-0 sticky-top bd-navbar" style="z-index: 1030">
    <nav class="container-fluid px-md-3 px-lg-4">
      <!-- App Brand -->
      <a
        class="navbar-brand d-inline-flex align-items-center m-0 p-0 me-lg-6 me-xl-9 p-1 rounded text-reset"
        href="/"
      >
        <!-- FIXME: Obter name do config Laravel, ou mudar -->
        Arts
      </a>

      <div class="collapse navbar-collapse">
        <ul class="navbar-nav navbar-nav-underline ps-lg-5 flex-grow-1">
          <li v-for="item in mainNavItems" :key="item.title" class="nav-item">
            <RouterLink class="nav-link" :to="item.to" v-if="item.can">{{ item.title }}</RouterLink>
          </li>
        </ul>
      </div>
      <div class="d-flex align-items-center ms-auto gap-3 me-2 me-lg-3">
        <NavUser />

        <button
          class="navbar-toggler"
          type="bdNavbar"
          aria-label="Alternar naveção"
          @click="toggle"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            fill="currentcolor"
            viewBox="0 0 16 16"
          >
            <path
              fill-rule="evenodd"
              d="M2.5 11.5A.5.5.0 013 11h10a.5.5.0 010 1H3a.5.5.0 01-.5-.5zm0-4A.5.5.0 013 7h10a.5.5.0 010 1H3a.5.5.0 01-.5-.5zm0-4A.5.5.0 013 3h10a.5.5.0 010 1H3a.5.5.0 01-.5-.5z"
            ></path>
          </svg>
        </button>
      </div>
    </nav>
  </header>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import NavUser from './NavUser.vue';
import { useSidebar } from '@/composables/useSidebar';

const { toggle } = useSidebar();

const mainNavItems = [
  // Adicione itens de navegação aqui se necessário
];
</script>
