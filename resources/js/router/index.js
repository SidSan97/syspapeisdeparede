import { nextTick } from 'vue';
import { createWebHistory, createRouter } from 'vue-router';

import routes from './routes';

import { useAuthStore } from '@/stores/auth';

const DEFAULT_TITLE = 'Arts';

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

router.afterEach((to, from) => {
  // Use next tick to handle router history correctly
  // see: https://github.com/vuejs/vue-router/issues/914#issuecomment-384477609
  nextTick(() => {
    document.title = to.meta.title || DEFAULT_TITLE;
  });
});

export default router;
