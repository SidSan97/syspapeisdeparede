<template>
  <section class="content">
    <Page title="Usuários" back-to="/settings" v-if="auth.can('users.view')">
      <template #actions>
        <router-link
          v-if="auth.can('users.view')"
          :to="{ name: 'UserCreate' }"
          class="btn btn-primary"
        >
          <i class="fa fa-plus"></i>

          Criar usuário
        </router-link>
      </template>

      <div class="row g-3 mt-1">
        <div class="col-lg-4">
          <div class="input-group input-group-prefix">
            <input
              type="search"
              class="form-control"
              placeholder="Pesquisar usuário"
              v-model="searchQuery"
            />
            <span class="input-group-text">
              <i class="fa fa-search"></i>
            </span>
          </div>
        </div>

        <div class="col-lg-3">
          <div class="dropdown">
            <button
              class="btn btn-outline-default dropdown-toggle"
              type="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              {{ selectedRoleLabel }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <button
                  class="dropdown-item"
                  type="button"
                  :class="{ active: selectedRoleName === null }"
                  @click="setRoleFilter(null)"
                >
                  Todos os papéis
                </button>
              </li>
              <li v-for="role in roles" :key="role.id">
                <button
                  class="dropdown-item"
                  type="button"
                  :class="{ active: selectedRoleName === role.name }"
                  @click="setRoleFilter(role.name)"
                >
                  {{ translateRole(role.name) }}
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div v-if="loading" class="p-5 text-center text-muted fw-semibold mt-3">
        Carregando usuários...
      </div>

      <EmptyState
        v-else-if="users.data.length === 0"
        heading="Nenhum usuário encontrado"
        icon="user"
        class="p-5 mt-3"
      >
        Ajuste os filtros ou adicione um novo usuário.
      </EmptyState>

      <div v-else class="table-responsive mt-3">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Usuário</th>
              <th scope="col">Papel</th>
              <th scope="col" class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users.data" :key="user.id">
              <th scope="row">{{ user.id }}</th>
              <td>
                <div class="d-flex align-items-center gap-3">
                  <img :src="user.avatar_url" :alt="user.name" class="avatar" />
                  <div>
                    <div class="fw-semibold">{{ user.name }}</div>
                    <div class="text-muted small">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="text-capitalize">
                  {{ getUserRole(user) }}
                </span>
              </td>
              <td class="text-end">
                <div class="dropdown">
                  <button
                    class="btn btn-sm btn-subtle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i class="fa fa-ellipsis-v fa-fw"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <router-link
                        class="dropdown-item"
                        :to="{ name: 'UserEdit', params: { id: user.id } }"
                      >
                        Editar
                      </router-link>
                    </li>
                    <li>
                      <button
                        class="dropdown-item text-danger"
                        type="button"
                        @click="confirmDelete(user)"
                      >
                        Excluir
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <Bootstrap5Pagination
        :data="users"
        @pagination-change-page="fetchUsers"
        class="justify-content-center mt-3"
      />
    </Page>
    <NotFound v-else />
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import NotFound from '@/components/NotFound.vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { useAuthStore } from '@/stores/auth';
import { swalConfirmation } from '@/utils/alerts';
import { translateRole } from '@/utils/roleTranslations';

const auth = useAuthStore();

const users = ref({ data: [] });
const roles = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const selectedRoleName = ref(null);

// ------------------ Computed ------------------
const selectedRoleLabel = computed(() =>
  selectedRoleName.value ? translateRole(selectedRoleName.value) : 'Filtrar por papel...',
);

// ------------------ Utilities ------------------
const showError = (title = 'Erro!', text = 'Ocorreu um erro.') => {
  window.Swal.fire({ title, text, icon: 'error', confirmButtonText: 'Entendi!' });
};

const showSuccess = (title = 'Sucesso!', text = '') => {
  window.Swal.fire({ title, text, confirmButtonText: 'Entendi!' });
};

const getUserRole = (user) => (user.roles?.[0] ? translateRole(user.roles[0]) : '—');

// ------------------ API Calls ------------------
const fetchRoles = async () => {
  try {
    const { data } = await axios.get('v1/roles/list');
    roles.value = Array.isArray(data?.data ?? data) ? (data?.data ?? data) : [];
  } catch {
    roles.value = [];
  }
};

const fetchUsers = async (page = 1) => {
  if (!auth.can('users.view')) return;

  loading.value = true;
  try {
    const params = { page, role: selectedRoleName.value, search: searchQuery.value.trim() };
    const { data } = await axios.get('v1/users', { params });
    users.value = data;
  } catch {
    showError('Não foi possível carregar os usuários. Tente novamente.');
    users.value = { data: [] };
  } finally {
    loading.value = false;
  }
};

// ------------------ Actions ------------------
const setRoleFilter = (roleName) => {
  selectedRoleName.value = roleName;
  fetchUsers(1);
};

const deleteUser = async (user) => {
  if (!user?.id) return;

  try {
    await axios.delete(`v1/users/${user.id}`);
    await fetchUsers(1);
    showSuccess('Usuário excluído!', 'Usuário excluído com sucesso.');
  } catch (error) {
    const message = error?.response?.data?.message ?? 'Erro ao excluir o usuário. Tente novamente.';
    showError('Erro!', message);
  }
};

const confirmDelete = async (user) => {
  if (!auth.can('users.delete')) return;

  const result = await swalConfirmation(
    'Excluir usuário?',
    'Essa ação é <strong>irreversível!</strong>',
    'warning',
    'Excluir',
    'Cancelar',
  );

  if (result.isConfirmed) deleteUser(user);
};

// ------------------ Debounced Search ------------------
const debouncedFetch = debounce(() => fetchUsers(1), 500);

watch(searchQuery, debouncedFetch);

// ------------------ Lifecycle ------------------
onMounted(async () => {
  document.title = 'Usuários';
  await fetchRoles();
  fetchUsers(1);
});
</script>
