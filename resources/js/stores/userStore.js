import { defineStore } from 'pinia';
import { ref } from 'vue';
import { userService } from '@/services/userService';

export const useUserStore = defineStore('users', () => {
  const users = ref({
    data: [],
    meta: {},
  });
  const currentUser = ref(null);

  const loadingUsers = ref(false);
  const loadingUserById = ref(false);
  const error = ref(null);

  async function loadUsers(params = {}) {
    if (loadingUsers.value) return; // Prevent concurrent calls

    loadingUsers.value = true;
    error.value = null;

    try {
      const response = await userService.all(params);
      users.value = response;
    } catch (e) {
      error.value = 'Erro ao carregar usuários';
      throw e;
    } finally {
      loadingUsers.value = false;
    }
  }

  async function loadUserById(id) {
    loadingUserById.value = true;
    error.value = null;

    try {
      currentUser.value = await userService.find(id);
    } catch (e) {
      error.value = 'Erro ao carregar usuário';
      throw e;
    } finally {
      loadingUserById.value = false;
    }
  }

  function clearCurrentUser() {
    currentUser.value = null;
  }

  return {
    users,
    currentUser,

    loadingUsers,
    loadingUserById,
    error,

    loadUsers,
    loadUserById,
    clearCurrentUser,
  };
});
