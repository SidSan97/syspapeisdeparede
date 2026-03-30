import { ref, reactive, computed } from 'vue';
import { useModelService } from '../services/modelService';
import { normalizeModel, extractItemsFromResponse, buildModelFormData } from '../utils/modelUtils';
import { swalConfirmation } from '@/utils/alerts';

/**
 * Composable para gerenciar modelos
 */
export function useModels() {
    const modelService = useModelService();

    const models = ref([]);
    const pagination = ref({
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1,
    });
    const isFormVisible = ref(false);
    const isEditing = ref(false);
    const isLoading = ref(false);
    const isSaving = ref(false);
    const deletingId = ref(null);
    const editingId = ref(null);

    const initialState = () => ({
        name: '',
        value: null,
        deadline: null,
        requests: {
            link: false,
            comment: false,
            file: false,
            collection: false,
            layout: false,
        },
    });

    const form = reactive(initialState());

    const totalModels = computed(() => pagination.value.total ?? models.value.length);
    const hasModels = computed(() => models.value.length > 0);

    /**
     * Busca modelos da API
     * @param {number} page - Número da página
     */
    async function fetchModels(page = 1) {
        isLoading.value = true;

        try {
            const response = await modelService.getModels(page);
            const payload = response?.data;
            const { items, meta } = extractItemsFromResponse(payload, pagination.value);

            models.value = items.map(normalizeModel);
            pagination.value = {
                current_page: meta.current_page ?? page,
                per_page: meta.per_page ?? 15,
                total: meta.total ?? items.length,
                last_page: meta.last_page ?? 1,
            };
        } catch (error) {
            window.Swal.fire({
                title: 'Erro!',
                text: 'Erro ao carregar modelos',
                icon: 'error',
                confirmButtonText: 'Entendi!',
            });
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Inicia o processo de criação de um novo modelo
     */
    function startCreating() {
        resetForm();
        isEditing.value = false;
        isFormVisible.value = true;
    }

    /**
     * Cancela o formulário
     */
    function cancelForm() {
        resetForm();
        isFormVisible.value = false;
    }

    /**
     * Reseta o formulário para o estado inicial
     */
    function resetForm() {
        Object.assign(form, initialState());
        editingId.value = null;
    }

    /**
     * Preenche o formulário com dados de um modelo para edição
     * @param {Object} model - Modelo a ser editado
     */
    function editModel(model) {
        isFormVisible.value = true;
        isEditing.value = true;
        editingId.value = model.id;

        form.name = model.name;
        form.value = model.value;
        form.deadline = model.deadline;
        form.requests.link = model.requests.link;
        form.requests.comment = model.requests.comment;
        form.requests.file = model.requests.file;
        form.requests.collection =model.requests.collection ?? false;
        form.requests.layout = model.requests.layout ?? false;
    }

    /**
     * Salva ou atualiza um modelo
     */
    async function handleSubmit() {
        if (isSaving.value) {
            return;
        }

        isSaving.value = true;

        try {
            const formData = buildModelFormData(form);
            let response;

            if (isEditing.value && editingId.value !== null) {
                response = await modelService.updateModel(editingId.value, formData);
            } else {
                response = await modelService.createModel(formData);
            }

            const saved = normalizeModel(response?.data?.data ?? {});

            if (saved.id) {
                const index = models.value.findIndex((item) => item.id === saved.id);
                if (index !== -1) {
                    models.value.splice(index, 1, saved);
                } else {
                    models.value.unshift(saved);
                    pagination.value.total = (pagination.value.total ?? 0) + 1;
                }
            } else {
                await fetchModels(pagination.value.current_page);
            }

            window.Swal.fire({
                title: isEditing.value ? 'Modelo atualizado!' : 'Modelo cadastrado!',
                text: isEditing.value
                    ? 'Modelo atualizado com sucesso'
                    : 'Modelo cadastrado com sucesso',
                confirmButtonText: 'Entendi!',
            });

            cancelForm();
        } catch (error) {
            const message =
                error?.response?.data?.message ??
                'Erro ao salvar modelo. Verifique os campos e tente novamente.';
            window.Swal.fire({
                title: 'Erro!',
                text: message,
                icon: 'error',
                confirmButtonText: 'Entendi!',
            });
        } finally {
            isSaving.value = false;
        }
    }

    /**
     * Confirma e executa a exclusão de um modelo
     * @param {Object} model - Modelo a ser excluído
     */
    async function confirmDelete(model) {
        if (deletingId.value !== null) {
            return;
        }

        const result = await swalConfirmation(
            'Excluir modelo?',
            'Essa ação é <strong>irreversível!</strong>',
            'warning',
            'Excluir',
            'Cancelar'
        );

        if (result.isConfirmed) {
            await destroyModel(model);
        }
    }

    /**
     * Exclui um modelo
     * @param {Object} model - Modelo a ser excluído
     */
    async function destroyModel(model) {
        if (!model?.id) {
            return;
        }

        deletingId.value = model.id;

        try {
            await modelService.deleteModel(model.id);

            const perPage = pagination.value.per_page ?? 15;
            const previousTotal = pagination.value.total ?? models.value.length;
            const newTotal = Math.max(previousTotal - 1, 0);
            const updatedModels = models.value.filter((item) => item.id !== model.id);
            let targetPage = pagination.value.current_page ?? 1;

            if (isFormVisible.value && editingId.value === model.id) {
                cancelForm();
            }

            models.value = updatedModels;

            if (newTotal === 0) {
                pagination.value = {
                    current_page: 1,
                    per_page: perPage,
                    total: 0,
                    last_page: 1,
                };
            } else {
                const lastPage = Math.max(Math.ceil(newTotal / perPage), 1);
                if (updatedModels.length === 0 && targetPage > 1) {
                    targetPage = Math.min(targetPage - 1, lastPage);
                    await fetchModels(targetPage);
                } else if (updatedModels.length < perPage && newTotal >= perPage) {
                    await fetchModels(targetPage);
                } else {
                    pagination.value = {
                        ...pagination.value,
                        total: newTotal,
                        last_page: lastPage,
                        current_page: Math.min(targetPage, lastPage),
                    };
                }
            }

            window.Swal.fire({
                title: 'Modelo excluído!',
                text: 'Modelo excluído com sucesso',
                confirmButtonText: 'Entendi!',
            });
        } catch (error) {
            const message =
                error?.response?.data?.message ??
                'Não foi possível excluir o modelo. Tente novamente.';
            window.Swal.fire({
                title: 'Erro!',
                text: message,
                icon: 'error',
                confirmButtonText: 'Entendi!',
            });
        } finally {
            deletingId.value = null;
        }
    }

    return {
        // State
        models,
        pagination,
        isFormVisible,
        isEditing,
        isLoading,
        isSaving,
        deletingId,
        editingId,
        form,

        // Computed
        totalModels,
        hasModels,

        // Methods
        fetchModels,
        startCreating,
        cancelForm,
        resetForm,
        editModel,
        handleSubmit,
        confirmDelete,
        destroyModel,
    };
}

