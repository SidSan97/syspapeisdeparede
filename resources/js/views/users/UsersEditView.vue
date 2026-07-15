<template>
  <section class="content">
    <page
      title="Editar usuário"
      :back-to="{ name: 'UserList' }"
      v-if="auth.hasPermission('edit user')"
    >
      <div class="card">
        <div class="card-body">
          <form @submit.prevent="updateUser()">
            <div class="row">
              <div class="col-lg-12">
                <UsersForm
                  ref="formRef"
                  :roles="roles"
                  :is-edit="true"
                  :loading="loading"
                ></UsersForm>
              </div>
            </div>
            <hr />
            <div class="mt-4">
              <button type="submit" class="btn btn-primary me-2" :disabled="saving || loading">
                Salvar as alterações
              </button>
              <router-link :to="{ name: 'UserList' }" class="btn btn-subtle">Cancelar</router-link>
            </div>
          </form>
        </div>
      </div>
    </page>
    <EmptyState heading="Sem acesso" icon="lock" v-else>
      Desculpe, mas você não está permitido para visualizar isso.
    </EmptyState>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import UsersForm from './components/UsersForm.vue';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';

import { useAuthStore } from '@/stores/auth';

const $router = useRouter();
const $route = useRoute();
const auth = useAuthStore();

const Toast = window.Toast;
const formRef = ref();
const roles = ref([]);
const saving = ref(false);
const loading = ref(true);

async function fetchUser() {
  if (!auth.hasPermission('edit user')) return;

  try {
    loading.value = true;

    const { data } = await axios.get('v1/users/' + $route.params.id);

    const user = data.data;

    // Aguardar o componente estar disponível
    if (!formRef.value) {
      await new Promise((resolve) => setTimeout(resolve, 100));
    }

    if (formRef.value) {
      formRef.value.form.reset();

      // Garantir que is_dropshipping seja número (0 ou 1)
      const isDropshipping = Number(user.is_dropshipping) || 0;

      const userRole = user.roles && user.roles.length > 0 ? user.roles[0] : '';

      const walletBalance =
        user.wallet_balance !== undefined && user.wallet_balance !== null
          ? Number(user.wallet_balance)
          : 0;

      formRef.value.form.fill({
        id: user.id,
        name: user.name,
        email: user.email,
        role: userRole,
        is_dropshipping: isDropshipping,
        wallet_balance: Number.isFinite(walletBalance) ? walletBalance : 0,
        email_verified_at: user.email_verified_at,
      });
    }
  } catch (error) {
    Toast.fire({
      icon: 'error',
      title: error.response?.data?.message || 'Erro ao carregar usuário',
    });

    $router.push({ name: 'UserList' });
  } finally {
    loading.value = false;
  }
}

async function updateUser() {
  // Bloquear submissão enquanto está carregando os dados
  if (loading.value) {
    return;
  }

  try {
    saving.value = true;
    const userId = formRef.value.form.id;

    const response = await formRef.value.form.put(`v1/users/${userId}`);

    Toast.fire({ icon: 'success', title: 'Usuário atualizado com sucesso' });

    $router.push({ name: 'UserList' });
  } catch (error) {
    Toast.fire({
      icon: 'error',
      title: error.response?.data?.message || 'Erro ao atualizar usuário',
    });
  } finally {
    saving.value = false;
  }
}

function fetchRoles() {
  return axios.get('v1/roles/list').then(({ data }) => {
    const payload = data?.data ?? data ?? [];
    roles.value = Array.isArray(payload) ? payload : [];
  });
}

onMounted(async () => {
  await fetchRoles();
  await fetchUser();
});
</script>
