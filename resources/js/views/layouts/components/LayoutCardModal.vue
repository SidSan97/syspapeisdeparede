<template>
    <Teleport v-if="card" to="body">
      <div class="trello-modal-overlay" @click="handleClose">
        <div class="trello-modal" @click.stop>
          <div class="trello-modal-header">
            <h2 class="trello-modal-title">{{ card.name }}</h2>
            <button class="trello-modal-close" @click="handleClose">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="trello-modal-body">
            <div v-if="coverImage" class="trello-modal-cover">
              <img :src="coverImage" :alt="`Imagem de capa de ${card.name}`" />
            </div>

            <div class="container-fluid pt-3">
                <div class="modal-buttons-options position-relative">
                    <button class="btn btn-primary me-2" @click="toggleMembersMenu">
                        <i class="fa-solid fa-plus"></i>
                        Adicionar membro
                    </button>

                    <button
                      v-if="!isCurrentUserMember"
                      class="btn btn-secondary"
                      @click="joinAsMember"
                      :disabled="joiningAsMember"
                    >
                      <i class="bi bi-plus-circle"></i>
                      {{ joiningAsMember ? 'Ingressando...' : 'Ingressar' }}
                    </button>
                    <button
                      v-else
                      class="btn btn-danger"
                      @click="leaveAsMember"
                      :disabled="leavingAsMember"
                    >
                      <i class="bi bi-x-circle"></i>
                      {{ leavingAsMember ? 'Saindo...' : 'Sair' }}
                    </button>

                    <div v-if="showMembersMenu" class="members-menu">
                        <div class="members-menu-header">
                            <button class="members-menu-back" @click="closeMembersMenu">
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <h3 class="members-menu-title">Membros</h3>
                            <button class="members-menu-close" @click="closeMembersMenu">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>

                        <div class="members-menu-search">
                            <input
                                v-model="memberSearchQuery"
                                type="text"
                                class="members-menu-search-input"
                                placeholder="Pesquisar membros"
                                @input="searchMembers"
                            />
                        </div>

                        <div class="members-menu-content">
                            <h4 class="members-menu-section-title">Adicionar membros</h4>
                            <div v-if="loadingMembers" class="members-menu-loading">
                                <span>Carregando...</span>
                            </div>
                            <div v-else-if="availableMembers.length === 0" class="members-menu-empty">
                                <span>Nenhum designer encontrado</span>
                            </div>
                            <div v-else class="members-menu-list">
                                <div
                                    v-for="member in filteredMembers"
                                    :key="member.id"
                                    class="members-menu-item"
                                    :class="{ 'is-adding': addingMember && currentAddingMemberId === member.id }"
                                    @click="addMember(member)"
                                >
                                    <div class="members-menu-avatar" :style="{ backgroundColor: getAvatarColor(member.name) }">
                                        {{ getInitials(member.name) }}
                                    </div>
                                    <span class="members-menu-name">{{ member.name }}</span>
                                    <span v-if="addingMember && currentAddingMemberId === member.id" class="members-menu-loading-indicator">
                                        <i class="fa fa-spinner fa-spin"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-content-layout">
              <div class="trello-modal-main">
                <div v-if="card.members && card.members.length > 0" class="trello-modal-section">
                  <h3 class="trello-modal-section-title">
                    <i class="fa fa-user"></i> Membros
                  </h3>
                  <div class="trello-modal-members-list">
                    <div
                      v-for="member in card.members"
                      :key="member.id"
                      class="trello-modal-member-avatar"
                      :class="{ 'is-clickable': canRemoveMembers }"
                      :style="{ backgroundColor: getAvatarColor(member.name) }"
                      :title="member.name"
                      @click="canRemoveMembers ? handleMemberClick(member) : null"
                    >
                      {{ getInitials(member.name) }}
                      <div v-if="showMemberMenu && selectedMember?.id === member.id" class="member-menu-popover" @click.stop>
                        <button class="member-menu-remove" @click="removeMember(member)">
                          <i class="fa fa-times"></i>
                          Remover do card
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="trello-modal-section">
                  <h3 class="trello-modal-section-title">
                    <i class="fa fa-calendar"></i> Prazo
                  </h3>
                  <div class="trello-modal-info">
                    <span>{{ card.delivery_date_start }} - {{ card.delivery_date_end }}</span>
                    <span class="trello-modal-info-label">{{ card.delivery_time }} dias</span>
                  </div>
                </div>

                <div class="trello-modal-section">
                  <h3 class="trello-modal-section-title">
                    <i class="fa fa-align-left"></i> Descrição
                  </h3>
                  <div v-if="!isEditingDescription" class="trello-modal-description" :class="{ 'is-empty': !card.description }" @click="startEditingDescription">
                    {{ card.description || 'Adicione uma descrição mais detalhada...' }}
                  </div>
                  <div v-else class="trello-modal-description-edit">
                    <textarea
                      v-model="descriptionText"
                      class="trello-modal-description-textarea"
                      maxlength="500"
                      rows="4"
                      placeholder="Adicione uma descrição mais detalhada..."
                    ></textarea>
                    <div class="trello-modal-description-footer">
                      <span class="trello-modal-description-counter">{{ descriptionText.length }}/500</span>
                      <div class="trello-modal-description-actions">
                        <button class="trello-modal-description-cancel" @click="cancelEditingDescription">
                          Cancelar
                        </button>
                        <button class="trello-modal-description-save" @click="saveDescription" :disabled="isSavingDescription">
                          {{ isSavingDescription ? 'Salvando...' : 'Salvar' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="card.uploaded_files && card.uploaded_files.length > 0" class="trello-modal-section">
                  <h3 class="trello-modal-section-title">
                    <i class="fa fa-paperclip"></i> Anexos
                  </h3>
                  <div class="trello-modal-attachments">
                    <div
                      v-for="(file, fileIndex) in card.uploaded_files"
                      :key="fileIndex"
                      class="trello-modal-attachment"
                    >
                      <div class="trello-modal-attachment-preview">
                        <img v-if="isImageFile(file)" :src="getImageUrl(file)" :alt="getAttachmentName(file, fileIndex)" />
                        <i v-else class="fa fa-file"></i>
                      </div>
                      <div class="trello-modal-attachment-body">
                        <div class="trello-modal-attachment-name">{{ getAttachmentName(file, fileIndex) }}</div>
                        <div class="trello-modal-attachment-meta">
                          {{ formatDate(file.created_at) }}
                        </div>
                        <a
                          class="trello-modal-attachment-button"
                          :href="getImageUrl(file)"
                          target="_blank"
                          rel="noopener noreferrer"
                        >
                          Abrir
                        </a>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="trello-modal-section">
                  <h3 class="trello-modal-section-title">
                    <i class="fa fa-money"></i> Valor do Orçamento
                  </h3>
                  <div class="trello-modal-info">
                    <span class="trello-modal-amount">{{ formatCurrency(card.total_amount) }}</span>
                  </div>
                </div>

                <!-- Detalhes da Parede -->
                <div v-if="card.wall" class="trello-modal-section">
                  <h3 class="trello-modal-section-title">
                    <i class="fa fa-ruler"></i> Detalhes da Parede
                  </h3>
                  <div class="trello-modal-wall-details">
                    <div class="trello-modal-wall-info-grid">
                      <div class="trello-modal-wall-info-item">
                        <div class="trello-modal-wall-info-label">Nome da Parede</div>
                        <div class="trello-modal-wall-info-value">{{ card.wall.name || 'Não informado' }}</div>
                      </div>
                      <div class="trello-modal-wall-info-item">
                        <div class="trello-modal-wall-info-label">Largura</div>
                        <div class="trello-modal-wall-info-value">{{ formatNumber(card.wall.width) }} m</div>
                      </div>
                      <div class="trello-modal-wall-info-item">
                        <div class="trello-modal-wall-info-label">Altura</div>
                        <div class="trello-modal-wall-info-value">{{ formatNumber(card.wall.height) }} m</div>
                      </div>
                      <div class="trello-modal-wall-info-item">
                        <div class="trello-modal-wall-info-label">Área Total</div>
                        <div class="trello-modal-wall-info-value">{{ formatNumber(card.wall.total_area) }} m²</div>
                      </div>
                      <div v-if="card.wall.strip_height" class="trello-modal-wall-info-item">
                        <div class="trello-modal-wall-info-label">Altura da Faixa</div>
                        <div class="trello-modal-wall-info-value">{{ formatNumber(card.wall.strip_height) }} m</div>
                      </div>
                      <div v-if="card.wall.strip_count" class="trello-modal-wall-info-item">
                        <div class="trello-modal-wall-info-label">Quantidade de Faixas</div>
                        <div class="trello-modal-wall-info-value">{{ card.wall.strip_count }}</div>
                      </div>
                    </div>

                    <!-- Continuações -->
                    <div v-if="card.wall.continue_same_art && card.wall.continuations && card.wall.continuations.length > 0" class="trello-modal-continuations">
                      <h4 class="trello-modal-continuations-title">
                        <i class="fa fa-arrows-h"></i> Continuações
                      </h4>
                      <div class="trello-modal-continuations-list">
                        <div
                          v-for="(continuation, index) in card.wall.continuations"
                          :key="index"
                          class="trello-modal-continuation-item"
                        >
                          <div class="trello-modal-continuation-header">
                            <span class="trello-modal-continuation-number">Continuação {{ index + 1 }}</span>
                          </div>
                          <div class="trello-modal-continuation-details">
                            <div class="trello-modal-continuation-detail">
                              <span class="trello-modal-continuation-label">Largura:</span>
                              <span class="trello-modal-continuation-value">{{ formatNumber(continuation.width) }} m</span>
                            </div>
                            <div class="trello-modal-continuation-detail">
                              <span class="trello-modal-continuation-label">Altura:</span>
                              <span class="trello-modal-continuation-value">{{ formatNumber(continuation.height) }} m</span>
                            </div>
                            <div class="trello-modal-continuation-detail">
                              <span class="trello-modal-continuation-label">Área:</span>
                              <span class="trello-modal-continuation-value">
                                {{ formatNumber((Number(continuation.width) || 0) * (Number(continuation.height) || 0)) }} m²
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Imagens de Upload -->
                <div v-if="card.uploaded_files && card.uploaded_files.length > 0" class="trello-modal-section">
                  <h3 class="trello-modal-section-title">
                    <i class="fa fa-upload"></i> Imagens Enviadas
                  </h3>
                  <div class="trello-modal-model-images">
                    <div
                      v-for="(file, fileIndex) in card.uploaded_files"
                      :key="fileIndex"
                      class="trello-modal-model-image"
                    >
                      <img :src="getImageUrl(file)" :alt="file.name || 'Imagem enviada'" />
                    </div>
                  </div>
                </div>

                <!-- Imagens de Coleção -->
                <div v-if="card.budget" class="trello-modal-section">
                  <h3 class="trello-modal-section-title">
                    <i class="fa fa-cube"></i> Modelos selecionados
                  </h3>
                  <div v-if="getCollectionModels(card.budget).length > 0">
                    {{ getCollectionModels(card.budget).map(model => model.name).join(', ') }}
                  </div>
                  <div v-else class="trello-modal-info text-muted">
                    Nenhum modelo selecionado
                  </div>
                </div>

                <!-- Imagens da Parede Específica -->
                <div v-if="card.wall && card.wall.collection_model" class="trello-modal-section">
                  <h3 class="trello-modal-section-title">
                    <i class="fa fa-image"></i> Imagens da Parede
                  </h3>
                  <div v-if="card.wall.collection_model.files && card.wall.collection_model.files.length > 0" class="trello-modal-model-images">
                    <div
                      v-for="(file, fileIndex) in card.wall.collection_model.files"
                      :key="fileIndex"
                      class="trello-modal-model-image"
                    >
                      <img :src="getImageUrl(file)" :alt="file.name || 'Imagem da parede'" />
                    </div>
                  </div>
                  <div v-else class="trello-modal-info text-muted">
                    Nenhuma imagem disponível para esta parede
                  </div>
                </div>

                <div v-if="card.budget" class="trello-modal-section">
                  <h3 class="trello-modal-section-title">
                    <i class="fa fa-link"></i> Links
                  </h3>
                  <div v-if="card.budget.link_referring_model" class="trello-modal-info">
                    <a
                      :href="card.budget.link_referring_model"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="trello-modal-link"
                    >
                      <i class="fa fa-external-link"></i>
                      {{ card.budget.link_referring_model }}
                    </a>
                  </div>
                  <div v-else class="trello-modal-info text-muted">
                    Nenhum link disponível
                  </div>
                </div>
              </div>

              <aside class="trello-modal-sidebar">
                <div class="trello-modal-sidebar-header">
                  <h3>Comentários e atividade</h3>
                  <button class="trello-modal-details-button" type="button" @click="toggleDetails">
                    {{ showDetails ? 'Ocultar Detalhes' : 'Mostrar Detalhes' }}
                  </button>
                </div>

                <!-- Caixa de texto para escrever comentários -->
                <div class="trello-modal-comment-input-section">
                  <div class="trello-modal-comment-input-wrapper">
                    <textarea
                      v-model="newCommentText"
                      class="trello-modal-comment-textarea"
                      maxlength="500"
                      rows="3"
                      placeholder="Escrever um comentário..."
                      @focus="isEditingComment = true"
                    ></textarea>
                    <div v-if="isEditingComment" class="trello-modal-comment-input-footer">
                      <span class="trello-modal-comment-counter">{{ newCommentText.length }}/500</span>
                      <div class="trello-modal-comment-input-actions">
                        <button class="trello-modal-comment-cancel" @click="cancelNewComment">
                          Cancelar
                        </button>
                        <button class="trello-modal-comment-save" @click="saveNewComment" :disabled="isSavingComment || !newCommentText.trim()">
                          {{ isSavingComment ? 'Salvando...' : 'Salvar' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Lista de comentários e atividades (visível apenas quando showDetails é true) -->
                <div v-if="showDetails">
                  <!-- Lista de comentários -->
                  <div class="trello-modal-comments-list">
                    <div
                      v-for="comment in cardComments"
                      :key="comment.id"
                      class="trello-modal-comment-item"
                    >
                      <div v-if="editingCommentId !== comment.id" class="trello-modal-comment-content">
                        <div class="trello-modal-comment-header">
                          <div class="trello-modal-comment-author-wrapper">
                            <div class="trello-modal-comment-avatar" :style="{ backgroundColor: getAvatarColor(comment.user_name) }">
                              {{ getInitials(comment.user_name) }}
                            </div>
                            <span class="trello-modal-comment-author">{{ comment.user_name }}</span>
                          </div>
                          <span class="trello-modal-comment-date">{{ formatDate(comment.created_at) }}</span>
                        </div>
                        <div class="trello-modal-comment-text">{{ comment.comment }}</div>
                        <div class="trello-modal-comment-actions">
                          <button class="trello-modal-comment-action-btn" @click="startEditComment(comment)">
                            Editar
                          </button>
                          <span class="trello-modal-comment-action-separator">/</span>
                          <button class="trello-modal-comment-action-btn trello-modal-comment-delete" @click="deleteComment(comment.id)">
                            Excluir
                          </button>
                        </div>
                      </div>
                      <div v-else class="trello-modal-comment-edit">
                        <textarea
                          v-model="editingCommentText"
                          class="trello-modal-comment-textarea"
                          maxlength="500"
                          rows="3"
                        ></textarea>
                        <div class="trello-modal-comment-input-footer">
                          <span class="trello-modal-comment-counter">{{ editingCommentText.length }}/500</span>
                          <div class="trello-modal-comment-input-actions">
                            <button class="trello-modal-comment-cancel" @click="cancelEditComment">
                              Cancelar
                            </button>
                            <button class="trello-modal-comment-save" @click="saveEditComment(comment.id)" :disabled="isSavingComment || !editingCommentText.trim()">
                              {{ isSavingComment ? 'Salvando...' : 'Salvar' }}
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div v-if="cardComments.length === 0" class="trello-modal-info text-muted">
                      Nenhum comentário ainda.
                    </div>
                  </div>

                  <!-- Atividades e Histórico -->
                  <div class="trello-modal-activity">
                    <div v-if="activityItems.length > 0" class="trello-modal-activity-list">
                      <div
                        v-for="(activity, activityIndex) in activityItems"
                        :key="activity.id || activityIndex"
                        class="trello-modal-activity-item"
                        :class="{ 'is-history': activity.type === 'history' }"
                      >
                        <div class="trello-modal-activity-header">
                          <div class="trello-modal-activity-author-wrapper">
                            <div v-if="activity.type !== 'history'" class="trello-modal-activity-avatar" :style="{ backgroundColor: getAvatarColor(getActivityUser(activity)) }">
                              {{ getInitials(getActivityUser(activity)) }}
                            </div>
                            <div v-else class="trello-modal-activity-icon">
                              <i class="fa fa-history"></i>
                            </div>
                            <span v-if="activity.type !== 'history'" class="trello-modal-activity-author">{{ getActivityUser(activity) }}</span>
                            <span v-else class="trello-modal-activity-author">Histórico</span>
                          </div>
                          <span class="trello-modal-activity-date">{{ formatDate(activity.created_at || activity.date) }}</span>
                        </div>
                        <div class="trello-modal-activity-content" v-html="getActivityText(activity)"></div>
                      </div>
                    </div>
                    <div v-else class="trello-modal-info text-muted">
                      Nenhuma atividade registrada.
                    </div>
                  </div>
                </div>
              </aside>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
</template>

  <script setup>
  import { computed, ref, watch } from 'vue';
  import { useAuthStore } from '@/stores/auth';

  const props = defineProps({
    card: {
      type: Object,
      default: null,
    },
  });

  const emit = defineEmits(['close']);

  const auth = useAuthStore();

  const showDetails = ref(false);
  const isEditingDescription = ref(false);
  const descriptionText = ref('');
  const originalDescription = ref('');
  const isSavingDescription = ref(false);

  // Comentários
  const isEditingComment = ref(false);
  const newCommentText = ref('');
  const isSavingComment = ref(false);
  const editingCommentId = ref(null);
  const editingCommentText = ref('');

  // Membros
  const showMembersMenu = ref(false);
  const availableMembers = ref([]);
  const memberSearchQuery = ref('');
  const loadingMembers = ref(false);
  const addingMember = ref(false);
  const currentAddingMemberId = ref(null);
  const joiningAsMember = ref(false);
  const leavingAsMember = ref(false);
  const showMemberMenu = ref(false);
  const selectedMember = ref(null);

  const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  });

  const activityDateFormatter = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });

  const coverImage = computed(() => {
    if (!props.card) {
      return '';
    }
    if (props.card.image) {
      return props.card.image;
    }
    const imageAttachment = props.card.uploaded_files?.find(file => isImageFile(file));
    return imageAttachment ? getImageUrl(imageAttachment) : '';
  });

  const cardComments = computed(() => {
    if (!props.card || !Array.isArray(props.card.comments)) {
      return [];
    }
    return props.card.comments;
  });

  const activityItems = computed(() => {
    if (!props.card) {
      return [];
    }

    const activities = [];

    // Adicionar histórico do card
    if (Array.isArray(props.card.history) && props.card.history.length > 0) {
      props.card.history.forEach((historyItem) => {
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

  function formatCurrency(value) {
    if (value === null || value === undefined) {
      return currencyFormatter.format(0);
    }
    const numericValue = Number(value);
    return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
  }

  function formatNumber(value) {
    if (value === null || value === undefined) {
      return '-';
    }
    const numericValue = Number(value);
    return Number.isFinite(numericValue) ? numericValue.toFixed(2) : value;
  }

  function getCollectionModels(budget) {
    if (!budget || !budget.rooms) {
      return [];
    }

    const models = [];
    budget.rooms.forEach(room => {
      if (room.walls) {
        room.walls.forEach(wall => {
          // Pode vir como collection_model ou collectionModel
          const model = wall.collection_model || wall.collectionModel;
          if (model) {
            models.push(model);
          }
        });
      }
    });

    return models;
  }

  function getImageUrl(file) {
    if (file.url) {
      return file.url;
    }
    if (file.fileUrl) {
      return file.fileUrl;
    }
    if (file.file_path) {
      // Se for um caminho relativo, construir a URL completa
      if (file.file_path.startsWith('http')) {
        return file.file_path;
      }
      return `/storage/${file.file_path}`;
    }
    return '';
  }

  function getAttachmentName(file, index = 0) {
    return file?.name || file?.original_name || file?.file_name || `Arquivo ${index + 1}`;
  }

  function isImageFile(file) {
    if (!file) {
      return false;
    }
    const mime = (file.mime || file.mimetype || '').toLowerCase();
    if (mime.startsWith('image/')) {
      return true;
    }
    const name = (file.name || file.original_name || file.file_name || '').toLowerCase();
    return ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'].some(ext => name.endsWith(ext));
  }

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
    // Se for histórico, retornar a descrição (que pode conter HTML)
    if (activity.type === 'history' && activity.description) {
      return activity.description;
    }
    return activity.text || activity.comment || activity.description || activity.message || '';
  }

  function toggleDetails() {
    showDetails.value = !showDetails.value;
  }

  function startEditingDescription() {
    originalDescription.value = props.card?.description || '';
    descriptionText.value = originalDescription.value;
    isEditingDescription.value = true;
  }

  function cancelEditingDescription() {
    descriptionText.value = originalDescription.value;
    isEditingDescription.value = false;
  }

  async function saveDescription() {
    if (!props.card?.id) {
      return;
    }

    isSavingDescription.value = true;

    try {
      const response = await axios.put(`v1/budgets/order-budgets/${props.card.id}/description`, {
        description: descriptionText.value,
      });

      // Atualizar o card localmente
      if (props.card) {
        props.card.description = descriptionText.value;
      }

      originalDescription.value = descriptionText.value;
      isEditingDescription.value = false;

      // Mostrar mensagem de sucesso
      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.data.message || 'Descrição salva com sucesso',
        });
      }
    } catch (error) {
      console.error('Erro ao salvar descrição:', error);
      const errorMessage = error.response?.data?.message || 'Erro ao salvar descrição. Tente novamente.';

      if (window.Swal) {
        window.Swal.fire('Erro!', errorMessage, 'error');
      } else {
        alert(errorMessage);
      }
    } finally {
      isSavingDescription.value = false;
    }
  }

  function handleClose() {
    emit('close');
  }

  // Funções de comentários
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
      const response = await axios.post(`v1/budgets/order-budgets/${props.card.id}/comments`, {
        comment: newCommentText.value.trim(),
      });

      // Adicionar o novo comentário à lista
      if (props.card && Array.isArray(props.card.comments)) {
        props.card.comments.unshift(response.data.data);
      } else if (props.card) {
        props.card.comments = [response.data.data];
      }

      newCommentText.value = '';
      isEditingComment.value = false;

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.data.message || 'Comentário adicionado com sucesso',
        });
      }
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


  const isCurrentUserMember = computed(() => {
    if (!auth.user?.id || !props.card?.members) {
      return false;
    }
    return props.card.members.some(member => member.id === auth.user.id);
  });

  const canRemoveMembers = computed(() => {
    return auth.user?.user_type_id === 2;
  });

  const filteredMembers = computed(() => {
    // Filtrar membros que já estão no card
    const cardMemberIds = props.card?.members?.map(m => m.id) || [];
    let members = availableMembers.value.filter(member => !cardMemberIds.includes(member.id));

    if (!memberSearchQuery.value.trim()) {
      return members;
    }
    const query = memberSearchQuery.value.toLowerCase().trim();
    return members.filter(member =>
      member.name.toLowerCase().includes(query)
    );
  });

  function toggleMembersMenu() {
    showMembersMenu.value = !showMembersMenu.value;
    if (showMembersMenu.value && availableMembers.value.length === 0) {
      fetchMembers();
    }
  }

  function closeMembersMenu() {
    showMembersMenu.value = false;
    memberSearchQuery.value = '';
  }

  async function fetchMembers() {
    try {
      loadingMembers.value = true;
      const response = await window.axios.get('v1/users/search', {
        params: {
          user_type_id: 4
        }
      });

      if (response.data.success && response.data.data) {
        // Se a resposta estiver paginada, pegar o array de dados
        if (response.data.data.data && Array.isArray(response.data.data.data)) {
          availableMembers.value = response.data.data.data;
        } else if (Array.isArray(response.data.data)) {
          availableMembers.value = response.data.data;
        } else {
          availableMembers.value = [];
        }
      }
    } catch (error) {
      console.error('Erro ao buscar membros:', error);
      availableMembers.value = [];
    } finally {
      loadingMembers.value = false;
    }
  }

  function searchMembers() {
    // A busca é feita via computed filteredMembers
    // Mas podemos adicionar debounce aqui se necessário
  }

  async function addMember(member) {
    if (!props.card?.id || addingMember.value) {
      return;
    }

    addingMember.value = true;
    currentAddingMemberId.value = member.id;

    try {
      const response = await axios.post(`v1/budgets/order-budgets/${props.card.id}/members`, {
        user_id: member.id,
      });

      // Fechar o menu de membros após adicionar
      closeMembersMenu();

      // Adicionar o membro à lista do card
      if (props.card && !props.card.members) {
        props.card.members = [];
      }
      if (props.card && !props.card.members.find(m => m.id === member.id)) {
        props.card.members.push({
          id: member.id,
          name: member.name,
        });
      }

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.data.message || 'Membro adicionado com sucesso',
        });
      }
    } catch (error) {
      console.error('Erro ao adicionar membro:', error);
      const errorMessage = error.response?.data?.message || 'Erro ao adicionar membro. Tente novamente.';

      if (window.Swal) {
        window.Swal.fire('Erro!', errorMessage, 'error');
      } else {
        alert(errorMessage);
      }
    } finally {
      addingMember.value = false;
      currentAddingMemberId.value = null;
    }
  }

  async function joinAsMember() {
    if (!props.card?.id || joiningAsMember.value || !auth.user?.id) {
      return;
    }

    joiningAsMember.value = true;

    try {
      const response = await axios.post(`v1/budgets/order-budgets/${props.card.id}/members`, {
        user_id: auth.user.id,
      });

      // Adicionar o usuário logado à lista de membros do card
      if (props.card && !props.card.members) {
        props.card.members = [];
      }
      if (props.card && auth.user && !props.card.members.find(m => m.id === auth.user.id)) {
        props.card.members.push({
          id: auth.user.id,
          name: auth.user.name,
        });
      }

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.data.message || 'Você ingressou no card com sucesso',
        });
      }
    } catch (error) {
      console.error('Erro ao ingressar no card:', error);
      const errorMessage = error.response?.data?.message || 'Erro ao ingressar no card. Tente novamente.';

      if (window.Swal) {
        window.Swal.fire('Erro!', errorMessage, 'error');
      } else {
        alert(errorMessage);
      }
    } finally {
      joiningAsMember.value = false;
    }
  }

  async function leaveAsMember() {
    if (!props.card?.id || leavingAsMember.value || !auth.user?.id) {
      return;
    }

    leavingAsMember.value = true;

    try {
      const response = await axios.delete(`v1/budgets/order-budgets/${props.card.id}/members/${auth.user.id}`);

      // Remover o usuário logado da lista de membros do card
      if (props.card && Array.isArray(props.card.members)) {
        props.card.members = props.card.members.filter(m => m.id !== auth.user.id);
      }

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.data.message || 'Você saiu do card com sucesso',
        });
      }
    } catch (error) {
      console.error('Erro ao sair do card:', error);
      const errorMessage = error.response?.data?.message || 'Erro ao sair do card. Tente novamente.';

      if (window.Swal) {
        window.Swal.fire('Erro!', errorMessage, 'error');
      } else {
        alert(errorMessage);
      }
    } finally {
      leavingAsMember.value = false;
    }
  }

  function handleMemberClick(member) {
    if (showMemberMenu.value && selectedMember.value?.id === member.id) {
      showMemberMenu.value = false;
      selectedMember.value = null;
    } else {
      showMemberMenu.value = true;
      selectedMember.value = member;
    }
  }

  async function removeMember(member) {
    if (!props.card?.id || !member?.id) {
      return;
    }

    if (window.Swal) {
      const result = await window.Swal.fire({
        title: 'Remover membro?',
        text: `Deseja remover ${member.name} do card?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, remover',
        cancelButtonText: 'Cancelar',
      });

      if (!result.isConfirmed) {
        showMemberMenu.value = false;
        selectedMember.value = null;
        return;
      }
    }

    try {
      const response = await axios.delete(`v1/budgets/order-budgets/${props.card.id}/members/${member.id}`);

      // Remover o membro da lista do card
      if (props.card && Array.isArray(props.card.members)) {
        props.card.members = props.card.members.filter(m => m.id !== member.id);
      }

      // Adicionar o membro de volta à lista de disponíveis
      if (!availableMembers.value.find(m => m.id === member.id)) {
        availableMembers.value.push(member);
      }

      showMemberMenu.value = false;
      selectedMember.value = null;

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.data.message || 'Membro removido com sucesso',
        });
      }
    } catch (error) {
      console.error('Erro ao remover membro:', error);
      const errorMessage = error.response?.data?.message || 'Erro ao remover membro. Tente novamente.';

      if (window.Swal) {
        window.Swal.fire('Erro!', errorMessage, 'error');
      } else {
        alert(errorMessage);
      }
    }
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
    // Cores vibrantes para avatares
    const colors = [
      '#00b8d9', // Cyan
      '#00a86b', // Teal
      '#0065ff', // Blue
      '#5243aa', // Purple
      '#ff5630', // Red
      '#ff8b00', // Orange
      '#36b37e', // Green
      '#ffab00', // Yellow
      '#6554c0', // Violet
      '#00c7e6', // Light Cyan
    ];
    // Gerar um índice baseado no nome para consistência
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
      hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return colors[Math.abs(hash) % colors.length];
  }

  function startEditComment(comment) {
    editingCommentId.value = comment.id;
    editingCommentText.value = comment.comment;
  }

  function cancelEditComment() {
    editingCommentId.value = null;
    editingCommentText.value = '';
  }

  async function saveEditComment(commentId) {
    if (!props.card?.id || !editingCommentText.value.trim()) {
      return;
    }

    isSavingComment.value = true;

    try {
      const response = await axios.put(`v1/budgets/order-budgets/${props.card.id}/comments/${commentId}`, {
        comment: editingCommentText.value.trim(),
      });

      // Atualizar o comentário na lista
      if (props.card && Array.isArray(props.card.comments)) {
        const index = props.card.comments.findIndex(c => c.id === commentId);
        if (index !== -1) {
          props.card.comments[index] = response.data.data;
        }
      }

      editingCommentId.value = null;
      editingCommentText.value = '';

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.data.message || 'Comentário atualizado com sucesso',
        });
      }
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

  async function deleteComment(commentId) {
    if (!props.card?.id) {
      return;
    }

    if (window.Swal) {
      const result = await window.Swal.fire({
        title: 'Excluir comentário?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
      });

      if (!result.isConfirmed) {
        return;
      }
    }

    try {
      await axios.delete(`v1/budgets/order-budgets/${props.card.id}/comments/${commentId}`);

      // Remover o comentário da lista
      if (props.card && Array.isArray(props.card.comments)) {
        props.card.comments = props.card.comments.filter(c => c.id !== commentId);
      }

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: 'Comentário excluído com sucesso',
        });
      }
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

  // Inicializar descrição quando o card mudar
  watch(() => props.card, (newCard) => {
    if (newCard) {
      descriptionText.value = newCard.description || '';
      originalDescription.value = newCard.description || '';
    }
    // Fechar menu de membro quando o card mudar
    showMemberMenu.value = false;
    selectedMember.value = null;
  }, { immediate: true });

  // Fechar menu de membro ao clicar fora
  watch(() => showMemberMenu.value, (isOpen) => {
    if (isOpen) {
      const closeMenu = (e) => {
        if (!e.target.closest('.trello-modal-member-avatar')) {
          showMemberMenu.value = false;
          selectedMember.value = null;
          document.removeEventListener('click', closeMenu);
        }
      };
      setTimeout(() => {
        document.addEventListener('click', closeMenu);
      }, 0);
    }
  });
  </script>

<style lang="scss" scoped>
  // Modal Styles
  .trello-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 20px;
    animation: fadeIn 0.2s ease;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  .trello-modal {
    background-color: #ffffff;
    border-radius: 8px;
    width: 100%;
    max-width: 1200px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 16px rgba(9, 30, 66, 0.25);
    animation: slideUp 0.3s ease;
    overflow: hidden;
  }

  @keyframes slideUp {
    from {
      transform: translateY(20px);
      opacity: 0;
    }
    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  .trello-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #dfe1e6;
  }

  .trello-modal-title {
    font-size: 20px;
    font-weight: 600;
    color: #172b4d;
    margin: 0;
  }

  .trello-modal-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #5e6c84;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s ease;

    &:hover {
      background-color: #dfe1e6;
      color: #172b4d;
    }
  }

  .trello-modal-body {
    padding: 0;
    overflow-y: auto;
    flex: 1;
  }

  .trello-modal-cover {
    width: 100%;
    height: 200px;
    border-radius: 0;
    overflow: hidden;
    margin-bottom: 0;
    background-color: #f0ece8;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }

  .modal-content-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 24px;
    align-items: flex-start;
    padding: 24px;
  }

  .trello-modal-main {
    min-width: 0;
    border-right: 1px solid silver;
  }

  .trello-modal-sidebar {
    background-color: #f9fafc;
    border: 1px solid #dfe1e6;
    border-radius: 8px;
    padding: 16px;
    position: sticky;
    top: 24px;
    max-height: calc(90vh - 200px);
    overflow-y: auto;
  }

  .trello-modal-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;

    h3 {
      font-size: 15px;
      font-weight: 600;
      color: #172b4d;
      margin: 0;
    }
  }

  .trello-modal-details-button {
    border: none;
    background-color: #091e42;
    color: #ffffff;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    transition: opacity 0.2s ease;

    &:hover {
      opacity: 0.85;
    }
  }

  .trello-modal-activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .trello-modal-activity-item {
    padding: 12px;
    background-color: #ffffff;
    border-radius: 6px;
    border: 1px solid #dfe1e6;
  }

  .trello-modal-activity-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
  }

  .trello-modal-activity-author-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .trello-modal-activity-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 600;
    font-size: 12px;
    flex-shrink: 0;
  }

  .trello-modal-activity-author {
    font-weight: 600;
    color: #172b4d;
    font-size: 13px;
  }

  .trello-modal-activity-date {
    font-size: 12px;
    color: #5e6c84;
  }

  .trello-modal-activity-content {
    font-size: 13px;
    color: #172b4d;
    line-height: 1.4;

    :deep(a) {
      color: #0079bf;
      text-decoration: none;

      &:hover {
        text-decoration: underline;
      }
    }
  }

  .trello-modal-activity-item.is-history {
    border-left: 3px solid #36b37e;
  }

  .trello-modal-activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #36b37e;
    color: #ffffff;
    font-size: 14px;
    flex-shrink: 0;
  }

  .trello-modal-description {
    padding: 16px;
    background-color: #f4f5f7;
    border-radius: 8px;
    min-height: 80px;
    color: #172b4d;
    line-height: 1.5;
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover {
      background-color: #ebecf0;
    }

    &.is-empty {
      color: #5e6c84;
    }
  }

  .trello-modal-description-edit {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .trello-modal-description-textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #dfe1e6;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    color: #172b4d;
    line-height: 1.5;
    resize: vertical;
    transition: border-color 0.2s ease;

    &:focus {
      outline: none;
      border-color: #0079bf;
    }

    &::placeholder {
      color: #5e6c84;
    }
  }

  .trello-modal-description-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .trello-modal-description-counter {
    font-size: 12px;
    color: #5e6c84;
  }

  .trello-modal-description-actions {
    display: flex;
    gap: 8px;
  }

  .trello-modal-description-cancel,
  .trello-modal-description-save {
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
  }

  .trello-modal-description-cancel {
    background-color: #dfe1e6;
    color: #172b4d;

    &:hover {
      background-color: #c1c7d0;
    }
  }

  .trello-modal-description-save {
    background-color: #0079bf;
    color: #ffffff;

    &:hover:not(:disabled) {
      background-color: #005a8b;
    }

    &:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
  }

  // Estilos de comentários
  .trello-modal-comment-input-section {
    margin-bottom: 16px;
  }

  .trello-modal-comment-input-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .trello-modal-comment-textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #dfe1e6;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    color: #172b4d;
    line-height: 1.5;
    resize: vertical;
    transition: border-color 0.2s ease;

    &:focus {
      outline: none;
      border-color: #0079bf;
    }

    &::placeholder {
      color: #5e6c84;
    }
  }

  .trello-modal-comment-input-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .trello-modal-comment-counter {
    font-size: 12px;
    color: #5e6c84;
  }

  .trello-modal-comment-input-actions {
    display: flex;
    gap: 8px;
  }

  .trello-modal-comment-cancel,
  .trello-modal-comment-save {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
  }

  .trello-modal-comment-cancel {
    background-color: #dfe1e6;
    color: #172b4d;

    &:hover {
      background-color: #c1c7d0;
    }
  }

  .trello-modal-comment-save {
    background-color: #0079bf;
    color: #ffffff;

    &:hover:not(:disabled) {
      background-color: #005a8b;
    }

    &:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
  }

  .trello-modal-comments-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
  }

  .trello-modal-comment-item {
    padding: 12px;
    background-color: #ffffff;
    border-radius: 6px;
    border: 1px solid #dfe1e6;
  }

  .trello-modal-comment-content {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .trello-modal-comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .trello-modal-comment-author-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .trello-modal-comment-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 600;
    font-size: 12px;
    flex-shrink: 0;
  }

  .trello-modal-comment-author {
    font-weight: 600;
    color: #172b4d;
    font-size: 13px;
  }

  .trello-modal-comment-date {
    font-size: 12px;
    color: #5e6c84;
  }

  .trello-modal-comment-text {
    font-size: 13px;
    color: #172b4d;
    line-height: 1.4;
    word-wrap: break-word;
  }

  .trello-modal-comment-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #f4f5f7;
    min-height: 24px;
  }

  .trello-modal-comment-action-separator {
    color: #dfe1e6;
    font-size: 11px;
    padding: 0 2px;
    user-select: none;
  }

  .trello-modal-comment-action-btn {
    background: none;
    border: none;
    color: #5e6c84;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 3px;
    transition: all 0.2s ease;
    text-decoration: none;
    line-height: 1.4;
    display: inline-block;

    &:hover {
      background-color: #f4f5f7;
      color: #172b4d;
      text-decoration: underline;
    }

    &:active {
      opacity: 0.7;
    }

    &.trello-modal-comment-delete {
      &:hover {
        background-color: #fee;
        color: #d32f2f;
      }
    }
  }

  .trello-modal-comment-edit {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .trello-modal-attachments {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .trello-modal-attachment {
    display: flex;
    gap: 12px;
    padding: 12px;
    background-color: #f4f5f7;
    border-radius: 8px;
    align-items: center;
  }

  .trello-modal-attachment-preview {
    width: 64px;
    height: 64px;
    border-radius: 6px;
    overflow: hidden;
    background-color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    i {
      font-size: 24px;
      color: #5e6c84;
    }
  }

  .trello-modal-attachment-body {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
  }

  .trello-modal-attachment-name {
    font-weight: 600;
    color: #172b4d;
    font-size: 14px;
  }

  .trello-modal-attachment-meta {
    font-size: 12px;
    color: #5e6c84;
  }

  .trello-modal-attachment-button {
    align-self: flex-start;
    padding: 6px 12px;
    background-color: #091e42;
    color: #ffffff;
    border-radius: 4px;
    font-size: 12px;
    text-decoration: none;
    transition: background-color 0.2s ease;

    &:hover {
      background-color: #03264c;
    }
  }

  .trello-modal-image {
    width: 100%;
    max-height: 300px;
    margin-bottom: 24px;
    border-radius: 8px;
    overflow: hidden;
    background-color: #f4f5f7;

    img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
  }

  .trello-modal-section {
    margin-bottom: 24px;

    &:last-child {
      margin-bottom: 0;
    }
  }

  .trello-modal-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #172b4d;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;

    i {
      color: #5e6c84;
    }
  }

  .trello-modal-info {
    color: #172b4d;
    font-size: 14px;
    line-height: 1.5;
  }

  .trello-modal-info-label {
    display: inline-block;
    margin-left: 8px;
    padding: 2px 8px;
    background-color: #dfe1e6;
    border-radius: 4px;
    font-size: 12px;
    color: #5e6c84;
  }

  .trello-modal-amount {
    font-size: 18px;
    font-weight: 600;
    color: #0079bf;
  }

  .trello-modal-models {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .trello-modal-model {
    padding: 12px;
    background-color: #f4f5f7;
    border-radius: 6px;
  }

  .trello-modal-model-name {
    font-weight: 600;
    color: #172b4d;
    margin-bottom: 8px;
  }

  .trello-modal-model-images {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .trello-modal-model-image {
    width: 120px;
    height: 120px;
    border-radius: 4px;
    overflow: hidden;
    background-color: #ffffff;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }

  .trello-modal-link {
    color: #0079bf;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    word-break: break-all;

    &:hover {
      text-decoration: underline;
    }

    i {
      font-size: 12px;
    }
  }

  .trello-modal-comment {
    padding: 12px;
    background-color: #f4f5f7;
    border-radius: 6px;
    color: #172b4d;
    line-height: 1.5;
    white-space: pre-wrap;
  }

  .trello-modal-wall-details {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .trello-modal-wall-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
  }

  .trello-modal-wall-info-item {
    padding: 12px;
    background-color: #f4f5f7;
    border-radius: 6px;
  }

  .trello-modal-wall-info-label {
    font-size: 12px;
    color: #5e6c84;
    margin-bottom: 4px;
    font-weight: 500;
  }

  .trello-modal-wall-info-value {
    font-size: 14px;
    color: #172b4d;
    font-weight: 600;
  }

  .trello-modal-continuations {
    margin-top: 8px;
  }

  .trello-modal-continuations-title {
    font-size: 14px;
    font-weight: 600;
    color: #172b4d;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;

    i {
      color: #5e6c84;
      font-size: 12px;
    }
  }

  .trello-modal-continuations-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .trello-modal-continuation-item {
    padding: 12px;
    background-color: #f4f5f7;
    border-radius: 6px;
    border-left: 3px solid #0079bf;
  }

  .trello-modal-continuation-header {
    margin-bottom: 8px;
  }

  .trello-modal-continuation-number {
    font-size: 13px;
    font-weight: 600;
    color: #172b4d;
  }

  .trello-modal-continuation-details {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
  }

  .trello-modal-continuation-detail {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .trello-modal-continuation-label {
    font-size: 12px;
    color: #5e6c84;
  }

  .trello-modal-continuation-value {
    font-size: 13px;
    color: #172b4d;
    font-weight: 600;
  }

  @media (max-width: 968px) {
    .modal-content-layout {
      grid-template-columns: 1fr;
    }

    .trello-modal-sidebar {
      position: static;
      max-height: none;
    }
  }

  // Menu de Membros
  .members-menu {
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 8px;
    width: 340px;
    background-color: #1d2125;
    border-radius: 8px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 600px;
  }

  .members-menu-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .members-menu-back,
  .members-menu-close {
    background: none;
    border: none;
    color: #ffffff;
    font-size: 16px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s ease;

    &:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }
  }

  .members-menu-title {
    font-size: 16px;
    font-weight: 600;
    color: #ffffff;
    margin: 0;
    flex: 1;
    text-align: center;
  }

  .members-menu-search {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .members-menu-search-input {
    width: 100%;
    padding: 8px 12px;
    background-color: #22272b;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    color: #ffffff;
    font-size: 14px;

    &::placeholder {
      color: #8c9cb8;
    }

    &:focus {
      outline: none;
      border-color: #0c66e4;
      background-color: #1d2125;
    }
  }

  .members-menu-content {
    flex: 1;
    overflow-y: auto;
    padding: 12px 16px;
  }

  .members-menu-section-title {
    font-size: 12px;
    font-weight: 600;
    color: #9fadbc;
    text-transform: uppercase;
    margin: 0 0 12px 0;
    letter-spacing: 0.5px;
  }

  .members-menu-loading,
  .members-menu-empty {
    padding: 16px;
    text-align: center;
    color: #8c9cb8;
    font-size: 14px;
  }

  .members-menu-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .members-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    position: relative;

    &:hover:not(.is-adding) {
      background-color: rgba(255, 255, 255, 0.12);
    }

    &:active:not(.is-adding) {
      background-color: rgba(255, 255, 255, 0.16);
    }

    &.is-adding {
      opacity: 0.7;
      cursor: wait;
    }
  }

  .members-menu-loading-indicator {
    margin-left: auto;
    color: #0c66e4;
    font-size: 14px;
  }

  .members-menu-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    flex-shrink: 0;
  }

  .members-menu-name {
    font-size: 14px;
    color: #ffffff;
    font-weight: 400;
  }

  // Lista de membros do card
  .trello-modal-members-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
  }

  .trello-modal-member-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 600;
    font-size: 14px;
    flex-shrink: 0;
    cursor: default;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;

    &:hover {
      transform: scale(1.1);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    &.is-clickable {
      cursor: pointer;
    }
  }

  .member-menu-popover {
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 8px;
    background-color: #ffffff;
    border: 1px solid #dfe1e6;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    min-width: 180px;
    overflow: hidden;
  }

  .member-menu-remove {
    width: 100%;
    padding: 10px 16px;
    background: none;
    border: none;
    text-align: left;
    color: #d32f2f;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.2s ease;

    &:hover {
      background-color: #fee;
    }

    i {
      font-size: 12px;
    }
  }
  </style>

