<template>
  <div class="bd-layout">
    <div class="bd-sidebar border-end">
      <div class="sidebar">
        <div class="navbar navbar-expand-lg border-0">
          <button class="btn btn-default d-md-none" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar" aria-expanded="false"
            aria-label="Alternar navegação">
            <i class="fa fa-sort me-2"></i> Configurações
          </button>
  
          <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar"
            aria-labelledby="offcanvasSidebarLabel">
            <div class="offcanvas-header">
              <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasSidebar"
                aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
              <ul class="list-group border-0 w-100 p-2">
                <RouterLink class="list-group-item list-group-item-action" :to="item.to"
                  v-for="(item, idx) in allowedMenuItems" :key="idx">
                  <i class="fa-solid fa-lg fa-fw me-3" :class="`fa-${item.icon}`"></i> {{ item.label }}
                </RouterLink>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="bd-main">
      <RouterView />
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

const menuItems = [
  { label: 'Usuários', to: { name: 'UsersList' }, icon: 'user', can: 'view users' },
  { label: 'Desenvolvedor', to: 'developer', icon: 'code' },
];

const allowedMenuItems = menuItems.filter(
  item => !item.can || auth.hasPermission(item.can)
);
</script>
