import {createWebHistory, createRouter} from "vue-router";
import { useAuthStore } from '@/stores/auth';
import { USER_TYPES } from '@/constants/userTypes';

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

    if (!auth.user) {
        next();
        return;
    }

    const userTypeId = auth.user.user_type_id;

    // Usuários do tipo admin têm acesso total ao sistema
    if (userTypeId === USER_TYPES.ADMIN) {
        // Permitir acesso a todas as rotas
        next();
        return;
    }

    // Verificar acesso às rotas de configurações (apenas admin)
    const settingsRoutes = ['/settings', '/modelos'];
    const isSettingsRoute = settingsRoutes.some(route => to.path.startsWith(route));
    const isCatalogRoute = to.path === '/colecao-arts/catalogo';

    if (isSettingsRoute || isCatalogRoute) {
        // Redirecionar para dashboard se não for admin
        next({ path: '/dashboard' });
        return;
    }

    // Verificar acesso para tenants (reseller)
    if (userTypeId === USER_TYPES.RESELLER) {
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
            // Permitir /budget, /budget/new-budget, /budget/edit/:id e /budget/:id (detalhes)
            if (to.path === '/budget' ||
                to.path === '/budget/new-budget' ||
                to.path.startsWith('/budget/edit/') ||
                /^\/budget\/\d+$/.test(to.path)) {
                isAllowed = true;
            }
        } else if (to.path.startsWith('/colecao-arts')) {
            // Permitir todas as sub-rotas de colecao-arts exceto /catalogo
            if (to.path !== '/colecao-arts/catalogo' && !to.path.startsWith('/colecao-arts/catalogo/')) {
                isAllowed = true;
            }
        } else if (to.path === '/pedidos' || to.path.startsWith('/pedidos/')) {
            // Permitir /pedidos e /pedidos/:id (detalhes do pedido)
            if (to.path === '/pedidos' || /^\/pedidos\/\d+$/.test(to.path)) {
                isAllowed = true;
            }
        }

        if (!isAllowed) {
            // Redirecionar para dashboard se tentar acessar rota não permitida
            next({ path: '/dashboard' });
            return;
        }
    }

    // Verificar acesso para usuários do tipo design (designer)
    // Designers só podem acessar dashboard, profile e layouts
    if (userTypeId === USER_TYPES.DESIGNER) {
        let isAllowed = false;

        if (to.path === '/dashboard' || to.path === '/') {
            isAllowed = true;
        } else if (to.path === '/profile') {
            isAllowed = true;
        } else if (to.path.startsWith('/layouts')) {
            isAllowed = true;
        }

        if (!isAllowed) {
            // Redirecionar para dashboard se tentar acessar rota não permitida
            next({ path: '/dashboard' });
            return;
        }
    }

    // Verificar acesso para usuários de produção
    const productionRoutes = ['/layouts', '/products', '/pedidos-producao'];
    const isProductionRoute = productionRoutes.some(route => to.path.startsWith(route));

    if (isProductionRoute) {
        // Permitir acesso a /layouts para DESIGNER também
        if (to.path.startsWith('/layouts')) {
            if (userTypeId !== USER_TYPES.PRODUCTION && userTypeId !== USER_TYPES.DESIGNER) {
                // Redirecionar para dashboard se não for usuário de produção ou designer
                next({ path: '/dashboard' });
                return;
            }
        } else if (to.path.startsWith('/pedidos-producao')) {
            if (userTypeId !== USER_TYPES.PRODUCTION && userTypeId !== USER_TYPES.COMMERCIAL) {
                next({ path: '/dashboard' });
                return;
            }
        } else if (userTypeId !== USER_TYPES.PRODUCTION) {
            // Para outras rotas de produção, apenas usuários de produção podem acessar
            next({ path: '/dashboard' });
            return;
        }
    }

    // Se for usuário de produção, só pode acessar rotas de produção e perfil
    if (userTypeId === 4) {
        const allowedRoutes = [
            '/dashboard',
            '/profile',
            '/layouts',
            '/products',
            '/pedidos-producao',
        ];

        let isAllowed = false;

        if (to.path === '/dashboard' || to.path === '/') {
            isAllowed = true;
        } else if (to.path === '/profile') {
            isAllowed = true;
        } else if (to.path.startsWith('/layouts')) {
            isAllowed = true;
        } else if (to.path.startsWith('/products')) {
            isAllowed = true;
        } else if (to.path.startsWith('/pedidos-producao')) {
            isAllowed = true;
        }

        if (!isAllowed) {
            // Redirecionar para dashboard se tentar acessar rota não permitida
            next({ path: '/dashboard' });
            return;
        }
    }

    if (userTypeId === USER_TYPES.COMMERCIAL) {
        let isAllowed = false;

        if (to.path === '/dashboard' || to.path === '/') {
            isAllowed = true;
        } else if (to.path === '/profile') {
            isAllowed = true;
        } else if (to.path === '/pedidos' || to.path.startsWith('/pedidos/')) {
            // Permitir /pedidos e /pedidos/:id (detalhes do pedido)
            if (to.path === '/pedidos' || /^\/pedidos\/\d+$/.test(to.path)) {
                isAllowed = true;
            }
        } else if (to.path.startsWith('/pedidos-producao')) {
            isAllowed = true;
        }

        if (!isAllowed) {
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
