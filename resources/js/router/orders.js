const basePath = '/orders';

const roles = ['super admin', 'admin', 'production', 'commercial', 'reseller'];

const withRoles = { meta: { roles } };

export default [
  {
    path: basePath,
    name: 'orders.list',
    ...withRoles,
    component: () => import('../views/orders/OrderListView.vue'),
  },
  {
    path: '/orders/:id',
    name: 'orders.show',
    component: () => import('../views/orders/OrderShowView.vue'),
  },
  {
    path: `${basePath}/:id/edit`,
    name: 'orders.edit',
    component: () => import('../views/orders/OrderEditView.vue'),
  },
  {
    path: `${basePath}/:id/invoice`,
    name: 'orders.invoice',
    ...withRoles,
    component: () => import('../views/orders/OrderInvoiceView.vue'),
  },
];
