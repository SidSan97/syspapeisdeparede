<template>
    <div class="trello-column" :data-column-id="column.id">
        <div class="trello-column-header">
            <div class="trello-column-header-left">
                <h3 v-if="!isEditing" class="trello-column-title mb-0">
                    {{ column.name }}
                </h3>
                <div v-else class="trello-column-edit d-flex align-items-center gap-2">
                    <input
                        :value="editingName"
                        @input="$emit('update:editingName', $event.target.value)"
                        @keyup.enter="$emit('save')"
                        @keyup.esc="$emit('cancel')"
                        class="form-control form-control-sm"
                        :ref="inputRef"
                    />
                    <button
                        @click="$emit('save')"
                        class="btn btn-primary btn-sm"
                        :disabled="saving"
                    >
                        <i class="fa fa-check"></i>
                    </button>
                </div>
            </div>
            <div class="trello-column-header-right d-flex align-items-center gap-2">
                <span class="badge trello-column-count-badge">{{ cardsCount }}</span>
                <div class="dropdown">
                    <button
                        class="btn btn-sm btn-link text-decoration-none p-1 trello-column-menu-btn"
                        type="button"
                        @click.stop="$emit('toggle-menu')"
                        :aria-expanded="menuOpen"
                    >
                        <i class="fa fa-ellipsis-v"></i>
                    </button>
                    <ul
                        v-if="menuOpen"
                        class="dropdown-menu dropdown-menu-end show"
                        @click.stop
                    >
                        <li>
                            <button @click="$emit('edit')" class="dropdown-item" type="button">
                                <i class="fa fa-edit me-2"></i> Editar
                            </button>
                        </li>
                        <li>
                            <button @click="$emit('delete')" class="dropdown-item text-danger" type="button">
                                <i class="fa fa-trash me-2"></i> Excluir
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div
            class="trello-column-content"
            @drop="$emit('drop', $event)"
            @dragover.prevent
            @dragenter.prevent
        >
            <slot />
        </div>
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';

const props = defineProps({
    column: {
        type: Object,
        required: true,
    },
    cardsCount: {
        type: Number,
        default: 0,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    editingName: {
        type: String,
        default: '',
    },
    saving: {
        type: Boolean,
        default: false,
    },
    menuOpen: {
        type: Boolean,
        default: false,
    },
    inputRef: {
        type: Object,
        default: null,
    },
});

defineEmits(['update:editingName', 'save', 'cancel', 'toggle-menu', 'edit', 'delete', 'drop']);
</script>

<style scoped>
.trello-column {
    flex: 0 0 300px;
    background-color: var(--bs-secondary-bg);
    border-radius: 0.5rem;
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    max-height: 100%;
    overflow: hidden;
}

.trello-column-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
    position: relative;
    overflow: visible;
    z-index: 10;
}

.trello-column-header-left {
    flex: 1;
    min-width: 0;
}

.trello-column-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--bs-body-color);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.trello-column-header-right {
    position: relative;
}

.trello-column-count-badge {
    background-color: var(--bs-secondary-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);
}

.trello-column-menu-btn {
    color: var(--bs-body-color);
    transition: all 0.15s ease-in-out;
}

.trello-column-menu-btn:hover {
    color: var(--bs-body-color);
    background-color: var(--bs-secondary-bg);
    opacity: 0.8;
}

.trello-column-header-right .dropdown-menu {
    position: absolute;
    top: calc(100% + 0.25rem);
    right: 0;
    z-index: 1050;
    min-width: 150px;
    background-color: var(--bs-dropdown-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.375rem;
    box-shadow: var(--bs-box-shadow-lg);
    padding: 0.25rem 0;
    display: block;
}

.trello-column-header-right .dropdown-item {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    clear: both;
    font-weight: 400;
    color: var(--bs-dropdown-color);
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
}

.trello-column-header-right .dropdown-item:hover {
    background-color: var(--bs-dropdown-link-hover-bg);
    color: var(--bs-dropdown-link-hover-color);
}

.trello-column-header-right .dropdown-item.text-danger {
    color: var(--bs-danger);
}

.trello-column-header-right .dropdown-item.text-danger:hover {
    background-color: var(--bs-danger-bg-subtle);
    color: var(--bs-danger);
}

.trello-column-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0 0.25rem;
}

.trello-column-content::-webkit-scrollbar {
    width: 8px;
}

.trello-column-content::-webkit-scrollbar-track {
    background: transparent;
}

.trello-column-content::-webkit-scrollbar-thumb {
    background: var(--bs-secondary);
    border-radius: 4px;
    opacity: 0.5;
}

.trello-column-content::-webkit-scrollbar-thumb:hover {
    background: var(--bs-secondary);
    opacity: 0.7;
}
</style>
