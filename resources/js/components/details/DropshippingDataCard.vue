<template>
  <div v-if="isDropshippingEnabled && dropshippingData" class="card mb-4">
    <div class="card-header bg-transparent">
      <h5 class="mb-0 fw-semibold">Dados de Dropshipping</h5>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="text-muted small">Nome</div>
          <div class="fw-semibold">{{ dropshippingData.name || '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="text-muted small">Tipo de Pessoa</div>
          <div class="fw-semibold">{{ dropshippingData.person_type || '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="text-muted small">CPF/CNPJ</div>
          <div class="fw-semibold">{{ dropshippingData.cpf_cnpj || '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="text-muted small">Email</div>
          <div class="fw-semibold">{{ dropshippingData.email || '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="text-muted small">Telefone</div>
          <div class="fw-semibold">{{ dropshippingData.phone || '-' }}</div>
        </div>
        <div class="col-md-12">
          <div class="text-muted small">Endereço Completo</div>
          <div class="fw-semibold">
            {{ formatDropshippingAddress() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  isDropshippingEnabled: {
    type: Boolean,
    default: false,
  },
  dropshippingData: {
    type: Object,
    default: null,
  },
});

function formatDropshippingAddress() {
  if (!props.dropshippingData) return '-';
  const addr = props.dropshippingData;
  const addressLine = [addr.public_space, addr.number ? `Nº ${addr.number}` : null]
    .filter(Boolean)
    .join(', ');
  const parts = [
    addressLine || null,
    addr.neighborhood,
    addr.city,
    addr.state,
    addr.cep ? `CEP: ${addr.cep}` : null,
  ].filter(Boolean);
  return parts.join(', ') || '-';
}
</script>
