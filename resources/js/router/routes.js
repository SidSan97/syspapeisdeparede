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
                path: '/budget',
                name: 'Budget',
                component: Budget
            },
            {
                path: '/colecao-arts',
                name: 'CollectionModels',
                component: () => import('../views/colecao-arts/CollectionModels.vue'),
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
