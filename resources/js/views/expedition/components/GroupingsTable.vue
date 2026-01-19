<template>
  <div>
    <form class="g-3 align-items-center mb-4" role="search">
      <div class="d-flex">
        <div style="min-width: 200px;">
          <label for="grouping-carrier-select" class="form-label">Transportadora</label>
          <select
            id="grouping-carrier-select"
            class="form-select"
            :value="selectedCarrier"
            @change="$emit('carrier-changed', $event.target.value)"
          >
            <option :value="null">Selecione uma transportadora</option>
            <option
              v-for="carrier in carriersList"
              :key="carrier"
              :value="carrier"
            >
              {{ carrier }}
            </option>
          </select>
        </div>
      </div>
    </form>

    <div class="card-body p-0 mt-4">
      <div v-if="loading" class="p-5 text-center text-muted fw-semibold">
        Carregando agrupamentos...
      </div>

      <EmptyState
        v-else-if="!selectedCarrier"
        heading="Selecione uma transportadora"
        icon="shipping-fast"
        class="p-5"
      >
        Por favor, selecione uma transportadora para visualizar os agrupamentos.
      </EmptyState>

      <EmptyState
        v-else-if="groupings.length === 0"
        heading="Nenhum agrupamento encontrado"
        icon="shipping-fast"
        class="p-5"
      >
        Não há agrupamentos para a transportadora selecionada no momento.
      </EmptyState>

      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Nº Agrupamento</th>
              <th scope="col">Transportadora</th>
              <th scope="col">Quantidade de Notas</th>
              <th scope="col">Data</th>
              <th scope="col" style="width: 64px;">Opções</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="grouping in groupings" :key="grouping.id">
              <th scope="row" class="fw-semibold">{{ grouping.idAgrupamento || '—' }}</th>
              <td>{{ selectedCarrier || '—' }}</td>
              <td>{{ grouping.expedicoes.length || 0 }}</td>
              <td>{{ formatDate(grouping.data) }}</td>
              <td>
                <div class="dropdown">
                  <button
                    class="btn btn-subtle btn-sm"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i class="fa fa-ellipsis-h"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <button
                        class="dropdown-item"
                        type="button"
                        @click="$emit('view-details', grouping)"
                      >
                        Ver detalhes
                      </button>
                    </li>
                    <li>
                      <button
                        class="dropdown-item"
                        type="button"
                        @click="$emit('print-labels', grouping.idAgrupamento)"
                      >
                        Imprimir etiquetas {{ selectedCarrier || '' }}
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import { formatDate } from '@/utils/dateUtils';
import { getCarriersList } from '@/constants/carriers';

const props = defineProps({
  groupings: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  selectedCarrier: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['carrier-changed', 'view-details', 'print-labels']);

const carriersList = ref(getCarriersList());
</script>
