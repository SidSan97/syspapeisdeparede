import { createWebHistory, createRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

import routes from './routes';

const router = createRouter({
  history: createWebHistory(),
  linkActiveClass: 'active',
  routes,
});

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();

  auth.init();

  // Usuários admin têm acesso total ao sistema
  if (auth.isAdmin()) {
    // Permitir acesso a todas as rotas
    next();
    return;
  }

  // Verificar acesso às rotas de configurações (apenas admin)
  const settingsRoutes = ['/settings', '/modelos'];
  const isSettingsRoute = settingsRoutes.some((route) => to.path.startsWith(route));
  const isCatalogRoute = to.path === '/colecao-arts/catalogo';

  if (isSettingsRoute || isCatalogRoute) {
    // Redirecionar para dashboard se não for admin
    next({ path: '/dashboard' });
    return;
  }

  // Verificar acesso para tenants (reseller)
  if (auth.hasRole('reseller')) {
    // Verificar se a rota é permitida
    let isAllowed = false;

    // Verificar rotas específicas primeiro
    if (to.path === '/dashboard' || to.path === '/') {
      isAllowed = true;
    } else if (to.path === '/profile') {
      isAllowed = true;
    } else if (to.path === '/budget' || to.path.startsWith('/budget/')) {
      // Permitir /budget, /budget/new-budget, /budget/edit/:id, /budget/:id (detalhes) e /budget/:id/pdf-preview
      if (
        to.path === '/budget' ||
        to.path === '/budget/new-budget' ||
        to.path.startsWith('/budget/edit/') ||
        /^\/budget\/\d+$/.test(to.path) ||
        /^\/budget\/\d+\/pdf-preview$/.test(to.path)
      ) {
        isAllowed = true;
      }
    } else if (to.path.startsWith('/colecao-arts')) {
      // Permitir todas as sub-rotas de colecao-arts exceto /catalogo
      if (to.path !== '/colecao-arts/catalogo' && !to.path.startsWith('/colecao-arts/catalogo/')) {
        isAllowed = true;
      }
    } else if (to.path === '/pedidos' || to.path.startsWith('/pedidos/')) {
      if (
        to.path === '/pedidos' ||
        /^\/pedidos\/\d+$/.test(to.path) ||
        /^\/pedidos\/\d+\/edit$/.test(to.path)
      ) {
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
  if (auth.hasRole('designer')) {
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
  const isProductionRoute = productionRoutes.some((route) => to.path.startsWith(route));

  if (isProductionRoute) {
    // Permitir acesso a /layouts para DESIGNER também
    if (to.path.startsWith('/layouts')) {
      if (!auth.hasRole(['production', 'designer'])) {
        // Redirecionar para dashboard se não for usuário de produção ou designer
        next({ path: '/dashboard' });
        return;
      }
    } else if (to.path.startsWith('/pedidos-producao')) {
      if (!auth.hasRole(['production', 'commercial'])) {
        next({ path: '/dashboard' });
        return;
      }
    } else if (!auth.hasRole('production')) {
      // Para outras rotas de produção, apenas usuários de produção podem acessar
      next({ path: '/dashboard' });
      return;
    }
  }

  // Usuário de produção: acesso à página Produção (/products) e ao próprio perfil (/profile)
  if (auth.hasRole('production')) {
    const isProductsRoute = to.path === '/products' || to.path.startsWith('/products/');
    const isProfileRoute = to.path === '/profile';
    if (!isProductsRoute && !isProfileRoute) {
      next({ path: '/products' });
      return;
    }
    next();
    return;
  }

  if (auth.hasRole('commercial')) {
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

  // Verificar acesso para usuários de expedição
  if (to.path.startsWith('/expedicao')) {
    if (!auth.hasRole('expedition')) {
      // Redirecionar para dashboard se não for usuário de expedição
      next({ path: '/dashboard' });
      return;
    }
  }

  // Se for usuário de expedição, só pode acessar rotas de expedição e perfil
  if (auth.hasRole('expedition')) {
    let isAllowed = false;

    if (to.path === '/dashboard' || to.path === '/') {
      isAllowed = true;
    } else if (to.path === '/profile') {
      isAllowed = true;
    } else if (to.path.startsWith('/expedicao')) {
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

export default router;
