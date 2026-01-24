<template>
    <Teleport v-if="visible" to="body">
        <div>
            <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cancelar orçamento</h5>
                            <button
                                type="button"
                                class="btn-close"
                                aria-label="Close"
                                @click="$emit('close')"
                            ></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">
                                Deseja realmente cancelar o orçamento
                                <strong>{{ budget?.name }}</strong>?
                            </p>
                            <p class="text-muted small mb-0">
                                Você poderá editá-lo ou reativá-lo
                                posteriormente se necessário.
                            </p>
                            <p v-if="error" class="text-danger small mt-3 mb-0">
                                {{ error }}
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-subtle"
                                :disabled="cancelling"
                                @click="$emit('close')"
                            >
                                Manter orçamento
                            </button>
                            <button
                                type="button"
                                class="btn btn-warning"
                                :disabled="cancelling"
                                @click="$emit('confirm')"
                            >
                                <span
                                    v-if="cancelling"
                                    class="spinner-border spinner-border-sm me-2"
                                    role="status"
                                    aria-hidden="true"
                                ></span>
                                Cancelar orçamento
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
    budget: {
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

