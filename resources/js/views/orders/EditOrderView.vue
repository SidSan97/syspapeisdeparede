<template>
    <section class="content">
        <Page title="Editar Pedido" back-to="/pedidos">
            <template #actions>
                <button class="btn btn-primary me-3" type="button" @click="updateBudget" :disabled="saving">
                    {{ saving ? 'Salvando...' : 'Salvar Pedido' }}
                </button>
            </template>
        <div v-if="loading" class="text-center text-muted py-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>

        <div v-else class="container py-4">
            <div class="row">
                <div class="col-12 col-lg-8">
                    <p class="ms-1"><strong>Revendedor: </strong> {{ budget.reseller_name }}</p>

                    <!-- Seção: Informações Básicas -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="budgetName" class="form-label">Nome do Orçamento</label>
                                <input
                                    v-model="budget.name"
                                    type="text"
                                    id="budgetName"
                                    class="form-control"
                                    placeholder="Ex: Orçamento Casa - Sala"
                                />
                            </div>
                            <div class="mb-3">
                                <label for="budgetStatus" class="form-label">Status</label>
                                <select
                                    v-model="budget.status"
                                    id="budgetStatus"
                                    class="form-control"
                                >
                                    <option :value="null">Sem status</option>
                                    <option value="Em aberto">Em aberto</option>
                                    <option value="Aprovado">Aprovado</option>
                                    <option value="Em produção">Em produção</option>
                                    <option value="Enviado">Enviado</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>

                            <!-- Checkbox Dropshipping (para admin, revendedor ou is_dropshipping === 1) -->
                            <div v-if="canEnableDropshipping" class="mb-3">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        v-model="enableDropshipping"
                                        id="enableDropshipping"
                                    />
                                    <label class="form-check-label" for="enableDropshipping">
                                        Habilitar Dropshipping
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulário de Dropshipping -->
                    <DropshippingForm
                        :enabled="enableDropshipping"
                        v-model="dropshippingData"
                        ref="dropshippingFormRef"
                    />

                    <!-- Seção: Ambientes e Paredes -->
                    <div class="card mb-4">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0 fw-semibold">Cômodos</h5>
                        </div>
                        <div class="card-body">
                                <div v-if="loadingBudget" class="text-center text-muted py-4">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                </div>
                                <template v-else>
                                <div v-for="(room, roomIndex) in budget.rooms" :key="roomIndex" class="card mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>{{ room.name || `Ambiente ${roomIndex + 1}` }}</strong>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            @click="removeRoom(roomIndex)"
                                            v-if="budget.rooms.length > 1"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label :for="`room-name-${roomIndex}`" class="form-label">Nome do Ambiente</label>
                                            <input
                                                v-model="room.name"
                                                type="text"
                                                :id="`room-name-${roomIndex}`"
                                                class="form-control"
                                                placeholder="Ex: Quarto Alice"
                                            />
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Paredes</label>
                                            <div v-for="(wall, wallIndex) in room.walls" :key="wallIndex" class="card mb-3 border">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <strong>Parede {{ wallIndex + 1 }}</strong>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-danger"
                                                            @click="removeWall(roomIndex, wallIndex)"
                                                            v-if="room.walls.length > 1"
                                                        >
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label :for="`wall-name-${roomIndex}-${wallIndex}`" class="form-label">Nome da Parede</label>
                                                        <input
                                                            v-model="wall.name"
                                                            type="text"
                                                            :id="`wall-name-${roomIndex}-${wallIndex}`"
                                                            class="form-control"
                                                            placeholder="Ex: Parede janela"
                                                        />
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label :for="`wall-width-${roomIndex}-${wallIndex}`" class="form-label">Largura (m)</label>
                                                            <input
                                                                v-model.number="wall.width"
                                                                type="number"
                                                                step="0.01"
                                                                :id="`wall-width-${roomIndex}-${wallIndex}`"
                                                                class="form-control"
                                                                placeholder="0.00"
                                                            />
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label :for="`wall-height-${roomIndex}-${wallIndex}`" class="form-label">Altura (m)</label>
                                                            <input
                                                                v-model.number="wall.height"
                                                                type="number"
                                                                step="0.01"
                                                                :id="`wall-height-${roomIndex}-${wallIndex}`"
                                                                class="form-control"
                                                                placeholder="0.00"
                                                            />
                                                        </div>
                                                    </div>

                                                    <!-- Continuations -->
                                                    <div class="mb-3">
                                                        <template v-if="wall.width && wall.height">
                                                            <div class="form-check form-switch">
                                                            <input
                                                                class="form-check-input"
                                                                type="checkbox"
                                                                    :id="`wall-continue-same-art-${roomIndex}-${wallIndex}`"
                                                                    v-model="wall.continueSameArt"
                                                                    @change="handleContinuationToggle(roomIndex, wallIndex)"
                                                            />
                                                                <label class="form-check-label" :for="`wall-continue-same-art-${roomIndex}-${wallIndex}`">
                                                                    Continuar esta parede com a mesma arte?
                                                            </label>
                                                        </div>
                                                        </template>
                                                        <template v-else>
                                                            <div class="alert alert-light border text-muted py-2 mb-0">
                                                                Informe a largura e a altura para habilitar continuações com a mesma arte.
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <div
                                                        v-if="wall.continueSameArt && wall.width && wall.height"
                                                        class="mb-3 border-start border-primary ps-3 ms-2"
                                                    >
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h6 class="mb-0">Continuações da parede</h6>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-secondary"
                                                                @click="addContinuation(roomIndex, wallIndex)"
                                                            >
                                                                <i class="fa fa-plus"></i>
                                                                Adicionar continuação
                                                            </button>
                                                        </div>
                                                        <small class="text-muted d-block mb-3">
                                                            Use estas continuações para representar trechos adicionais com a mesma arte.
                                                        </small>

                                                        <div v-if="!wall.continuations.length" class="alert alert-info py-2">
                                                            Nenhuma continuação adicionada. Clique em "Adicionar continuação".
                                                        </div>

                                                        <div
                                                            v-for="(continuation, continuationIndex) in wall.continuations"
                                                            :key="`continuation-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                                            class="card border border-primary-subtle mb-3"
                                                        >
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                                    <h6 class="mb-0">
                                                                        Continuação {{ continuationIndex + 1 }}
                                                                        <span v-if="continuation.name">- {{ continuation.name }}</span>
                                                                    </h6>
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-sm btn-outline-danger"
                                                                        @click="removeContinuation(roomIndex, wallIndex, continuationIndex)"
                                                                    >
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                </div>

                                                        <div class="mb-3">
                                                                    <label :for="`continuation-name-${roomIndex}-${wallIndex}-${continuationIndex}`" class="form-label">
                                                                        Nome da continuação
                                                                    </label>
                                                                <input
                                                                    v-model="continuation.name"
                                                                    type="text"
                                                                    :id="`continuation-name-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                                                    class="form-control"
                                                                    placeholder="Ex: Armário"
                                                                />
                                                        </div>

                                                        <div class="mb-3">
                                                                    <label :for="`continuation-direction-${roomIndex}-${wallIndex}-${continuationIndex}`" class="form-label">
                                                                        Direção
                                                                    </label>
                                                            <select
                                                                        v-model="continuation.direction"
                                                                        :id="`continuation-direction-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                                                class="form-control"
                                                            >
                                                                <option value="">Selecione a direção</option>
                                                                <option value="left-to-right">Da esquerda para direita</option>
                                                                <option value="right-to-left">Da direita para esquerda</option>
                                                            </select>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                        <label :for="`continuation-width-${roomIndex}-${wallIndex}-${continuationIndex}`" class="form-label">
                                                                            Largura (m)
                                                                        </label>
                                                                <input
                                                                            v-model.number="continuation.width"
                                                                    type="number"
                                                                    step="0.01"
                                                                            :id="`continuation-width-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                                                    class="form-control"
                                                                    placeholder="0.00"
                                                                />
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                        <label :for="`continuation-height-${roomIndex}-${wallIndex}-${continuationIndex}`" class="form-label">
                                                                            Altura (m)
                                                                        </label>
                                                                <input
                                                                            v-model.number="continuation.height"
                                                                    type="number"
                                                                    step="0.01"
                                                                            :id="`continuation-height-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                                                    class="form-control"
                                                                    placeholder="0.00"
                                                                />
                                                            </div>
                                                        </div>

                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Wall Calculation -->
                                                    <div class="alert alert-success" v-if="getWallArea(wall) > 0">
                                                        <strong>Metro:</strong> {{ getWallArea(wall).toFixed(2) }} m
                                                        <br>
                                                        <strong>Quantidade de faixas:</strong> {{ calculateStrips(wall) }}
                                                        <br>
                                                        <strong>Tamanho da faixa:</strong> {{ formatStripHeight(wall) }} m
                                                    </div>

                                                    <p
                                                        v-if="formatStripHeight(wall) > 6"
                                                        class="mb-0 text-danger small"
                                                    >
                                                        Obs.:<br />
                                                        Faixas maiores que 6 metros são vendidas apenas em pares.
                                                    </p>

                                                    <div class="mt-4">
                                                        <h6 class="mb-3">Definir modelo da parede</h6>
                                                        <div v-if="modelsLoading" class="text-center text-muted py-3">
                                                            Carregando modelos...
                                                        </div>
                                                        <div v-else-if="modelsError" class="alert alert-danger" role="alert">
                                                            {{ modelsError }}
                                                        </div>
                                                        <div v-else-if="!productModels.length" class="alert alert-warning" role="alert">
                                                            Nenhum modelo disponível. Tente novamente mais tarde.
                                                        </div>
                                                        <div v-else class="row">
                                                            <div class="col-lg-4 col-md-6 mb-3" v-for="model in productModels" :key="model.id">
                                                                <div
                                                                    class="card h-100 "
                                                                    :class="{ 'border-primary': wall.model === model.id }"
                                                                    @click="wall.model = model.id"
                                                                    style="cursor: pointer;"
                                                                >
                                                                    <div class="card-body d-flex flex-column">
                                                                        <div class="mb-2">
                                                                            <strong>{{ model.displayName }}</strong>
                                                                        </div>
                                                                        <div class="small text-muted">
                                                                            <div><strong>Valor:</strong> R$ {{ model.value.toFixed(2) }}</div>
                                                                            <div><strong>Prazo:</strong> {{ model.deadline }} dia(s)</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <BudgetModelRequeriments
                                                            v-if="wall.model"
                                                            :wall="wall"
                                                            :model="getModelById(wall.model)"
                                                            :disabled="saving"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-outline-primary"
                                            @click="addWall(roomIndex)"
                                        >
                                            <i class="fa fa-plus fa-fw"></i> Adicionar Parede
                                        </button>
                                    </div>
                                </div>

                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="addRoom"
                            >
                                <i class="fa fa-plus fa-fw"></i> Adicionar Ambiente
                            </button>
                                </template>
                        </div>
                    </div>

                    <!-- Solicitação de Artes -->
                    <div class="card mb-4">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0 fw-semibold">
                                <i class="fa fa-paint-brush me-2"></i>
                                Solicitação de Artes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div v-if="loadingRequestArts" class="text-center text-muted py-3">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Carregando solicitações de artes...
                            </div>
                            <div v-else-if="requestLayoutArts.length === 0" class="text-center text-muted py-3">
                                Nenhuma solicitação de arte encontrada.
                            </div>
                            <div v-else class="accordion" id="requestArtsAccordion">
                                <div
                                    v-for="(interaction, interactionIndex) in requestLayoutArts"
                                    :key="interaction.id || interactionIndex"
                                    class="accordion-item mb-3"
                                >
                                    <h2 class="accordion-header">
                                        <button
                                            class="accordion-button p-2"
                                            :class="{ collapsed: interactionIndex !== 0 }"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            :data-bs-target="`#interaction-${interactionIndex}`"
                                            :aria-expanded="interactionIndex === 0"
                                            :aria-controls="`interaction-${interactionIndex}`"
                                        >
                                            <i class="fa fa-comments me-2"></i>
                                            Interação #{{ interaction.id }}
                                            <span v-if="interaction.wall_info?.wall_name" class="badge bg-info ms-2">
                                                {{ interaction.wall_info.wall_name }}
                                            </span>
                                            <span class="badge bg-secondary ms-2">
                                                {{ interaction.arts_count }} arte(s)
                                            </span>
                                        </button>
                                    </h2>
                                    <div
                                        :id="`interaction-${interactionIndex}`"
                                        class="accordion-collapse collapse"
                                        :class="{ show: interactionIndex === 0 }"
                                        data-bs-parent="#requestArtsAccordion"
                                    >
                                        <div class="accordion-body">
                                            <div v-if="interaction.wall_info" class="mb-3 p-2 rounded border">
                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <div class="text-muted small">Ambiente</div>
                                                        <div class="fw-semibold">{{ interaction.wall_info.room_name || 'N/A' }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="text-muted small">Parede</div>
                                                        <div class="fw-semibold">{{ interaction.wall_info.wall_name || 'N/A' }}</div>
                                                    </div>
                                                    <div v-if="interaction.wall_info.width" class="col-md-4">
                                                        <div class="text-muted small">Largura</div>
                                                        <div class="fw-semibold">{{ formatNumber(interaction.wall_info.width) }} m</div>
                                                    </div>
                                                    <div v-if="interaction.wall_info.height" class="col-md-4">
                                                        <div class="text-muted small">Altura</div>
                                                        <div class="fw-semibold">{{ formatNumber(interaction.wall_info.height) }} m</div>
                                                    </div>
                                                    <div v-if="interaction.wall_info.total_area" class="col-md-4">
                                                        <div class="text-muted small">Área</div>
                                                        <div class="fw-semibold">{{ formatNumber(interaction.wall_info.total_area) }} m²</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="interaction.created_at" class="mb-3 p-2 border rounded">
                                                <div class="text-muted small">
                                                    <i class="fa fa-calendar me-1"></i>
                                                    Interação criada em: {{ formatDate(interaction.created_at) }}
                                                </div>
                                            </div>

                                            <!-- Lista de Artes da Interação -->
                                            <div v-if="interaction.arts && interaction.arts.length > 0" class="mt-3">
                                                <h6 class="mb-3">
                                                    <i class="fa fa-images me-2"></i>
                                                    Artes ({{ interaction.arts.length }})
                                                </h6>
                                                <div
                                                    v-for="(art, artIndex) in interaction.arts"
                                                    :key="art.id || artIndex"
                                                    class="card mb-3 border"
                                                    :class="{ 'border-top': artIndex > 0 }"
                                                >
                                                    <div class="card-body">
                                                        <div class="mb-3 p-2 border rounded">
                                                            <div class="row g-2">
                                                                <div v-if="art.dealer_name" class="col-md-6">
                                                                    <div class="text-muted small">
                                                                        <i class="fa fa-user-tie me-1"></i>
                                                                        Revendedor
                                                                    </div>
                                                                    <div class="fw-semibold">{{ art.dealer_name }}</div>
                                                                </div>
                                                                <div v-if="art.designer_name" class="col-md-6">
                                                                    <div class="text-muted small">
                                                                        <i class="fa fa-user me-1"></i>
                                                                        Designer
                                                                    </div>
                                                                    <div class="fw-semibold">{{ art.designer_name }}</div>
                                                                </div>
                                                                <div v-if="art.created_at" class="col-12">
                                                                    <div class="text-muted small">
                                                                        <i class="fa fa-calendar me-1"></i>
                                                                        Enviado em: {{ formatDate(art.created_at) }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div v-if="art.comment" class="mb-3">
                                                            <div class="text-muted small mb-1">Comentário</div>
                                                            <div class="p-2 rounded border">{{ art.comment }}</div>
                                                        </div>
                                                        <div v-if="art.image_url" class="mb-3">
                                                            <div class="text-muted small mb-2">Imagem da Arte</div>
                                                            <div class="d-flex justify-content-center">
                                                                <img
                                                                    :src="art.image_url"
                                                                    :alt="`Arte ${art.id}`"
                                                                    class="img-thumbnail"
                                                                    style="max-width: 100%; max-height: 400px; object-fit: contain;"
                                                                    @error="handleImageError"
                                                                />
                                                            </div>
                                                            <div class="mt-2 text-center">
                                                                <a
                                                                    :href="art.image_url"
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    class="btn btn-sm btn-outline-primary"
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: Frete e Pagamento -->
                <div class="col-12 col-lg-4">
                    <!-- Seção: Frete -->
                    <div class="card mb-4 mt-2">
                        <div class="card-body">
                            <h5 class="card-title">Frete</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="cep" class="form-label">CEP</label>
                                        <input
                                            v-model="budget.cep"
                                            type="text"
                                            id="cep"
                                            class="form-control"
                                            placeholder="00000-000"
                                            maxlength="9"
                                            @input="formatCEP"
                                        />
                                    </div>
                                    <div class="col-md-6 mb-3 d-flex align-items-end">
                                        <button
                                            type="button"
                                            class="btn btn-primary"
                                            @click="calculateFreight"
                                            :disabled="!budget.cep || calculatingFreight"
                                        >
                                            <span v-if="calculatingFreight" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            {{ calculatingFreight ? 'Calculando...' : 'Calcular Frete' }}
                                        </button>
                                    </div>
                                </div>

                                <div v-if="budget.carriers && budget.carriers.length > 0" class="mt-4">
                                    <label class="form-label">Transportadora</label>
                                    <div class="list-group">
                                        <div
                                            v-for="(carrier, index) in budget.carriers"
                                            :key="index"
                                            :class="{ 'active': budget.selectedCarrier === index }"
                                            @click="budget.selectedCarrier = index"
                                            style="cursor: pointer;"
                                        >
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ carrier.name }}</strong>
                                                    <br>
                                                    <small class="text-muted">Prazo: {{ carrier.deliveryTime }} dias</small>
                                                </div>
                                                <div class="text-end">
                                                    <strong class="text-primary">R$ {{ carrier.price.toFixed(2) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>

                    <!-- Seção: Pagamento -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Pagamento</h5>
                            <div class="mb-3">
                                <label for="freightValue" class="form-label">Valor do frete</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input
                                        type="text"
                                        id="freightValue"
                                        class="form-control"
                                        :value="budget.selectedCarrier !== null ? budget.carriers[budget.selectedCarrier]?.price.toFixed(2).replace('.', ',') : '0,00'"
                                        readonly
                                    />
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <label class="form-label">Selecione a forma de pagamento</label>
                                <div class="row g-2">
                                    <div class="col-12 mb-2">
                                        <div class="card h-100" :class="{ 'border-primary': budget.paymentMethod === 'pix' }" @click="budget.paymentMethod = 'pix'" style="cursor: pointer;">
                                            <div class="card-body text-center py-3">
                                                <i class="fa fa-qrcode fa-2x mb-2"></i>
                                                <h6 class="mb-1">À Vista (PIX)</h6>
                                                <p class="text-muted small mb-0">Pagamento à vista com desconto</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12" v-if="budget.installmentLimit > 1">
                                        <div class="card h-100" :class="{ 'border-primary': budget.paymentMethod === 'credit_card' }" @click="budget.paymentMethod = 'credit_card'" style="cursor: pointer;">
                                            <div class="card-body text-center py-3">
                                                <i class="fa fa-credit-card fa-2x mb-2"></i>
                                                <h6 class="mb-1">A Prazo (Cartão ou Boleto)</h6>
                                                <p class="text-muted small mb-0">Parcelado em até {{ budget.installmentLimit }}x</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="budget.paymentMethod === 'credit_card'" class="mt-3">
                                    <label for="installments" class="form-label">Número de Parcelas</label>
                                    <select v-model.number="budget.installments" id="installments" class="form-control">
                                        <option v-for="n in budget.installmentLimit" :key="n" :value="n">{{ n }}x</option>
                                    </select>
                                </div>

                                <br> 

                                <span class="budget-attention-info">
                                    ATENÇÃO! OS PREÇOS DO
                                    ORÇAMENTO OU PEDIDOS NÃO PAGOS SERÃO MANTIDOS POR 30 DIAS CORRIDOS, APÓS ESSE
                                    PERÍODO OS VALORES PODEM SOFRER REAJUSTES AUTOMÁTICOS.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Resumo -->
                    <ResumeProductCard
                      :total-rooms="budget.rooms.length"
                      :total-walls="totalWalls"
                      :total-area="totalArea.toFixed(2)"
                      :freight="budget.selectedCarrier !== null
                        ? `R$ ${budget.carriers[budget.selectedCarrier]?.price.toFixed(2)}`
                        : ''"
                      :artwork-days="artworkDays"
                      :transport-days="transportDays"
                      :delivery-time="`${calculateDeliveryTime(budget)} dias`"
                      :total-vista="`R$ ${totalBudgetVista.toFixed(2)}`"
                      :total-prazo="`R$ ${totalBudgetPrazo.toFixed(2)}`"
                    />
                </div>
            </div>
        </div>
      </Page>
    </section>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Page from '@/components/page/Page.vue';
import DropshippingForm from '@/modules/budgets/components/DropshippingForm.vue';
import { useAuthStore } from '@/stores/auth';
import ResumeProductCard from '@/components/resume-product-card/ResumeProductCard.vue';
import BudgetModelRequeriments from '@/components/budget/BudgetModelRequeriments.vue';
import { sumArtworkDays } from '@/utils/artWorkDaysSum';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const tinyErpProducts = ref([]);
const loading = ref(false);

const PRECO_VISTA = ref(0);
const PRECO_PRAZO = ref(0);

const saving = ref(false);
const budgetId = ref(null);
const orderId = ref(null);
const loadingBudget = ref(false);
const loadingOrder = ref(false);
const originalBudget = ref(null);
const enableDropshipping = ref(false);
const dropshippingData = ref({});
const dropshippingFormRef = ref(null);

// Modelos de produto disponíveis
const productModels = ref([]);
const modelsLoading = ref(false);
const modelsError = ref(null);
const modelSlides = reactive({});

const STRIP_WIDTH = 0.6;
const STRIP_HEIGHT_OPTIONS = [
    1.0, 1.2, 1.5, 1.7, 2.0, 2.2, 2.5, 2.7, 3.0, 3.2, 3.3, 3.4, 3.5, 3.6,
    3.7, 3.8, 3.9, 4.0, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5.0,
    5.1, 5.2, 5.3, 5.4, 5.5, 6.0, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8,
    6.9, 7.0, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 8.0
];
const calculatingFreight = ref(false);

// Solicitações de arte
const requestLayoutArts = ref([]);
const loadingRequestArts = ref(false);

const createDefaultContinuation = () => ({
                        name: '',
                        direction: '',
                        width: null,
                        height: null,
                        sameArt: false
});

const createDefaultWall = () => ({
    id: null,
    name: '',
    width: null,
    height: null,
    model: null,
    continueSameArt: false,
    continuations: [],
    comment_referring_model: '',
    link_referring_model: '',
    files_referring_model: [],
    collection_referring_model: ''
});

const showWarning = (message) =>
    window.Swal.fire({
        title: 'Atenção',
        text: message,
        icon: 'warning'
    });

const extractItemsFromResponse = (payload) => {
    if (!payload) {
        return { items: [], meta: {} };
    }

    if (Array.isArray(payload)) {
        return { items: payload, meta: {} };
    }

    const resourceItems = payload.items?.data ?? payload.items ?? [];
    const meta =
        payload.meta ??
        payload.items?.meta ?? {
            current_page: payload.items?.current_page ?? 1,
            per_page: payload.items?.per_page ?? resourceItems.length,
            total: payload.items?.total ?? resourceItems.length,
            last_page: payload.items?.last_page ?? 1
        };

    return {
        items: resourceItems,
        meta
    };
};

const normalizeCollectionModel = (model = {}) => {
    const files = Array.isArray(model.files)
        ? model.files.map((file) => ({
              id: file.id ?? null,
              name: file.name ?? file.fileName ?? file.file_name ?? 'Arquivo',
              url: file.url ?? file.fileUrl ?? null
          }))
        : [];

    const name = (model.name ?? '').toString().trim();
    const typeName =
        model.type?.name ??
        model.type_name ??
        model.typeModelName ??
        null;

    const displayName = name.length > 0
        ? name
        : model.comment?.trim()
            ? model.comment.trim()
            : `Modelo ${model.id}`;

    return {
        id: Number(model.id),
        displayName,
        typeName,
        value: Number(model.value ?? 0),
        deadline: Number(model.deadline ?? 0),
        requests: {
            link: Boolean(model?.requests?.link),
            comment: Boolean(model?.requests?.comment),
            file: Boolean(model?.requests?.file),
            collection: Boolean(
                model?.requests?.collection ??
                model?.request_collection ??
                model?.requestCollection
            )
        },
        link: model.link ?? '',
        comment: model.comment ?? '',
        files
    };
};

const budget = reactive({
    id: null,
    name: '',
    status: '',
    rooms: [
        {
            name: '',
            walls: [createDefaultWall()]
        }
    ],
    deliveryTime: 0,
    cep: '',
    carriers: [],
    selectedCarrier: null,
    paymentMethod: '',
    installmentLimit: 12,
    installments: 1,
    total_amount: 0,
    total_amount_installments: 0,
    dropshipping_budget: 0,
    dropshipping_data: null
});

const canEnableDropshipping = computed(() => {
    return auth.hasRole(['admin', 'reseller']) ||
           auth.user?.is_dropshipping === 1;
});

async function searchTinyErpProducts() {
    try {
        loading.value = true;

        const { data } = await axios.get('v1/tiny-erp/all');
        tinyErpProducts.value = data;

        PRECO_VISTA.value = tinyErpProducts.value.precoPromocionalVista;
        PRECO_PRAZO.value = tinyErpProducts.value.precoPromocionalPrazo;
    } catch (error) {
        console.error('Erro ao buscar produtos:', error);
        window.Swal.fire({
            title: 'Erro ao buscar produtos!',
            text: 'Não foi possível buscar os produtos do Tiny ERP. Tente novamente mais tarde.',
            confirmButtonText: 'Entendi!',
        });
        tinyErpProducts.value = [];
    } finally {
        loading.value = false;
    }
}

async function fetchCollectionModels() {
    modelsLoading.value = true;
    modelsError.value = null;

    try {
        const { data } = await axios.get('v1/collection-models');

        const payload = data?.data;
        const { items } = extractItemsFromResponse(payload);

    const normalized = items.map(normalizeCollectionModel);
        const availableIds = new Set(normalized.map((item) => item.id));

        productModels.value = normalized;

    normalized.forEach((model) => {
        if (typeof modelSlides[model.id] !== 'number' || Number.isNaN(modelSlides[model.id])) {
            modelSlides[model.id] = 0;
        } else {
            const maxIndex = Math.max(0, model.files.length - 1);
            modelSlides[model.id] = Math.min(Math.max(modelSlides[model.id], 0), maxIndex);
        }
    });

    // Remove slide states for removed models
    Object.keys(modelSlides).forEach((id) => {
        const numericId = Number(id);
        if (!availableIds.has(numericId)) {
            delete modelSlides[id];
        }
    });

        budget.rooms.forEach((room) => {
            room.walls.forEach((wall) => {
                if (wall.model && !availableIds.has(wall.model)) {
                    wall.model = null;
                }
            });
        });
    } catch (error) {
        modelsError.value =
            error?.response?.data?.message ||
            'Não foi possível carregar os modelos. Tente novamente.';
    } finally {
        modelsLoading.value = false;
    }
}

const productModelsMap = computed(() => {
    const map = new Map();
    productModels.value.forEach((model) => {
        map.set(model.id, model);
    });
    return map;
});

const getModelById = (id) => productModelsMap.value.get(id);

const artworkDays = computed(() => sumArtworkDays(budget, getModelById));

const transportDays = computed(() => {
    if (
        budget.selectedCarrier !== null &&
        Array.isArray(budget.carriers) &&
        budget.carriers[budget.selectedCarrier]
    ) {
        return Math.max(
            0,
            Number(budget.carriers[budget.selectedCarrier]?.deliveryTime ?? 0)
        );
    }

    return '';
});

// Função auxiliar para normalizar valores para comparação
function normalizeForComparison(value) {
    if (value === null || value === undefined) return null;
    if (typeof value === 'string') return value.trim();
    if (typeof value === 'number') return value;
    if (Array.isArray(value)) {
        return value.map(item => normalizeForComparison(item));
    }
    if (typeof value === 'object') {
        const normalized = {};
        for (const key in value) {
            normalized[key] = normalizeForComparison(value[key]);
        }
        return normalized;
    }
    return value;
}

const hasChanges = computed(() => {
    if (!originalBudget.value) return false;

    // Criar objetos com a mesma estrutura para comparação
    const original = {
        name: originalBudget.value.name || '',
        status: originalBudget.value.status || '',
        rooms: originalBudget.value.rooms || [],
        cep: originalBudget.value.cep || '',
        selectedCarrier: originalBudget.value.selectedCarrier,
        paymentMethod: originalBudget.value.paymentMethod || '',
        installments: originalBudget.value.installments || 1
    };

    const current = {
        name: budget.name || '',
        status: budget.status || '',
        rooms: budget.rooms || [],
        cep: budget.cep || '',
        selectedCarrier: budget.selectedCarrier,
        paymentMethod: budget.paymentMethod || '',
        installments: budget.installments || 1
    };

    // Normalizar antes de comparar
    const normalizedOriginal = normalizeForComparison(original);
    const normalizedCurrent = normalizeForComparison(current);

    // Comparar usando JSON.stringify
    return JSON.stringify(normalizedOriginal) !== JSON.stringify(normalizedCurrent);
});

// Normalizar dados do Order da API
function normalizeOrderFromAPI(orderData) {
    const rooms = [];

    if (orderData.rooms && Array.isArray(orderData.rooms)) {
        orderData.rooms.forEach((room) => {
            const walls = [];

            if (room.walls && Array.isArray(room.walls)) {
                room.walls.forEach((wall) => {
                    const wallData = {
                        id: wall.id ?? null,
                        name: wall.name || '',
                        width: wall.width ? Number(wall.width) : null,
                        height: wall.height ? Number(wall.height) : null,
                        model: (wall.collection_model_id || wall.collection_model?.id) ? Number(wall.collection_model_id || wall.collection_model?.id) : null,
                        continueSameArt: false,
                        continuations: [],
                        comment_referring_model: wall.comment_referring_model ?? '',
                        link_referring_model: wall.link_referring_model ?? '',
                        files_referring_model: Array.isArray(wall.files_referring_model) ? wall.files_referring_model : [],
                        collection_referring_model: wall.collection_referring_model ?? ''
                    };

                    // Processar continuações se existirem
                    if (wall.continue_same_art || (wall.continuations && Array.isArray(wall.continuations) && wall.continuations.length > 0)) {
                        wallData.continueSameArt = Boolean(wall.continue_same_art);
                        if (wall.continuations && Array.isArray(wall.continuations)) {
                            wallData.continuations = wall.continuations.map(cont => ({
                                name: cont.name || '',
                                direction: cont.direction || '',
                                width: cont.width ? Number(cont.width) : null,
                                height: cont.height ? Number(cont.height) : null,
                                sameArt: Boolean(cont.sameArt ?? false)
                            }));
                        }
                    }

                    walls.push(wallData);
                });
            }

            rooms.push({
                name: room.name || '',
                walls: walls.length > 0 ? walls : [createDefaultWall()]
            });
        });
    }

    let carriers = Array.isArray(orderData.carriers_snapshot)
        ? orderData.carriers_snapshot
        : [];

    let selectedCarrierIndex = null;

    // Se há frete selecionado mas não há lista de transportadoras, recriar a lista
    if (orderData.selected_carrier_name && orderData.selected_carrier_price !== null && orderData.selected_carrier_price !== undefined) {
        if (carriers.length === 0) {
            // Recriar lista de transportadoras com base no frete selecionado
            carriers = [
                {
                    name: orderData.selected_carrier_name,
                    price: Number(orderData.selected_carrier_price) || 0,
                    deliveryTime: Number(orderData.selected_carrier_delivery_time) || 0
                }
            ];
            selectedCarrierIndex = 0;
        } else {
            // Buscar o índice da transportadora selecionada
            selectedCarrierIndex = carriers.findIndex(c => c.name === orderData.selected_carrier_name);
            if (selectedCarrierIndex < 0) {
                // Se não encontrou, adicionar a transportadora selecionada à lista
                carriers.push({
                    name: orderData.selected_carrier_name,
                    price: Number(orderData.selected_carrier_price) || 0,
                    deliveryTime: Number(orderData.selected_carrier_delivery_time) || 0
                });
                selectedCarrierIndex = carriers.length - 1;
            }
        }
    }

    const normalizedPaymentMethod = orderData.payment_method === 'installment'
        ? 'credit_card'
        : orderData.payment_method || '';

    return {
        id: orderData.id,
        name: orderData.name || '',
        status: orderData.status || '',
        rooms: rooms.length > 0 ? rooms : [{ name: '', walls: [createDefaultWall()] }],
        cep: orderData.cep || '',
        carriers: carriers,
        selectedCarrier: selectedCarrierIndex,
        paymentMethod: normalizedPaymentMethod,
        installmentLimit: orderData.installment_limit || 12,
        installments: orderData.installments || 1,
        total_amount: orderData.total_amount ? Number(orderData.total_amount) : 0,
        total_amount_installments: orderData.total_amount_installments ? Number(orderData.total_amount_installments) : 0,
        dropshipping_budget: orderData.dropshipping_budget || 0,
        dropshipping_data: orderData.dropshipping_data || null,
        reseller_name: orderData.reseller_name ?? null
    };
}

async function loadOrder() {
    const id = route.params.id;
    if (!id) {
        window.Swal.fire({
            title: 'Erro!',
            text: 'ID do pedido não encontrado',
            icon: 'error',
            confirmButtonText: 'Entendi!',
        });
        router.push('/pedidos');
        return;
    }

    orderId.value = Number(id);
    budgetId.value = Number(id);
    loadingOrder.value = true;
    loadingBudget.value = true;

    try {
        const { data } = await axios.get(`v1/orders/${orderId.value}`);

        const orderData = data?.data || data;

        if (!orderData) {
            throw new Error('Pedido não encontrado');
        }

        const normalized = normalizeOrderFromAPI(orderData);
        Object.assign(budget, normalized);

        // Carregar dados de dropshipping se existirem
        if (normalized.dropshipping_budget === 1 && normalized.dropshipping_data) {
            enableDropshipping.value = true;
            const dropshippingDataCopy = { ...normalized.dropshipping_data };
            if (dropshippingDataCopy.number != null) {
                dropshippingDataCopy.number = String(dropshippingDataCopy.number);
            }
            dropshippingData.value = dropshippingDataCopy;
        } else {
            enableDropshipping.value = false;
            dropshippingData.value = {};
        }

        // Criar cópia profunda do budget atual (não do normalized) para comparação
        // Usar a mesma estrutura que será comparada no hasChanges
        originalBudget.value = JSON.parse(JSON.stringify({
            name: budget.name || '',
            status: budget.status || '',
            rooms: budget.rooms || [],
            cep: budget.cep || '',
            selectedCarrier: budget.selectedCarrier,
            paymentMethod: budget.paymentMethod || '',
            installments: budget.installments || 1
        }));

        await fetchRequestLayoutArts();

    } catch (error) {
        console.error('Erro ao carregar pedido:', error);
        window.Swal.fire({
            title: 'Erro!',
            text: error?.response?.data?.message || 'Não foi possível carregar o pedido',
            icon: 'error',
            confirmButtonText: 'Entendi!',
        });
        router.push('/pedidos');
    } finally {
        loadingOrder.value = false;
        loadingBudget.value = false;
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

function formatNumber(value) {
    if (value === null || value === undefined) {
        return '-';
    }
    const numericValue = Number(value);
    return Number.isFinite(numericValue) ? numericValue.toFixed(2) : value;
}

function formatDate(value) {
    if (!value) {
        return '-';
    }
    try {
        const date = value instanceof Date ? value : new Date(value);
        if (Number.isNaN(date.getTime())) {
            return typeof value === 'string' ? value : '-';
        }
        return date.toLocaleString('pt-BR');
    } catch (error) {
        return typeof value === 'string' ? value : '-';
    }
}

async function fetchRequestLayoutArts() {
    if (!orderId.value || !auth.user?.id) {
        requestLayoutArts.value = [];
        loadingRequestArts.value = false;
        return;
    }

    try {
        loadingRequestArts.value = true;

        const params = {
            order_id: orderId.value,
        };

        const response = await axios.get('v1/budgets/request-layout-arts', { params });
        const responseData = response?.data || response;

        if (responseData?.success && Array.isArray(responseData.data)) {
            requestLayoutArts.value = responseData.data.map((interaction) => ({
                id: interaction.id,
                card_id: interaction.card_id,
                created_at: interaction.created_at,
                wall_info: interaction.wall_info || null,
                arts: (interaction.arts || []).map((art) => ({
                    id: art.id,
                    budget_id: art.budget_id || null,
                    order_budget_id: art.order_budget_id || null,
                    interactions_card_id: art.interactions_card_id || null,
                    dealer_id: art.dealer_id || auth.user.id,
                    designer_id: art.designer_id || auth.user.id,
                    comment: art.comment || null,
                    path_file: art.path_file || null,
                    image_url: art.image_url || (art.path_file ? resolveImageUrl(art.path_file) : null),
                    created_at: art.created_at || null,
                    designer_name: art.designer?.name || art.designer_name || null,
                    dealer_name: art.dealer?.name || art.dealer_name || null,
                })),
                arts_count: interaction.arts_count || (interaction.arts || []).length,
            }));
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

onMounted(() => {
    fetchCollectionModels();
    loadOrder();
    searchTinyErpProducts();
});

watch(
    () => budget.rooms.map((room) =>
        room.walls.map((wall) => ({
            width: wall.width,
            height: wall.height,
            continueSameArt: wall.continueSameArt
        }))
    ),
    () => {
        budget.rooms.forEach((room) => {
            room.walls.forEach((wall) => {
                if (!wall.width || !wall.height) {
                    if (wall.continueSameArt || (Array.isArray(wall.continuations) && wall.continuations.length)) {
                        wall.continueSameArt = false;
                        wall.continuations = [];
                    }
                }
            });
        });
    },
    { deep: true }
);

// Computed
const totalWalls = computed(() => {
    return budget.rooms.reduce((total, room) => total + room.walls.length, 0);
});

const totalArea = computed(() => {
    let area = 0;
    budget.rooms.forEach(room => {
        room.walls.forEach(wall => {
            area += getWallArea(wall);
        });
    });
    return area;
});

// Calcular custo dos modelos
const totalModelsCost = computed(() => {
    let total = 0;
    budget.rooms.forEach(room => {
        room.walls.forEach(wall => {
            if (wall.model) {
                const model = getModelById(wall.model);
                if (model) {
                    total += model.value;
                }
            }
        });
    });
    return total;
});

// Calcular custo do frete
const freightCost = computed(() => {
    if (budget.selectedCarrier !== null && budget.carriers[budget.selectedCarrier]) {
        return budget.carriers[budget.selectedCarrier].price;
    }
    return 0;
});

// Calcular total à vista dinamicamente
const calculatedTotalBudgetVista = computed(() => {
    return (totalArea.value * PRECO_VISTA.value) + totalModelsCost.value + freightCost.value;
});

// Calcular total a prazo dinamicamente
const calculatedTotalBudgetPrazo = computed(() => {
    return (totalArea.value * PRECO_PRAZO.value) + totalModelsCost.value + freightCost.value;
});

// Total à vista: usar valor do banco se não houver mudanças, senão calcular dinamicamente
const totalBudgetVista = computed(() => {
    if (hasChanges.value) {
        return calculatedTotalBudgetVista.value;
    }
    // Por padrão, usar valores do banco que vieram na requisição
    return budget.total_amount || calculatedTotalBudgetVista.value;
});

// Total a prazo: usar valor do banco se não houver mudanças, senão calcular dinamicamente
const totalBudgetPrazo = computed(() => {
    if (hasChanges.value) {
        return calculatedTotalBudgetPrazo.value;
    }
    // Por padrão, usar valores do banco que vieram na requisição
    return budget.total_amount_installments || calculatedTotalBudgetPrazo.value;
});


// Methods
function validateBudget() {
    if (!budget.name) {
        showWarning('Por favor, informe o nome do pedido');
        return false;
    }

    // Validar se há pelo menos um ambiente com pelo menos uma parede
    if (budget.rooms.length === 0) {
        showWarning('Por favor, adicione pelo menos um ambiente');
        return false;
    }

    const isValidDimension = (value) =>
        typeof value === 'number' && !Number.isNaN(value) && value > 0;

    for (let roomIndex = 0; roomIndex < budget.rooms.length; roomIndex++) {
        const room = budget.rooms[roomIndex];
        const roomLabel = room.name?.trim() || `Ambiente ${roomIndex + 1}`;

        if (!room.name || !room.name.toString().trim()) {
            showWarning(`Informe o nome do ${roomLabel}.`);
            return false;
        }

        if (!room.walls.length) {
            showWarning(`Adicione pelo menos uma parede em ${roomLabel}.`);
            return false;
        }

        for (let wallIndex = 0; wallIndex < room.walls.length; wallIndex++) {
            const wall = room.walls[wallIndex];
            const wallLabel = wall.name?.trim() || `Parede ${wallIndex + 1}`;

            if (!wall.name || !wall.name.toString().trim()) {
                showWarning(`Informe o nome da ${wallLabel} em ${roomLabel}.`);
                return false;
            }

            if (!isValidDimension(wall.width) || !isValidDimension(wall.height)) {
                showWarning(
                    `Informe largura e altura válidas para ${wallLabel} em ${roomLabel}.`
                );
                return false;
            }

            if (wall.continueSameArt && Array.isArray(wall.continuations)) {
                for (
                    let continuationIndex = 0;
                    continuationIndex < wall.continuations.length;
                    continuationIndex++
                ) {
                    const continuation = wall.continuations[continuationIndex];
                    if (!isValidDimension(continuation.width)) {
                        showWarning(
                            `Informe a largura da continuação ${continuationIndex + 1} em ${wallLabel} (${roomLabel}).`
                        );
                        return false;
                    }
                    if (!isValidDimension(continuation.height)) {
                        showWarning(
                            `Informe a altura da continuação ${continuationIndex + 1} em ${wallLabel} (${roomLabel}).`
                        );
                        return false;
                    }
                }
            }

            if (!wall.model) {
                showWarning(`Por favor, selecione um modelo para ${wallLabel} em ${roomLabel}`);
                return false;
            }

            const model = getModelById(wall.model);
            const requests = model?.requests ?? {};
            const needComment = Boolean(requests.comment);
            const needLink = Boolean(requests.link);
            const needFile = Boolean(requests.file);
            const needCollection = Boolean(requests.collection);

            if (needComment && !String(wall.comment_referring_model ?? '').trim()) {
                showWarning(`Informe a descrição do modelo para ${wallLabel} em ${roomLabel}.`);
                return false;
            }

            if (needLink && !String(wall.link_referring_model ?? '').trim()) {
                showWarning(`Informe o link de referência para ${wallLabel} em ${roomLabel}.`);
                return false;
            }

            if (needFile) {
                const files = Array.isArray(wall.files_referring_model) ? wall.files_referring_model : [];
                if (!files.length) {
                    showWarning(`Informe ao menos um arquivo de referência para ${wallLabel} em ${roomLabel}.`);
                    return false;
                }
            }

            if (needCollection && !String(wall.collection_referring_model ?? '').trim()) {
                showWarning(`Selecione uma arte da coleção para ${wallLabel} em ${roomLabel}.`);
                return false;
            }
        }
    }

    if (modelsLoading.value) {
        showWarning('Aguarde o carregamento dos modelos antes de salvar.');
        return false;
    }

    if (!productModels.value.length) {
        showWarning('Nenhum modelo disponível no momento.');
        return false;
    }

    if (budget.cep && budget.cep.length >= 8 && budget.selectedCarrier === null) {
        showWarning('Por favor, selecione uma transportadora ou remova o CEP');
        return false;
    }

    if (!budget.paymentMethod) {
        showWarning('Por favor, selecione uma forma de pagamento');
        return false;
    }

    return true;
}

function addRoom() {
    budget.rooms.push({
        name: '',
        walls: [createDefaultWall()]
    });
}

function removeRoom(index) {
    budget.rooms.splice(index, 1);
}

function addWall(roomIndex) {
    budget.rooms[roomIndex].walls.push(createDefaultWall());
}

function removeWall(roomIndex, wallIndex) {
    budget.rooms[roomIndex].walls.splice(wallIndex, 1);
}

function addContinuation(roomIndex, wallIndex) {
    const wall = budget.rooms[roomIndex].walls[wallIndex];
    if (!Array.isArray(wall.continuations)) {
        wall.continuations = [];
    }
    if (!wall.continueSameArt) {
        wall.continueSameArt = true;
    }
    wall.continuations.push(createDefaultContinuation());
}

function removeContinuation(roomIndex, wallIndex, continuationIndex) {
    const wall = budget.rooms[roomIndex].walls[wallIndex];
    if (!Array.isArray(wall.continuations)) {
        return;
    }
    wall.continuations.splice(continuationIndex, 1);
}

function handleContinuationToggle(roomIndex, wallIndex) {
    const wall = budget.rooms[roomIndex].walls[wallIndex];

    if (!wall.continueSameArt) {
        wall.continuations = [];
        return;
    }

    if (!wall.width || !wall.height) {
        wall.continueSameArt = false;
        wall.continuations = [];
        return;
    }

    if (!Array.isArray(wall.continuations) || !wall.continuations.length) {
        wall.continuations = [createDefaultContinuation()];
    }
}

function getWallContinuations(wall) {
    if (!wall.continueSameArt) {
        return [];
    }

    return Array.isArray(wall.continuations) ? wall.continuations : [];
}

function getWallArea(wall) {
    const strips = calculateStrips(wall);
    const stripHeight = calculateStripHeight(wall);

    if (strips > 0 && stripHeight) {
        return strips * stripHeight;
    }

    return 0;
}

function getStripCalculation(wall) {
    const baseWidth = Number(wall.width) || 0;
    const continuationWidth = getWallContinuations(wall).reduce((sum, continuation) => {
        const width = Number(continuation.width) || 0;
        return sum + width;
    }, 0);
    const width = baseWidth + continuationWidth;

    const heights = [];
    const baseHeight = Number(wall.height) || 0;
    if (baseHeight) {
        heights.push(baseHeight);
    }

    getWallContinuations(wall).forEach((continuation) => {
        const continuationHeight = Number(continuation.height) || 0;
        if (continuationHeight) {
            heights.push(continuationHeight);
        }
    });

    const height = heights.length ? Math.max(...heights) : 0;

    if (!width || !height) {
        return {
            numberOfStrips: 0,
            stripHeight: null
        };
    }

    let numberOfStrips = Math.ceil(width / STRIP_WIDTH);
    const stripHeight = STRIP_HEIGHT_OPTIONS.find(
        (alt) => alt >= height + 0.09
    );

    if (stripHeight && stripHeight >= 6 && numberOfStrips % 2 !== 0) {
        numberOfStrips += 1;
    }

    return {
        numberOfStrips,
        stripHeight: stripHeight || null
    };
}

function calculateStrips(wall) {
    return getStripCalculation(wall).numberOfStrips;
}

function calculateStripHeight(wall) {
    return getStripCalculation(wall).stripHeight;
}

function formatStripHeight(wall) {
    const height = calculateStripHeight(wall);
    return height ? height.toFixed(2) : 'N/D';
}

function formatCEP(event) {
    let value = event.target.value.replace(/\D/g, '');
    if (value.length > 5) {
        value = value.substring(0, 5) + '-' + value.substring(5, 8);
    }
    budget.cep = value;
}

async function calculateFreight() {
    if (!budget.cep) {
        return;
    }
    calculatingFreight.value = true;

    try {
        const { data } = await axios.post('v1/frenet/calculate-shipping', {
            cep: budget.cep,
            productData: tinyErpProducts.value
        });

        // Mapear os dados da resposta para o formato esperado
        if (data?.data?.ShippingSevicesArray && Array.isArray(data.data.ShippingSevicesArray)) {
            budget.carriers = data.data.ShippingSevicesArray
                .filter(service => !service.Error) // Filtrar apenas serviços sem erro
                .map(service => ({
                    name: `${service.Carrier} - ${service.ServiceDescription}`,
                    price: parseFloat(service.ShippingPrice) || 0,
                    deliveryTime: parseInt(service.DeliveryTime) || 0
                }));

            // Resetar a seleção se não houver carriers ou se o índice selecionado não existir mais
            if (budget.carriers.length === 0) {
                budget.selectedCarrier = null;
            } else if (budget.selectedCarrier !== null && budget.selectedCarrier >= budget.carriers.length) {
                budget.selectedCarrier = null;
            }
        } else {
            budget.carriers = [];
            budget.selectedCarrier = null;
        }

        calculatingFreight.value = false;
    } catch (error) {
        console.error('Erro ao calcular frete:', error);
        window.Swal.fire({
            title: 'Erro ao calcular frete!',
            text: 'Não foi possível calcular o frete. Tente novamente mais tarde.',
            confirmButtonText: 'Entendi!',
        });
    }
}

function calculateDeliveryTime(budget) {
    // Encontrar o maior tempo de desenvolvimento de arte entre todas as paredes
    let maxDevelopmentTime = 0;

    budget.rooms.forEach(room => {
        room.walls.forEach(wall => {
            if (wall.model) {
                const model = getModelById(wall.model);
                if (model) {
                    const days = Math.max(0, Number(model.deadline ?? 0));
                    if (days > maxDevelopmentTime) {
                        maxDevelopmentTime = days;
                    }
                }
            }
        });
    });

    // Tempo de produção base (5 dias) + maior tempo de desenvolvimento + tempo de frete
    const productionTime = 5;
    const freightTime = budget.carriers[budget.selectedCarrier]?.deliveryTime || 0;

    budget.deliveryTime = productionTime + maxDevelopmentTime + freightTime;

    return budget.deliveryTime;
}

function updateBudget() {
    if (!validateBudget()) {
        return;
    }

    // Validar dropshipping se estiver habilitado
    if (enableDropshipping.value && dropshippingFormRef.value) {
        const isValid = dropshippingFormRef.value.validate();
        if (!isValid) {
            return;
        }
    }

    saving.value = true;

    const payload = JSON.parse(JSON.stringify(budget));

    // Processar selectedCarrier e carriers_snapshot
    if (
        payload.selectedCarrier !== null &&
        Array.isArray(payload.carriers) &&
        payload.carriers[payload.selectedCarrier]
    ) {
        const selectedCarrierObj = payload.carriers[payload.selectedCarrier];
        payload.selectedCarrier = selectedCarrierObj;
        // Salvar snapshot dos carriers antes de deletar
        payload.carriers_snapshot = payload.carriers;
    } else {
        payload.selectedCarrier = null;
        // Manter carriers_snapshot se existir
        if (!payload.carriers_snapshot && Array.isArray(payload.carriers) && payload.carriers.length > 0) {
            payload.carriers_snapshot = payload.carriers;
        }
    }

    delete payload.carriers;
    delete payload.id;

    if (payload.paymentMethod === 'pix') {
        payload.installmentLimit = null;
        payload.installments = null;
    }

    // Configurar dropshipping_budget e dropshipping_data
    payload.dropshipping_budget = enableDropshipping.value ? 1 : 0;

    if (enableDropshipping.value && dropshippingFormRef.value) {
        payload.dropshipping_data = dropshippingFormRef.value.getData();
    } else {
        delete payload.dropshipping_data;
    }

    axios.put(`v1/orders/${orderId.value}`, payload)
        .then(response => {
            const updatedData = response.data?.data || response.data || budget;
            console.log('Pedido atualizado:', updatedData);

            window.Swal.fire({
                title: 'Pedido atualizado!',
                text: 'Pedido foi atualizado com sucesso!',
                confirmButtonText: 'Entendi!',
            });

            // Atualizar originalBudget e budget para refletir as mudanças salvas
            const normalized = normalizeOrderFromAPI(updatedData);
            // Atualizar valores salvos no budget
            budget.total_amount = normalized.total_amount;
            budget.total_amount_installments = normalized.total_amount_installments;
            originalBudget.value = JSON.parse(JSON.stringify(normalized));
            // Redirecionar para a lista de pedidos
            setTimeout(() => {
                router.push('/pedidos');
            }, 1500);
        })
        .catch(error => {
            console.error('Erro ao atualizar pedido:', error);
            const message = error.response?.data?.message || 'Tente novamente mais tarde.';
            window.Swal.fire({
                title: 'Erro ao atualizar pedido!',
                text: message ?? 'Tente novamente mais tarde.',
                confirmButtonText: 'Entendi!',
            });
        })
        .finally(() => {
            saving.value = false;
        });
}
</script>

<style scoped>
.budget-attention-info {
  font-size: 12px;
  font-weight: 600;
}
</style>

