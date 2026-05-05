<template>
  <div>
    <!-- Lista de membros do card -->
    <div>
      <h3 class="fs-xs text-body-secondary">Membros</h3>
      <div class="avatar-group">
        <BaseDropdown
          v-for="member in card.members"
          :key="`member-${member.id}`"
          :title="member.name"
        >
          <template #trigger="{ toggle }">
            <BaseAvatar :src="avatarUrl(member.name)" @click="toggle" />
          </template>

          <li v-if="canRemoveMembers && member.id !== auth.user?.id">
            <button class="dropdown-item" @click="confirmRemoveMember(member)">
              Remover do Cartão
            </button>
          </li>

          <li v-if="member.id === auth.user?.id">
            <button class="dropdown-item" @click="handleLeaveAsMember" :disabled="loadingLeave">
              <span v-if="loadingLeave">Saindo...</span>
              <span v-else>Sair do Cartão</span>
            </button>
          </li>
        </BaseDropdown>

        <BaseDropdown @open="fetchMembers">
          <template #trigger="{ open, toggle }">
            <button class="avatar btn btn-default avatar-btn" @click="toggle">
              <IconPlus :size="18" class="p-1" />
            </button>
          </template>

          <li><h6 class="dropdown-header text-center">Membros</h6></li>
          <li class="px-4">
            <input
              v-model="query"
              type="text"
              class="form-control form-control-sm"
              placeholder="Pesquisar membros"
            />
          </li>

          <template v-if="loadingMembers">
            <li class="p-4 text-center text-muted">Carregando...</li>
          </template>
          <template v-else-if="availableMembers.length === 0">
            <li class="p-4 text-center text-muted">Nenhum designer encontrado</li>
          </template>
          <template v-else>
            <li v-for="member in filteredMembers" :key="member.id">
              <button
                type="button"
                class="dropdown-item d-flex align-items-center gap-3"
                :disabled="loadingAdd === member.id"
                @click.prevent="handleAdd(member)"
              >
                <BaseAvatar :src="avatarUrl(member.name)" />
                <span>{{ member.name }}</span>
              </button>
            </li>
          </template>
        </BaseDropdown>
      </div>
    </div>

    <!-- Botões de ação -->
    <div class="mb-4 mt-2">
      <button
        v-if="!isCurrentUserMember"
        class="btn btn-default btn-sm"
        @click="handleAdd(auth.user)"
        :disabled="!auth.user || loadingAdd === auth.user?.id"
      >
        <IconUserPlus :size="18" />

        Ingressar
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useDialog } from '@/composables/useDialog';
import { useToast } from '@/composables/useToast';
import { userService } from '@/services/userService';
import { memberService } from '@/modules/card-modals/services/memberService';
import { useAuthStore } from '@/stores/auth';

import BaseDropdown from '@/components/common/BaseDropdown.vue';
import BaseAvatar from '@/components/common/BaseAvatar.vue';

import { IconUserPlus, IconPlus } from '@tabler/icons-vue';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
  canRemoveMembers: {
    type: Boolean,
    default: false,
  },
  typePage: {
    type: String,
    default: 'layout',
    validator: (value) => ['layout', 'product'].includes(value),
  },
});

const emit = defineEmits(['member-added', 'member-removed']);

const dialog = useDialog();
const toast = useToast();
const auth = useAuthStore();

const hasFetchedMembers = ref(false);
const availableMembers = ref([]);
const query = ref('');
const debouncedQuery = ref('');

watch(
  query,
  useDebounceFn((value) => {
    debouncedQuery.value = value;
  }, 300),
);

const loadingMembers = ref(false);
const loadingAdd = ref(null);
const loadingLeave = ref(false);

const isCurrentUserMember = computed(() => {
  if (!auth.user?.id || !props.card?.members) return false;

  return props.card.members.some((member) => member.id === auth.user.id);
});

const cardMemberIds = computed(() => {
  const members = props.card?.members ?? [];
  return new Set(members.map((m) => m.id));
});

const availableNonCardMembers = computed(() =>
  availableMembers.value.filter((member) => !cardMemberIds.value.has(member.id)),
);

const filteredMembers = computed(() => {
  const base = availableNonCardMembers.value;

  if (!debouncedQuery.value.trim()) return base;

  const query = debouncedQuery.value.toLowerCase();

  return base.filter((member) => member.name.toLowerCase().includes(query));
});

const avatarUrl = (name) =>
  `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&size=32&background=random`;

async function fetchMembers() {
  if (hasFetchedMembers.value || loadingMembers.value) return;

  query.value = '';
  debouncedQuery.value = '';

  loadingMembers.value = true;

  try {
    const { data } = await userService.all({ role: 'designer' });

    availableMembers.value = data;
    hasFetchedMembers.value = true;
  } catch (err) {
    handleApiError(err, 'Erro ao carregar membros.');
  } finally {
    loadingMembers.value = false;
  }
}

async function handleAdd(member) {
  if (!member?.id) return;
  if (loadingAdd.value === member.id) return;

  loadingAdd.value = member.id;

  try {
    emit('member-added', member);

    await memberService.addMember(props.card.id, member.id, props.typePage);

    toast.success('Usuário adicionado com sucesso.');
  } catch (err) {
    emit('member-removed', member.id);

    handleApiError(err, 'Erro ao adicionar membro.');
  } finally {
    loadingAdd.value = null;
  }
}

async function handleLeaveAsMember() {
  if (!props.card?.id || loadingLeave.value || !auth.user?.id) return;

  loadingLeave.value = true;

  try {
    await memberService.removeMember(props.card.id, auth.user.id, props.typePage);

    toast.success('Você saiu do card com sucesso');

    emit('member-removed', auth.user.id);
  } catch (err) {
    console.error('Erro ao sair do card:', err);

    handleApiError(err, 'Erro ao sair do card. Tente novamente.');
  } finally {
    loadingLeave.value = false;
  }
}

async function confirmRemoveMember(member) {
  if (!member?.id) return;

  const confirmed = await dialog.confirmDelete({
    title: 'Remover membro?',
    text: `Deseja remover ${member.name} do card?`,
    showCancelButton: true,
    confirmButtonText: 'Sim, remover',
  });

  if (!confirmed) return;

  await removeMember(member);
}

async function removeMember(member) {
  emit('member-removed', member.id);

  try {
    await memberService.removeMember(props.card.id, member.id, props.typePage);

    toast.success('Membro removido com sucesso.');
  } catch (err) {
    emit('member-added', member);

    console.error('Erro ao remover membro:', err);

    handleApiError(err, 'Erro ao remover membro. Tente novamente.');
  }
}

function handleApiError(error, fallbackMessage) {
  console.error(error);

  const message = error.response?.data?.message || fallbackMessage;

  toast.error(message);
}
</script>

<style lang="scss" scoped>
.avatar-btn {
  --bs-avatar-bg: var(--bs-btn-bg);
  --bs-btn-padding-x: 0;
  --bs-btn-padding-y: 0;
  color: var(--bs-btn-color);
}
</style>
