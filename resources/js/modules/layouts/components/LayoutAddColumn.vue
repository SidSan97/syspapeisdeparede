<template>
    <div class="trello-column trello-column-add">
        <button
            v-if="!showModal"
            class="btn btn-light w-100 d-flex align-items-center justify-content-center gap-2"
            @click="$emit('open')"
        >
            <i class="fa fa-plus"></i>
            <span>Adicionar outra lista</span>
        </button>
        <div v-else class="card">
            <div class="card-body p-2">
                <input
                    :value="columnName"
                    @input="$emit('update:columnName', $event.target.value)"
                    @keyup.enter="$emit('create')"
                    @keyup.esc="$emit('close')"
                    class="form-control form-control-sm mb-2"
                    placeholder="Digite o nome da lista..."
                    :ref="inputRef"
                />
                <div class="d-flex gap-2">
                    <button
                        class="btn btn-primary btn-sm flex-fill"
                        @click="$emit('create')"
                        :disabled="!columnName.trim() || creating"
                    >
                        {{ creating ? 'Criando...' : 'Adicionar Lista' }}
                    </button>
                    <button
                        class="btn btn-secondary btn-sm"
                        @click="$emit('close')"
                    >
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    showModal: {
        type: Boolean,
        default: false,
    },
    columnName: {
        type: String,
        default: '',
    },
    creating: {
        type: Boolean,
        default: false,
    },
    inputRef: {
        type: Object,
        default: null,
    },
});

defineEmits(['open', 'close', 'create', 'update:columnName']);
</script>

<style scoped>
.trello-column-add {
    flex: 0 0 300px;
    display: flex;
    align-items: flex-start;
    padding-top: 0.5rem;
}

@media (max-width: 768px) {
    .trello-column-add {
        flex: 0 0 280px;
    }
}
</style>
