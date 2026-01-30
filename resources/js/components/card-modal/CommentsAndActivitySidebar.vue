<template>
  <aside class="p-4 border-start h-100">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 comments-activity-sidebar-header">
      <h3 class="fs-sm">
        <i class="far fa-comment-alt me-2"></i>

        Comentários e atividade
    </h3>
      <button class="btn btn-default" type="button" @click="toggleDetails">
        {{ showDetails ? 'Ocultar Detalhes' : 'Mostrar Detalhes' }}
      </button>
    </div>

    <CommentInput v-model="newCommentText" @save="saveNewComment"/>

    <!-- Lista de comentários e atividades (visível apenas quando showDetails é true) -->
    <div v-if="showDetails">
      <!-- Lista de comentários -->
      <div class="d-flex flex-column gap-3 my-3">
        <CommentItem
          v-for="(comment, index) in comments"
          :key="comment?.id ?? `comment-${index}`"
          :comment="comment"
          :saving="isSavingComment"
          @delete="onDeleteComment"
          @save="onUpdateComment"
        ></CommentItem>
        <div v-if="comments.length === 0" class="comments-activity-info text-muted">
          Nenhum comentário ainda.
        </div>
      </div>

      <!-- Atividades e Histórico -->
      <div class="comments-activity-activity">
        <div v-if="activityItems.length > 0" class="d-flex flex-column gap-3">
          <div
            v-for="(activity, activityIndex) in activityItems"
            :key="activity.id || activityIndex"
            class="p-3 bg-body rounded comments-activity-activity-item"
            :class="{ 'is-history': activity.type === 'history' }"
          >
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="d-flex align-items-center gap-2">
                <div v-if="activity.type !== 'history'" class="rounded-circle d-flex align-items-center justify-content-center text-white fw-semibold flex-shrink-0 comments-activity-avatar" :style="{ backgroundColor: getAvatarColor(getActivityUser(activity)) }">
                  {{ getInitials(getActivityUser(activity)) }}
                </div>
                <div v-else class="rounded-circle d-flex align-items-center justify-content-center bg-success text-white flex-shrink-0 comments-activity-icon">
                  <i class="fa fa-history"></i>
                </div>
                <span v-if="activity.type !== 'history'" class="fw-semibold text-body comments-activity-author">{{ getActivityUser(activity) }}</span>
                <span v-else class="fw-semibold text-body comments-activity-author">Histórico</span>
              </div>
              <span class="comments-activity-date">{{ formatDate(activity.created_at || activity.date) }}</span>
            </div>
            <div class="comments-activity-content" v-html="getActivityText(activity)"></div>
          </div>
        </div>
        <div v-else class="comments-activity-info text-muted">
          Nenhuma atividade registrada.
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useCommentService } from '@/modules/card-modals/services/commentService';
import CommentInput from '@/components/CommentInput.vue';
import CommentItem from '@/components/CommentItem.vue';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
  showDetails: {
    type: Boolean,
    default: false,
  },
  typePage: {
    type: String,
    default: 'layout',
    validator: (value) => ['layout', 'product'].includes(value),
  },
});

const emit = defineEmits(['update:showDetails', 'comment-added', 'comment-updated', 'comment-deleted']);

const commentService = useCommentService();

const isEditingComment = ref(false);
const newCommentText = ref('');
const isSavingComment = ref(false);
const editingCommentId = ref(null);
const editingCommentText = ref('');

// Lista local reativa para exibir comentários (permite ver novos comentários imediatamente)
const localComments = ref([]);

watch(
  () => [props.card?.id, props.card?.comments],
  () => {
    if (!props.card || !Array.isArray(props.card.comments)) {
      localComments.value = [];
      return;
    }
    localComments.value = props.card.comments.filter((c) => c != null);
  },
  { immediate: true, deep: true }
);

const comments = computed(() => localComments.value);

const activityItems = computed(() => {
  if (!props.card) {
    return [];
  }

  const activities = [];

  // Adicionar histórico do card (filtrar pelo tipo de página)
  if (Array.isArray(props.card.history) && props.card.history.length > 0) {
    props.card.history
      .filter((historyItem) => historyItem.type_page === props.typePage)
      .forEach((historyItem) => {
        activities.push({
          id: `history-${historyItem.id}`,
          type: 'history',
          description: historyItem.description,
          created_at: historyItem.created_at,
          date: historyItem.created_at,
        });
      });
  }

  // Adicionar atividades existentes
  if (Array.isArray(props.card.activities) && props.card.activities.length > 0) {
    activities.push(...props.card.activities);
  }

  // Adicionar comentário do budget se existir
  if (props.card.budget?.comment_referring_model) {
    activities.push({
      id: 'budget-comment',
      type: 'comment',
      user_name: props.card.responsible_name || 'Comentário',
      created_at: props.card.updated_at,
      date: props.card.updated_at,
      comment: props.card.budget.comment_referring_model,
    });
  }

  // Ordenar por data (mais recente primeiro)
  return activities.sort((a, b) => {
    const dateA = new Date(a.created_at || a.date || 0);
    const dateB = new Date(b.created_at || b.date || 0);
    return dateB - dateA;
  });
});

const activityDateFormatter = new Intl.DateTimeFormat('pt-BR', {
  dateStyle: 'medium',
  timeStyle: 'short',
});

function formatDate(date) {
  if (!date) {
    return '';
  }
  const parsedDate = new Date(date);
  if (Number.isNaN(parsedDate.getTime())) {
    return date;
  }
  return activityDateFormatter.format(parsedDate);
}

function getActivityUser(activity) {
  if (!activity) {
    return 'Anônimo';
  }
  return (
    activity.user_name ||
    activity.author?.name ||
    activity.user?.name ||
    activity.user ||
    activity.created_by ||
    'Anônimo'
  );
}

function getActivityText(activity) {
  if (!activity) {
    return '';
  }
  if (typeof activity === 'string') {
    return activity;
  }
  if (activity.type === 'history' && activity.description) {
    return activity.description;
  }
  return activity.text || activity.comment || activity.description || activity.message || '';
}

function getInitials(name) {
  if (!name) {
    return '??';
  }
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
}

function getAvatarColor(name) {
  if (!name) {
    return '#5e6c84';
  }

  const colors = [
    '#00b8d9', '#00a86b', '#0065ff', '#5243aa', '#ff5630',
    '#ff8b00', '#36b37e', '#ffab00', '#6554c0', '#00c7e6',
  ];

  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  return colors[Math.abs(hash) % colors.length];
}

function toggleDetails() {
  emit('update:showDetails', !props.showDetails);
}

function cancelNewComment() {
  newCommentText.value = '';
  isEditingComment.value = false;
}

async function saveNewComment() {
  if (!props.card?.id || !newCommentText.value.trim()) {
    return;
  }

  isSavingComment.value = true;

  try {
    const response = await commentService.createComment(props.card.id, newCommentText.value);

    const commentFromApi = response?.data ?? response;
    const newComment = {
      ...commentFromApi,
      id: commentFromApi?.id ?? `new-${Date.now()}`,
    };
    localComments.value = [newComment, ...localComments.value];

    newCommentText.value = '';
    isEditingComment.value = false;

    if (window.Toast) {
      window.Toast.fire({
        icon: 'success',
        title: response.message || 'Comentário adicionado com sucesso',
      });
    }

    emit('comment-added', newComment);
  } catch (error) {
    console.error('Erro ao adicionar comentário:', error);
    const errorMessage = error.response?.data?.message || 'Erro ao adicionar comentário. Tente novamente.';

    if (window.Swal) {
      window.Swal.fire('Erro!', errorMessage, 'error');
    } else {
      alert(errorMessage);
    }
  } finally {
    isSavingComment.value = false;
  }
}

function startEditComment(comment) {
  editingCommentId.value = comment.id;
  editingCommentText.value = comment.comment;
}

function cancelEditComment() {
  editingCommentId.value = null;
  editingCommentText.value = '';
}

async function onUpdateComment({id, newComment}) {
  if (!props.card?.id || !newComment.trim()) {
    return;
  }

  isSavingComment.value = true;

  try {
    const response = await commentService.updateComment(props.card.id, id, newComment);

    const index = localComments.value.findIndex((c) => c.id === id);
    if (index !== -1) {
      localComments.value = [
        ...localComments.value.slice(0, index),
        response.data,
        ...localComments.value.slice(index + 1),
      ];
    }

    editingCommentId.value = null;
    editingCommentText.value = '';

    if (window.Toast) {
      window.Toast.fire({
        icon: 'success',
        title: response.message || 'Comentário atualizado com sucesso',
      });
    }

    emit('comment-updated', response.data);
  } catch (error) {
    console.error('Erro ao atualizar comentário:', error);
    const errorMessage = error.response?.data?.message || 'Erro ao atualizar comentário. Tente novamente.';

    if (window.Swal) {
      window.Swal.fire('Erro!', errorMessage, 'error');
    } else {
      alert(errorMessage);
    }
  } finally {
    isSavingComment.value = false;
  }
}

async function onDeleteComment(commentId) {
  if (!props.card?.id) {
    return;
  }

  try {
    await commentService.onDeleteComment(props.card.id, commentId);

    localComments.value = localComments.value.filter((c) => c.id !== commentId);

    if (window.Toast) {
      window.Toast.fire({
        icon: 'success',
        title: 'Comentário excluído com sucesso',
      });
    }

    emit('comment-deleted', commentId);
  } catch (error) {
    console.error('Erro ao excluir comentário:', error);
    const errorMessage = error.response?.data?.message || 'Erro ao excluir comentário. Tente novamente.';

    if (window.Swal) {
      window.Swal.fire('Erro!', errorMessage, 'error');
    } else {
      alert(errorMessage);
    }
  }
}
</script>

<style lang="scss" scoped>
.comments-activity-sidebar {
  top: 1.5rem;
  max-height: calc(90vh - 200px);
  overflow-y: auto;
  background-color: var(--bs-secondary-bg);
}

.comments-activity-sidebar-title {
  font-size: 0.9375rem;
}

.comments-activity-avatar {
  width: 32px;
  height: 32px;
  font-size: 0.75rem;
}

.comments-activity-author {
  font-size: 0.8125rem;
}

.comments-activity-date {
  font-size: 0.75rem;
}

.comments-activity-text {
  font-size: 0.8125rem;
  line-height: 1.4;
  word-wrap: break-word;
}

.comments-activity-actions {
  min-height: 24px;
}

.comments-activity-action-separator {
  font-size: 0.6875rem;
  padding: 0 0.125rem;
}

.comments-activity-action-btn {
  font-size: 12px;

  &:hover {
    text-decoration: underline;
  }
}

.comments-activity-activity-item {
  &.is-history {
    border-left: 3px solid var(--bs-success);
  }
}

.comments-activity-content {
  font-size: 0.8125rem;
  line-height: 1.4;
}

.comments-activity-icon {
  width: 32px;
  height: 32px;
  font-size: 0.875rem;
}

.comments-activity-info {
  font-size: 0.875rem;
  line-height: 1.5;
}
</style>

