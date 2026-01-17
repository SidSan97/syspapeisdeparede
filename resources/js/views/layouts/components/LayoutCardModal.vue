<template>
    <Teleport v-if="card" to="body">
      <div class="layout-modal-overlay" @click="handleClose">
        <div class="layout-modal" @click.stop>
          <div class="layout-modal-header">
            <h2 class="layout-modal-title">{{ getCardDisplayName(card) }}</h2>
            <button class="layout-modal-close" @click="handleClose">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="layout-modal-body">
            <div v-if="coverImage" class="layout-modal-cover">
              <img :src="coverImage" :alt="`Imagem de capa de ${card.name}`" />
            </div>

            <div class="container-fluid pt-3">
                <div class="modal-buttons-options position-relative">
                    <button class="btn btn-primary me-2" @click="toggleMembersMenu">
                        <i class="fa-solid fa-plus fa-fw"></i>
                        Adicionar membro
                    </button>

                    <button
                      v-if="!isCurrentUserMember"
                      class="btn btn-secondary"
                      @click="joinAsMember"
                      :disabled="joiningAsMember"
                    >
                      <i class="bi bi-plus-circle fa-fw"></i>
                      {{ joiningAsMember ? 'Ingressando...' : 'Ingressar' }}
                    </button>
                    <button
                      v-else
                      class="btn btn-danger"
                      @click="leaveAsMember"
                      :disabled="leavingAsMember"
                    >
                      <i class="bi bi-x-circle fa-fw"></i>
                      {{ leavingAsMember ? 'Saindo...' : 'Sair' }}
                    </button>

                    <div v-if="showMembersMenu" class="members-menu">
                        <div class="members-menu-header">
                            <button class="members-menu-back" @click="closeMembersMenu">
                                <i class="fa fa-chevron-left fa-fw"></i>
                            </button>
                            <h3 class="members-menu-title">Membros</h3>
                            <button class="members-menu-close" @click="closeMembersMenu">
                                <i class="fa fa-times fa-fw"></i>
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
                                        <i class="fa fa-spinner fa-spin fa-fw"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-content-layout">
              <div class="layout-modal-main">
                <div v-if="card.members && card.members.length > 0" class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-user"></i> Membros
                  </h3>
                  <div class="layout-modal-members-list">
                    <div
                      v-for="member in card.members"
                      :key="member.id"
                      class="layout-modal-member-avatar"
                      :class="{ 'is-clickable': canRemoveMembers }"
                      :style="{ backgroundColor: getAvatarColor(member.name) }"
                      :title="member.name"
                      @click="canRemoveMembers ? handleMemberClick(member) : null"
                    >
                      {{ getInitials(member.name) }}
                      <div v-if="showMemberMenu && selectedMember?.id === member.id" class="member-menu-popover" @click.stop>
                        <button class="member-menu-remove" @click="removeMember(member)">
                          <i class="fa fa-times fa-fw"></i>
                          Remover do card
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-calendar"></i> Prazo
                  </h3>
                  <div class="layout-modal-info">
                    <span>{{ card.delivery_date_start }} - {{ card.delivery_date_end }}</span>
                    <span class="layout-modal-info-label">{{ card.delivery_time }} dias</span>
                  </div>
                </div>

                <DescriptionSection :card="card" />

                <div v-if="card.uploaded_files && card.uploaded_files.length > 0" class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-paperclip"></i> Anexos
                  </h3>
                  <div class="d-flex flex-column gap-3">
                    <div
                      v-for="(file, fileIndex) in card.uploaded_files"
                      :key="fileIndex"
                      class="layout-modal-attachment"
                    >
                      <div class="layout-modal-attachment-preview">
                        <img v-if="isImageFile(file)" :src="getImageUrl(file)" :alt="getAttachmentName(file, fileIndex)" />
                        <i v-else class="fa fa-file fa-fw"></i>
                      </div>
                      <div class="d-flex flex-column gap-2 flex-grow-1">
                        <div class="layout-modal-attachment-name">{{ getAttachmentName(file, fileIndex) }}</div>
                        <div class="layout-modal-attachment-meta">
                          {{ formatDate(file.created_at) }}
                        </div>
                        <a
                          class="layout-modal-attachment-button"
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

                <div class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-money"></i> Valores do Orçamento
                  </h3>
                  <div class="row layout-modal-info">
                    <div class="col-md-6 mb-2">
                      <div class="text-muted small mb-1">Total à Vista</div>
                      <div class="layout-modal-amount text-success">{{ formatCurrency(card.total_amount || 0) }}</div>
                    </div>
                    <div class="col-md-6 mb-2">
                      <div class="text-muted small mb-1">Total a Prazo</div>
                      <div class="layout-modal-amount text-primary">{{ formatCurrency(card.total_amount_installments || 0) }}</div>
                    </div>
                  </div>
                </div>

                <!-- Detalhes da Parede -->
                <div v-if="card.wall" class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-ruler"></i> Detalhes da Parede
                  </h3>
                  <div class="layout-modal-wall-details">
                    <div class="layout-modal-wall-info-grid">
                      <div class="layout-modal-wall-info-item">
                        <div class="layout-modal-wall-info-label">Nome da Parede</div>
                        <div class="layout-modal-wall-info-value">{{ card.wall.name || 'Não informado' }}</div>
                      </div>
                      <div class="layout-modal-wall-info-item">
                        <div class="layout-modal-wall-info-label">Largura</div>
                        <div class="layout-modal-wall-info-value">{{ formatNumber(card.wall.width) }} m</div>
                      </div>
                      <div class="layout-modal-wall-info-item">
                        <div class="layout-modal-wall-info-label">Altura</div>
                        <div class="layout-modal-wall-info-value">{{ formatNumber(card.wall.height) }} m</div>
                      </div>
                      <div class="layout-modal-wall-info-item">
                        <div class="layout-modal-wall-info-label">Metros</div>
                        <div class="layout-modal-wall-info-value">{{ formatNumber(card.wall.total_area) }} m</div>
                      </div>
                      <div v-if="card.wall.strip_height" class="layout-modal-wall-info-item">
                        <div class="layout-modal-wall-info-label">Tamanho da Faixa</div>
                        <div class="layout-modal-wall-info-value">{{ formatNumber(card.wall.strip_height) }} m</div>
                      </div>
                      <div v-if="card.wall.strip_count" class="layout-modal-wall-info-item">
                        <div class="layout-modal-wall-info-label">Quantidade de Faixas</div>
                        <div class="layout-modal-wall-info-value">{{ card.wall.strip_count }}</div>
                      </div>
                    </div>

                    <!-- Continuações -->
                    <div v-if="card.wall.continue_same_art && card.wall.continuations && card.wall.continuations.length > 0" class="layout-modal-continuations">
                      <h4 class="layout-modal-continuations-title">
                        <i class="fa fa-arrows-h"></i> Continuações
                      </h4>
                      <div class="layout-modal-continuations-list">
                        <div
                          v-for="(continuation, index) in card.wall.continuations"
                          :key="index"
                          class="layout-modal-continuation-item"
                        >
                          <div class="layout-modal-continuation-header">
                            <span class="layout-modal-continuation-number">Continuação {{ index + 1 }}</span>
                          </div>
                          <div class="layout-modal-continuation-details">
                            <div class="layout-modal-continuation-detail">
                              <span class="layout-modal-continuation-label">Largura:</span>
                              <span class="layout-modal-continuation-value">{{ formatNumber(continuation.width) }} m</span>
                            </div>
                            <div class="layout-modal-continuation-detail">
                              <span class="layout-modal-continuation-label">Altura:</span>
                              <span class="layout-modal-continuation-value">{{ formatNumber(continuation.height) }} m</span>
                            </div>
                            <div class="layout-modal-continuation-detail">
                              <span class="layout-modal-continuation-label">Metro:</span>
                              <span class="layout-modal-continuation-value">
                                {{ formatNumber(getWallArea(continuation)) }} m
                              </span>
                            </div>
                            <div class="layout-modal-continuation-detail">
                              <span class="layout-modal-continuation-label">Quantidade de Faixas:</span>
                              <span class="layout-modal-continuation-value">{{ calculateStrips(continuation) }}</span>
                            </div>
                            <div class="layout-modal-continuation-detail">
                              <span class="layout-modal-continuation-label">Tamanho da Faixa:</span>
                              <span class="layout-modal-continuation-value">{{ formatNumber(calculateStripHeight(continuation)) }} m</span>
                            </div>
                            <div class="layout-modal-continuation-detail">
                                <span class="layout-modal-continuation-label">Sentido:</span>
                                <span class="layout-modal-continuation-value">{{ getDirection(continuation) }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Imagens de Coleção -->
                <div v-if="card.wall && card.wall.collection_model" class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-cube"></i> Modelos selecionados
                  </h3>
                  <div v-if="card.wall.collection_model.name">
                    {{ card.wall.collection_model.name }}
                  </div>
                  <div v-else class="layout-modal-info text-muted">
                    Nenhum modelo selecionado
                  </div>
                </div>

                <!-- Imagens da Parede Específica -->
                <div v-if="card.wall && card.wall.collection_model" class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-image"></i> Imagens da Parede
                  </h3>
                  <div v-if="card.wall.collection_model.files && card.wall.collection_model.files.length > 0" class="layout-modal-model-images">
                    <div
                      v-for="(file, fileIndex) in card.wall.collection_model.files"
                      :key="fileIndex"
                      class="layout-modal-model-image"
                    >
                      <img :src="getImageUrl(file)" :alt="file.name || 'Imagem da parede'" />
                    </div>
                  </div>
                  <div v-else class="layout-modal-info text-muted">
                    Nenhuma imagem disponível para esta parede
                  </div>
                </div>

                <div v-if="card.budget" class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-link"></i> Links
                  </h3>
                  <div v-if="card.budget.link_referring_model" class="layout-modal-info">
                    <a
                      :href="card.budget.link_referring_model"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="layout-modal-link"
                    >
                      <i class="fa fa-external-link"></i>
                      {{ card.budget.link_referring_model }}
                    </a>
                  </div>
                  <div v-else class="layout-modal-info text-muted">
                    Nenhum link disponível
                  </div>
                </div>

                <RequestArtsSection
                  :show="!!card.budget"
                  :loading="loadingRequestArts"
                  :arts="requestLayoutArts"
                  accordionId="requestArtsAccordion"
                  prefix=""
                />

                <LoadArtSection
                  :card="card"
                  :canLoad="canLoadArt"
                  @art-uploaded="handleArtUploaded"
                />

                <RequestArtsSection
                  :show="true"
                  :loading="loadingRequestArts"
                  :arts="requestLayoutArts"
                  accordionId="requestArtsAccordionBottom"
                  prefix="bottom"
                />
              </div>

              <CommentsAndActivitySidebar
                :card="card"
                v-model:showDetails="showDetails"
              />
            </div>
          </div>
        </div>
      </div>
    </Teleport>
</template>

  <script setup>
  import { computed, ref, watch, toRef } from 'vue';
  import { useAuthStore } from '@/stores/auth';
  import axios from 'axios';
  import { getCardDisplayName } from '@/utils/cardUtils';
  import { getWallArea, calculateStrips, calculateStripHeight } from '@/utils/calculateStripsUtils.js';
  import CommentsAndActivitySidebar from './layoutCardModal/CommentsAndActivitySidebar.vue';
  import DescriptionSection from './layoutCardModal/DescriptionSection.vue';
  import LoadArtSection from './layoutCardModal/LoadArtSection.vue';
  import RequestArtsSection from './layoutCardModal/RequestArtsSection.vue';
  import { useRequestLayoutArts } from '@/views/layouts/composables/useRequestLayoutArts';

  const props = defineProps({
    card: {
      type: Object,
      default: null,
    },
  });

  const emit = defineEmits(['close']);

  const auth = useAuthStore();

  const showDetails = ref(false);

  const cardRef = toRef(props, 'card');
  const { requestLayoutArts, loadingRequestArts, fetchRequestLayoutArts } = useRequestLayoutArts(cardRef);

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

    function getDirection(continuation) {
        if (continuation.direction === 'left-to-right') {
            return 'Esquerda para direita';
        } else if (continuation.direction === 'right-to-left') {
            return 'Direita para esquerda';
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

  function handleClose() {
    emit('close');
  }


  const isCurrentUserMember = computed(() => {
    if (!auth.user?.id || !props.card?.members) {
      return false;
    }
    return props.card.members.some(member => member.id === auth.user.id);
  });

  const canRemoveMembers = computed(() => {
    return auth.isAdmin();
  });

  const canLoadArt = computed(() => {
    return auth.hasRole(['designer', 'admin', 'production']);
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
          role: 'designer'
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

  async function addMember(member) {
    if (!props.card?.id || addingMember.value) {
      return;
    }

    addingMember.value = true;
    currentAddingMemberId.value = member.id;

    try {
      const response = await axios.post(`v1/budgets/order-budgets/${props.card.id}/members`, {
        user_id: member.id,
        type_page: 'layout',
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
        type_page: 'layout',
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
      const response = await axios.delete(`v1/budgets/order-budgets/${props.card.id}/members/${auth.user.id}`, {
        data: { type_page: 'layout' }
      });

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
      const response = await axios.delete(`v1/budgets/order-budgets/${props.card.id}/members/${member.id}`, {
        data: { type_page: 'layout' }
      });

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

    let hash = 0;
    for (let i = 0; i < name.length; i++) {
      hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return colors[Math.abs(hash) % colors.length];
  }

  function handleArtUploaded() {
    // Recarregar lista de artes quando uma arte for enviada
    fetchRequestLayoutArts();
  }

  // Inicializar quando o card mudar
  watch(() => props.card, (newCard) => {
    if (newCard) {
      // O fetchRequestLayoutArts é gerenciado pelo composable useRequestLayoutArts
    }
    // Fechar menu de membro quando o card mudar
    showMemberMenu.value = false;
    selectedMember.value = null;
  }, { immediate: true });

  // Fechar menu de membro ao clicar fora
  watch(() => showMemberMenu.value, (isOpen) => {
    if (isOpen) {
      const closeMenu = (e) => {
        if (!e.target.closest('.layout-modal-member-avatar')) {
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
  @import '@/scss/card-modal.scss';
</style>
