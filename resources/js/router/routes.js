import settings from './settings';
import AppLayout from '../layouts/AppLayout.vue';
import Budget from '../views/budget/BudgetView.vue';

const routes = [
    {
        path: '/',
        component: AppLayout,
        children: [
            {
                path: '',
                redirect: '/dashboard'
            },
            {
                path: '/dashboard',
                name: 'Dashboard',
                component: () => import('../views/dashboard/DashboardView.vue')
            },
            {
                path: '/profile',
                name: 'Profile',
                component: () => import('../views/profile/ProfileView.vue')
            },
            {
                path: '/budget/new-budget',
                name: 'NewBudget',
                component: () => import('../views/budget/NewBudget.vue')
            },
            {
                path: '/budget/edit/:id',
                name: 'EditBudget',
                component: () => import('../views/budget/EditBudget.vue')
            },
            {
                path: '/budget/:id',
                name: 'ShowBudgetDetails',
                component: () => import('../components/ShowDetails.vue')
            },
            {
                path: '/budget/:id/pdf-preview',
                name: 'BudgetPdfPreview',
                component: () => import('../views/budget/BudgetPdfPreview.vue')
            },
            {
                path: '/budget',
                name: 'Budget',
                component: Budget
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
                path: '/modelos',
                name: 'Models',
                component: () => import('../views/models/ModelsView.vue'),
            },
            {
                path: '/layouts',
                name: 'Layouts',
                component: () => import('../views/layouts/LayoutsView.vue'),
            },
            {
                path: '/products',
                name: 'Product',
                component: () => import('../views/production/ProductView.vue'),
            },
            {
                path: '/pedidos-producao',
                name: 'InternalOrders',
                component: () => import('../views/internal-orders/InternalOrders.vue'),
            },
            {
                path: '/expedicao',
                name: 'Expedition',
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
            ...settings,
        ]
    },
    {
        path: '/:catchAll(.*)',
        component: () => import('../components/NotFound.vue')
    },
];

export default routes;
