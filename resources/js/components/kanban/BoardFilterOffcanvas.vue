<template>
  <Teleport to="body">
    <div v-if="isOpen" class="offcanvas-backdrop fade show" @click="handleBackdropClick"></div>

    <div
      class="offcanvas offcanvas-end"
      :class="{ show: isOpen }"
      tabindex="-1"
      role="dialog"
      aria-labelledby="boardFilterOffcanvasLabel"
    >
      <div class="offcanvas-header">
        <h5 id="boardFilterOffcanvasLabel" class="offcanvas-title">Filtrar cartões</h5>
        <button type="button" class="btn-close" aria-label="Fechar" @click="close"></button>
      </div>

      <div class="offcanvas-body d-flex flex-column p-0">
        <div class="flex-grow-1 overflow-y-auto px-3 pt-3">
          <!-- Status do card -->
          <div class="mb-4">
            <h6 class="fs-xs text-body-secondary text-uppercase mb-2">Status do card</h6>

            <div class="form-check">
              <input
                id="filter-completed"
                class="form-check-input"
                type="checkbox"
                :checked="localFilters.is_completed === 1"
                @change="setIsCompleted(1)"
              />
              <label class="form-check-label" for="filter-completed">
                Marcado como concluído
              </label>
            </div>

            <div class="form-check">
              <input
                id="filter-not-completed"
                class="form-check-input"
                type="checkbox"
                :checked="localFilters.is_completed === 0"
                @change="setIsCompleted(0)"
              />
              <label class="form-check-label" for="filter-not-completed">
                Não marcado como concluído
              </label>
            </div>
          </div>

          <!-- Data de entrega -->
          <div class="mb-4">
            <label for="filter-delivery-date" class="form-label fs-xs text-body-secondary text-uppercase">
              Data de entrega
            </label>
            <input
              id="filter-delivery-date"
              v-model="localFilters.delivery_date"
              type="date"
              class="form-control"
            />
          </div>

          <!-- Número do pedido -->
          <div class="mb-4">
            <label for="filter-order-number" class="form-label fs-xs text-body-secondary text-uppercase">
              Número do pedido
            </label>
            <input
              id="filter-order-number"
              v-model.number="localFilters.order_number"
              type="number"
              min="1"
              class="form-control"
              placeholder="Ex.: 1042"
            />
          </div>

          <!-- Nome do orçamento -->
          <div class="mb-4">
            <label for="filter-quote-name" class="form-label fs-xs text-body-secondary text-uppercase">
              Nome do orçamento
            </label>
            <input
              id="filter-quote-name"
              v-model="localFilters.quote_name"
              type="text"
              class="form-control"
              placeholder="Pesquisar por nome"
            />
          </div>

          <!-- Membros -->
          <div class="mb-4">
            <h6 class="fs-xs text-body-secondary text-uppercase mb-2">Membros</h6>

            <div class="form-check">
              <input
                id="filter-member-none"
                v-model="memberFilterMode"
                class="form-check-input"
                type="radio"
                name="member-filter-mode"
                value="none"
              />
              <label class="form-check-label" for="filter-member-none">Todos</label>
            </div>

            <div class="form-check">
              <input
                id="filter-member-unassigned"
                v-model="memberFilterMode"
                class="form-check-input"
                type="radio"
                name="member-filter-mode"
                value="unassigned"
              />
              <label class="form-check-label" for="filter-member-unassigned">Sem membros</label>
            </div>

            <div class="form-check">
              <input
                id="filter-member-me"
                v-model="memberFilterMode"
                class="form-check-input"
                type="radio"
                name="member-filter-mode"
                value="me"
              />
              <label class="form-check-label" for="filter-member-me">Atribuídos a mim</label>
            </div>

            <div class="form-check">
              <input
                id="filter-member-specific"
                v-model="memberFilterMode"
                class="form-check-input"
                type="radio"
                name="member-filter-mode"
                value="specific"
              />
              <label class="form-check-label" for="filter-member-specific">
                Selecionar membros específicos
              </label>
            </div>

            <div v-if="memberFilterMode === 'specific'" class="mt-2 ps-4">
              <input
                v-model="memberSearch"
                type="text"
                class="form-control form-control-sm mb-2"
                placeholder="Pesquisar membros"
              />

              <div v-if="loadingMembers" class="text-body-secondary small">Carregando...</div>

              <div v-else class="member-list border rounded">
                <div v-if="filteredMembers.length === 0" class="p-2 text-body-secondary small">
                  Nenhum membro encontrado
                </div>

                <div v-for="member in filteredMembers" :key="member.id" class="form-check px-2 py-1">
                  <input
                    :id="`filter-member-${member.id}`"
                    v-model="localFilters.member_ids"
                    class="form-check-input"
                    type="checkbox"
                    :value="member.id"
                  />
                  <label class="form-check-label" :for="`filter-member-${member.id}`">
                    {{ member.name }}
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Ações -->
        <div class="d-flex gap-2 p-3 border-top">
          <button type="button" class="btn btn-subtle flex-fill" @click="handleReset">
            Limpar filtros
          </button>
          <button type="button" class="btn btn-primary flex-fill" @click="handleApply">Aplicar</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onUnmounted, reactive, ref, watch } from 'vue';

import { userService } from '@/services/userService';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue', 'apply-filters', 'reset-filters']);

function createDefaultFilters() {
  return {
    is_completed: null,
    delivery_date: '',
    order_number: '',
    quote_name: '',
    member_ids: [],
  };
}

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const localFilters = reactive(createDefaultFilters());
const memberFilterMode = ref('none');

const members = ref([]);
const memberSearch = ref('');
const loadingMembers = ref(false);
let hasFetchedMembers = false;

const filteredMembers = computed(() => {
  if (!memberSearch.value.trim()) return members.value;

  const query = memberSearch.value.toLowerCase();

  return members.value.filter((member) => member.name.toLowerCase().includes(query));
});

function syncFromProps() {
  const source = props.filters ?? {};

  Object.assign(localFilters, createDefaultFilters(), {
    is_completed: source.is_completed ?? null,
    delivery_date: source.delivery_date ?? '',
    order_number: source.order_number ?? '',
    quote_name: source.quote_name ?? '',
    member_ids: Array.isArray(source.member_ids) ? [...source.member_ids] : [],
  });

  if (source.unassigned) {
    memberFilterMode.value = 'unassigned';
  } else if (source.assigned_to_me) {
    memberFilterMode.value = 'me';
  } else if (localFilters.member_ids.length > 0) {
    memberFilterMode.value = 'specific';
  } else {
    memberFilterMode.value = 'none';
  }
}

async function fetchMembers() {
  if (hasFetchedMembers || loadingMembers.value) return;

  loadingMembers.value = true;

  try {
    const { data } = await userService.all();
    members.value = Array.isArray(data) ? data : [];
    hasFetchedMembers = true;
  } catch (error) {
    console.error(error);
  } finally {
    loadingMembers.value = false;
  }
}

function setIsCompleted(value) {
  localFilters.is_completed = localFilters.is_completed === value ? null : value;
}

function close() {
  isOpen.value = false;
}

function handleBackdropClick() {
  close();
}

function handleEscapeKey(event) {
  if (event.key === 'Escape') {
    close();
  }
}

function buildPayload() {
  return {
    is_completed: localFilters.is_completed,
    delivery_date: localFilters.delivery_date || null,
    order_number: localFilters.order_number || null,
    quote_name: localFilters.quote_name || null,
    unassigned: memberFilterMode.value === 'unassigned',
    assigned_to_me: memberFilterMode.value === 'me',
    member_ids: memberFilterMode.value === 'specific' ? [...localFilters.member_ids] : [],
  };
}

function handleApply() {
  emit('apply-filters', buildPayload());
  close();
}

function handleReset() {
  Object.assign(localFilters, createDefaultFilters());
  memberFilterMode.value = 'none';
  memberSearch.value = '';

  emit('reset-filters');
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      syncFromProps();
      fetchMembers();
      document.body.classList.add('offcanvas-open-lock');
      document.addEventListener('keydown', handleEscapeKey);
    } else {
      document.body.classList.remove('offcanvas-open-lock');
      document.removeEventListener('keydown', handleEscapeKey);
    }
  },
);

onUnmounted(() => {
  document.body.classList.remove('offcanvas-open-lock');
  document.removeEventListener('keydown', handleEscapeKey);
});
</script>

<style scoped>
.member-list {
  max-height: 200px;
  overflow-y: auto;
}
</style>
