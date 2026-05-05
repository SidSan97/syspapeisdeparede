<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

// Icons
import {
  IconFile,
  IconFolder,
  IconHome,
  IconInbox,
  IconPaint,
  IconPhoto,
  IconPlus,
  IconSettings,
  IconTruck,
} from '@tabler/icons-vue';
import { useSidebar } from '@/composables/useSidebar';

defineProps({
  isOpen: Boolean,
});

const authStore = useAuthStore();

const { close } = useSidebar();

// Roles
const roles = {
  admin: computed(() => authStore.isAdmin()),
  production: computed(() => authStore.hasRole('production')),
  designer: computed(() => authStore.hasRole('designer')),
  expedition: computed(() => authStore.hasRole('expedition')),
  commercial: computed(() => authStore.hasRole('commercial')),
  reseller: computed(() => authStore.hasRole('reseller')),
};

// Configuração do menu
// FIXME: Usar permissões ao invés de papéis.
const menuGroups = [
  {
    visible: () => true,
    items: [
      {
        to: '/dashboard',
        label: 'Início',
        icon: IconHome,
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
      {
        to: {
          name: 'budgets.create',
        },
        label: 'Novo orçamento',
        icon: IconPlus,
      },
      {
        to: {
          name: 'budgets.list',
        },
        label: 'Orçamentos',
        icon: IconFile,
      },
      {
        to: '/colecao-arts',
        label: 'Coleção Arts',
        icon: IconPhoto,
      },
    ],
  },
  {
    visible: () =>
      roles.admin.value || roles.commercial.value || roles.production.value || roles.reseller.value,
    items: [
      {
        to: {
          name: 'orders.list',
        },
        label: 'Pedidos',
        icon: IconInbox,
      },
    ],
  },
  {
    visible: () => roles.admin.value || roles.designer.value,
    items: [{ to: '/layouts', label: 'Layouts', icon: IconPaint }],
  },
  {
    visible: () => roles.admin.value || roles.production.value,
    items: [
      {
        to: { name: 'production.board' },
        label: 'Produção',
        icon: IconFolder,
      },
    ],
  },
  {
    visible: () => roles.admin.value || roles.expedition.value,
    items: [
      {
        to: '/expedicao',
        label: 'Expedição',
        icon: IconTruck,
      },
    ],
  },
  {
    visible: () => roles.admin.value,
    items: [
      {
        to: {
          name: 'settings.home',
        },
        label: 'Configurações',
        icon: IconSettings,
      },
    ],
  },
];

const visibleMenuGroups = computed(() => menuGroups.filter((group) => group.visible()));
</script>

<template>
  <aside class="bd-sidebar border-end" v-if="authStore.user">
    <div class="sidebar">
      <div
        class="offcanvas-lg offcanvas-end d-lg-block py-3 py-lg-4"
        id="sidebar-nav"
        :class="{ show: isOpen }"
      >
        <div class="offcanvas-header p-3 d-sm-none">
          <button
            type="button"
            class="btn-close ms-auto"
            aria-label="Fechar"
            @click="close"
          ></button>
        </div>

        <div v-for="(group, index) in visibleMenuGroups" :key="index" class="list-group border-0">
          <RouterLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="list-group-item list-group-item-action"
          >
            <component :is="item.icon" :size="18" class="me-3" />

            {{ item.label }}

            <span v-if="item.debug" class="badge text-bg-warning"> debug </span>
          </RouterLink>
        </div>
      </div>
    </div>
  </aside>

  <div v-if="isOpen" class="offcanvas-backdrop fade show d-md-none" @click="close" />
</template>

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

.list-group-item {
  --bs-list-group-bg: transparent;
}
</style>
