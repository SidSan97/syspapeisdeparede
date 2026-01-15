<template>
  <section class="content">
    <Page title="Usuários" back-to="/settings" v-if="auth.hasPermission('view users')">
      <template #actions>
        <router-link
          v-if="auth.hasPermission('view users')"
          :to="{ name: 'UsersCreate' }"
          class="btn btn-primary"
        >
          Adicionar usuário
        </router-link>
      </template>

      <div class="shadow-sm">
        <div class="border-0 pb-0">
          <div class="d-flex flex-column gap-3">
            <div class="row buttons-filters mt-3">
              <div class="col-lg-4">
                <div class="input-group input-group-prefix">
                  <input type="search" class="form-control" placeholder="Pesquisar usuário"
                   v-model="searchQuery"
                  >
                  <span class="input-group-text">
                    <i class="fa fa-search"></i>
                  </span>
                </div>
              </div>

              <div class="dropdown col-lg-3 mt-2 mt-lg-0">
                <button
                    class="btn btn-outline-default d-flex align-items-center gap-2"
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
        </div>

        <div class="card-body p-0">
          <div v-if="loading" class="p-5 text-center text-muted fw-semibold">
            Carregando usuários...
          </div>

          <EmptyState
            v-else-if="users.length === 0"
            heading="Nenhum usuário encontrado"
            icon="user"
            class="p-5"
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
                <tr v-for="(user, index) in users" :key="user.id">
                  <td class="fw-semibold">{{ (paginationData.current_page - 1) * paginationData.per_page + index + 1 }}</td>
                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <div
                        v-if="!user.avatar"
                        class="rounded-circle d-flex align-items-center justify-content-center user-avatar"
                      >
                        {{ getUserInitial(user.name) }}
                      </div>
                      <img
                        v-else
                        :src="getAvatarUrl(user.avatar)"
                        :alt="user.name"
                        class="rounded-circle user-avatar-img"
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
                        class="btn btn-sm btn-subtle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                      >
                        <i class="fa fa-ellipsis-v"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm">
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

          <div v-if="!loading && users.length > 0 && paginationData.last_page > 1" class="p-3">
            <pagination
              :data="paginationData"
              @pagination-change-page="fetchUsers"
            />
          </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import NotFound from '@/components/NotFound.vue';
import { useAuthStore } from '@/stores/auth';
import { swalConfirmation } from '../../../utils/alerts';
import debounce from 'lodash/debounce'

const router = useRouter();
const auth = useAuthStore();

const users = ref([]);
const typeUsers = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const selectedRoleId = ref(null);
const paginationData = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
});

const debouncedFetch = debounce(() => {
  fetchUsers(1);
}, 500);

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
  fetchUsers(1);
};

const fetchUsers = async (page = 1) => {
  if (!auth.hasPermission('view users')) return;

  loading.value = true;
  try {
    const params = {
      page,
      per_page: 15,
    };

    // Adicionar filtro de busca
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim();
    }

    // Adicionar filtro de tipo de usuário
    if (selectedRoleId.value !== null) {
      params.user_type_id = selectedRoleId.value;
    }

    const { data } = await axios.get('v1/users', { params });

    if (data?.success && data?.data) {
      const items = Array.isArray(data.data.data) ? data.data.data : [];
      users.value = items;

      // Atualizar dados de paginação
      paginationData.value = {
        current_page: data.data.current_page || 1,
        last_page: data.data.last_page || 1,
        per_page: data.data.per_page || 15,
        total: data.data.total || 0,
        from: data.data.from || 0,
        to: data.data.to || 0,
      };
    } else {
      users.value = [];
      paginationData.value = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0,
      };
    }
  } catch (error) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar os usuários. Tente novamente.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    users.value = [];
    paginationData.value = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0,
    };
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
    // Recarregar a página atual após exclusão
    fetchUsers(paginationData.value.current_page);
    window.Swal.fire({
      title: 'Usuário excluído!',
      text: 'Usuário excluído com sucesso.',
      confirmButtonText: 'Entendi!',
    });
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível excluir o usuário. Tente novamente.';
    window.Swal.fire({
      title: 'Erro!',
      text: message,
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  }
};

onMounted(async () => {
  document.title = 'Usuários';
  await fetchTypeUsers();
  fetchUsers(1);
});

watch(searchQuery, () => {
  debouncedFetch();
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

.user-avatar {
  width: 40px;
  height: 40px;
  font-weight: 600;
  font-size: 1rem;
  background-color: #6f42c1;
  color: white;
  flex-shrink: 0;
}

.user-avatar-img {
  width: 40px;
  height: 40px;
  object-fit: cover;
  flex-shrink: 0;
}

.table thead th {
  border-bottom: 1px solid var(--bs-border-color);
  font-weight: 600;
  color: var(--bs-body-color);
}

.table tbody tr {
  border-bottom: 1px solid var(--bs-border-color);
}

.table tbody tr:hover {
  background-color: var(--bs-secondary-bg);
}

.dropdown-menu {
  border: 1px solid var(--bs-border-color);
  background-color: var(--bs-dropdown-bg);
}

.dropdown-item {
  color: var(--bs-dropdown-color);
  padding: 0.5rem 1rem;
}

.dropdown-item:hover {
  background-color: var(--bs-dropdown-link-hover-bg);
  color: var(--bs-dropdown-link-hover-color);
}

.dropdown-item.text-danger {
  color: var(--bs-danger);
}

.dropdown-item.text-danger:hover {
  background-color: var(--bs-danger-bg-subtle);
  color: var(--bs-danger);
}
</style>
