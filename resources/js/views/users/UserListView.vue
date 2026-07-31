<template>
  <section class="content">
    <div class="row" v-if="authStore.can('users.view')">
      <div class="col-lg-7 mx-auto">
        <Page title="Usuários" :breadcrumbs="routes">
          <template #extra>
            <button
              v-if="authStore.can('users.create')"
              type="button"
              class="btn btn-subtle"
              title="Sincronizar clientes com o Tiny"
              :disabled="syncing"
              @click="syncResellers"
            >
              <span v-if="syncing" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              <IconCloudDown v-else :size="18" />
            </button>
            <router-link
              v-if="authStore.can('users.view')"
              :to="{ name: 'settings.users.create' }"
              class="btn btn-primary"
            >
              Criar usuário
            </router-link>
          </template>
          <UserFilters v-model="filters" @search="fetchUsers" />
          <UserTable :users="userList" :loading="loading" @delete="confirmDelete" />
          <Bootstrap5Pagination
            :data="userStore.users"
            @pagination-change-page="goToPage"
            class="justify-content-center mt-3"
          />
        </Page>
      </div>
    </div>
    <NotFound v-else />
  </section>
</template>

<script setup>
import { IconCloudDown } from '@tabler/icons-vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { onMounted, ref } from 'vue';

import NotFound from '@/components/NotFound.vue';
import Page from '@/components/page/Page.vue';

import UserFilters from '@/components/users/UserFilters.vue';
import UserTable from '@/components/users/UserTable.vue';

import { useDialog } from '@/composables/useDialog';
import { useToast } from '@/composables/useToast';
import { useUsersList } from '@/composables/useUsersList';
import { resellerService } from '@/services/resellerService';
import { useAuthStore } from '@/stores/auth';
import { useUserStore } from '@/stores/userStore';

const dialog = useDialog();
const toast = useToast();
const authStore = useAuthStore();
const userStore = useUserStore();

const routes = [
  { path: '/settings', breadcrumbName: 'Configurações' },
  { path: '/settings/users', breadcrumbName: 'Usuários' },
];

const { filters, loading, userList, fetchUsers, goToPage, deleteUser } = useUsersList();

const syncing = ref(false);

const syncResellers = async () => {
  syncing.value = true;

  try {
    await resellerService.sync();

    toast.success('Sincronização com o Tiny iniciada.');
  } catch (error) {
    toast.error('Erro ao iniciar a sincronização com o Tiny. Tente novamente.');
  } finally {
    syncing.value = false;
  }
};

const confirmDelete = async (user) => {
  if (!authStore.can('users.delete')) return;

  const confirmed = await dialog.confirmDelete({
    title: 'Excluir usuário?',
    html: 'Tem certeza que deseja excluir o usuário? Esta ação não pode ser desfeita.',
  });

  if (confirmed) deleteUser(user);
};

onMounted(() => {
  document.title = 'Usuários';

  fetchUsers();
});
</script>
