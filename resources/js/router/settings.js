import users from './users';

export default [
  {
    path: '/settings',
    component: () => import('../components/SettingsLayout.vue'),
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
      ...users,
    ],
  },
];
