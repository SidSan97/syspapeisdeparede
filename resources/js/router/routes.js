import settings from './settings';
import budgets from './budgets';
import orders from './orders';

import AppLayout from '@/layouts/AppLayout.vue';

const routes = [
  {
    path: '/',
    component: AppLayout,
    children: [
      {
        path: '',
        redirect: '/dashboard',
      },
      {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('../views/dashboard/DashboardView.vue'),
      },
      {
        path: '/profile',
        name: 'Profile',
        component: () => import('../views/profile/ProfileView.vue'),
      },
      {
        path: '/carteira',
        name: 'Credits',
        component: () => import('../views/credits/CreditView.vue'),
      },
      {
        path: '/colecao-arts',
        name: 'CollectionModels',
        component: () => import('../views/colecao-arts/CollectionModelsView.vue'),
      },
      {
        path: '/colecao-arts/colecao/:id',
        name: 'CollectionSubcategories',
        component: () => import('../views/colecao-arts/CollectionModelsView.vue'),
        // import('../views/colecao-arts/CollectionSubcategoriesView.vue'),
      },
      {
        path: '/colecao-arts/catalogo',
        name: 'CollectionCatalog',
        redirect: { name: 'settings.collections' },
      },
      {
        path: '/colecao-arts/subcategoria/:id',
        name: 'SubcategoryImages',
        component: () => import('../views/colecao-arts/SubcategoryImagesView.vue'),
      },
      {
        path: '/colecao-arts/favoritos',
        name: 'MyFavorites',
        component: () => import('../views/colecao-arts/MyFavoritesView.vue'),
      },
      {
        path: '/layouts',
        name: 'layouts.board',
        meta: {
          roles: ['super admin', 'admin', 'designer', 'production'],
        },
        component: () => import('../views/layouts/LayoutsBoardView.vue'),
      },
      {
        path: '/production',
        name: 'production.board',
        meta: { roles: ['super admin', 'admin', 'production'] },
        component: () => import('../views/production/ProductionBoardView.vue'),
      },
      {
        path: '/orders-producao',
        name: 'InternalOrders',
        redirect: { name: 'Pedidos' },
      },
      {
        path: '/expedicao',
        name: 'Expedition',
        meta: { roles: ['super admin', 'admin', 'expedition'] },
        component: () => import('../views/expedition/ExpeditionView.vue'),
      },
      {
        path: '/expedicao/nota-fiscal/:id',
        name: 'ShowInvoiceDetails',
        component: () => import('../modules/expedition/components/showInvoiceDetails.vue'),
      },
      {
        path: '/expedicao/agrupamento/:id',
        name: 'ShowGroupingDetails',
        component: () => import('../modules/expedition/components/showGroupingDetails.vue'),
      },
      ...orders,
      ...budgets,
      ...settings,
      {
        path: '/unauthorized',
        name: 'Unauthorized',
        component: () => import('../views/errors/UnauthorizedView.vue'),
      },
    ],
  },
  {
    path: '/:catchAll(.*)',
    component: () => import('../views/errors/NotFoundView.vue'),
  },
];

export default routes;
