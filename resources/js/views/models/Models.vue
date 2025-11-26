<template>
  <section class="content">
    <Page title="Modelos" subtitle="Cadastre e edite aqui os modelos utilizados pelos orçamentos." back-to="/settings">
      <template #actions>
        <button
          class="btn btn-primary"
          type="button"
          @click="startCreating"
        >
          <i class="fa fa-plus-circle me-2"></i>
          Criar modelo
        </button>
      </template>
      
      <div class="collection-models">

      <section v-if="isFormVisible" class="form-container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="mb-0">
            {{ isEditing ? 'Editar modelo' : 'Novo modelo' }}
          </h5>
          <button
            type="button"
            class="btn btn-outline-secondary"
            @click="resetForm"
          >
            Limpar
          </button>
        </div>
        <form @submit.prevent="handleSubmit">
          <div class="row">
            <div class="col-md-6 mb-2">
              <label for="modelName" class="form-label">Nome</label>
              <input
                id="modelName"
                v-model.trim="form.name"
                type="text"
                class="form-control"
                placeholder="Ex: Painel fotográfico"
                required
              />
            </div>
            <div class="col-md-6 mb-2">
              <label for="modelValue" class="form-label">Valor</label>
              <div class="input-group">
                <span class="input-group-text">R$</span>
                <input
                  id="modelValue"
                  v-model.number="form.value"
                  type="number"
                  step="0.01"
                  min="0"
                  class="form-control"
                  required
                />
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label for="modelDeadline" class="form-label">Prazo (dias)</label>
              <input
                id="modelDeadline"
                v-model.number="form.deadline"
                type="number"
                min="0"
                class="form-control"
                required
              />
            </div>
          </div>

          <div class="mt-4">
            <h6 class="fw-semibold mb-3">Solicitações adicionais</h6>
            <div class="row g-3">
              <div class="col-md-3">
                <div class="form-check form-switch">
                  <input
                    id="requiresLink"
                    v-model="form.requests.link"
                    class="form-check-input"
                    type="checkbox"
                  />
                  <label class="form-check-label" for="requiresLink">
                    Solicita link?
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check form-switch">
                  <input
                    id="requiresComment"
                    v-model="form.requests.comment"
                    class="form-check-input"
                    type="checkbox"
                  />
                  <label class="form-check-label" for="requiresComment">
                    Solicita comentário?
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check form-switch">
                  <input
                    id="requiresFile"
                    v-model="form.requests.file"
                    class="form-check-input"
                    type="checkbox"
                  />
                  <label class="form-check-label" for="requiresFile">
                    Solicita arquivo?
                  </label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check form-switch">
                  <input
                    id="requiresCollection"
                    v-model="form.requests.collection"
                    class="form-check-input"
                    type="checkbox"
                  />
                  <label class="form-check-label" for="requiresCollection">
                    Escolher da coleção?
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-5 d-flex justify-content-end gap-3">
          <button
            type="button"
            class="btn btn-subtle"
            @click="cancelForm"
          >
            Cancelar
          </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="isSaving"
            >
              {{ isEditing ? 'Salvar alterações' : 'Salvar modelo' }}
            </button>
          </div>
        </form>
      </section>

      <section class="models-table mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Modelos cadastrados</h5>
          <span class="badge bg-secondary rounded-pill">
            {{ totalModels }} {{ totalModels === 1 ? 'modelo' : 'modelos' }}
          </span>
        </div>

        <div v-if="isLoading" class="border rounded p-4 text-center text-muted">
          Carregando modelos...
        </div>
        <div v-else-if="!hasModels" class="border rounded p-4 text-center text-muted">
          Nenhum modelo cadastrado ainda.
        </div>

        <div v-else class="table-responsive shadow-sm rounded">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Nome</th>
                <th>Valor</th>
                <th>Prazo (dias)</th>
                <th>Solicita link?</th>
                <th>Solicita comentário?</th>
                <th>Solicita arquivo?</th>
                <th>Escolher da coleção?</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="model in models" :key="model.id">
                <td>{{ model.name }}</td>
                <td>R$ {{ model.value.toFixed(2) }}</td>
                <td>{{ model.deadline }}</td>
                <td>
                  <i
                    class="fa"
                    :class="model.requests.link ? 'fa-check text-success' : 'fa-times text-muted'"
                  />
                </td>
                <td>
                  <i
                    class="fa"
                    :class="model.requests.comment ? 'fa-check text-success' : 'fa-times text-muted'"
                  />
                </td>
                <td>
                  <i
                    class="fa"
                    :class="model.requests.file ? 'fa-check text-success' : 'fa-times text-muted'"
                  />
                </td>
                <td>
                  <i
                    class="fa"
                    :class="model.requests.collection ? 'fa-check text-success' : 'fa-times text-muted'"
                  />
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm" role="group">
                    <button
                      type="button"
                      class="btn btn-outline-primary edit-button"
                      @click="editModel(model)"
                    >
                      <i class="fa fa-edit me-1"></i>
                      Editar
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-danger"
                      :disabled="deletingId === model.id"
                      @click="confirmDelete(model)"
                    >
                      <i class="fa fa-trash me-1"></i>
                      Excluir
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { swalSuccess, swalError, swalConfirmation } from '../../../utils/alerts';
import Page from '@/components/page/Page.vue';

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
  },
});

const form = reactive(initialState());

const totalModels = computed(() => pagination.value.total ?? models.value.length);
const hasModels = computed(() => models.value.length > 0);
const normalizeFile = (file = {}) => ({
  id: file.id ?? null,
  name: file.name ?? file.fileName ?? file.file_name ?? '',
  url: file.url ?? file.fileUrl ?? null,
});

const normalizeModel = (model = {}) => {
  const normalizedFiles = Array.isArray(model.files)
    ? model.files.map((file) => normalizeFile(file))
    : [];

  if (normalizedFiles.length === 0 && (model.fileName || model.fileUrl)) {
    normalizedFiles.push(
      normalizeFile({
        id: null,
        name: model.fileName,
        url: model.fileUrl,
      })
    );
  }

  return {
    id: model.id,
    name: model.name ?? '',
    value: Number(model.value ?? 0),
    deadline: Number(model.deadline ?? 0),
    requests: {
      link: Boolean(model?.requests?.link),
      comment: Boolean(model?.requests?.comment),
      file: Boolean(model?.requests?.file),
      collection: Boolean(
        model?.requests?.collection ??
        model?.requestCollection ??
        model?.request_collection
      ),
    },
    link: model.link ?? '',
    comment: model.comment ?? '',
    files: normalizedFiles,
  };
};

const extractItemsFromResponse = (payload) => {
  if (!payload) {
    return {
      items: [],
      meta: pagination.value,
    };
  }

  if (Array.isArray(payload)) {
    return {
      items: payload,
      meta: pagination.value,
    };
  }

  const resourceItems = payload.items?.data ?? payload.items ?? [];
  const meta =
    payload.meta ??
    payload.items?.meta ?? {
      current_page: payload.items?.current_page ?? 1,
      per_page: payload.items?.per_page ?? resourceItems.length,
      total: payload.items?.total ?? resourceItems.length,
      last_page: payload.items?.last_page ?? 1,
    };

  return {
    items: resourceItems,
    meta,
  };
};

const fetchModels = async (page = 1) => {
  isLoading.value = true;

  try {
    const { data } = await axios.get('v1/collection-models', {
      params: { page },
    });

    const payload = data?.data;
    const { items, meta } = extractItemsFromResponse(payload);

    models.value = items.map(normalizeModel);
    pagination.value = {
      current_page: meta.current_page ?? page,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? items.length,
      last_page: meta.last_page ?? 1,
    };
  } catch (error) {
    swalError('Erro ao carregar modelos');
  } finally {
    isLoading.value = false;
  }
};

const startCreating = () => {
  resetForm();
  isEditing.value = false;
  isFormVisible.value = true;
};

const cancelForm = () => {
  resetForm();
  isFormVisible.value = false;
};

const buildFormData = () => {
  const formData = new FormData();

  formData.append('name', form.name ?? '');
  formData.append('value', form.value ?? '');
  formData.append('deadline', form.deadline ?? '');
  formData.append('requests[link]', form.requests.link ? 1 : 0);
  formData.append('requests[comment]', form.requests.comment ? 1 : 0);
  formData.append('requests[file]', form.requests.file ? 1 : 0);
  formData.append('requests[collection]', form.requests.collection ? 1 : 0);
  return formData;
};

const handleSubmit = async () => {
  if (isSaving.value) {
    return;
  }

  isSaving.value = true;

  try {
    const formData = buildFormData();
    let response;

    if (isEditing.value && editingId.value !== null) {
      formData.append('_method', 'PUT');

      response = await axios.post(
        `v1/collection-models/${editingId.value}`,
        formData,
        {
          headers: { 'Content-Type': 'multipart/form-data' },
        }
      );
    } else {
      response = await axios.post('v1/collection-models', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
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

    swalSuccess(
      isEditing.value
        ? 'Modelo atualizado com sucesso'
        : 'Modelo cadastrado com sucesso'
    );

    cancelForm();
  } catch (error) {
    const message =
      error?.response?.data?.message ??
      'Erro ao salvar modelo. Verifique os campos e tente novamente.';
    swalError(message);
  } finally {
    isSaving.value = false;
  }
};

const editModel = (model) => {
  isFormVisible.value = true;
  isEditing.value = true;
  editingId.value = model.id;

  form.name = model.name;
  form.value = model.value;
  form.deadline = model.deadline;
  form.requests.link = model.requests.link;
  form.requests.comment = model.requests.comment;
  form.requests.file = model.requests.file;
  form.requests.collection =
    model.requests.collection ??
    model.requestCollection ??
    model.request_collection ??
    false;
};

const resetForm = () => {
  Object.assign(form, initialState());
  editingId.value = null;
};

const confirmDelete = async (model) => {
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
};

const destroyModel = async (model) => {
  if (!model?.id) {
    return;
  }

  deletingId.value = model.id;

  try {
    await axios.delete(`v1/collection-models/${model.id}`);

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

    swalSuccess('Modelo excluído com sucesso');
  } catch (error) {
    const message =
      error?.response?.data?.message ??
      'Não foi possível excluir o modelo. Tente novamente.';
    swalError(message);
  } finally {
    deletingId.value = null;
  }
};

onMounted(() => {
  fetchModels();
});
</script>

<style scoped>
.collection-models {
  width: 100%;
  padding-bottom: 3rem;
}

.form-container {
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 0.75rem;
  padding: 2.5rem 2rem;
  box-shadow: 0 1rem 2.5rem rgba(15, 15, 15, 0.08);
}

.models-table .table {
  background: var(--bs-body-bg);
}

.table td,
.table th {
  vertical-align: middle;
}

.edit-button {
  margin-right: 10px;
}

.btn-subtle {
  background-color: transparent;
  border: none;
  color: var(--bs-danger);
  padding: 0.375rem 0.75rem;
  transition: color 0.2s ease-in-out;
}

.btn-subtle:hover,
.btn-subtle:focus {
  background-color: transparent;
  color: var(--bs-danger-hover, #bb2d3b);
  text-decoration: underline;
}
</style>

