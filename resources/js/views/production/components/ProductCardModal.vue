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
                <div class="modal-buttons-options position-relative d-flex justify-content-between">
                    <div>
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
                    </div>

                    <div class="me-3">
                        <button
                            class="btn btn-primary"
                            @click="markAsProduced"
                            :disabled="markingAsProduced"
                            v-if="card.production_column_names_id < 2"
                        >
                            <span v-if="markingAsProduced" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            {{ markingAsProduced ? 'Atualizando...' : 'Produzido' }}
                        </button>
                        <span v-else>{{ card.production_percentage == 100 ? 'Produzido' : 'Em produção' }}</span>
                    </div>

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

                <div v-if="card.production_column_names_id >= 2" class="layout-modal-section">
                  <div v-if="!isEditingProductionPercentage" class="layout-modal-info">
                    <strong>
                        <span>Total produzido: </span>
                    </strong> {{ formatProductionPercentage(card.production_percentage) }}%
                    <button
                      class="layout-modal-production-edit-btn"
                      @click="startEditingProductionPercentage"
                      title="Editar porcentagem"
                    >
                      <i class="fa fa-edit fa-fw"></i>
                    </button>
                  </div>
                  <div v-else class="layout-modal-production-percentage-edit">
                    <div class="layout-modal-production-input-wrapper">
                      <span class="layout-modal-production-label">Total produzido:</span>
                      <input
                        v-model.number="productionPercentageText"
                        type="number"
                        class="layout-modal-production-input"
                        min="0"
                        max="100"
                        step="0.1"
                        placeholder="0.0"
                        @keyup.enter="saveProductionPercentage"
                        @keyup.esc="cancelEditingProductionPercentage"
                      />
                      <span class="layout-modal-production-input-suffix">%</span>
                    </div>
                    <div class="layout-modal-production-actions">
                      <button
                        class="layout-modal-production-cancel"
                        @click="cancelEditingProductionPercentage"
                      >
                        Cancelar
                      </button>
                      <button
                        class="layout-modal-production-save"
                        @click="saveProductionPercentage"
                        :disabled="isSavingProductionPercentage"
                      >
                        {{ isSavingProductionPercentage ? 'Salvando...' : 'Salvar' }}
                      </button>
                    </div>
                  </div>
                </div>

                <div class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-align-left"></i> Descrição
                  </h3>
                  <div v-if="!isEditingDescription" class="layout-modal-description" :class="{ 'is-empty': !card.description }" @click="startEditingDescription">
                    {{ card.description || 'Adicione uma descrição mais detalhada...' }}
                  </div>
                  <div v-else class="d-flex flex-column gap-3">
                    <textarea
                      v-model="descriptionText"
                      class="form-control"
                      maxlength="500"
                      rows="4"
                      placeholder="Adicione uma descrição mais detalhada..."
                    ></textarea>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-muted small">{{ descriptionText.length }}/500</span>
                      <div class="d-flex gap-1">
                        <button class="btn btn-subtle" @click="cancelEditingDescription">
                          Cancelar
                        </button>
                        <button class="btn btn-primary" @click="saveDescription" :disabled="isSavingDescription">
                          {{ isSavingDescription ? 'Salvando...' : 'Salvar' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

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
                        <div class="layout-modal-wall-info-label">Metro</div>
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

                <!-- Solicitações de Artes -->
                <div v-if="card.budget" class="layout-modal-section p-1">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-paint-brush"></i> Solicitações de Artes
                  </h3>
                  <div v-if="loadingRequestArts" class="layout-modal-info text-muted">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Carregando solicitações de artes...
                  </div>
                  <div v-else-if="requestLayoutArts.length === 0" class="layout-modal-info text-muted">
                    Nenhuma solicitação de arte encontrada para este card.
                  </div>
                  <div v-else class="accordion" id="requestArtsAccordion">
                    <div
                      v-for="(art, artIndex) in requestLayoutArts"
                      :key="art.id || artIndex"
                      class="accordion-item mb-3"
                    >
                      <h2 class="accordion-header">
                        <button
                          class="accordion-button p-3 me-1"
                          :class="{ collapsed: artIndex !== 0 }"
                          type="button"
                          data-bs-toggle="collapse"
                          :data-bs-target="`#art-${artIndex}`"
                          :aria-expanded="artIndex === 0"
                          :aria-controls="`art-${artIndex}`"
                        >
                          <i class="fa fa-image me-2"></i>
                          Arte #{{ art.id }}
                          <span v-if="art.wall_name" class="badge bg-info ms-2">
                            {{ art.wall_name }}
                          </span>
                        </button>
                      </h2>
                      <div
                        :id="`art-${artIndex}`"
                        class="accordion-collapse collapse"
                        :class="{ show: artIndex === 0 }"
                        data-bs-parent="#requestArtsAccordion"
                      >
                        <div class="accordion-body">
                          <div class="layout-modal-art-item-content">
                            <div class="layout-modal-art-header">
                              <div class="layout-modal-art-date">
                                {{ formatDate(art.created_at) }}
                              </div>
                            </div>

                            <div v-if="art.wall_info" class="layout-modal-art-wall-info">
                              <div class="layout-modal-art-info-row">
                                <span class="layout-modal-art-info-label">Ambiente:</span>
                                <span class="layout-modal-art-info-value">{{ art.wall_info.room_name || 'N/A' }}</span>
                              </div>
                              <div class="layout-modal-art-info-row">
                                <span class="layout-modal-art-info-label">Parede:</span>
                                <span class="layout-modal-art-info-value">{{ art.wall_info.wall_name || 'N/A' }}</span>
                              </div>
                              <div v-if="art.wall_info.width || art.wall_info.height" class="layout-modal-art-info-row">
                                <span class="layout-modal-art-info-label">Dimensões:</span>
                                <span class="layout-modal-art-info-value">
                                  {{ formatNumber(art.wall_info.width) }}m x {{ formatNumber(art.wall_info.height) }}m
                                  <span v-if="art.wall_info.total_area"> ({{ formatNumber(art.wall_info.total_area) }} m²)</span>
                                </span>
                              </div>
                            </div>

                            <div v-if="art.dealer_name || art.designer_name" class="layout-modal-art-authors">
                              <div v-if="art.dealer_name" class="layout-modal-art-author">
                                <i class="fa fa-user-tie me-1"></i>
                                <span class="layout-modal-art-author-label">Revendedor:</span>
                                <span class="layout-modal-art-author-name">{{ art.dealer_name }}</span>
                              </div>
                              <div v-if="art.designer_name" class="layout-modal-art-author">
                                <i class="fa fa-user me-1"></i>
                                <span class="layout-modal-art-author-label">Designer:</span>
                                <span class="layout-modal-art-author-name">{{ art.designer_name }}</span>
                              </div>
                            </div>

                            <div v-if="art.comment" class="layout-modal-art-comment">
                              <div class="layout-modal-art-comment-label">Comentário:</div>
                              <div class="layout-modal-art-comment-text">{{ art.comment }}</div>
                            </div>

                            <div v-if="art.image_url" class="layout-modal-art-image">
                              <img
                                :src="art.image_url"
                                :alt="`Arte ${art.id}`"
                                class="layout-modal-art-image-preview"
                                @error="handleImageError"
                              />
                              <a
                                :href="art.image_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="layout-modal-art-image-link"
                              >
                                <i class="fa fa-external-link me-1"></i>
                                Abrir em nova aba
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Relatórios de Produção -->
                <div v-if="card.id" class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-file-pdf"></i> Relatórios de Produção
                  </h3>
                  <div v-if="loadingProductionReports" class="text-muted">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Carregando relatórios...
                  </div>
                  <div v-else-if="productionReports.length === 0" class="text-muted">
                    Nenhum relatório de produção encontrado para este card.
                  </div>
                  <div v-else class="d-flex flex-column gap-3">
                    <div
                      v-for="report in productionReports"
                      :key="report.id"
                      class="card"
                    >
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                          <div class="flex-grow-1">
                            <h6 class="card-title mb-2 d-flex align-items-center">
                              <i class="fa fa-file-pdf me-2"></i>
                              Relatório #{{ report.id }}
                            </h6>
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                              <small class="text-muted">
                                {{ formatDate(report.action_date) }}
                              </small>
                              <span class="badge" :class="getReportBadgeClass(report.action_type)">
                                {{ getReportActionTypeLabel(report.action_type) }}
                              </span>
                            </div>
                            <div v-if="report.user" class="small d-flex align-items-center">
                              <i class="fa fa-user me-1"></i>
                              {{ report.user.name }}
                            </div>
                          </div>
                          <div class="flex-shrink-0">
                            <button
                              class="btn btn-sm btn-primary"
                              @click="downloadReportPdf(report.id)"
                              :disabled="downloadingReportId === report.id"
                              title="Baixar PDF"
                            >
                              <span v-if="downloadingReportId === report.id" class="spinner-border spinner-border-sm me-2" role="status"></span>
                              <i v-else class="fa fa-download me-2"></i>
                              {{ downloadingReportId === report.id ? 'Baixando...' : 'Baixar PDF' }}
                            </button>
                          </div>
                        </div>
                        <div v-if="report.column_name" class="border-top pt-3 small">
                          <span class="fw-semibold text-muted">Coluna:</span>
                          <span class="ms-2">{{ report.column_name }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Carregar Arte -->
                <div v-if="card.budget" class="layout-modal-section">
                  <div class="form-check mb-3">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      :id="`load-art-${card.id}`"
                      v-model="showLoadArtInput"
                    />
                    <label class="form-check-label" :for="`load-art-${card.id}`">
                      Carregar arte
                    </label>
                  </div>
                  <div v-if="showLoadArtInput" class="layout-modal-load-art">
                    <input
                      type="file"
                      :ref="el => artFileInput = el"
                      accept="image/*"
                      @change="handleArtFileChange"
                      class="form-control mb-3"
                      :disabled="uploadingArt"
                    />
                    <textarea
                     v-if="showLoadArtInput"
                      v-model="artComment"
                      class="form-control"
                      maxlength="500"
                      rows="3"
                      placeholder="Escreva um comentário para a arte..."
                    ></textarea>
                    <button
                      v-if="selectedArtFile"
                      type="button"
                      class="btn btn-primary btn-sm mt-2"
                      @click="uploadArt"
                      :disabled="uploadingArt"
                    >
                      <span v-if="uploadingArt" class="spinner-border spinner-border-sm me-2" role="status"></span>
                      <i v-else class="fa fa-upload me-2"></i>
                      {{ uploadingArt ? 'Enviando...' : 'Enviar Arte' }}
                    </button>
                  </div>
                </div>
              </div>

              <aside class="layout-modal-sidebar">
                <div class="layout-modal-sidebar-header">
                  <h3>Comentários e atividade</h3>
                  <button class="btn btn-primary" type="button" @click="toggleDetails">
                    {{ showDetails ? 'Ocultar Detalhes' : 'Mostrar Detalhes' }}
                  </button>
                </div>

                <!-- Caixa de texto para escrever comentários -->
                <div class="mb-3">
                  <div class="d-flex flex-column gap-2">
                    <textarea
                      v-model="newCommentText"
                      class="form-control"
                      maxlength="500"
                      rows="3"
                      placeholder="Escrever um comentário..."
                      @focus="isEditingComment = true"
                    ></textarea>
                    <div v-if="isEditingComment" class="d-flex justify-content-between align-items-center">
                      <span class="text-muted small">{{ newCommentText.length }}/500</span>
                      <div class="layout-modal-comment-input-actions">
                        <button class="btn btn-subtle" @click="cancelNewComment">
                          Cancelar
                        </button>
                        <button class="btn btn-primary" @click="saveNewComment" :disabled="isSavingComment || !newCommentText.trim()">
                          {{ isSavingComment ? 'Salvando...' : 'Salvar' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Lista de comentários e atividades (visível apenas quando showDetails é true) -->
                <div v-if="showDetails">
                  <!-- Lista de comentários -->
                  <div class="d-flex flex-column gap-3 mb-3">
                    <div
                      v-for="comment in cardComments"
                      :key="comment.id"
                      class="layout-modal-comment-item"
                    >
                      <div v-if="editingCommentId !== comment.id" class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center">
                          <div class="d-flex align-items-center gap-2">
                            <div class="layout-modal-comment-avatar" :style="{ backgroundColor: getAvatarColor(comment.user_name) }">
                              {{ getInitials(comment.user_name) }}
                            </div>
                            <span class="layout-modal-comment-author">{{ comment.user_name }}</span>
                          </div>
                          <span class="layout-modal-comment-date">{{ formatDate(comment.created_at) }}</span>
                        </div>
                        <div class="layout-modal-comment-text">{{ comment.comment }}</div>
                        <div class="layout-modal-comment-actions">
                          <button class="layout-modal-comment-action-btn" @click="startEditComment(comment)">
                            Editar
                          </button>
                          <span class="layout-modal-comment-action-separator">/</span>
                          <button class="layout-modal-comment-action-btn layout-modal-comment-delete" @click="deleteComment(comment.id)">
                            Excluir
                          </button>
                        </div>
                      </div>
                      <div v-else class="d-flex flex-column gap-2">
                        <textarea
                          v-model="editingCommentText"
                          class="form-control"
                          maxlength="500"
                          rows="3"
                        ></textarea>
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="layout-modal-comment-counter">{{ editingCommentText.length }}/500</span>
                          <div class="layout-modal-comment-input-actions">
                            <button class="btn btn-subtle" @click="cancelEditComment">
                              Cancelar
                            </button>
                            <button class="btn btn-primary" @click="saveEditComment(comment.id)" :disabled="isSavingComment || !editingCommentText.trim()">
                              {{ isSavingComment ? 'Salvando...' : 'Salvar' }}
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div v-if="cardComments.length === 0" class="layout-modal-info text-muted">
                      Nenhum comentário ainda.
                    </div>
                  </div>

                  <!-- Atividades e Histórico -->
                  <div class="layout-modal-activity">
                    <div v-if="activityItems.length > 0" class="d-flex flex-column gap-3">
                      <div
                        v-for="(activity, activityIndex) in activityItems"
                        :key="activity.id || activityIndex"
                        class="layout-modal-activity-item"
                        :class="{ 'is-history': activity.type === 'history' }"
                      >
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <div class="d-flex align-items-center gap-2">
                            <div v-if="activity.type !== 'history'" class="layout-modal-activity-avatar" :style="{ backgroundColor: getAvatarColor(getActivityUser(activity)) }">
                              {{ getInitials(getActivityUser(activity)) }}
                            </div>
                            <div v-else class="layout-modal-activity-icon">
                              <i class="fa fa-history"></i>
                            </div>
                            <span v-if="activity.type !== 'history'" class="layout-modal-activity-author">{{ getActivityUser(activity) }}</span>
                            <span v-else class="layout-modal-activity-author">Histórico</span>
                          </div>
                          <span class="layout-modal-activity-date">{{ formatDate(activity.created_at || activity.date) }}</span>
                        </div>
                        <div class="layout-modal-activity-content" v-html="getActivityText(activity)"></div>
                      </div>
                    </div>
                    <div v-else class="layout-modal-info text-muted">
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
  import axios from 'axios';
  import { getCardDisplayName } from '@/utils/cardUtils';
  import { getWallArea, calculateStrips, calculateStripHeight } from '@/utils/calculateStripsUtils.js';

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

  // Carregar arte
  const showLoadArtInput = ref(false);
  const selectedArtFile = ref(null);
  const artFileInput = ref(null);
  const uploadingArt = ref(false);
  const artComment = ref('');

  // Solicitações de arte
  const requestLayoutArts = ref([]);
  const loadingRequestArts = ref(false);

  // Relatórios de produção
  const productionReports = ref([]);
  const loadingProductionReports = ref(false);
  const downloadingReportId = ref(null);

  // Marcar como produzido
  const markingAsProduced = ref(false);

  // Editar porcentagem de produção
  const isEditingProductionPercentage = ref(false);
  const productionPercentageText = ref(0);
  const originalProductionPercentage = ref(0);
  const isSavingProductionPercentage = ref(false);

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

    // Adicionar histórico do card (filtrar apenas histórico de produção)
    if (Array.isArray(props.card.history) && props.card.history.length > 0) {
      props.card.history
        .filter((historyItem) => historyItem.type_page === 'product')
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
        type_page: 'product',
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

    function getDirection(continuation) {
        if (continuation.direction === 'left-to-right') {
            return 'Esquerda para direita';
        } else if (continuation.direction === 'right-to-left') {
            return 'Direita para esquerda';
        }

        return '';
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
    return auth.isAdmin();
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
        type_page: 'product',
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
        type_page: 'product',
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
        data: { type_page: 'product' }
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
        data: { type_page: 'product' }
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

  // Funções para carregar arte
  function handleArtFileChange(event) {
    const file = event.target.files?.[0];
    if (file) {
      selectedArtFile.value = file;
    }
  }

  async function uploadArt() {
    if (!selectedArtFile.value || !props.card?.id || !props.card?.budget?.user_id || !props.card?.budget?.id || !auth.user?.id) {
      return;
    }

    uploadingArt.value = true;

    try {
      const formData = new FormData();
      formData.append('art_file', selectedArtFile.value);
      formData.append('order_budget_id', props.card.id);
      formData.append('dealer_id', props.card.budget.user_id);
      formData.append('designer_id', auth.user.id);
      formData.append('budget_id', props.card.budget.id);
      formData.append('comment', artComment.value);
      const response = await axios.post('v1/budgets/order-budgets/upload-art', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      if (response.data?.success) {
        // Atualizar o card localmente
        if (props.card) {
          props.card.status = 'Pendente de Revisão';
          if (props.card.budget) {
            props.card.budget.status = 'Pendente de Revisão';
          }
        }

        // Limpar o formulário
        selectedArtFile.value = null;
        showLoadArtInput.value = false;
        artComment.value = '';
        if (artFileInput.value) {
          artFileInput.value.value = '';
        }

        // Recarregar lista de artes
        await fetchRequestLayoutArts();

        if (window.Toast) {
          window.Toast.fire({
            icon: 'success',
            title: response.data.message || 'Arte carregada com sucesso',
          });
        }
      }
    } catch (error) {
      console.error('Erro ao carregar arte:', error);
      const errorMessage = error.response?.data?.message || 'Erro ao carregar arte. Tente novamente.';

      if (window.Swal) {
        window.Swal.fire('Erro!', errorMessage, 'error');
      } else {
        alert(errorMessage);
      }
    } finally {
      uploadingArt.value = false;
    }
  }

  function resolveImageUrl(path) {
    if (!path) {
      return '';
    }
    if (/^https?:\/\//i.test(path)) {
      return path;
    }
    const baseUrl = window.location.origin.replace(/\/$/, '');
    return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
  }

  function handleImageError(event) {
    event.target.style.display = 'none';
  }

  async function fetchRequestLayoutArts() {
    if (!props.card?.id || !props.card?.budget?.id || !auth.user?.id) {
      requestLayoutArts.value = [];
      loadingRequestArts.value = false;
      return;
    }

    try {
      loadingRequestArts.value = true;

      const budgetId = props.card.budget.id;

      const params = {
        budget_id: budgetId,
        dealer_id: auth.user.id,
      };

      const response = await axios.get('v1/budgets/request-layout-arts', {
        params,
      });

      const data = response?.data || response;

      if (data?.success && Array.isArray(data.data)) {
        requestLayoutArts.value = data.data.map((art) => {
          let imageUrl = art.image_url;
          if (!imageUrl && art.path_file) {
            imageUrl = resolveImageUrl(art.path_file);
          }

          return {
            id: art.id,
            comment: art.comment || null,
            image_url: imageUrl,
            path_file: art.path_file || null,
            created_at: art.created_at || null,
            designer_name: art.designer?.name || art.designer_name || null,
            dealer_name: art.dealer?.name || art.dealer_name || null,
            wall_info: art.wall_info || null,
            wall_name: art.wall_name || art.wall_info?.wall_name || null,
          };
        });
      } else {
        requestLayoutArts.value = [];
      }
    } catch (error) {
      console.error('Erro ao buscar solicitações de artes:', error);
      requestLayoutArts.value = [];
    } finally {
      loadingRequestArts.value = false;
    }
  }

  async function fetchProductionReports() {
    if (!props.card?.id) {
      productionReports.value = [];
      loadingProductionReports.value = false;
      return;
    }

    try {
      loadingProductionReports.value = true;

      const response = await axios.get(`v1/orders/order-budgets/${props.card.id}/production-reports`);

      if (response.data?.success && Array.isArray(response.data.data)) {
        productionReports.value = response.data.data;
      } else {
        productionReports.value = [];
      }
    } catch (error) {
      console.error('Erro ao buscar relatórios de produção:', error);
      productionReports.value = [];
    } finally {
      loadingProductionReports.value = false;
    }
  }

  function getReportActionTypeLabel(actionType) {
    const labels = {
      'mark_as_produced': 'Marcado como Produzido',
      'production_percentage_100': 'Produção 100%',
    };
    return labels[actionType] || actionType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
  }

  function getReportBadgeClass(actionType) {
    const classes = {
      'mark_as_produced': 'bg-success',
      'production_percentage_100': 'bg-info',
    };
    return classes[actionType] || 'bg-secondary';
  }

  async function downloadReportPdf(reportId) {
    if (!reportId || downloadingReportId.value === reportId) {
      return;
    }

    downloadingReportId.value = reportId;

    try {
      const response = await axios.get(`v1/orders/production-reports/${reportId}/download-pdf`, {
        responseType: 'blob',
      });

      // Criar URL do blob e fazer download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `relatorio-producao-${reportId}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: 'PDF baixado com sucesso',
        });
      }
    } catch (error) {
      console.error('Erro ao baixar PDF do relatório:', error);
      const errorMessage = error.response?.data?.message || 'Erro ao baixar PDF. Tente novamente.';

      if (window.Swal) {
        window.Swal.fire('Erro!', errorMessage, 'error');
      } else {
        alert(errorMessage);
      }
    } finally {
      downloadingReportId.value = null;
    }
  }

  async function markAsProduced() {
    if (!props.card?.id || markingAsProduced.value) {
      return;
    }

    markingAsProduced.value = true;

    try {
      const response = await axios.post(`v1/orders/order-budgets/${props.card.id}/mark-as-produced`);

      // Atualizar o card localmente
      if (props.card && response.data?.data) {
        props.card.production_date = response.data.data.production_date;
        props.card.production_column_names_id = response.data.data.production_column_names_id;
      }

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.data.message || 'Data de produção atualizada com sucesso',
        });
      }
    } catch (error) {
      console.error('Erro ao marcar como produzido:', error);
      const errorMessage = error.response?.data?.message || 'Erro ao atualizar data de produção. Tente novamente.';

      if (window.Swal) {
        window.Swal.fire('Erro!', errorMessage, 'error');
      } else {
        alert(errorMessage);
      }
    } finally {
      markingAsProduced.value = false;
    }
  }

  function formatProductionPercentage(value) {
    if (value === null || value === undefined) {
      return '0.0';
    }
    const numericValue = Number(value);
    return Number.isFinite(numericValue) ? numericValue.toFixed(1) : '0.0';
  }

  function startEditingProductionPercentage() {
    originalProductionPercentage.value = props.card?.production_percentage || 0;
    productionPercentageText.value = originalProductionPercentage.value;
    isEditingProductionPercentage.value = true;
  }

  function cancelEditingProductionPercentage() {
    productionPercentageText.value = originalProductionPercentage.value;
    isEditingProductionPercentage.value = false;
  }

  async function saveProductionPercentage() {
    if (!props.card?.id || isSavingProductionPercentage.value) {
      return;
    }

    // Validar valor
    const percentage = Number(productionPercentageText.value);
    if (isNaN(percentage) || percentage < 0 || percentage > 100) {
      if (window.Swal) {
        window.Swal.fire('Erro!', 'Porcentagem deve estar entre 0 e 100', 'error');
      } else {
        alert('Porcentagem deve estar entre 0 e 100');
      }
      return;
    }

    const isFullyProduced = props.card.production_percentage === 100 || Number(props.card.production_percentage) === 100;
    const isChangingFrom100 = isFullyProduced && percentage !== 100;

    if (isChangingFrom100) {
      const result = await window.Swal.fire({
        icon: 'warning',
        title: 'Alterar porcentagem de produção?',
        text: 'Este card está 100% produzido. Você realmente deseja alterar a porcentagem de produção?',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, alterar',
        cancelButtonText: 'Cancelar',
      });

      if (!result.isConfirmed) {
        productionPercentageText.value = originalProductionPercentage.value;
        return;
      }
    }

    isSavingProductionPercentage.value = true;

    try {
      const response = await axios.put(`v1/orders/order-budgets/${props.card.id}/production-percentage`, {
        production_percentage: percentage,
      });

      // Atualizar o card localmente
      if (props.card && response.data?.data) {
        props.card.production_percentage = response.data.data.production_percentage;
      }

      originalProductionPercentage.value = percentage;
      isEditingProductionPercentage.value = false;

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.data.message || 'Porcentagem de produção atualizada com sucesso',
        });
      }
    } catch (error) {
      console.error('Erro ao atualizar porcentagem:', error);
      const errorMessage = error.response?.data?.message || 'Erro ao atualizar porcentagem de produção. Tente novamente.';

      if (window.Swal) {
        window.Swal.fire('Erro!', errorMessage, 'error');
      } else {
        alert(errorMessage);
      }
    } finally {
      isSavingProductionPercentage.value = false;
    }
  }

  // Inicializar descrição quando o card mudar
  watch(() => props.card, (newCard) => {
    if (newCard) {
      descriptionText.value = newCard.description || '';
      originalDescription.value = newCard.description || '';
      // Buscar requisições de arte quando o card mudar
      fetchRequestLayoutArts();
      // Buscar relatórios de produção quando o card mudar
      fetchProductionReports();
    }
    // Fechar menu de membro quando o card mudar
    showMemberMenu.value = false;
    selectedMember.value = null;
    // Resetar upload de arte
    showLoadArtInput.value = false;
    selectedArtFile.value = null;
    // Resetar edição de porcentagem
    isEditingProductionPercentage.value = false;
    productionPercentageText.value = newCard?.production_percentage || 0;
    originalProductionPercentage.value = newCard?.production_percentage || 0;
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

  // Estilos para porcentagem de produção
  .layout-modal-production-edit-btn {
    background: none;
    border: none;
    color: var(--bs-primary);
    font-size: 0.875rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    margin-left: 0.5rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s ease;

    &:hover {
      background-color: var(--bs-secondary-bg);
    }
  }

  .layout-modal-production-percentage-edit {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .layout-modal-production-input-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background-color: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 0.5rem;
    transition: border-color 0.2s ease;

    &:focus-within {
      border-color: var(--bs-primary);
    }
  }

  .layout-modal-production-label {
    font-size: 0.875rem;
    color: var(--bs-body-color);
  }

  .layout-modal-production-input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 0.875rem;
    color: var(--bs-body-color);
    outline: none;
    max-width: 80px;

    &::-webkit-inner-spin-button,
    &::-webkit-outer-spin-button {
      -webkit-appearance: none;
      appearance: none;
      margin: 0;
    }

    &[type=number] {
      -moz-appearance: textfield;
      appearance: textfield;
    }
  }

  .layout-modal-production-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
  }

  .layout-modal-production-cancel,
  .layout-modal-production-save {
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
  }

  .layout-modal-production-cancel {
    background-color: var(--bs-secondary-bg);
    color: var(--bs-body-color);

    &:hover {
      background-color: var(--bs-tertiary-bg);
    }
  }

  .layout-modal-production-save {
    background-color: var(--bs-primary);
    color: var(--bs-white);

    &:hover:not(:disabled) {
      background-color: var(--bs-primary-emphasis);
    }

    &:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
  }
</style>

