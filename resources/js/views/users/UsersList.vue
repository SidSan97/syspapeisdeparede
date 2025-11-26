<template>
  <section class="content">
    <Page title="Usuários" back-to="/settings" v-if="auth.hasPermission('view users')">
      <template #actions>
        <router-link
          v-if="auth.hasPermission('create users')"
          :to="{ name: 'UsersCreate' }"
          class="btn btn-primary"
        >
          <i class="fa fa-plus me-2"></i>
          Adicionar usuário
        </router-link>
      </template>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pb-0">
          <div class="d-flex flex-column flex-md-row gap-3 mb-4">
            <div class="flex-grow-1">
              <div class="input-group input-group-lg">
                <span class="input-group-text bg-body-secondary border border-secondary">
                  <i class="fa fa-search text-muted"></i>
                </span>
                <input
                  v-model="searchQuery"
                  type="search"
                  class="form-control border border-secondary bg-body-secondary"
                  placeholder="Pesquisar usuário"
                  aria-label="Pesquisar usuário"
                />
              </div>
            </div>

            <div class="dropdown">
              <button
                class="btn btn-outline-secondary btn-lg d-flex align-items-center gap-2"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                {{ selectedRoleLabel }}
                <i class="fa fa-chevron-down small"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                  <button
                    class="dropdown-item"
                    type="button"
                    :class="{ active: selectedRoleId === null }"
                    @click="setRoleFilter(null)"
                  >
                    Todos os papéis
                  </button>
                </li>
                <li v-for="role in typeUsers" :key="role.id">
                  <button
                    class="dropdown-item"
                    type="button"
                    :class="{ active: selectedRoleId === role.id }"
                    @click="setRoleFilter(role.id)"
                  >
                    {{ role.name }}
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="card-body p-0">
          <div v-if="loading" class="p-5 text-center text-muted fw-semibold">
            Carregando usuários...
          </div>

          <EmptyState
            v-else-if="filteredUsers.length === 0"
            heading="Nenhum usuário encontrado"
            icon="user"
            class="p-5"
          >
            Ajuste os filtros ou adicione um novo usuário.
          </EmptyState>

          <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Usuário</th>
                  <th scope="col">Papel</th>
                  <th scope="col" class="text-end">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in filteredUsers" :key="user.id">
                  <td class="fw-semibold">{{ user.id }}</td>
                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <div
                        v-if="!user.avatar"
                        class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; font-weight: 600; font-size: 1rem;"
                      >
                        {{ getUserInitial(user.name) }}
                      </div>
                      <img
                        v-else
                        :src="getAvatarUrl(user.avatar)"
                        :alt="user.name"
                        class="rounded-circle"
                        style="width: 40px; height: 40px; object-fit: cover;"
                      />
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
                        class="btn btn-sm btn-outline-secondary"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                      >
                        <i class="fa fa-ellipsis-h"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                          <router-link
                            class="dropdown-item"
                            :to="{ name: 'UsersEdit', params: { id: user.id } }"
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
        </div>
      </div>
    </Page>
    <NotFound v-else />
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import NotFound from '@/components/NotFound.vue';
import { useAuthStore } from '@/stores/auth';
import { swalConfirmation, swalError, swalSuccess } from '../../../utils/alerts';
import { useUtils } from '@/composables/useUtils';

const router = useRouter();
const auth = useAuthStore();
const { debouncer } = useUtils();

const users = ref([]);
const typeUsers = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const selectedRoleId = ref(null);

const filteredUsers = computed(() => {
  let filtered = users.value;

  // Filtrar por busca
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.trim().toLowerCase();
    filtered = filtered.filter(
      (user) =>
        user.name?.toLowerCase().includes(query) ||
        user.email?.toLowerCase().includes(query) ||
        String(user.id).includes(query)
    );
  }

  // Filtrar por papel (user_type_id)
  if (selectedRoleId.value !== null) {
    filtered = filtered.filter((user) => user.user_type_id === selectedRoleId.value);
  }

  return filtered;
});

const selectedRoleLabel = computed(() => {
  if (selectedRoleId.value === null) {
    return 'Filtrar por papel...';
  }
  const role = typeUsers.value.find((r) => r.id === selectedRoleId.value);
  return role ? role.name : 'Filtrar por papel...';
});

const getUserInitial = (name) => {
  if (!name) return '?';
  return name.charAt(0).toUpperCase();
};

const getAvatarUrl = (avatarPath) => {
  if (!avatarPath) return '';
  const baseUrl = window.location.origin.replace(/\/$/, '');
  return `${baseUrl}/storage/${avatarPath.replace(/^\//, '')}`;
};

const getUserRole = (user) => {
  if (user.user_type && user.user_type.name) {
    return user.user_type.name;
  }
  if (user.userType && user.userType.name) {
    return user.userType.name;
  }
  return '—';
};

const setRoleFilter = (roleId) => {
  selectedRoleId.value = roleId;
};

const fetchUsers = async () => {
  if (!auth.hasPermission('view users')) return;

  loading.value = true;
  try {
    const { data } = await axios.get('v1/users');
    const payload = data?.data ?? {};
    // Laravel paginate retorna { data: [...], current_page, total, etc }
    const items = payload.data ?? [];

    users.value = Array.isArray(items) ? items : [];
  } catch (error) {
    swalError('Não foi possível carregar os usuários. Tente novamente.');
    users.value = [];
  } finally {
    loading.value = false;
  }
};

const fetchTypeUsers = async () => {
  try {
    const { data } = await axios.get('v1/type-users/list');
    const payload = data?.data ?? data ?? [];
    typeUsers.value = Array.isArray(payload) ? payload : [];
  } catch (error) {
    typeUsers.value = [];
  }
};

const confirmDelete = async (user) => {
  if (!auth.hasPermission('delete users')) return;

  const result = await swalConfirmation(
    'Excluir usuário?',
    'Essa ação é <strong>irreversível!</strong>',
    'warning',
    'Excluir',
    'Cancelar'
  );

  if (result.isConfirmed) {
    await deleteUser(user);
  }
};

const deleteUser = async (user) => {
  if (!user?.id) return;

  try {
    await axios.delete(`v1/users/${user.id}`);
    users.value = users.value.filter((item) => item.id !== user.id);
    swalSuccess('Usuário excluído com sucesso.');
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível excluir o usuário. Tente novamente.';
    swalError(message);
  }
};

const debouncedSearch = debouncer(() => {
  // A busca é feita via computed filteredUsers
});

watch(searchQuery, () => {
  debouncedSearch();
});

onMounted(async () => {
  document.title = 'Usuários';
  await fetchTypeUsers();
  fetchUsers();
});
</script>

<style scoped>
.input-group-text {
  border-radius: 0.375rem 0 0 0.375rem;
}

.input-group .form-control {
  border-radius: 0 0.375rem 0.375rem 0;
}

.input-group .form-control:focus {
  border-color: var(--bs-secondary);
  box-shadow: none;
}

.input-group-lg .input-group-text,
.input-group-lg .form-control {
  padding-block: 0.85rem;
}
</style>
