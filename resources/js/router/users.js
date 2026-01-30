export default [
    {
        path: 'usuarios',
        name: 'UsersList',
        component: () => import('../views/users/UsersListView.vue')
    },
    {
        path: 'usuarios/criar',
        name: 'UsersCreate',
        component: () => import('../views/users/UsersCreateView.vue')
    },
    {
        path: 'usuarios/:id/editar',
        name: 'UsersEdit',
        component: () => import('../views/users/UsersEditView.vue')
    },
];
