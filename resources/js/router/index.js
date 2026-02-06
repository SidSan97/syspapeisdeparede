import { createWebHistory, createRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

import routes from './routes';

const router = createRouter({
  history: createWebHistory(),
  linkActiveClass: 'active',
  routes,
});

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();

  auth.init();

  if (!!to.meta.roles && !auth.hasAnyRole(to.meta.roles)) {
    return next({ name: 'Unauthorized' });
  }

  next();
});

export default router;
