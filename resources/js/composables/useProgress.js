import { getCurrentInstance } from 'vue';

export function useProgress() {
  const instance = getCurrentInstance();

  if (!instance) {
    throw new Error('useProgress() must be called within setup()');
  }

  return instance.proxy.$Progress;
}
