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

    // Verificar se o usuário está autenticado
    if (!auth.user) {
        next();
        return;
    }

    const userTypeId = auth.user.user_type_id;

    // Verificar acesso às rotas de configurações (apenas user_type_id === 2)
    const settingsRoutes = ['/settings', '/modelos'];
    const isSettingsRoute = settingsRoutes.some(route => to.path.startsWith(route));
    const isCatalogRoute = to.path === '/colecao-arts/catalogo';

    if (isSettingsRoute || isCatalogRoute) {
        if (userTypeId !== 2) {
            // Redirecionar para dashboard se não for admin
            next({ path: '/dashboard' });
            return;
        }
    }

    // Verificar acesso para tenants (user_type_id === 3)
    if (userTypeId === 3) {
        // Rotas permitidas para tenants
        const allowedRoutes = [
            '/dashboard',
            '/profile',
            '/budget',
            '/budget/new-budget',
            '/budget/edit',
            '/colecao-arts',
            '/pedidos',
        ];

        // Verificar se a rota é permitida
        let isAllowed = false;

        // Verificar rotas específicas primeiro
        if (to.path === '/dashboard' || to.path === '/') {
            isAllowed = true;
        } else if (to.path === '/profile') {
            isAllowed = true;
        } else if (to.path === '/budget' || to.path.startsWith('/budget/')) {
            // Permitir /budget, /budget/new-budget e /budget/edit/:id
            if (to.path === '/budget' ||
                to.path === '/budget/new-budget' ||
                to.path.startsWith('/budget/edit/')) {
                isAllowed = true;
            }
        } else if (to.path.startsWith('/colecao-arts')) {
            // Permitir todas as sub-rotas de colecao-arts exceto /catalogo
            if (to.path !== '/colecao-arts/catalogo' && !to.path.startsWith('/colecao-arts/catalogo/')) {
                isAllowed = true;
            }
        } else if (to.path === '/pedidos') {
            isAllowed = true;
        }

        if (!isAllowed) {
            // Redirecionar para dashboard se tentar acessar rota não permitida
            next({ path: '/dashboard' });
            return;
        }
    }

    // Permitir navegação
    next();
});

// Log para debug de navegação
router.afterEach((to, from) => {
    console.log('Navigated from:', from.path, 'to:', to.path);
});

export default router;
