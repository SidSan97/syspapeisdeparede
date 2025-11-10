<template>
  <section class="content">
    <page title="Editar usuário" v-if="auth.hasPermission('edit users')">
      <div class="card">
        <div class="card-body">
          <form @submit.prevent="updateUser()">
            <div class="row">
              <div class="col-lg-6">
                <UsersForm ref="formRef" :roles="roles"></UsersForm>
              </div>
            </div>
            <hr>
            <div class="mt-4">
              <button type="submit" class="btn btn-primary me-2" :disabled="saving">Salvar as alterações</button>
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
import { useRoute, useRouter } from 'vue-router'

import UsersForm from './components/UsersForm.vue'
import Page from '@/components/page/Page.vue'
import EmptyState from '@/components/empty-state/EmptyState.vue'

import { useAuthStore } from '@/stores/auth'

const $router = useRouter()
const $route = useRoute()
const auth = useAuthStore()

const formRef = ref()
const roles = ref([])
const saving = ref(false)

async function fetchUser() {
  if (!auth.hasPermission('edit users')) return;

  try {
    saving.value = true

    const { data } = await axios.get('v1/users/' + $route.params.id);

    const user = data.data

    formRef.value.form.reset()
    formRef.value.form.fill(user)
    formRef.value.form.role = user.roles[0]?.name
  } catch (error) {    
    Toast.fire({
      icon: 'error',
      title: error.response?.data?.message
    })

    $router.push({ name: 'UsersList' })
  } finally {
    saving.value = false
  }
}

async function updateUser() {
  try {
    const userId = formRef.value.form.id

    const response = await formRef.value.form.put(`v1/users/${userId}`)

    Toast.fire({ icon: 'success', title: response.data.message })

    $router.push({ name: 'UsersList' })
  } catch (error) {
    Toast.fire({
      icon: 'error',
      title: error.response.data.message
    })
  }
}

function fetchRoles() {
  return axios
    .get('v1/roles/list')
    .then(({ data }) => {
      roles.value = data.data
    })
}

onMounted(async () => {
  await fetchRoles()
  await fetchUser()
})
</script>
