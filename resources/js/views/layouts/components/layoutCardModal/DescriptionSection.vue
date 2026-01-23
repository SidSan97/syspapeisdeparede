<template>
  <section class="d-flex gap-3 mb-3">
    <i class="fa fa-align-left py-1"></i>

    <div class="flex-fill">
      <header class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fs-sm fw-bold text-body m-0">Descrição</h3>

        <button
          type="button"
          class="btn btn-default btn-sm"
          @click="startEdit"
          v-if="!isEditing"
        >
          Editar
        </button>
      </header>

      <!-- Visualização -->
      <div
        v-if="!isEditing"
        class="cursor-pointer"
        :class="{ 'is-empty': !description }"
        @click="startEdit"
      >
        {{ description || 'Adicione uma descrição mais detalhada...' }}
      </div>

      <!-- Edição -->
      <div v-else class="d-flex flex-column gap-3">
        <textarea
          ref="textareaRef"
          v-model.trim="descriptionDraft"
          class="form-control"
          maxlength="500"
          rows="4"
          placeholder="Adicione uma descrição mais detalhada..."
          @keyup.esc="cancelEdit"
        />

        <div>
          <button
            class="btn btn-primary me-2"
            @click="save"
            :disabled="isSaving || !canSave"
          >
            {{ isSaving ? 'Salvando...' : 'Salvar' }}
          </button>

          <button class="btn btn-default" @click="cancelEdit">
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useDescriptionService } from '@/views/layouts/services/descriptionService'

const props = defineProps({
  card: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['description-updated'])

const descriptionService = useDescriptionService()

const isEditing = ref(false)
const isSaving = ref(false)

const descriptionDraft = ref('')
const originalDescription = ref('')
const textareaRef = ref(null)

const description = computed(() => props.card?.description ?? '')

const canSave = computed(() => {
  return (
    descriptionDraft.value.trim().length > 0 &&
    descriptionDraft.value !== originalDescription.value
  )
})

watch(
  () => props.card?.id,
  () => {
    resetState()
  },
  { immediate: true }
)

function resetState() {
  const value = props.card?.description ?? ''
  descriptionDraft.value = value
  originalDescription.value = value
  isEditing.value = false
}

async function startEdit() {
  isEditing.value = true
  await nextTick()
  textareaRef.value?.focus()
}

function cancelEdit() {
  descriptionDraft.value = originalDescription.value
  isEditing.value = false
}

async function save() {
  if (!props.card?.id || !canSave.value) return

  isSaving.value = true

  try {
    const response = await descriptionService.updateDescription(
      props.card.id,
      descriptionDraft.value,
      'layout'
    )

    originalDescription.value = descriptionDraft.value
    isEditing.value = false

    emit('description-updated', descriptionDraft.value)

    window.Toast?.fire({
      icon: 'success',
      title: response.message || 'Descrição salva com sucesso'
    })
  } catch (error) {
    console.error(error)

    const message =
      error.response?.data?.message ||
      'Erro ao salvar descrição. Tente novamente.'

    window.Swal
      ? window.Swal.fire('Erro!', message, 'error')
      : alert(message)
  } finally {
    isSaving.value = false
  }
}
</script>

<style lang="scss" scoped>
.cursor-pointer {
  cursor: pointer;
}

.is-empty {
  color: var(--bs-secondary);
}
</style>

