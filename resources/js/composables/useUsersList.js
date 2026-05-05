import { computed, reactive } from 'vue';
import { useToast } from '@/composables/useToast';
import { userService } from '@/services/userService';
import { useUserStore } from '@/stores/userStore';

export function useUsersList() {
  const toast = useToast();
  const userStore = useUserStore();

  const filters = reactive({
    page: 1,
    search: '',
    role: '',
  });

  const userList = computed(() => userStore.users?.data || []);
  const loading = computed(() => userStore.loadingUsers);

  const fetchUsers = (params) => {
    if (params) Object.assign(filters, params);

    userStore.loadUsers(params);
  };

  function goToPage(page) {
    filters.page = page;

    fetchUsers();
  }

  const deleteUser = async (user) => {
    if (!user?.id) return;

    try {
      await userService.delete(user.id);

      fetchUsers(1);

      toast.success('Usuário excluído com sucesso.');
    } catch (error) {
      toast.error('Erro ao excluir o usuário. Tente novamente.');
    }
  };

  return {
    filters,
    userList,
    loading,
    fetchUsers,
    goToPage,
    deleteUser,
  };
}
