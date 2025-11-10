export default [
    {
        path: '/users',
        name: 'UsersList',
        component: () => import('../views/users/UsersList.vue')
    },
    {
        path: '/users/create',
        name: 'UsersCreate',
        component: () => import('../views/users/UsersCreate.vue')
    },
    {
        path: '/users/:id/edit',
        name: 'UsersEdit',
        component: () => import('../views/users/UsersEdit.vue')
    },
];
