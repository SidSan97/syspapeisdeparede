const themeConfig = {
  actTheme: 'system',
};

/**
 * @typedef {'light' | 'dark' | 'system'} Theme
 */

/**
 * Verifica se o sistema do usuário prefere modo escuro
 * @returns {boolean}
 */
const prefersDark = () => {
  if (typeof window === 'undefined') return false;
  return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

/**
 * Aplica o tema selecionado
 * @param {Theme} theme
 * @returns {void}
 */
export function applyTheme(theme) {
  if (typeof window === 'undefined') return;

  /** @type {'light' | 'dark'} */
  let resolved = theme;

  if (theme === 'system') {
    resolved = prefersDark() ? 'dark' : 'light';
  }

  document.documentElement.setAttribute('data-bs-theme', resolved);
  themeConfig.actTheme = theme;
  localStorage.setItem('theme', theme);
}

/**
 * Inicializa o tema da aplicação
 * @returns {void}
 */
export function initializeTheme() {
  if (typeof window === 'undefined') return;

  /** @type {Theme | null} */
  const saved = localStorage.getItem('theme');

  /** @type {Theme} */
  const initialTheme = saved || themeConfig.actTheme;

  applyTheme(initialTheme);

  // Se o tema inicial for system, escuta mudanças do sistema
  if (initialTheme === 'system') {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      applyTheme('system');
    });
  }
}
