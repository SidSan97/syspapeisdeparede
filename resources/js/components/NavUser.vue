<script setup>
import { useAuthStore } from '@/stores/auth';

const { user } = useAuthStore();

const getAvatarUrl = () => {
  if (!user) return '';

  const assetUrl = window.LaravelApp?.assetUrl || '';
  if (user.avatar) {
    return `${assetUrl}storage/${user.avatar}`;
  }
  return `${assetUrl}assets/img/avatar.svg`;
};

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
  <div class="dropdown" v-if="user">
    <a
      id="navbarDropdown"
      class="nav-link dropdown-toggle d-flex align-items-center"
      href="#"
      role="button"
      data-bs-toggle="dropdown"
      aria-haspopup="true"
      aria-expanded="false"
    >
      <img class="avatar avatar-sm rounded-circle me-2" :src="getAvatarUrl()" :alt="user.name" />
      <span class="d-none d-md-inline">{{ user.name }}</span>
    </a>

    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
      <div class="dropdown-item-text">
        <div class="d-flex gap-3">
          <img class="avatar avatar-lg rounded-circle" :src="getAvatarUrl()" :alt="user.name" />
          <div>
            <strong class="text-truncate">{{ user.name }}</strong>
            <p class="text-muted m-0 small">{{ user.email }}</p>
          </div>
        </div>
      </div>

      <hr class="dropdown-divider" />

      <RouterLink :to="'/profile'" class="dropdown-item">
        <i class="fas fa-user me-2"></i> Perfil
      </RouterLink>

      <a class="dropdown-item" href="#" @click.prevent="handleLogout">
        <i class="fas fa-sign-out-alt me-2"></i> Sair
      </a>
    </div>
  </div>
</template>
