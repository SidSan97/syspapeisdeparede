<script setup>
import { ref, useTemplateRef, computed } from 'vue';
import { Modal } from 'bootstrap';
import { http } from '@/lib/http';

const CHUNK_SIZE = 1 * 1024 * 1024; // 1 MB por chunk (abaixo do upload_max_filesize padrão do PHP)
const POLL_INTERVAL_MS = 2500;

const emit = defineEmits(['imported']);

const modalRef = useTemplateRef('modalRef');
const modal = ref(null);

const zipFile = ref(null);
const zipFileName = ref('');
const phase = ref('idle'); // idle | uploading | processing | completed | failed
const uploadProgress = ref(0); // 0-100 (fase de upload)
const errorMsg = ref('');
const result = ref(null);

let pollTimer = null;

const progressLabel = computed(() => {
  if (phase.value === 'uploading') return `Enviando... ${uploadProgress.value}%`;
  if (phase.value === 'processing') return 'Processando imagens...';
  return '';
});

const open = () => {
  reset();
  if (!modal.value && modalRef.value) {
    modal.value = new Modal(modalRef.value);
  }
  modal.value?.show();
};

const reset = () => {
  zipFile.value = null;
  zipFileName.value = '';
  phase.value = 'idle';
  uploadProgress.value = 0;
  errorMsg.value = '';
  result.value = null;
  clearPollTimer();
};

const handleFileChange = (e) => {
  const file = e.target.files[0] ?? null;
  zipFile.value = file;
  zipFileName.value = file ? file.name : '';
  errorMsg.value = '';
  result.value = null;
};

const startImport = async () => {
  if (!zipFile.value) {
    errorMsg.value = 'Selecione um arquivo ZIP.';
    return;
  }

  const uploadId = generateUploadId();
  const file = zipFile.value;
  const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

  phase.value = 'uploading';
  uploadProgress.value = 0;
  errorMsg.value = '';

  try {
    for (let i = 0; i < totalChunks; i++) {
      const start = i * CHUNK_SIZE;
      const end = Math.min(start + CHUNK_SIZE, file.size);
      const chunk = file.slice(start, end);

      const fd = new FormData();
      fd.append('chunk', chunk);
      fd.append('upload_id', uploadId);
      fd.append('chunk_index', String(i));
      fd.append('total_chunks', String(totalChunks));

      await http.post('v1/collection-import/chunk', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      uploadProgress.value = Math.round(((i + 1) / totalChunks) * 100);
    }

    // Todos os chunks enviados — aciona o processamento
    const { data } = await http.post('v1/collection-import/process', {
      upload_id: uploadId,
      total_chunks: totalChunks,
    });

    if (!data?.success) {
      throw new Error(data?.message ?? 'Erro ao iniciar processamento.');
    }

    const importId = data.data.import_id;
    phase.value = 'processing';
    startPolling(importId);
  } catch (err) {
    phase.value = 'failed';
    errorMsg.value = err?.response?.data?.message ?? err?.message ?? 'Falha no envio.';
  }
};

const startPolling = (importId) => {
  clearPollTimer();
  pollTimer = setInterval(async () => {
    try {
      const { data } = await http.get(`v1/collection-import/${importId}/status`);
      const state = data?.data?.state;

      if (state === 'completed') {
        clearPollTimer();
        result.value = data.data.result;
        phase.value = 'completed';
        emit('imported', result.value);
      } else if (state === 'failed') {
        clearPollTimer();
        errorMsg.value = data.data.error ?? 'Falha no processamento.';
        phase.value = 'failed';
      }
    } catch {
      // Ignora erros de rede transitórios durante polling
    }
  }, POLL_INTERVAL_MS);
};

const clearPollTimer = () => {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
};

const generateUploadId = () => {
  return 'imp-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
};

defineExpose({ open });
</script>

<template>
  <Teleport to="body">
    <div
      class="modal fade"
      tabindex="-1"
      aria-labelledby="import-modal-label"
      aria-hidden="true"
      ref="modalRef"
      data-bs-backdrop="static"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="import-modal-label">Importar catálogo via ZIP</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Fechar"
              :disabled="phase === 'uploading' || phase === 'processing'"
            />
          </div>

          <div class="modal-body">
            <!-- Instruções -->
            <div v-if="phase === 'idle'" class="alert alert-info small mb-3">
              <strong>Estrutura esperada do ZIP:</strong>
              <pre class="mb-0 mt-1" style="font-size: 0.78rem">
📁 categoria/
  📁 subcategoria/
    🖼 imagem.jpg</pre
              >
              <p class="mb-0 mt-2">
                Uma imagem pode estar em múltiplas subcategorias. Categorias e subcategorias são
                criadas automaticamente se não existirem.
              </p>
            </div>

            <!-- Seleção de arquivo -->
            <div v-if="phase === 'idle'" class="mb-3">
              <label class="form-label fw-semibold">Arquivo ZIP</label>
              <div class="d-flex align-items-center gap-2">
                <input
                  type="file"
                  class="form-control d-none"
                  id="import-zip-input"
                  accept=".zip,application/zip"
                  @change="handleFileChange"
                />
                <label
                  for="import-zip-input"
                  class="btn btn-outline-default mb-0"
                  style="cursor: pointer; white-space: nowrap"
                >
                  Escolher arquivo
                </label>
                <span class="text-muted text-truncate">
                  {{ zipFileName || 'Nenhum arquivo selecionado' }}
                </span>
              </div>
            </div>

            <!-- Progress bar (upload + processing) -->
            <div v-if="phase === 'uploading' || phase === 'processing'" class="mb-2">
              <div class="d-flex justify-content-between mb-1 small text-muted">
                <span>{{ progressLabel }}</span>
                <span v-if="phase === 'uploading'">{{ uploadProgress }}%</span>
              </div>
              <div class="progress" style="height: 10px">
                <div
                  class="progress-bar progress-bar-striped progress-bar-animated"
                  role="progressbar"
                  :style="{ width: phase === 'uploading' ? uploadProgress + '%' : '100%' }"
                />
              </div>
              <p v-if="phase === 'processing'" class="text-muted small mt-2 mb-0">
                As imagens estão sendo processadas em segundo plano. Pode levar alguns minutos
                dependendo do tamanho do arquivo.
              </p>
            </div>

            <!-- Erro -->
            <div v-if="phase === 'failed'" class="alert alert-danger small py-2">
              <strong>Erro:</strong> {{ errorMsg }}
            </div>

            <!-- Resultado -->
            <div v-if="phase === 'completed'" class="alert alert-success small py-2">
              <strong>Importação concluída!</strong>
              <ul class="mb-0 mt-1">
                <li>
                  Categorias:
                  <strong>{{ result.categories_created }} criadas</strong>,
                  {{ result.categories_found }} já existiam
                </li>
                <li>
                  Subcategorias:
                  <strong>{{ result.subcategories_created }} criadas</strong>,
                  {{ result.subcategories_found }} já existiam
                </li>
                <li>
                  Imagens:
                  <strong>{{ result.images_imported }} importadas</strong>
                  <span v-if="result.images_skipped > 0"
                    >, {{ result.images_skipped }} ignoradas</span
                  >
                </li>
              </ul>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-subtle"
              data-bs-dismiss="modal"
              :disabled="phase === 'uploading' || phase === 'processing'"
            >
              {{ phase === 'completed' || phase === 'failed' ? 'Fechar' : 'Cancelar' }}
            </button>
            <button
              v-if="phase === 'idle' || phase === 'failed'"
              type="button"
              class="btn btn-primary"
              @click="startImport"
              :disabled="!zipFile"
            >
              Importar
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
