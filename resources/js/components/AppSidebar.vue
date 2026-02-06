<template>
  <aside class="bd-sidebar border-end">
    <div class="sidebar">
      <div class="offcanvas-lg offcanvas-end d-lg-block py-3 py-lg-4" id="sidebar-nav">
        <div class="offcanvas-header p-3 d-sm-none">
          <button
            type="button"
            class="btn-close ms-auto"
            data-bs-toggle="offcanvas"
            data-bs-target="#sidebar-nav"
            aria-controls="sidebar-nav"
            aria-label="Close"
          />
        </div>

        <div v-for="(group, index) in visibleMenuGroups" :key="index" class="list-group border-0">
          <RouterLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="list-group-item list-group-item-action bg-transparent"
          >
            <i :class="['me-3', item.icon]"></i>

            {{ item.label }}
          </RouterLink>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

// Roles
const roles = {
  admin: computed(() => auth.isAdmin()),
  production: computed(() => auth.hasRole('production')),
  designer: computed(() => auth.hasRole('designer')),
  expedition: computed(() => auth.hasRole('expedition')),
  commercial: computed(() => auth.hasRole('commercial')),
};

// Configuração do menu
// FIXME: Usar permissões ao invés de papéis.
const menuGroups = [
  {
    visible: () => !roles.production.value,
    items: [
      {
        to: '/dashboard',
        label: 'Início',
        icon: 'fa fa-home',
      },
    ],
  },
  {
    visible: () =>
      !roles.designer.value &&
      !roles.expedition.value &&
      !roles.commercial.value &&
      !roles.production.value,
    items: [
      { to: '/budget/new-budget', label: 'Novo orçamento', icon: 'fa fa-plus-circle' },
      { to: '/budget', label: 'Orçamentos', icon: 'fa fa-file-alt' },
      { to: '/colecao-arts', label: 'Coleção Arts', icon: 'fa fa-images' },
      { to: '/pedidos', label: 'Pedidos', icon: 'fa fa-inbox' },
    ],
  },
  {
    visible: () => roles.admin.value || roles.designer.value,
    items: [{ to: '/layouts', label: 'Layouts', icon: 'fa fa-paint-brush' }],
  },
  {
    visible: () => roles.admin.value || roles.production.value,
    items: [{ to: '/products', label: 'Produção', icon: 'fa fa-folder' }],
  },
  {
    visible: () => roles.admin.value || roles.commercial.value,
    items: [{ to: '/pedidos-producao', label: 'Pedidos', icon: 'fa fa-inbox' }],
  },
  {
    visible: () => roles.admin.value || roles.expedition.value,
    items: [{ to: '/expedicao', label: 'Expedição', icon: 'fa fa-truck' }],
  },
  {
    visible: () => roles.admin.value,
    items: [{ to: '/settings', label: 'Configurações', icon: 'fa fa-cog' }],
  },
];

const visibleMenuGroups = computed(() => menuGroups.filter((group) => group.visible()));
</script>

<style lang="scss" scoped>
.sidebar {
  position: relative;
  display: flex;
  flex-direction: column;
  font-size: 14px;

  @media (min-width: 992px) {
    position: sticky;
    height: calc(100vh - 56px);
    width: 242px;
    top: 56px;
    overflow-y: auto;
  }

  @media (min-width: 1200px) {
    width: 272px;
  }
}
</style>
