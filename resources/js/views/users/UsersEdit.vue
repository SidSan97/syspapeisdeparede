<template>
  <section class="content">
    <page title="Editar usuário" v-if="auth.user?.user_type_id === 2">
      <div class="card">
        <div class="card-body">
          <form @submit.prevent="updateUser()">
            <div class="row">
              <div class="col-lg-12">
                <UsersForm ref="formRef" :type-users="typeUsers" :is-edit="true"></UsersForm>
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

const Toast = window.Toast
const formRef = ref()
const typeUsers = ref([])
const saving = ref(false)

async function fetchUser() {
  if (auth.user?.user_type_id !== 2) return;

  try {
    saving.value = true

    const { data } = await axios.get('v1/users/' + $route.params.id);

    const user = data.data

    formRef.value.form.reset()
    formRef.value.form.fill({
      id: user.id,
      name: user.name,
      email: user.email,
      user_type_id: user.user_type_id,
      email_verified_at: user.email_verified_at,
    })
  } catch (error) {
    Toast.fire({
      icon: 'error',
      title: error.response?.data?.message || 'Erro ao carregar usuário'
    })

    $router.push({ name: 'UsersList' })
  } finally {
    saving.value = false
  }
}

async function updateUser() {
  // Validar formulário antes de submeter
  if (!formRef.value.validateForm()) {
    return
  }

  try {
    saving.value = true
    const userId = formRef.value.form.id

    const response = await formRef.value.form.put(`v1/users/${userId}`)

    Toast.fire({ icon: 'success', title: response.data.message })

    $router.push({ name: 'UsersList' })
  } catch (error) {
    Toast.fire({
      icon: 'error',
      title: error.response?.data?.message || 'Erro ao atualizar usuário'
    })
  } finally {
    saving.value = false
  }
}

function fetchTypeUsers() {
  return axios.get('v1/type-users/list').then(({ data }) => {
    const payload = data?.data ?? data ?? []
    typeUsers.value = Array.isArray(payload) ? payload : []
  })
}

onMounted(async () => {
  await fetchTypeUsers()
  await fetchUser()
})
</script>
