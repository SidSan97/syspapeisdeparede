<template>
  <section class="content">
    <div class="row">
      <div class="col-lg-6 mx-auto">
        <Page
          title="Editar usuário"
          :back-to="{ name: 'settings.users.list' }"
          :breadcrumbs="routes"
          v-if="canEdit"
        >
          <form @submit.prevent="updateUser()">
            <UserForm ref="formRef" :loading="userStore.loadingUserById"></UserForm>
            <div class="mt-4">
              <button
                type="submit"
                class="btn btn-primary me-2"
                :disabled="saving || userStore.loadingUserById"
              >
                Salvar as alterações
              </button>
            </div>
          </form>
        </Page>
        <EmptyState heading="Sem acesso" :icon="IconLock" v-else>
          Desculpe, mas você não está permitido para visualizar isso.
        </EmptyState>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, nextTick, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import UserForm from '@/components/users/UserForm.vue';

import { IconLock } from '@tabler/icons-vue';

import { useAuthStore } from '@/stores/auth';
import { useUserStore } from '@/stores/userStore';
import { userService } from '@/services/userService';
import { useToast } from '@/composables/useToast';

const formRef = ref(null);
const saving = ref(false);

const router = useRouter();
const route = useRoute();
const toast = useToast();
const authStore = useAuthStore();
const userStore = useUserStore();

const canEdit = computed(() => authStore.hasPermission('edit user'));

const routes = [
  { path: '/settings', breadcrumbName: 'Configurações' },
  { path: '/settings/users', breadcrumbName: 'Usuários' },
  { path: '#', breadcrumbName: 'Editar' },
];

async function fetchUser() {
  if (!canEdit.value) return;

  try {
    await userStore.loadUserById(route.params.id);

    await nextTick();

    if (!formRef.value) return;

    const user = userStore.currentUser;

    formRef.value.form.reset();
    formRef.value.form.fill({
      id: user.id,
      name: user.name,
      email: user.email,
      role: user.roles?.[0] || '',
      is_dropshipping: Boolean(user.is_dropshipping),
      wallet_balance: parseFloat(user.wallet_balance ?? 0),
    });
  } catch (error) {
    toast.error('Erro ao carregar o usuário');
    router.push({ name: 'settings.users.list' });
  }
}

async function updateUser() {
  if (userStore.loadingUserById) return;

  try {
    saving.value = true;

    const userId = formRef.value.form.id;

    await userService.update(userId, formRef.value.form.data());

    toast.success('Usuário atualizado com sucesso');

    router.push({ name: 'settings.users.list' });
  } catch (error) {
    formRef.value.form.handleErrors(error);
    toast.error('Erro ao atualizar usuário');
  } finally {
    saving.value = false;
  }
}

watch(() => route.params.id, fetchUser, { immediate: true });
</script>
