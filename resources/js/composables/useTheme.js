import { ref, computed } from 'vue';
import { applyTheme } from '@/lib/theme';

/**
 * @typedef {'light' | 'dark' | 'system'} Theme
 */

export function useTheme() {
  /** @type {import('vue').Ref<Theme>} */
  const theme = ref('system');

  /** @type {import('vue').ComputedRef<'light' | 'dark'>} */
  const resolvedTheme = computed(() => {
    if (theme.value === 'system') {
      const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      return isDark ? 'dark' : 'light';
    }
    return theme.value;
  });

  /**
   * @param {Theme} newTheme
   */
  const setTheme = (newTheme) => {
    theme.value = newTheme;
    applyTheme(newTheme);
  };

  const toggleTheme = () => {
    /** @type {Theme} */
    let next;

    if (theme.value === 'light') next = 'dark';
    else if (theme.value === 'dark') next = 'light';
    else next = 'light'; // system → light

    setTheme(next);
  };

  return {
    theme,
    resolvedTheme,
    toggleTheme,
    setTheme,
  };
}
