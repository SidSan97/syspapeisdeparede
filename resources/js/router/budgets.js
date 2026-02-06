const basePath = '/budgets';

const roles = ['super admin', 'admin', 'commercial', 'reseller'];

const withRoles = { meta: { roles } };

export default [
  {
    path: basePath,
    name: 'BudgetList',
    component: () => import('../views/budget/BudgetView.vue'),
    ...withRoles,
  },
  {
    path: `${basePath}/create`,
    name: 'BudgetCreate',
    component: () => import('../views/budget/NewBudget.vue'),
    ...withRoles,
  },
  {
    path: `${basePath}/:id`,
    name: 'BudgetDetail',
    component: () => import('../components/ShowDetails.vue'),
  },
  {
    path: `${basePath}/:id/edit`,
    name: 'BudgetEdit',
    component: () => import('../views/budget/EditBudget.vue'),
  },
  {
    path: `${basePath}/:id/pdf-preview`,
    name: 'BudgetPdfPreview',
    component: () => import('../views/budget/BudgetPdfPreview.vue'),
  },
];
