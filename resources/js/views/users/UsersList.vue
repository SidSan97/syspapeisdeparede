<template>
  <section class="content">
    <Page title="Usuários" v-if="auth.hasPermission('view users')">
      <template #actions>
        <router-link v-if="auth.hasPermission('create users')" :to="{ name: 'UsersCreate' }" class="btn btn-default">
          <i class="fa fa-plus"></i> Criar usuário
        </router-link>
      </template>

      <div>
        <form class="row g-3 align-items-center mb-4" role="search">
          <label for="search-query" class="sr-only">Pesquisar usuário</label>
          <div class="col-12 col-md-auto">
            <div class="input-group input-group-prefix">
              <input id="search-query" type="text" v-model="searchQuery" class="form-control"
                placeholder="Pesquisar usuário" />
              <span class="input-group-text">
                <i class="fa fa-search"></i>
              </span>
            </div>
          </div>

          <div class="col-12 col-sm-auto">
            <div class="dropdown">
              <button class="btn btn-subtle dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                {{ searchUserTypeLabel() }}
              </button>

              <ul class="dropdown-menu">
                <li><button class="dropdown-item" type="button" @click="searchUserType = ''; searchUsers()"
                    :disabled="!searchUserType">Todos</button></li>
                <li v-for="role in filteredRoles" :key="role.id">
                  <button class="dropdown-item" type="button" @click="searchUserType = role.name; searchUsers()"
                    :disabled="searchUserType === role.name">
                    {{ translateRolename(role.name) }}
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </form>


        <div class="row" v-if="!ready">
          <div class="col-lg-6">
            <div class="d-flex align-items-center">
              <span class="placeholder rounded-circle bd-h-10 bd-w-10"></span>
              <div class="ms-3 flex-grow-1">
                <span class="placeholder placeholder-xs col-10"></span>
                <span class="placeholder placeholder-xs col-4"></span>
              </div>
            </div>
          </div>
        </div>
        <div class="card" v-else>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover m-0" v-if="haveUsers">
                <thead>
                  <tr>
                    <th class="text-nowrap" scope="col">Nome</th>
                    <th class="text-nowrap" scope="col" style="width: 64px">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in users.data" :key="user.id">
                    <td style="min-width: 240px;">
                      <router-link class="d-flex" :to="{
                        name: 'UsersEdit',
                        params: { id: user.id }
                      }">
                        <img v-if="user.avatar" :src="$asset(`storage/${user.avatar}`)" alt=""
                          class="avatar flex-shrink-0 me-2" loading="lazy">
                        <span v-else class="avatar flex-shrink-0 text-uppercase bg-discovery me-2">{{ user.name[0]
                          }}</span>
                        <div>
                          <span class="text-body font-weight-bold mb-0 blue">
                            {{ user.name }}
                          </span>
                          <div class="text-muted small">
                            {{ user.email }}
                          </div>
                        </div>
                      </router-link>
                    </td>
                    <td>
                      <a class="btn btn-link text-danger" href="#" @click="deleteUser(user.id)">Excluir</a>
                    </td>
                  </tr>
                </tbody>
              </table>
              <EmptyState heading="Nenhum usuário encontrado" icon="user" class="p-4" v-else>
                Tente pesquisar por um usuário diferente.
              </EmptyState>
            </div>
          </div>
        </div>
      </div>
      <div v-if="haveUsers">
        <pagination :data="users" :limit="4" @pagination-change-page="getResults"></pagination>
      </div>
    </Page>
    <div v-else>
      <not-found></not-found>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import _, { findLastKey } from 'lodash';

import { useConfirm } from '@/composables/useConfirm.js';
import { useAuthStore } from '@/stores/auth';

import EmptyState from '@/components/empty-state/EmptyState.vue';
import Page from '@/components/page/Page.vue';
import { useUtils } from '@/composables/useUtils';

const auth = useAuthStore()
const { debouncer } = useUtils()
const confirm = useConfirm()

const ready = ref(false);
const users = ref({ data: [] });
const searchQuery = ref('');
const searchUserType = ref('');
const roles = ref([]);

const haveUsers = computed(() => !_.isEmpty(users.value.data));

async function getResults(page = 1) {
  if (!auth.hasPermission('view users')) return;

  
  try {
    ready.value = false;

    const { data } = await axios.get(`v1/users?page=${page}`)

    users.value = data.data;
  } catch (error) {
  } finally {
    ready.value = true;
  }
}

const debouncedSearch = debouncer(() => {
  if (searchQuery.value.trim() !== '') {
    searchUsers();
  } else {
    fetchUsers();
  }
})

function deleteUser(id) {
  if (!auth.hasPermission('delete users')) return;

  confirm.modal({
    title: 'Excluir usuário?',
    text: 'Se você excluir o usuário, não será possível recuperá-lo.',
    acceptLabel: 'Excluir usuário',
    onConfirm: async () => {
      try {
        const response = await axios.delete(`v1/users/${id}`);

        Toast.fire({
          icon: 'success',
          title: response.data.message
        });

        fetchUsers();
      } catch (error) {
        Swal.fire('Falhou!', error.response.data.message, 'warning');
      }
    }
  })
}

async function fetchUsers() {
  if (!auth.hasPermission('view users')) return;

  try {
    ready.value = false;

    const {data} = await axios.get('v1/users')

    users.value = data.data;
  } catch (error) {
  } finally {
    ready.value = true;
  }
}

async function searchUsers() {
  if (!auth.hasPermission('view users')) return;
  
  try {
    ready.value = false;

    const { data } = await axios.get('v1/users/search', {
      params: {
        name: searchQuery.value,
        type: searchUserType.value
      }
    });

    users.value = data.data;
  } catch (error) {
    Swal.fire({
      title: 'Oops!',
      text: 'A busca falhou, aguarde alguns instantes e tente novamente.',
      icon: 'warning'
    });
  } finally {
    ready.value = true;
  }
}

function fetchRoles() {
  return axios
    .get('v1/roles/list')
    .then(({ data }) => {
      roles.value = data.data
    })
}

const roleNames = {
  'super admin': 'Superadministrador',
  'admin': 'Administrador',
}

const translateRolename = (roleName) => {
  if ('' === roleName) return 'Nível de acesso'
  return roleNames[roleName] || roleName
}

const filteredRoles = computed(() => {
  return roles.value.filter(role => {
    if (!role) return false;

    if (role.name.toLowerCase() === 'super admin' && !auth.hasRole('super admin')) {
      return false
    }

    return true
  });
})

function userType(val) {
  val = val.toString();

  switch (val) {
    case 'admin':
      return 'Administrador';
    case 'super admin':
      return 'Superadministrador';
    case 'user':
      return 'Usuário';
    default:
      return 'Nível de acesso';
  }
}

function searchUserTypeLabel() {
  return userType(searchUserType.value)
}

watch(searchQuery, () => {
  debouncedSearch();
});

onMounted(async () => {
  document.title = 'Usuários | ' + APP_NAME;
  await fetchRoles()
  fetchUsers();
});
</script>
