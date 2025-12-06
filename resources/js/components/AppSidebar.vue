<template>
  <aside class="bd-sidebar" :class="{ 'bd-sidebar-collapsed': isCollapsed }">
    <nav class="bd-sidebar-nav">
      <!-- Grupo 1: Início (Ativo) -->
      <div class="bd-sidebar-group">
        <RouterLink
          to="/dashboard"
          class="bd-sidebar-item"
          :class="{ 'active': isActiveRoute('/dashboard') || isActiveRoute('/') }"
        >
          <i class="fa fa-home"></i>
          <span class="bd-sidebar-text">Início</span>
        </RouterLink>
      </div>

      <hr class="bd-sidebar-divider">

      <!-- Grupo 2: Orçamentos e Pedidos -->
      <div class="bd-sidebar-group">
        <RouterLink
          to="/budget/new-budget"
          class="bd-sidebar-item"
          :class="{ 'active': isActiveRoute('/budget/new-budget') }"
        >
          <i class="fa fa-plus-circle"></i>
          <span class="bd-sidebar-text">Novo orçamento</span>
        </RouterLink>

        <RouterLink
          to="/budget"
          class="bd-sidebar-item"
          :class="{ 'active': isBudgetListActive }"
        >
          <i class="fa fa-file-alt"></i>
          <span class="bd-sidebar-text">Orçamentos</span>
        </RouterLink>

        <RouterLink
          to="/colecao-arts"
          class="bd-sidebar-item"
          :class="{ 'active': isActiveRoute('/colecao-arts') }"
        >
          <i class="fa fa-images"></i>
          <span class="bd-sidebar-text">Coleção Arts</span>
        </RouterLink>

        <RouterLink
          to="/pedidos"
          class="bd-sidebar-item"
          :class="{ 'active': isActiveRoute('/pedidos') }"
        >
          <i class="fa-lg me-3 fa fa-inbox"></i>
          <span class="bd-sidebar-text">Pedidos</span>
        </RouterLink>
      </div>

      <hr v-if="isAdmin || isProductionUser" class="bd-sidebar-divider">

      <!-- Grupo 3: Layouts -->
      <div v-if="isAdmin || isProductionUser" class="bd-sidebar-group">
        <RouterLink
          to="/layouts"
          class="bd-sidebar-item"
          :class="{ 'active': isActiveRoute('/layouts') }"
        >
          <i class="fa fa-paint-brush"></i>
          <span class="bd-sidebar-text">Layouts</span>
        </RouterLink>
      </div>

      <hr v-if="isAdmin || isProductionUser" class="bd-sidebar-divider">

      <!-- Grupo 4: Produção -->
      <div v-if="isAdmin || isProductionUser" class="bd-sidebar-group">
        <RouterLink
          to="/products"
          class="bd-sidebar-item"
          :class="{ 'active': isActiveRoute('/products') }"
        >
          <i class="fa fa-folder"></i>
          <span class="bd-sidebar-text">Produção</span>
        </RouterLink>
        <RouterLink
          to="/pedidos-producao"
          class="bd-sidebar-item"
          :class="{ 'active': isActiveRoute('/pedidos-producao') }"
        >
          <i class="fa-lg me-3 fa fa-inbox"></i>
          <span class="bd-sidebar-text">Pedidos</span>
        </RouterLink>
      </div>

      <hr v-if="!isTenant && !isProductionUser" class="bd-sidebar-divider">

      <!-- Grupo 5: Expedição -->
      <div v-if="!isTenant && !isProductionUser" class="bd-sidebar-group">
        <RouterLink
          to="/expedicao"
          class="bd-sidebar-item"
          :class="{ 'active': isActiveRoute('/expedicao') }"
        >
          <i class="fa fa-truck"></i>
          <span class="bd-sidebar-text">Expedição</span>
        </RouterLink>
      </div>

      <hr v-if="isAdmin" class="bd-sidebar-divider">

      <!-- Grupo 6: Configurações -->
      <div v-if="isAdmin" class="bd-sidebar-group">
        <RouterLink
          to="/settings"
          class="bd-sidebar-item"
          :class="{ 'active': isActiveRoute('/settings') }"
        >
          <i class="fa fa-cog"></i>
          <span class="bd-sidebar-text">Configurações</span>
        </RouterLink>
      </div>
    </nav>

    <!-- Botão para colapsar/expandir em mobile -->
    <button
      class="bd-sidebar-toggle d-lg-none"
      @click="toggleSidebar"
      type="button"
    >
      <i class="fa" :class="isCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
    </button>
  </aside>

  <!-- Overlay para mobile -->
  <div
    v-if="!isCollapsed"
    class="bd-sidebar-overlay d-lg-none"
    @click="toggleSidebar"
  ></div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { USER_TYPES } from '@/constants/userTypes';

const auth = useAuthStore();
const isCollapsed = ref(true); // Começar como true para evitar flash
const route = useRoute();

const isAdmin = computed(() => auth.user?.user_type_id === USER_TYPES.ADMIN);
const isTenant = computed(() => auth.user?.user_type_id === USER_TYPES.RESELLER);
const isProductionUser = computed(() => auth.user?.user_type_id === USER_TYPES.PRODUCTION);

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value;
};

// Expor método para acesso externo
defineExpose({
  toggleSidebar
});

const handleResize = () => {
  if (window.innerWidth >= 992) {
    isCollapsed.value = false;
  } else {
    isCollapsed.value = true;
  }
};

// Fechar sidebar em mobile quando a rota mudar
watch(() => route.path, () => {
  if (window.innerWidth < 992) {
    isCollapsed.value = true;
  }
});

const isActiveRoute = (path) => {
  return route.path === path || route.path.startsWith(path + '/');
};

const isBudgetListActive = computed(() => {
  const path = route.path;
  return path === '/budget' || (path.startsWith('/budget/') && !path.startsWith('/budget/new-budget') && !path.startsWith('/budget/edit/'));
});

onMounted(() => {
  handleResize();
  window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
});
</script>

<style lang="scss" scoped>
.bd-sidebar {
  position: fixed;
  top: 56px; 
  left: 0;
  height: calc(100vh - 56px);
  width: 250px;
  background-color: var(--bs-body-bg);
  border-right: 1px solid var(--bs-border-color);
  z-index: 1020;
  transition: transform 0.3s ease-in-out, background-color 0.2s ease, border-color 0.2s ease;
  overflow-y: auto;
  overflow-x: hidden;

  &-collapsed {
    transform: translateX(-100%);
  }

  &-nav {
    padding: 1rem 0;
  }

  &-group {
    padding: 0.25rem 0;
  }

  &-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: var(--bs-body-color);
    text-decoration: none;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
    font-size: 0.95rem;

    i {
      width: 20px;
      margin-right: 0.75rem;
      font-size: 1.1rem;
      color: var(--bs-secondary-color);
      transition: color 0.2s ease;
    }

    &-text {
      white-space: nowrap;
    }

    &:hover {
      background-color: var(--bs-tertiary-bg);
      color: var(--bs-emphasis-color);
      border-left-color: var(--bs-border-color);

      i {
        color: var(--bs-body-color);
      }
    }

    &.active {
      background-color: var(--bs-primary-bg-subtle);
      color: var(--bs-primary);
      border-left-color: var(--bs-primary);
      font-weight: 500;

      i {
        color: var(--bs-primary);
      }
    }
  }

  &-divider {
    margin: 0.5rem 1rem;
    border: 0;
    border-top: 1px solid var(--bs-border-color);
    opacity: 1;
  }

  &-toggle {
    position: absolute;
    top: 10px;
    right: -40px;
    width: 40px;
    height: 40px;
    background-color: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-left: none;
    border-radius: 0 0.375rem 0.375rem 0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 1001;
    color: var(--bs-body-color);
    transition: all 0.2s ease;

    &:hover {
      background-color: var(--bs-tertiary-bg);
      color: var(--bs-emphasis-color);
    }
  }
}

.bd-sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 999;
  transition: opacity 0.3s ease;
}

// Responsividade
@media (max-width: 991.98px) {
  .bd-sidebar {
    top: 56px;
    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
  }
}

@media (min-width: 992px) {
  .bd-sidebar {
    transform: translateX(0) !important;
  }

  .bd-sidebar-toggle,
  .bd-sidebar-overlay {
    display: none !important;
  }
}

// Scrollbar personalizada
.bd-sidebar::-webkit-scrollbar {
  width: 6px;
}

.bd-sidebar::-webkit-scrollbar-track {
  background: var(--bs-secondary-bg);
}

.bd-sidebar::-webkit-scrollbar-thumb {
  background: var(--bs-border-color);
  border-radius: 3px;

  &:hover {
    background: var(--bs-secondary-color);
  }
}
</style>
