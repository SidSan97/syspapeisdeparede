import users from './users';

export default [
    {
        path: '/settings',
        component: () => import('../components/SettingsLayout.vue'),
        children: [
            ...users,
            {
                path: '',
                redirect: { name: 'UsersList' },
            }
        ],
    },
];
