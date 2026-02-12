<template>
  <div
    v-if="budget?.dropshipping_data"
    class="border rounded p-3 mb-3"
  >
    <h6 class="fw-semibold mb-3">Dados de entrega (dropshipping)</h6>
    <div class="small">
      <div class="mb-1"><strong>{{ budget.dropshipping_data.name }}</strong></div>
      <div v-if="budget.dropshipping_data.person_type" class="text-muted">
        {{ budget.dropshipping_data.person_type === 'PF' ? 'Pessoa Física' : 'Pessoa Jurídica' }}
        <span v-if="budget.dropshipping_data.cpf_cnpj"> · {{ budget.dropshipping_data.cpf_cnpj }}</span>
      </div>
      <div v-if="budget.dropshipping_data.email" class="mt-1">{{ budget.dropshipping_data.email }}</div>
      <div v-if="budget.dropshipping_data.phone" class="mt-1">{{ budget.dropshipping_data.phone }}</div>
      <div v-if="budget.dropshipping_data.cep || budget.dropshipping_data.public_space" class="mt-2 border-top pt-2">
        <div v-if="budget.dropshipping_data.public_space">
          {{ budget.dropshipping_data.public_space }}
          <span v-if="budget.dropshipping_data.number">, {{ budget.dropshipping_data.number }}</span>
          <span v-if="budget.dropshipping_data.complement"> – {{ budget.dropshipping_data.complement }}</span>
        </div>
        <div v-if="budget.dropshipping_data.neighborhood || budget.dropshipping_data.city">
          {{ [budget.dropshipping_data.neighborhood, budget.dropshipping_data.city, budget.dropshipping_data.uf].filter(Boolean).join(' · ') }}
        </div>
        <div v-if="budget.dropshipping_data.cep">CEP {{ budget.dropshipping_data.cep }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  budget: { type: Object, default: null },
});
</script>