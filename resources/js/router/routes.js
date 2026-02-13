import settings from './settings';
import AppLayout from '../layouts/AppLayout.vue';
import budgets from './budgets';

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
        path: '/colecao-arts',
        name: 'CollectionModels',
        component: () => import('../views/colecao-arts/CollectionModelsView.vue'),
      },
      {
        path: '/colecao-arts/colecao/:id',
        name: 'CollectionSubcategories',
        component: () => import('../views/colecao-arts/CollectionSubcategoriesView.vue'),
      },
      {
        path: '/colecao-arts/catalogo',
        name: 'CollectionCatalog',
        redirect: { name: 'SettingsCollections' },
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
        path: '/pedidos',
        name: 'Pedidos',
        meta: { roles: ['super admin', 'admin', 'production', 'commercial', 'reseller'] },
        component: () => import('../views/orders/OrdersView.vue'),
      },
      {
        path: '/pedidos/:id/edit',
        name: 'EditOrder',
        component: () => import('../views/orders/EditOrderView.vue'),
      },
      {
        path: '/pedidos/:id',
        name: 'ShowOrderDetails',
        component: () => import('../components/ShowDetails.vue'),
      },
      {
        path: '/layouts',
        name: 'Layouts',
        meta: { roles: ['super admin', 'admin', 'designer', 'production'] },
        component: () => import('../views/layouts/LayoutsView.vue'),
      },
      {
        path: '/products',
        name: 'Product',
        meta: { roles: ['super admin', 'admin', 'production'] },
        component: () => import('../views/production/ProductView.vue'),
      },
      {
        path: '/pedidos-producao',
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
