<template>
  <div class="members-section">
    <!-- Botões de ação -->
    <div class="members-section-actions position-relative">
      <button class="btn btn-primary me-2" @click="toggleMembersMenu">
        <i class="fa-solid fa-plus fa-fw"></i>
        Adicionar membro
      </button>

      <button
        v-if="!isCurrentUserMember"
        class="btn btn-secondary"
        @click="handleJoinAsMember"
        :disabled="joiningAsMember"
      >
        <i class="bi bi-plus-circle fa-fw"></i>
        {{ joiningAsMember ? 'Ingressando...' : 'Ingressar' }}
      </button>
      <button
        v-else
        class="btn btn-danger"
        @click="handleLeaveAsMember"
        :disabled="leavingAsMember"
      >
        <i class="bi bi-x-circle fa-fw"></i>
        {{ leavingAsMember ? 'Saindo...' : 'Sair' }}
      </button>

      <!-- Menu de adicionar membros -->
      <div v-if="showMembersMenu" class="members-section-menu">
        <div class="members-section-menu-header">
          <button class="members-section-menu-back" @click="closeMembersMenu">
            <i class="fa fa-chevron-left fa-fw"></i>
          </button>
          <h3 class="members-section-menu-title">Membros</h3>
          <button class="members-section-menu-close" @click="closeMembersMenu">
            <i class="fa fa-times fa-fw"></i>
          </button>
        </div>

        <div class="members-section-menu-search">
          <input
            v-model="memberSearchQuery"
            type="text"
            class="members-section-menu-search-input"
            placeholder="Pesquisar membros"
          />
        </div>

        <div class="members-section-menu-content">
          <h4 class="members-section-menu-section-title">Adicionar membros</h4>
          <div v-if="loadingMembers" class="members-section-menu-loading">
            <span>Carregando...</span>
          </div>
          <div v-else-if="availableMembers.length === 0" class="members-section-menu-empty">
            <span>Nenhum designer encontrado</span>
          </div>
          <div v-else class="members-section-menu-list">
            <div
              v-for="member in filteredMembers"
              :key="member.id"
              class="members-section-menu-item"
              :class="{ 'is-adding': addingMember && currentAddingMemberId === member.id }"
              @click="handleAddMember(member)"
            >
              <div class="members-section-menu-avatar" :style="{ backgroundColor: getAvatarColor(member.name) }">
                {{ getInitials(member.name) }}
              </div>
              <span class="members-section-menu-name">{{ member.name }}</span>
              <span v-if="addingMember && currentAddingMemberId === member.id" class="members-section-menu-loading-indicator">
                <i class="fa fa-spinner fa-spin fa-fw"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de membros do card -->
    <div v-if="card.members && card.members.length > 0" class="members-section-list-container">
      <h3 class="members-section-list-title">
        <i class="fa fa-user"></i> Membros
      </h3>
      <div class="members-section-list">
        <div
          v-for="member in card.members"
          :key="member.id"
          class="members-section-member-avatar"
          :class="{ 'is-clickable': canRemoveMembers }"
          :style="{ backgroundColor: getAvatarColor(member.name) }"
          :title="member.name"
          @click="canRemoveMembers ? handleMemberClick(member) : null"
        >
          {{ getInitials(member.name) }}
          <div v-if="showMemberMenu && selectedMember?.id === member.id" class="members-section-member-popover" @click.stop>
            <button class="members-section-member-remove" @click="handleRemoveMember(member)">
              <i class="fa fa-times fa-fw"></i>
              Remover do card
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useMemberService } from '@/views/layouts/services/memberService';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
  canRemoveMembers: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['member-added', 'member-removed', 'user-joined', 'user-left']);

const auth = useAuthStore();
const memberService = useMemberService();

const showMembersMenu = ref(false);
const availableMembers = ref([]);
const memberSearchQuery = ref('');
const loadingMembers = ref(false);
const addingMember = ref(false);
const currentAddingMemberId = ref(null);
const joiningAsMember = ref(false);
const leavingAsMember = ref(false);
const showMemberMenu = ref(false);
const selectedMember = ref(null);

const isCurrentUserMember = computed(() => {
  if (!auth.user?.id || !props.card?.members) {
    return false;
  }
  return props.card.members.some(member => member.id === auth.user.id);
});

const filteredMembers = computed(() => {
  // Filtrar membros que já estão no card
  const cardMemberIds = props.card?.members?.map(m => m.id) || [];
  let members = availableMembers.value.filter(member => !cardMemberIds.includes(member.id));

  if (!memberSearchQuery.value.trim()) {
    return members;
  }
  const query = memberSearchQuery.value.toLowerCase().trim();
  return members.filter(member =>
    member.name.toLowerCase().includes(query)
  );
});

function getInitials(name) {
  if (!name) {
    return '??';
  }
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
}

function getAvatarColor(name) {
  if (!name) {
    return '#5e6c84';
  }

  const colors = [
    '#00b8d9', '#00a86b', '#0065ff', '#5243aa', '#ff5630',
    '#ff8b00', '#36b37e', '#ffab00', '#6554c0', '#00c7e6',
  ];

  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  return colors[Math.abs(hash) % colors.length];
}

function toggleMembersMenu() {
  showMembersMenu.value = !showMembersMenu.value;
  if (showMembersMenu.value && availableMembers.value.length === 0) {
    fetchMembers();
  }
}

function closeMembersMenu() {
  showMembersMenu.value = false;
  memberSearchQuery.value = '';
}

async function fetchMembers() {
  try {
    loadingMembers.value = true;
    const response = await memberService.searchMembers('designer');

    if (response.success && response.data) {
      // Se a resposta estiver paginada, pegar o array de dados
      if (response.data.data && Array.isArray(response.data.data)) {
        availableMembers.value = response.data.data;
      } else if (Array.isArray(response.data)) {
        availableMembers.value = response.data;
      } else if (Array.isArray(response)) {
        availableMembers.value = response;
      } else {
        availableMembers.value = [];
      }
    } else {
      availableMembers.value = [];
    }
  } catch (error) {
    console.error('Erro ao buscar membros:', error);
    availableMembers.value = [];
  } finally {
    loadingMembers.value = false;
  }
}

async function handleAddMember(member) {
  if (!props.card?.id || addingMember.value) {
    return;
  }

  addingMember.value = true;
  currentAddingMemberId.value = member.id;

  try {
    const response = await memberService.addMember(props.card.id, member.id, 'product');

    // Fechar o menu de membros após adicionar
    closeMembersMenu();

    // Adicionar o membro à lista do card
    if (props.card && !props.card.members) {
      props.card.members = [];
    }
    if (props.card && !props.card.members.find(m => m.id === member.id)) {
      props.card.members.push({
        id: member.id,
        name: member.name,
      });
    }

    if (window.Toast) {
      window.Toast.fire({
        icon: 'success',
        title: response.message || 'Membro adicionado com sucesso',
      });
    }

    emit('member-added', member);
  } catch (error) {
    console.error('Erro ao adicionar membro:', error);
    const errorMessage = error.response?.data?.message || 'Erro ao adicionar membro. Tente novamente.';

    if (window.Swal) {
      window.Swal.fire('Erro!', errorMessage, 'error');
    } else {
      alert(errorMessage);
    }
  } finally {
    addingMember.value = false;
    currentAddingMemberId.value = null;
  }
}

async function handleJoinAsMember() {
  if (!props.card?.id || joiningAsMember.value || !auth.user?.id) {
    return;
  }

  joiningAsMember.value = true;

  try {
    const response = await memberService.addMember(props.card.id, auth.user.id);

    // Adicionar o usuário logado à lista de membros do card
    if (props.card && !props.card.members) {
      props.card.members = [];
    }
    if (props.card && auth.user && !props.card.members.find(m => m.id === auth.user.id)) {
      props.card.members.push({
        id: auth.user.id,
        name: auth.user.name,
      });
    }

    if (window.Toast) {
      window.Toast.fire({
        icon: 'success',
        title: response.message || 'Você ingressou no card com sucesso',
      });
    }

    emit('user-joined');
  } catch (error) {
    console.error('Erro ao ingressar no card:', error);
    const errorMessage = error.response?.data?.message || 'Erro ao ingressar no card. Tente novamente.';

    if (window.Swal) {
      window.Swal.fire('Erro!', errorMessage, 'error');
    } else {
      alert(errorMessage);
    }
  } finally {
    joiningAsMember.value = false;
  }
}

async function handleLeaveAsMember() {
  if (!props.card?.id || leavingAsMember.value || !auth.user?.id) {
    return;
  }

  leavingAsMember.value = true;

  try {
    const response = await memberService.removeMember(props.card.id, auth.user.id, 'layout');

    // Remover o usuário logado da lista de membros do card
    if (props.card && Array.isArray(props.card.members)) {
      props.card.members = props.card.members.filter(m => m.id !== auth.user.id);
    }

    if (window.Toast) {
      window.Toast.fire({
        icon: 'success',
        title: response.message || 'Você saiu do card com sucesso',
      });
    }

    emit('user-left');
  } catch (error) {
    console.error('Erro ao sair do card:', error);
    const errorMessage = error.response?.data?.message || 'Erro ao sair do card. Tente novamente.';

    if (window.Swal) {
      window.Swal.fire('Erro!', errorMessage, 'error');
    } else {
      alert(errorMessage);
    }
  } finally {
    leavingAsMember.value = false;
  }
}

function handleMemberClick(member) {
  if (showMemberMenu.value && selectedMember.value?.id === member.id) {
    showMemberMenu.value = false;
    selectedMember.value = null;
  } else {
    showMemberMenu.value = true;
    selectedMember.value = member;
  }
}

async function handleRemoveMember(member) {
  if (!props.card?.id || !member?.id) {
    return;
  }

  if (window.Swal) {
    const result = await window.Swal.fire({
      title: 'Remover membro?',
      text: `Deseja remover ${member.name} do card?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sim, remover',
      cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) {
      showMemberMenu.value = false;
      selectedMember.value = null;
      return;
    }
  }

  try {
    const response = await memberService.removeMember(props.card.id, member.id, 'product');

    // Remover o membro da lista do card
    if (props.card && Array.isArray(props.card.members)) {
      props.card.members = props.card.members.filter(m => m.id !== member.id);
    }

    // Adicionar o membro de volta à lista de disponíveis
    if (!availableMembers.value.find(m => m.id === member.id)) {
      availableMembers.value.push(member);
    }

    showMemberMenu.value = false;
    selectedMember.value = null;

    if (window.Toast) {
      window.Toast.fire({
        icon: 'success',
        title: response.message || 'Membro removido com sucesso',
      });
    }

    emit('member-removed', member);
  } catch (error) {
    console.error('Erro ao remover membro:', error);
    const errorMessage = error.response?.data?.message || 'Erro ao remover membro. Tente novamente.';

    if (window.Swal) {
      window.Swal.fire('Erro!', errorMessage, 'error');
    } else {
      alert(errorMessage);
    }
  }
}

// Fechar menu de membro ao clicar fora
watch(() => showMemberMenu.value, (isOpen) => {
  if (isOpen) {
    const closeMenu = (e) => {
      if (!e.target.closest('.members-section-member-avatar')) {
        showMemberMenu.value = false;
        selectedMember.value = null;
        document.removeEventListener('click', closeMenu);
      }
    };
    setTimeout(() => {
      document.addEventListener('click', closeMenu);
    }, 0);
  }
});

// Resetar quando o card mudar
watch(() => props.card?.id, () => {
  showMemberMenu.value = false;
  selectedMember.value = null;
  closeMembersMenu();
});
</script>

<style lang="scss" scoped>
.members-section {
  margin-bottom: 24px;
}

.members-section-actions {
  margin-bottom: 16px;
}

.members-section-menu {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 0.5rem;
  width: 340px;
  background-color: var(--bs-dark, var(--bs-body-bg));
  border-radius: 0.5rem;
  box-shadow: var(--bs-box-shadow-lg);
  z-index: 1000;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 600px;
  border: 1px solid var(--bs-border-color);
}

.members-section-menu-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--bs-border-color-translucent);
}

.members-section-menu-back,
.members-section-menu-close {
  background: none;
  border: none;
  color: var(--bs-body-color);
  font-size: 1rem;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s ease;

  &:hover {
    background-color: var(--bs-secondary-bg);
  }
}

.members-section-menu-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--bs-body-color);
  margin: 0;
  flex: 1;
  text-align: center;
}

.members-section-menu-search {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--bs-border-color-translucent);
}

.members-section-menu-search-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  background-color: var(--bs-secondary-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 0.25rem;
  color: var(--bs-body-color);
  font-size: 0.875rem;

  &::placeholder {
    color: var(--bs-secondary);
  }

  &:focus {
    outline: none;
    border-color: var(--bs-primary);
    background-color: var(--bs-body-bg);
  }
}

.members-section-menu-content {
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem 1rem;
}

.members-section-menu-section-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--bs-secondary);
  text-transform: uppercase;
  margin: 0 0 0.75rem 0;
  letter-spacing: 0.5px;
}

.members-section-menu-loading,
.members-section-menu-empty {
  padding: 1rem;
  text-align: center;
  color: var(--bs-secondary);
  font-size: 0.875rem;
}

.members-section-menu-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.members-section-menu-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  border-radius: 0.25rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
  position: relative;

  &:hover:not(.is-adding) {
    background-color: var(--bs-secondary-bg);
  }

  &:active:not(.is-adding) {
    background-color: var(--bs-tertiary-bg);
  }

  &.is-adding {
    opacity: 0.7;
    cursor: wait;
  }
}

.members-section-menu-loading-indicator {
  margin-left: auto;
  color: var(--bs-primary);
  font-size: 0.875rem;
}

.members-section-menu-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--bs-white);
  font-size: 0.8125rem;
  font-weight: 600;
  flex-shrink: 0;
}

.members-section-menu-name {
  font-size: 0.875rem;
  color: var(--bs-body-color);
  font-weight: 400;
}

.members-section-list-container {
  margin-top: 16px;
}

.members-section-list-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--bs-body-color);
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.members-section-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.members-section-member-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--bs-white);
  font-weight: 600;
  font-size: 0.875rem;
  flex-shrink: 0;
  cursor: default;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  position: relative;

  &:hover {
    transform: scale(1.1);
    box-shadow: var(--bs-box-shadow);
  }

  &.is-clickable {
    cursor: pointer;
  }
}

.members-section-member-popover {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 0.5rem;
  background-color: var(--bs-dropdown-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 0.375rem;
  box-shadow: var(--bs-box-shadow-lg);
  z-index: 1000;
  min-width: 180px;
  overflow: hidden;
}

.members-section-member-remove {
  width: 100%;
  padding: 0.625rem 1rem;
  background: antiquewhite;
  border: none;
  text-align: left;
  color: var(--bs-danger);
  font-size: 0.875rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: background-color 0.2s ease;

  &:hover {
    background-color: var(--bs-danger-bg-subtle);
  }

  i {
    font-size: 0.75rem;
  }
}
</style>

