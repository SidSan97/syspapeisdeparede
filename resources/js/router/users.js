export default [
    {
        path: 'usuarios',
        name: 'UsersList',
        component: () => import('../views/users/UsersList.vue')
    },
    {
        path: 'usuarios/criar',
        name: 'UsersCreate',
        component: () => import('../views/users/UsersCreate.vue')
    },
    {
        path: 'usuarios/:id/editar',
        name: 'UsersEdit',
        component: () => import('../views/users/UsersEdit.vue')
    },
];
