import {createWebHistory, createRouter} from "vue-router";
import { useAuthStore } from '@/stores/auth';

import routes from "./routes";

const router = createRouter({
    history: createWebHistory(),
    linkActiveClass: 'active',
    routes,
})

// Guard de navegação para atualizar dados do usuário
router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    // Se já temos dados do window.LaravelApp, use-os
    if (window.LaravelApp?.user && !auth.ready) {
        auth.setUser({
            user: window.LaravelApp.user,
            roles: window.LaravelApp.roles || [],
            permissions: window.LaravelApp.permissions || [],
            direct_permissions: window.LaravelApp.direct_permissions || [],
        });
        auth.ready = true;
    }

    // Se não há dados mas o usuário está autenticado, tenta buscar
    if (!auth.user && !auth.ready && document.querySelector('meta[name="csrf-token"]')) {
        // Tenta buscar da API (apenas se estiver em uma rota autenticada)
        await auth.fetchUser();
    }

    // Permitir navegação
    next();
});

// Log para debug de navegação
router.afterEach((to, from) => {
    console.log('Navigated from:', from.path, 'to:', to.path);
});

export default router;
