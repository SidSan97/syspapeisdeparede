import { ref } from 'vue';
import { useProductionService } from '../services/productionService';

/**
 * Composable para gerenciar colunas de produção
 */
export function useProductionColumns() {
    const productionService = useProductionService();

    const columns = ref([]);
    const loading = ref(false);
    const openMenuColumn = ref(null);
    const editingColumns = ref({});
    const editingNames = ref({});
    const savingColumn = ref(null);
    const showAddColumnModal = ref(false);
    const newColumnName = ref('');
    const creatingColumn = ref(false);

    async function fetchColumns() {
        try {
            loading.value = true;
            columns.value = await productionService.getColumns();
        } catch (error) {
            console.error('Erro ao carregar colunas:', error);
            columns.value = [];
        } finally {
            loading.value = false;
        }
    }

    function toggleColumnMenu(columnId) {
        openMenuColumn.value = openMenuColumn.value === columnId ? null : columnId;
    }

    function startEditColumn(columnId) {
        const column = columns.value.find(c => c.id === columnId);
        if (column) {
            editingColumns.value[columnId] = true;
            editingNames.value[columnId] = column.name;
            openMenuColumn.value = null;
            return columnId;
        }
        return null;
    }

    function cancelEdit(columnId) {
        editingColumns.value[columnId] = false;
        delete editingNames.value[columnId];
    }

    async function saveColumnName(columnId) {
        const newName = editingNames.value[columnId]?.trim();
        if (!newName) {
            return false;
        }

        try {
            savingColumn.value = columnId;
            const updated = await productionService.updateColumn(columnId, newName);

            const columnIndex = columns.value.findIndex(c => c.id === columnId);
            if (columnIndex !== -1) {
                columns.value[columnIndex].name = updated?.name ?? newName;
            }
            editingColumns.value[columnId] = false;
            delete editingNames.value[columnId];
            return true;
        } catch (error) {
            console.error('Erro ao salvar coluna:', error);
            return false;
        } finally {
            savingColumn.value = null;
        }
    }

    async function deleteColumn(columnId) {
        try {
            await productionService.deleteColumn(columnId);

            columns.value = columns.value.filter(c => c.id !== columnId);
            return columns.value.length > 0 ? columns.value[0].id : null;
        } catch (error) {
            console.error('Erro ao excluir coluna:', error);
            return null;
        }
    }

    async function createColumn() {
        const name = newColumnName.value?.trim();
        if (!name || creatingColumn.value) {
            return null;
        }

        try {
            creatingColumn.value = true;
            const column = await productionService.createColumn(name);

            if (column?.id) {
                columns.value.push({
                    id: column.id,
                    name: column.name,
                });
                return column;
            }
            return null;
        } catch (error) {
            console.error('Erro ao criar coluna:', error);
            return null;
        } finally {
            creatingColumn.value = false;
        }
    }

    function openAddColumnModal() {
        showAddColumnModal.value = true;
        newColumnName.value = '';
    }

    function closeAddColumnModal() {
        showAddColumnModal.value = false;
        newColumnName.value = '';
    }

    return {
        columns,
        loading,
        openMenuColumn,
        editingColumns,
        editingNames,
        savingColumn,
        showAddColumnModal,
        newColumnName,
        creatingColumn,
        fetchColumns,
        toggleColumnMenu,
        startEditColumn,
        cancelEdit,
        saveColumnName,
        deleteColumn,
        createColumn,
        openAddColumnModal,
        closeAddColumnModal,
    };
}


