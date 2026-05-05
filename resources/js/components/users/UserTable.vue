<template>
  <div class="table-responsive mt-3">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th scope="col">Usuário</th>
          <th scope="col">Papel</th>
          <th scope="col" class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="4" class="p-5 text-center text-muted fw-semibold">Carregando usuários...</td>
        </tr>
        <tr v-else-if="users.length === 0">
          <td colspan="4" class="p-5 text-center text-muted fw-semibold">
            Nenhum usuário encontrado.
          </td>
        </tr>
        <tr v-for="user in users" v-else :key="user.id">
          <td>
            <div class="d-flex align-items-center gap-3">
              <img :src="user.avatar_url" :alt="user.name" class="avatar" />
              <div>
                <div class="fw-semibold">{{ user.name }}</div>
                <div class="text-muted small">
                  {{ user.email }}
                </div>
              </div>
            </div>
          </td>
          <td>
            <span class="text-capitalize">
              {{ getUserRole(user) }}
            </span>
          </td>
          <td class="text-end">
            <div class="d-none d-sm-flex align-items-center justify-content-end">
              <router-link
                :to="{
                  name: 'settings.users.edit',
                  params: { id: user.id },
                }"
                class="btn btn-link btn-sm"
              >
                Editar
              </router-link>
              <span>·</span>
              <button
                type="button"
                @click.prevent="$emit('delete', user)"
                class="btn btn-link btn-sm"
                style="color: var(--ds-text-danger)"
              >
                Remover
              </button>
            </div>

            <BaseDropdown align="end">
              <template #trigger="{ open, toggle }">
                <button
                  :class="['btn btn-sm btn-subtle d-inline-block d-sm-none', { show: open }]"
                  type="button"
                  :aria-expanded="open"
                  @click="toggle"
                >
                  <IconDotsVertical :size="18" />
                </button>
              </template>

              <li>
                <router-link
                  class="dropdown-item"
                  :to="{
                    name: 'settings.users.edit',
                    params: { id: user.id },
                  }"
                >
                  Editar
                </router-link>
              </li>
              <li>
                <button
                  class="dropdown-item"
                  style="color: var(--ds-text-danger)"
                  type="button"
                  @click="$emit('delete', user)"
                >
                  Excluir
                </button>
              </li>
            </BaseDropdown>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { USER_ROLE_LABELS } from '@/constants/userRoles';
import BaseDropdown from '../common/BaseDropdown.vue';
import { IconDotsVertical } from '@tabler/icons-vue';

defineProps({
  users: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['delete']);

const getUserRole = (user) => (user.roles?.[0] ? USER_ROLE_LABELS[user.roles[0]] : '—');
</script>
