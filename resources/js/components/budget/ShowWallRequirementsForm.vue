<template v-for="(wall, idx) in (wallsWithRequirements || []).filter(Boolean)" :key="wall?.key ?? `wall-${idx}`">
    <div v-if="wall" class="border rounded p-3 mb-3">
      <div class="mb-3">
        <div class="fw-semibold">{{ wall.roomName }}</div>
        <div class="text-muted small">{{ wall.wallName }}</div>
        <div class="text-muted small">Modelo: {{ wall.collectionModel?.name ?? 'N/A' }}</div>
      </div>

      <div v-if="wall.requiresComment" class="mb-3">
        <label :for="`orderComment-${wall.key}`" class="form-label">
          Descrição do modelo
        </label>
        <textarea
          :id="`orderComment-${wall.key}`"
          v-model.trim="wallForms[wall.key].comment"
          class="form-control"
          rows="3"
          maxlength="500"
          placeholder="Descreva o que deve ser produzido com base neste orçamento"
          :disabled="orderSubmitting"
        ></textarea>
        <small class="text-muted">Máximo de 500 caracteres.</small>
      </div>

      <div v-if="wall.requiresFiles" class="mb-3">
        <label :for="`orderFiles-${wall.key}`" class="form-label">
          Uploads de referência
        </label>
        <input
          :id="`orderFiles-${wall.key}`"
          :ref="(el) => setFileInputRef && setFileInputRef(wall.key, el)"
          class="form-control"
          type="file"
          accept="image/*"
          multiple
          :disabled="orderSubmitting"
          @change="(e) => handleOrderFilesChange(wall.key, e)"
        >
        <small class="text-muted">Envie imagens em formatos JPG, PNG ou WEBP (máx. 5MB cada).</small>

        <div v-if="wall.existingFiles && wall.existingFiles.length" class="mt-2">
          <div class="text-muted small mb-1">Arquivos enviados anteriormente</div>
          <ul class="list-unstyled small mb-0">
            <li v-for="(file, index) in wall.existingFiles" :key="`existing-file-${wall.key}-${index}`">
              <a :href="resolveStorageUrl(file)" target="_blank" rel="noopener">
                {{ extractFileName(file) }}
              </a>
            </li>
          </ul>
        </div>

        <div v-if="getWallNewFiles(wall.key).length" class="mt-2">
          <div class="text-muted small mb-1">Arquivos selecionados</div>
          <ul class="list-unstyled small mb-0">
            <li
              v-for="(file, index) in getWallNewFiles(wall.key)"
              :key="`new-file-${wall.key}-${index}`"
              class="d-flex align-items-center gap-2"
            >
              <span>{{ file.name }}</span>
              <button
                class="btn btn-link btn-sm text-danger p-0"
                type="button"
                :disabled="orderSubmitting"
                @click="removeNewFile(wall.key, index)"
              >
                Remover
              </button>
            </li>
          </ul>
        </div>
      </div>

      <div v-if="wall.requiresLink" class="mb-3">
        <label :for="`orderLink-${wall.key}`" class="form-label">
          Link de referência
        </label>
        <input
          :id="`orderLink-${wall.key}`"
          v-model.trim="wallForms[wall.key].link"
          type="url"
          class="form-control"
          placeholder="https://exemplo.com/referencia"
          :disabled="orderSubmitting"
        >
      </div>
    </div>
</template>

<script setup>
defineProps({
  wallsWithRequirements: { type: Array, default: () => [] },
  wallForms: { type: Object, default: () => ({}) },
  orderSubmitting: { type: Boolean, default: false },
  setFileInputRef: { type: Function, default: null },
  handleOrderFilesChange: { type: Function, default: null },
  removeNewFile: { type: Function, default: null },
  getWallNewFiles: { type: Function, default: null },
  resolveStorageUrl: { type: Function, default: null },
  extractFileName: { type: Function, default: null },
});
</script>
