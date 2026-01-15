<template>
    <div class="card mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0 fw-semibold">Frete</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="text-muted small">CEP</div>
                <div class="fw-semibold">{{ data.cep || '-' }}</div>
            </div>
            <div v-if="data.selected_carrier_name" class="mb-3">
                <div class="text-muted small">Transportadora</div>
                <div class="fw-semibold">{{ data.selected_carrier_name }}</div>
            </div>
            <div v-if="data.selected_carrier_price" class="mb-3">
                <div class="text-muted small">Valor do Frete</div>
                <div class="fw-semibold">{{ formatCurrency(data.selected_carrier_price) }}</div>
            </div>
            <div v-if="data.selected_carrier_delivery_time" class="mb-0">
                <div class="text-muted small">Prazo de Entrega</div>
                <div class="fw-semibold">{{ formatDeliveryTime(data.selected_carrier_delivery_time) }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useFormatting } from '@/composables/useFormatting';

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
});

const { formatCurrency, formatDeliveryTime } = useFormatting();
</script>

