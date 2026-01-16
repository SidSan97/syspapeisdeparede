<template>
    <form class="g-3 align-items-center mb-4" role="search">
        <label for="search-query" class="sr-only">Pesquisar orçamento</label>

        <div class="d-flex">
            <div class="me-3">
                <div class="input-group input-group-prefix">
                    <input
                        id="search-query"
                        type="text"
                        class="form-control"
                        placeholder="Pesquisar orçamento"
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', $event.target.value)"
                    />
                    <span class="input-group-text">
                        <i class="fa fa-search"></i>
                    </span>
                </div>
            </div>

            <div class="">
                <div class="dropdown">
                    <button
                        class="btn btn-outline-default dropdown-toggle"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        :value="statusFilter"
                    >
                        {{ currentStatusLabel }}
                    </button>

                    <ul class="dropdown-menu">
                        <li>
                            <button
                                class="dropdown-item"
                                type="button"
                                @click="setStatusFilter('all')"
                            >
                                Todos
                            </button>
                        </li>

                        <li v-for="option in statusOptions" :key="option.value">
                            <button
                                class="dropdown-item"
                                type="button"
                                :class="{ active: statusFilter === option.value }"
                                @click="setStatusFilter(option.value)"
                            >
                                {{ option.label }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div v-if="isAdmin" class="row buttons-filters mt-2">
            <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                <label for="dateFrom" class="form-label small mb-1">Data Inicial</label>
                <input
                    id="dateFrom"
                    :value="dateFrom"
                    @input="$emit('update:dateFrom', $event.target.value)"
                    v-mask="'##/##/####'"
                    type="text"
                    class="form-control"
                    placeholder="DD/MM/AAAA"
                    :disabled="loading"
                    maxlength="10"
                />
            </div>

            <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                <label for="dateTo" class="form-label small mb-1">Data Final</label>
                <input
                    id="dateTo"
                    :value="dateTo"
                    @input="$emit('update:dateTo', $event.target.value)"
                    v-mask="'##/##/####'"
                    type="text"
                    class="form-control"
                    placeholder="DD/MM/AAAA"
                    :disabled="loading"
                    maxlength="10"
                />
            </div>

            <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                <label for="userFilter" class="form-label small mb-1">Revendedor</label>
                <select
                    id="userFilter"
                    :value="selectedUserId"
                    @change="$emit('update:selectedUserId', $event.target.value ? Number($event.target.value) : null)"
                    class="form-control"
                    :disabled="loading || loadingUsers"
                >
                    <option :value="0">Todos os revendedores</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                        {{ user.name }}
                    </option>
                </select>
            </div>

            <div class="col-lg-3 col-md-6 d-flex align-items-end mb-2 mb-lg-0">
                <button
                    type="button"
                    class="btn btn-outline-default"
                    @click="clearFilters"
                    :disabled="loading"
                >
                    <i class="fa fa-times fa-fw"></i>
                    Limpar Filtros
                </button>
            </div>
        </div>
    </form>
</template>

<script setup>
import { watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    loadingUsers: {
        type: Boolean,
        default: false,
    },
    users: {
        type: Array,
        default: () => [],
    },
    searchQuery: {
        type: String,
        default: '',
    },
    statusFilter: {
        type: String,
        default: 'all',
    },
    dateFrom: {
        type: String,
        default: '',
    },
    dateTo: {
        type: String,
        default: '',
    },
    selectedUserId: {
        type: [Number, String, null],
        default: null,
    },
    statusOptions: {
        type: Array,
        required: true,
    },
    currentStatusLabel: {
        type: String,
        required: true,
    },
});

const emit = defineEmits([
    'update:searchQuery',
    'update:statusFilter',
    'update:dateFrom',
    'update:dateTo',
    'update:selectedUserId',
    'clearFilters',
]);

function setStatusFilter(value) {
    emit('update:statusFilter', value);
}

function clearFilters() {
    emit('update:dateFrom', '');
    emit('update:dateTo', '');
    emit('update:selectedUserId', null);
    emit('clearFilters');
}

// Debounced watchers para busca e datas
const debouncedFilterChange = debounce(() => {
    // Trigger filter change via parent
}, 500);

const debouncedDateChange = debounce(() => {
    // Trigger filter change via parent
}, 1500);

watch(() => props.searchQuery, () => {
    debouncedFilterChange();
});

watch(() => props.dateFrom, () => {
    debouncedDateChange();
});

watch(() => props.dateTo, () => {
    debouncedDateChange();
});

watch(() => props.selectedUserId, () => {
    // Filter change handled by parent
});
</script>

<style scoped>
.input-group-text,
.buttons-filters button,
input {
    height: 36px !important;
}
</style>
