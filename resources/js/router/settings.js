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
      ...users,
    ],
  },
];
