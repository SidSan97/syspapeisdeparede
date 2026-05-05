const basePath = '/budgets';

const roles = ['super admin', 'admin', 'commercial', 'reseller'];

const withRoles = { meta: { roles } };

export default [
  {
    path: basePath,
    name: 'budgets.list',
    component: () => import('../views/budgets/BudgetListView.vue'),
    ...withRoles,
  },
  {
    path: `${basePath}/create`,
    name: 'budgets.create',
    component: () => import('../views/budgets/BudgetCreateView.vue'),
    ...withRoles,
  },
  {
    path: `${basePath}/:id`,
    name: 'budgets.show',
    component: () => import('../views/budgets/BudgetShowView.vue'),
  },
  {
    path: `${basePath}/:id/edit`,
    name: 'budgets.edit',
    component: () => import('../views/budgets/BudgetEditView.vue'),
  },
  {
    path: `${basePath}/:id/pdf-preview`,
    name: 'budgets.pdf-preview',
    component: () => import('../views/budgets/BudgetPdfPreview.vue'),
  },
];
