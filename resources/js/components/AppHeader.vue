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
                            <i :class="`fa ${themeIconActive}`"></i>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" role="menu">
                        <li>
                            <button class="dropdown-item" :class="{ active: currentTheme === 'light' }" @click="setTheme('light')">
                                <i class="fa fa-sun"></i> <span class="ms-2">Claro</span>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item" :class="{ active: currentTheme === 'dark' }" @click="setTheme('dark')">
                                <i class="fa fa-moon"></i> <span class="ms-2">Escuro</span>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item" :class="{ active: currentTheme === 'auto' }" @click="setTheme('auto')">
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
import { inject, computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const toggleSidebar = inject('toggleSidebar', () => {});
const auth = useAuthStore();
const router = useRouter();
const currentTheme = ref('light');
const themeIconActive = ref('fa-sun');

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

const mainNavItems = [
    // Adicione itens de navegação aqui se necessário
];

// Funções para gerenciar tema
function getStoredTheme() {
    return localStorage.getItem('bs-theme') || 'light';
}

function setStoredTheme(theme) {
    localStorage.setItem('bs-theme', theme);
}

function getPreferredTheme() {
    const storedTheme = getStoredTheme();
    if (storedTheme) {
        return storedTheme;
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function setTheme(theme) {
    if (theme === 'auto') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-bs-theme', prefersDark ? 'dark' : 'light');
    } else {
        document.documentElement.setAttribute('data-bs-theme', theme);
    }
    setStoredTheme(theme);
    currentTheme.value = theme;
    updateThemeIcon();
}

function updateThemeIcon() {
    // Mostrar o ícone baseado no tema selecionado (não no tema ativo)
    if (currentTheme.value === 'dark') {
        themeIconActive.value = 'fa-moon';
    } else if (currentTheme.value === 'auto') {
        themeIconActive.value = 'fa-adjust';
    } else {
        themeIconActive.value = 'fa-sun';
    }
}

function getActiveTheme() {
    const stored = getStoredTheme();
    if (stored === 'auto') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    return stored;
}

function initTheme() {
    const theme = getStoredTheme();
    currentTheme.value = theme;
    setTheme(theme);
}

// Listener para mudanças na preferência do sistema (modo auto)
let mediaQueryListener = null;
let systemThemeChangeHandler = null;

function setupAutoThemeListener() {
    // Remover listener anterior se existir
    if (mediaQueryListener && systemThemeChangeHandler) {
        if (mediaQueryListener.removeEventListener) {
            mediaQueryListener.removeEventListener('change', systemThemeChangeHandler);
        } else if (mediaQueryListener.removeListener) {
            mediaQueryListener.removeListener(systemThemeChangeHandler);
        }
    }

    // Criar novo listener
    mediaQueryListener = window.matchMedia('(prefers-color-scheme: dark)');
    systemThemeChangeHandler = (e) => {
        if (currentTheme.value === 'auto') {
            document.documentElement.setAttribute('data-bs-theme', e.matches ? 'dark' : 'light');
            updateThemeIcon();
        }
    };

    // Adicionar listener (compatibilidade com diferentes navegadores)
    if (mediaQueryListener.addEventListener) {
        mediaQueryListener.addEventListener('change', systemThemeChangeHandler);
    } else if (mediaQueryListener.addListener) {
        mediaQueryListener.addListener(systemThemeChangeHandler);
    }
}

function removeAutoThemeListener() {
    if (mediaQueryListener && systemThemeChangeHandler) {
        if (mediaQueryListener.removeEventListener) {
            mediaQueryListener.removeEventListener('change', systemThemeChangeHandler);
        } else if (mediaQueryListener.removeListener) {
            mediaQueryListener.removeListener(systemThemeChangeHandler);
        }
    }
    mediaQueryListener = null;
    systemThemeChangeHandler = null;
}

onMounted(() => {
    initTheme();

    // Listener para mudanças na preferência do sistema quando estiver em modo auto
    if (currentTheme.value === 'auto') {
        setupAutoThemeListener();
    }
});

onUnmounted(() => {
    removeAutoThemeListener();
});

// Observar mudanças no tema atual
watch(currentTheme, (newTheme, oldTheme) => {
    if (newTheme === 'auto') {
        setupAutoThemeListener();
        // Aplicar tema inicial baseado na preferência do sistema
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-bs-theme', prefersDark ? 'dark' : 'light');
        updateThemeIcon();
    } else {
        removeAutoThemeListener();
    }
});
</script>
