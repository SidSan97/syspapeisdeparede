<script setup>
import { IconLogout, IconSettings } from '@tabler/icons-vue';
import NavTheme from './NavTheme.vue';
import { useAuthStore } from '@/stores/auth';
import BaseDropdown from '@/components/common/BaseDropdown.vue';

const { user } = useAuthStore();

const handleLogout = () => {
  // Criar formulário de logout
  const form = document.createElement('form');
  form.method = 'POST';
  const baseUrl = window.LaravelApp?.baseUrl || '';
  form.action = `${baseUrl}/logout`;

  const csrfToken = document.querySelector('meta[name="csrf-token"]');
  if (csrfToken) {
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken.getAttribute('content');
    form.appendChild(csrfInput);
  }

  document.body.appendChild(form);
  form.submit();
};
</script>

<template>
  <BaseDropdown v-if="user" style="--bs-dropdown-min-width: 19rem">
    <template #trigger="{ open, toggle }">
      <button
        class="btn btn-subtle p-1"
        :class="{ show: open }"
        role="button"
        aria-haspopup="true"
        :aria-expanded="open"
        @click.prevent="toggle"
      >
        <img class="avatar avatar-sm rounded-circle" :src="user.avatar_url" :alt="user.name" />
      </button>
    </template>

    <li><h6 class="dropdown-header text-uppercase fs-xs">Conta</h6></li>
    <div class="py-1 px-3"></div>

    <div class="dropdown-item-text pt-0">
      <div class="d-flex gap-3">
        <img class="avatar avatar-lg rounded-circle" :src="user.avatar_url" :alt="user.name" />
        <div>
          <span class="text-truncate">{{ user.name }}</span>
          <p class="text-muted m-0 small">{{ user.email }}</p>
        </div>
      </div>
    </div>

    <RouterLink :to="{ name: 'Profile' }" class="dropdown-item">
      <IconSettings :size="18" class="me-2" />
      Configurações da conta
    </RouterLink>

    <NavTheme />

    <hr class="dropdown-divider" />

    <a class="dropdown-item" href="#" @click.prevent="handleLogout">
      <IconLogout :size="18" class="me-2" />
      Fazer logout</a
    >
  </BaseDropdown>
</template>
