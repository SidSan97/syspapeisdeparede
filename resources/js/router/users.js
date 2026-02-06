const roles = ['super admin', 'admin'];

const withRoles = { meta: { roles } };

export default [
  {
    path: 'users',
    name: 'UserList',
    component: () => import('../views/users/UsersListView.vue'),
    ...withRoles,
  },
  {
    path: 'users/create',
    name: 'UserCreate',
    component: () => import('../views/users/UsersCreateView.vue'),
    ...withRoles,
  },
  {
    path: 'users/:id/edit',
    name: 'UserEdit',
    component: () => import('../views/users/UsersEditView.vue'),
    ...withRoles,
  },
];
