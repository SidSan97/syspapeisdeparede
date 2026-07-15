<script setup>
import { useAuthStore } from '@/stores/auth';
import BaseDropdown from '@/components/common/BaseDropdown.vue';

// Icons
import { IconLogout, IconUser, IconWallet } from '@tabler/icons-vue';

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
  <BaseDropdown v-if="user">
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

    <div class="dropdown-item-text">
      <div class="d-flex gap-3">
        <img class="avatar avatar-lg rounded-circle" :src="user.avatar_url" :alt="user.name" />
        <div>
          <strong class="text-truncate">{{ user.name }}</strong>
          <p class="text-muted m-0 small">{{ user.email }}</p>
        </div>
      </div>
    </div>

    <hr class="dropdown-divider" />

    <RouterLink :to="{ name: 'Profile' }" class="dropdown-item">
      <IconUser :size="18" class="me-2" />
      <span>Perfil</span>
    </RouterLink>

    <!-- <RouterLink to="/carteira" class="dropdown-item">
      <IconWallet :size="18" class="me-2" />
      <span>Ver saldo</span>
    </RouterLink> -->

    <a class="dropdown-item" href="#" @click.prevent="handleLogout">
      <IconLogout :size="18" class="me-2" />
      <span>Sair</span>
    </a>
  </BaseDropdown>
</template>
