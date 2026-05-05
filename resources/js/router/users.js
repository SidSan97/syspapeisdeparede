const basePath = '/settings/users';

const roles = ['super admin', 'admin'];

const withRoles = { meta: { roles } };

export default [
  {
    path: basePath,
    name: 'settings.users.list',
    component: () => import('../views/users/UserListView.vue'),
    ...withRoles,
  },
  {
    path: `${basePath}/create`,
    name: 'settings.users.create',
    component: () => import('../views/users/UserCreateView.vue'),
    ...withRoles,
  },
  {
    path: `${basePath}/:id/edit`,
    name: 'settings.users.edit',
    component: () => import('../views/users/UserEditView.vue'),
    ...withRoles,
  },
];
