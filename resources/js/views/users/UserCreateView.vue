<template>
  <section class="content">
    <div class="row">
      <div class="col-lg-6 mx-auto">
        <Page
          title="Criar novo usuário"
          :back-to="{ name: 'settings.users.list' }"
          :breadcrumbs="routes"
          v-if="auth.hasPermission('create user')"
        >
          <form @submit.prevent="createUser()">
            <UserForm ref="formRef"></UserForm>

            <div class="mt-4">
              <button type="submit" class="btn btn-primary me-2" :disabled="saving">Salvar</button>
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
import { ref } from 'vue';
import { useRouter } from 'vue-router';

import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import UserForm from '@/components/users/UserForm.vue';

import { IconLock } from '@tabler/icons-vue';

import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';
import { userService } from '@/services/userService';

const toast = useToast();
const auth = useAuthStore();
const router = useRouter();

const formRef = ref();
const saving = ref(false);

const routes = [
  { path: '/settings', breadcrumbName: 'Configurações' },
  { path: '/settings/users', breadcrumbName: 'Usuários' },
  { path: '/settings/users/create', breadcrumbName: 'Criar' },
];

async function createUser() {
  try {
    saving.value = true;

    await userService.create(formRef.value.form.data());

    toast.success('Usuário criado com sucesso!');

    router.push({ name: 'settings.users.list' });
  } catch (error) {
    formRef.value.form.handleErrors(error);
    toast.error('Opa! Algo deu errado ao criar o usuário.');
  } finally {
    saving.value = false;
  }
}
</script>
