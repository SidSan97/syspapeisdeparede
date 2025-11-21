import settings from './settings';
import AppLayout from '../layouts/AppLayout.vue';
import Budget from '../views/budget/budget.vue';

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
                component: () => import('../views/dashboard/Dashboard.vue')
            },
            {
                path: '/profile',
                name: 'Profile',
                component: () => import('../views/profile/Profile.vue')
            },
            {
                path: '/budget/new-budget',
                name: 'NewBudget',
                component: () => import('../views/budget/new-budget.vue')
            },
            {
                path: '/budget/edit/:id',
                name: 'EditBudget',
                component: () => import('../views/budget/edit-budget.vue')
            },
            {
                path: '/budget',
                name: 'Budget',
                component: Budget
            },
            {
                path: '/colecao-arts',
                name: 'CollectionModels',
                component: () => import('../views/colecao-arts/CollectionModels.vue'),
            },
            {
                path: '/colecao-arts/colecao/:id',
                name: 'CollectionSubcategories',
                component: () => import('../views/colecao-arts/CollectionSubcategories.vue'),
            },
            {
                path: '/colecao-arts/catalogo',
                name: 'CollectionCatalog',
                component: () => import('../views/colecao-arts/CollectionCatalog.vue'),
            },
            {
                path: '/colecao-arts/subcategoria/:id',
                name: 'SubcategoryImages',
                component: () => import('../views/colecao-arts/SubcategoryImages.vue'),
            },
            {
                path: '/colecao-arts/favoritos',
                name: 'MyFavorites',
                component: () => import('../views/colecao-arts/MyFavorites.vue'),
            },
            {
                path: '/pedidos',
                name: 'Pedidos',
                component: () => import('../views/orders/Orders.vue'),
            },
            {
                path: '/modelos',
                name: 'Models',
                component: () => import('../views/models/Models.vue'),
            },
            {
                path: '/layouts',
                name: 'Layouts',
                component: () => import('../views/layouts/Layouts.vue'),
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
