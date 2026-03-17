<template>
  <header class="navbar navbar-expand-lg py-0 sticky-top bd-navbar" style="z-index: 1030">
    <nav class="container-fluid px-md-3 px-lg-4">
      <!-- App Brand -->
      <a
        class="navbar-brand d-inline-flex align-items-center m-0 p-0 me-lg-6 me-xl-9 p-1 rounded text-reset"
        href="/"
      >
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
        <NavTheme />

        <RouterLink :to="'/settings'" class="btn btn-subtle px-2" v-if="auth.user">
          <i class="fa fa-cog"></i>
        </RouterLink>

        <NavUser />

        <button
          class="navbar-toggler"
          type="bdNavbar"
          data-bs-toggle="offcanvas"
          data-bs-target="#sidebar-nav"
          aria-controls="sidebar-nav"
          aria-label="Toggle navigation"
        >
          <i class="fa fa-bars"></i>
        </button>
      </div>
    </nav>
  </header>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import NavUser from './NavUser.vue';
import NavTheme from './NavTheme.vue';
import { useAuthStore } from '@/stores/auth';

const mainNavItems = [
  // Adicione itens de navegação aqui se necessário
];

const auth = useAuthStore();
</script>
