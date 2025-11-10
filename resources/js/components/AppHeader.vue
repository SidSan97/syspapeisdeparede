<template>
    <header class="navbar navbar-expand-lg py-0 sticky-top bd-navbar" style="z-index: 1030;">
        <nav class="container-fluid px-md-3 px-lg-4">
            <!-- App Brand -->
            <a class="navbar-brand d-inline-flex align-items-center m-0 p-0 me-lg-6 me-xl-9 p-1 rounded text-reset"
                href="/">
                Laravel
            </a>

            <div class="collapse navbar-collapse">
                <ul class="navbar-nav navbar-nav-underline ps-lg-5 flex-grow-1">
                    <li v-for="item in mainNavItems" :key="item.title" class="nav-item">
                        <RouterLink class="nav-link" :to="item.to" v-if="item.can">{{ item.title }}</RouterLink>
                    </li>
                </ul>
            </div>
            <div class="d-flex align-items-center ms-auto gap-3 me-2 me-lg-3">
                <div class="position-relative">
                    <button class="btn btn-subtle px-2" id="bd-theme" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="theme-icon-active">
                            <i class="fa fa-sun"></i>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" role="menu">
                        <li>
                            <button class="dropdown-item" data-bs-theme-value="light">
                                <i class="fa fa-sun"></i> <span class="ms-2">Claro</span>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item" data-bs-theme-value="dark">
                                <i class="fa fa-moon"></i> <span class="ms-2">Escuro</span>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item" data-bs-theme-value="auto">
                                <i class="fa fa-adjust"></i> <span class="ms-2">Auto</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <router-link :to="'/settings'" class="btn btn-subtle px-2">
                    <i class="fa fa-cog"></i>
                </router-link>

                <!-- User Dropdown -->
                <div class="dropdown" v-if="auth.user">
                    <a
                        id="navbarDropdown"
                        class="nav-link dropdown-toggle d-flex align-items-center"
                        href="#"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <img
                            class="avatar avatar-sm rounded-circle me-2"
                            :src="getAvatarUrl()"
                            :alt="auth.user.name"
                        />
                        <span class="d-none d-md-inline">{{ auth.user.name }}</span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <div class="dropdown-item-text">
                            <div class="d-flex gap-3">
                                <img
                                    class="avatar avatar-lg rounded-circle"
                                    :src="getAvatarUrl()"
                                    :alt="auth.user.name"
                                />
                                <div>
                                    <strong>{{ truncateName(auth.user.name, 15) }}</strong>
                                    <p class="text-muted m-0 small">{{ auth.user.email }}</p>
                                </div>
                            </div>
                        </div>

                        <hr class="dropdown-divider">

                        <router-link
                            :to="'/profile'"
                            class="dropdown-item"
                        >
                            <i class="fas fa-user me-2"></i> Perfil
                        </router-link>

                        <a
                            class="dropdown-item"
                            href="#"
                            @click.prevent="handleLogout"
                        >
                            <i class="fas fa-sign-out-alt me-2"></i> Sair
                        </a>
                    </div>
                </div>

                <button
                    class="navbar-toggler d-lg-none"
                    type="button"
                    @click="toggleSidebar"
                    aria-label="Toggle navigation"
                >
                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" fill="currentcolor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M2.5 11.5A.5.5.0 013 11h10a.5.5.0 010 1H3a.5.5.0 01-.5-.5zm0-4A.5.5.0 013 7h10a.5.5.0 010 1H3a.5.5.0 01-.5-.5zm0-4A.5.5.0 013 3h10a.5.5.0 010 1H3a.5.5.0 01-.5-.5z">
                        </path>
                    </svg>
                </button>
            </div>
        </nav>
    </header>
</template>

<script setup>
import { inject, computed } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const toggleSidebar = inject('toggleSidebar', () => {});
const auth = useAuthStore();
const router = useRouter();

const getAvatarUrl = () => {
    if (!auth.user) return '';

    const assetUrl = window.LaravelApp?.assetUrl || '';
    if (auth.user.avatar) {
        return `${assetUrl}storage/${auth.user.avatar}`;
    }
    return `${assetUrl}assets/img/avatar.svg`;
};

const truncateName = (name, length) => {
    if (!name) return '';
    return name.length > length ? name.substring(0, length) + '...' : name;
};

const handleLogout = () => {
    // Criar formulário de logout
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';

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

const mainNavItems = [
    // Adicione itens de navegação aqui se necessário
];
</script>
