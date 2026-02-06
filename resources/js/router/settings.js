import users from './users';

export default [
  {
    path: '/settings',
    component: () => import('../components/SettingsLayout.vue'),
    meta: {
      roles: ['super admin', 'admin'],
    },
    children: [
      {
        path: '',
        name: 'SettingsHome',
        component: () => import('../views/settings/SettingsHome.vue'),
      },
      {
        path: 'colecoes',
        name: 'SettingsCollections',
        component: () => import('../views/settings/CollectionArts.vue'),
      },
      {
        path: 'tiny-erp',
        name: 'TinyErpSettings',
        component: () => import('../views/settings/tiny-erp/TinyErp.vue'),
      },
      {
        path: 'models',
        name: 'ModelList',
        meta: { roles: ['super admin', 'admin'] },
        component: () => import('../views/models/ModelsView.vue'),
      },
      ...users,
    ],
  },
];
