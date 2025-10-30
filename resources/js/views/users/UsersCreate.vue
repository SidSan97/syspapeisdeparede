<template>
  <section class="content">
    <page title="Criar novo usuário" v-if="auth.hasPermission('create users')">
      <div class="card">
        <div class="card-body">
          <form @submit.prevent="createUser()">
            <div class="row">
              <div class="col-lg-6">
                <UsersForm ref="formRef" :roles="roles"></UsersForm>
              </div>
            </div>
            <hr>
            <div class="mt-4">
              <button type="submit" class="btn btn-primary me-2" :disabled="saving">Salvar</button>
              <router-link :to="{ name: 'UsersList' }" class="btn btn-subtle">Cancelar</router-link>
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import Page from '@/components/page/Page.vue'
import EmptyState from '@/components/empty-state/EmptyState.vue'
import UsersForm from './components/UsersForm.vue'

import { useAuthStore } from '@/stores/auth';

const Toast = window.Toast
const auth = useAuthStore()
const router = useRouter()

const formRef = ref()
const roles = ref([])
const saving = ref(false)

async function createUser() {
  try {
    saving.value = true

    const response = await formRef.value.form.post('v1/users')

    Toast.fire({ icon: 'success', title: response.data.message })

    router.push({ name: 'UsersList' })
  } catch (error) {
    Toast.fire({
      icon: 'error',
      title: error.response.data.message
    })
  } finally {
    saving.value = false
  }
}

function fetchRoles() {
  return axios.get('v1/roles/list').then(({ data }) => {
    roles.value = data.data
  })
}

onMounted(async () => {
  await fetchRoles()
})
</script>
