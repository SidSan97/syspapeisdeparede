import settings from './settings';

const routes = [
    ...settings,
    {
        path: '/dashboard',
        component: () => import('../views/dashboard/Dashboard.vue')
    },
    {
        path: '/profile',
        component: () => import('../views/profile/Profile.vue')
    },
    {
        path: '/:catchAll(.*)',
        component: () => import('../components/NotFound.vue')
    },
];

export default routes;
