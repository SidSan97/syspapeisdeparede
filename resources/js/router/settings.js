import users from './users';

export default [
  {
    path: '/settings',
    component: () => import('../components/SettingsLayout.vue'),
    redirect: { name: 'settings.users.list' },
    meta: {
      roles: ['super admin', 'admin'],
    },
    children: [
      {
        path: '',
        name: 'settings.home',
        component: () => import('../views/settings/SettingsHomeView.vue'),
      },
      {
        path: 'collections',
        name: 'settings.collections',
        component: () => import('../views/settings/SettingsCollectionsView.vue'),
      },
      {
        path: 'tiny-erp',
        name: 'settings.tiny-erp',
        component: () => import('../views/settings/tiny-erp/TinyErp.vue'),
      },
      {
        path: 'terms-of-use',
        name: 'settings.terms-of-use',
        meta: { roles: ['super admin', 'admin'] },
        component: () => import('../views/settings/SettingsTermsOfUseView.vue'),
      },
      {
        path: 'models',
        name: 'settings.models.list',
        meta: { roles: ['super admin', 'admin'] },
        component: () => import('../views/models/CollectionModelListView.vue'),
      },
      {
        path: 'models/create',
        name: 'settings.models.create',
        meta: { roles: ['super admin', 'admin'] },
        component: () => import('../views/models/CollectionModelCreateView.vue'),
      },
      {
        path: 'models/:id/edit',
        name: 'settings.models.edit',
        meta: { roles: ['super admin', 'admin'] },
        component: () => import('../views/models/CollectionModelEditView.vue'),
      },
      ...users,
    ],
  },
];
