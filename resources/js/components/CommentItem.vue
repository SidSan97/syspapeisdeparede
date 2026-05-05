<script setup>
import { useFormatting } from '@/composables/useFormatting';
import { IconX } from '@tabler/icons-vue';
import { ref, computed, watch, nextTick } from 'vue';
import BaseAvatar from './common/BaseAvatar.vue';
import BaseDropdown from './common/BaseDropdown.vue';

const { formatDate } = useFormatting();

const props = defineProps({
  comment: {
    type: Object,
    required: true,
    default: () => ({
      id: null,
      user_name: 'Autor',
      comment: '',
      created_at: new Date(),
    }),
  },
  saving: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['save', 'delete']);

const editMode = ref(false);
const showDeleteConfirm = ref(false);
const inputRef = ref(null);
const newComment = ref(props.comment.comment);

watch(
  () => props.comment.comment,
  (val) => {
    newComment.value = val;
  },
);

const canSave = computed(() => newComment.value.trim().length > 0 && !props.saving);

const openEdit = async () => {
  editMode.value = true;
  await nextTick();
  inputRef.value?.focus();
};

const cancel = () => {
  newComment.value = props.comment.comment;
  editMode.value = false;
};

const save = () => {
  emit('save', { id: props.comment.id, newComment: newComment.value });
  editMode.value = false;
};

const deleteComment = () => {
  emit('delete', props.comment.id);
};

const getInitials = (name) => {
  return name
    .split(' ')
    .map((n) => n[0].toUpperCase())
    .join('');
};
</script>

<template>
  <div class="comment-item mb-3">
    <!-- Visualização -->
    <div class="d-flex gap-2">
      <BaseAvatar>{{ getInitials(comment.user_name) }}</BaseAvatar>
      <div class="flex-1">
        <div class="mb-2">
          <strong class="fs-sm me-2">{{ comment.user_name }}</strong>
          <small class="text-muted fs-xs">{{ formatDate(comment.created_at) }}</small>
        </div>

        <div v-if="!editMode">
          <div class="card border-0 shadow mb-2">
            <div class="card-body p-3">{{ comment.comment }}</div>
          </div>
          <div class="d-flex align-items-center gap-2 fs-xs">
            <a href="#" class="text-body text-decoration-underline" @click.prevent="openEdit">
              Editar
            </a>
            &bull;
            <BaseDropdown>
              <template #trigger="{ toggle }">
                <button
                  type="button"
                  class="btn btn-link btn-sm p-0 fs-xs fw-normal text-body text-decoration-underline"
                  @click.prevent="toggle"
                >
                  Excluir
                </button>
              </template>

              <template #default="{ open, close }">
                <h6 class="dropdown-header d-flex justify-content-between">
                  <span></span>
                  <span>Excluir comentário?</span>
                  <a href="#" class="text-body text-decoration-none" @click.prevent="close">
                    <IconX :size="18" />
                  </a>
                </h6>
                <p class="mb-2 px-3" style="max-width: 200px">
                  A exclusão de um comentário é permanente. Não é possível desfazer.
                </p>
                <div class="p-3">
                  <button
                    type="button"
                    class="btn btn-danger w-100"
                    @click="
                      deleteComment();
                      toggle();
                    "
                  >
                    Excluir comentário
                  </button>
                </div>
              </template>
            </BaseDropdown>
          </div>
        </div>

        <!-- Edit mode -->
        <div v-else class="mt-2">
          <textarea
            ref="inputRef"
            v-model="newComment"
            class="form-control mb-3"
            maxlength="500"
            rows="3"
            @keyup.esc="cancel"
          ></textarea>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" @click="save" :disabled="!canSave">
              <span
                v-if="props.saving"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              Salvar
            </button>
            <button type="button" class="btn btn-default" @click="cancel">
              Descartar as alterações
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
