<template>
  <div v-if="budget?.dropshipping_budget === 1 && budget?.dropshipping_data" class="border rounded p-3 mb-3">
    <h6 class="fw-semibold mb-3">Dados de Dropshipping</h6>
    <div class="row g-2 small">
      <div class="col-md-6"><span class="text-muted">Nome:</span> {{ budget.dropshipping_data.name || '-' }}</div>
      <div class="col-md-6"><span class="text-muted">CPF/CNPJ:</span> {{ budget.dropshipping_data.cpf_cnpj || '-' }}</div>
      <div class="col-md-6"><span class="text-muted">Email:</span> {{ budget.dropshipping_data.email || '-' }}</div>
      <div class="col-md-6"><span class="text-muted">Telefone:</span> {{ budget.dropshipping_data.phone || '-' }}</div>
      <div class="col-12">
        <div class="text-muted small mb-1">Endereço</div>
        <div v-for="line in getDropshippingAddressLines(budget.dropshipping_data)" :key="line.label" class="small">
          <span class="text-muted">{{ line.label }}:</span> {{ line.value }}
        </div>
        <div v-if="!getDropshippingAddressLines(budget.dropshipping_data).length" class="small">-</div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  budget: {
    type: Object,
    default: null,
  },
});

function getDropshippingAddressLines(addr) {
  if (!addr)
    return [];

  const lines = [];
  const logradouro = [addr.public_space, addr.number ? `Nº ${addr.number}`
        : null, addr.complement || null].filter(Boolean).join(', ');

  if (logradouro)
    lines.push({ label: 'Logradouro', value: logradouro });

  if (addr.neighborhood)
    lines.push({ label: 'Bairro', value: addr.neighborhood });

  const cidadeUf = [addr.city, addr.state || addr.uf].filter(Boolean).join(' - ');

  if (cidadeUf)
    lines.push({ label: 'Cidade/UF', value: cidadeUf });

  if (addr.cep)
    lines.push({ label: 'CEP', value: addr.cep.replace(/\D/g, '').replace(/(\d{5})(\d{3})/, '$1-$2') });

  return lines;
}
</script>
