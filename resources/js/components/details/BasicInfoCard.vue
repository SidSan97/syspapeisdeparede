<template>
    <div class="card mb-4">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <div class="fw-semibold fs-5">{{ data.name }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <div>
                    <span
                        class="badge"
                        :class="{
                            'bg-warning text-dark': data.status === 'Pendente de Revisão',
                            'bg-success': data.status === 'Aprovado',
                            'bg-info': data.status === 'Em aberto'
                        }"
                    >
                        {{ data.status || 'Sem status' }}
                    </span>
                </div>
            </div>
            <div v-if="isDropshippingEnabled" class="mb-3">
                <label class="form-label">Dropshipping</label>
                <div>
                    <span class="badge bg-primary">Habilitado</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    data: {
        type: Object,
        required: true,
    },
    isDropshippingEnabled: {
        type: Boolean,
        default: false,
    },
});
</script>

