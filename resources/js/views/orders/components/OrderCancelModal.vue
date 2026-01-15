<template>
    <Teleport v-if="visible" to="body">
        <div>
            <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cancelar pedido</h5>
                            <button
                                type="button"
                                class="btn-close"
                                aria-label="Close"
                                @click="$emit('close')"
                            ></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">
                                Tem certeza que deseja cancelar o pedido
                                <strong>{{ pedido?.name }}</strong>?
                            </p>
                            <p class="text-muted small mb-0">
                                Essa ação não pode ser desfeita. O status do pedido será alterado
                                para <strong>Cancelado</strong>.
                            </p>
                            <p v-if="error" class="text-danger small mt-3 mb-0">
                                {{ error }}
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                :disabled="cancelling"
                                @click="$emit('close')"
                            >
                                Manter pedido
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger"
                                :disabled="cancelling"
                                @click="$emit('confirm')"
                            >
                                <span
                                    v-if="cancelling"
                                    class="spinner-border spinner-border-sm me-2"
                                    role="status"
                                    aria-hidden="true"
                                ></span>
                                Cancelar pedido
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        </div>
    </Teleport>
</template>

<script setup>
defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    pedido: {
        type: Object,
        default: null,
    },
    cancelling: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
});

defineEmits(['close', 'confirm']);
</script>
