<template>
    <section class="content">
      <Page title="Pedidos">
        <div class="border-0 shadow-sm">
          <form class="g-3 align-items-center mb-4" role="search">
            <label for="search-query" class="sr-only">Pesquisar pedido</label>

            <div class="d-flex">
              <div class="me-3">
                  <div class="input-group input-group-prefix">
                      <input id="search-query" type="text" class="form-control"
                          placeholder="Pesquisar pedido" v-model="searchQuery">
                      <span class="input-group-text">
                          <i class="fa fa-search"></i>
                      </span>
                  </div>
              </div>

            <div class="">
              <div class="dropdown">
                <button
                  class="btn btn-outline-default dropdown-toggle"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                  :value="statusFilter"
                >
                  {{ currentStatusLabel }}
                </button>

                <ul class="dropdown-menu">
                  <li>
                    <button
                      class="dropdown-item"
                      type="button"
                      @click="setStatusFilter('all')"
                    >
                      Todos
                    </button>
                  </li>

                  <li v-for="option in statusOptions" :key="option.value">
                    <button
                        class="dropdown-item"
                        type="button"
                        :class="{ active: statusFilter === option.value }"
                        @click="setStatusFilter(option.value)"
                      >
                        {{ option.label }}
                    </button>
                  </li>
                </ul>
              </div>
            </div>
            </div>

            <div v-if="isAdmin" class="row buttons-filters mt-2">
              <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                <label for="dateFrom" class="form-label small mb-1">Data Inicial</label>
                <input
                  id="dateFrom"
                  v-model="dateFrom"
                  v-mask="'##/##/####'"
                  type="text"
                  class="form-control"
                  placeholder="DD/MM/AAAA"
                  :disabled="loading"
                  maxlength="10"
                />
              </div>

              <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                <label for="dateTo" class="form-label small mb-1">Data Final</label>
                <input
                  id="dateTo"
                  v-model="dateTo"
                  v-mask="'##/##/####'"
                  type="text"
                  class="form-control"
                  placeholder="DD/MM/AAAA"
                  :disabled="loading"
                  maxlength="10"
                />
              </div>

              <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                <label for="userFilter" class="form-label small mb-1">Revendedor</label>
                <select
                  id="userFilter"
                  v-model="selectedUserId"
                  class="form-control"
                  :disabled="loading || loadingUsers"
                >
                  <option :value="null">Todos os revendedores</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <div class="col-lg-3 col-md-6 d-flex align-items-end mb-2 mb-lg-0">
                <button
                  type="button"
                  class="btn btn-outline-default"
                  @click="clearFilters"
                  :disabled="loading"
                >
                  <i class="fa fa-times fa-fw"></i>
                  Limpar Filtros
                </button>
              </div>
              </div>
          </form>

          <div class="card-body p-0 mt-4">
            <div v-if="loading" class="p-5 text-center text-muted fw-semibold">
              Carregando pedidos...
            </div>

            <EmptyState
              v-else-if="filteredOrders.length === 0"
              heading="Nenhum pedido encontrado"
              icon="file-alt"
              class="p-5"
            >
              Ajuste os filtros ou crie um novo pedido.
            </EmptyState>

            <div v-else class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th scope="col" style="width: 64px;">Número</th>
                    <th scope="col" style="width: 64px;">Data</th>
                    <th class="text-nowrap" scope="col">Pedido</th>
                    <th class="text-nowrap" scope="col">Situação</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="order in filteredOrders" :key="order.id">
                    <th scope="row">{{ order.id }}</th>
                    <td>{{ formatDate(order.created_at || order.createdAt) }}</td>
                    <td style="min-width: 240px;">
                      <button
                        class="btn btn-link text-decoration-none p-0 text-start fw-semibold"
                        @click="viewOrder(order)"
                      >
                        {{ order.name }}
                      </button>
                    </td>
                    <td>

                        <span class="status-dot" :class="`status-dot-${getStatusVariant(order.status)}`"></span>
                        {{ formatStatusLabel(order.status) }}

                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </Page>
    </section>
  </template>

<script setup>
  import { computed, onMounted, ref } from 'vue';
  import { useRouter } from 'vue-router';
  import Page from '@/components/page/Page.vue';
  import EmptyState from '@/components/empty-state/EmptyState.vue';
  import axios from 'axios';
  import { useAuthStore } from '@/stores/auth';
  import { parseDateFromMask, formatDate } from '@/utils/dateUtils';

  const auth = useAuthStore();
  const orders = ref([]);
  const loading = ref(true);
  const searchQuery = ref('');
  const statusFilter = ref('all');
  const dateFrom = ref('');
  const dateTo = ref('');
  const selectedUserId = ref(null);
  const users = ref([]);
  const loadingUsers = ref(false);

  const isAdmin = computed(() => auth.isAdmin());
  const isCommercialUser = computed(() => auth.hasRole('commercial'));

  const router = useRouter();

  const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  });

  function formatCurrency(value) {
    if (value === null || value === undefined) {
      return currencyFormatter.format(0);
    }

    const numericValue = Number(value);
    return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
  }

  function formatDeliveryTime(days) {
    if (!days) {
      return 'Não informado';
    }

    return `${days} ${days === 1 ? 'dia' : 'dias'}`;
  }


  function normalizeOrder(order) {
    if (!order) {
      return {
        id: null,
        name: '',
        total_amount: 0,
        total_amount_installments: 0,
        delivery_time: null,
        status: null,
        rooms: [],
        comment_referring_model: '',
        commentReferringModel: '',
        link_referring_model: '',
        linkReferringModel: '',
        files_referring_model: [],
        filesReferringModel: [],
        collection_referring_model: null,
        collectionReferringModel: null,
      };
    }

    const totalAmount = order.total_amount ?? order.totalAmount ?? 0;
    const totalAmountInstallments = order.total_amount_installments ?? order.totalAmountInstallments ?? 0;
    const deliveryTime = order.delivery_time ?? order.deliveryTime ?? null;
    const status = order.status ?? order.Status ?? null;
    const commentRef = order.comment_referring_model ?? order.commentReferringModel ?? '';
    const linkRef = order.link_referring_model ?? order.linkReferringModel ?? '';
    const filesRef = Array.isArray(order.files_referring_model)
      ? order.files_referring_model
      : Array.isArray(order.filesReferringModel)
        ? order.filesReferringModel
        : [];
    const collectionRef = order.collection_referring_model ?? order.collectionReferringModel ?? null;
    const rooms = Array.isArray(order.rooms) ? order.rooms : [];

    return {
      ...order,
      name: order.name ?? '',
      total_amount: totalAmount,
      total_amount_installments: totalAmountInstallments,
      delivery_time: deliveryTime,
      status,
      rooms,
      comment_referring_model: commentRef,
      commentReferringModel: commentRef,
      link_referring_model: linkRef,
      linkReferringModel: linkRef,
      files_referring_model: filesRef,
      filesReferringModel: filesRef,
      collection_referring_model: collectionRef,
      collectionReferringModel: collectionRef,
    };
  }

  const statusOptions = [
    { label: 'Em aberto', value: 'em aberto' },
    { label: 'Aprovado', value: 'aprovado' },
    { label: 'Aprovar Layout', value: 'aprovar layout' },
    { label: 'Pendente de Revisão', value: 'pendente de revisão' },
    { label: 'Cancelado', value: 'cancelado' },
  ];

  const filteredOrders = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    const status = statusFilter.value;

    return orders.value.filter((order) => {
      // Filtro de busca
      const matchesQuery = !query
        || order.name?.toLowerCase().includes(query)
        || String(order.id).includes(query);

      // Filtro de status
      const normalizedStatus = (order.status || '').toString().toLowerCase();
      const matchesStatus = status === 'all' || normalizedStatus === status;

      // Filtro de período (apenas para admin)
      let matchesPeriod = true;
      if (isAdmin.value && (dateFrom.value || dateTo.value)) {
        const orderDateStr = order.created_at || order.createdAt;
        if (!orderDateStr) {
          matchesPeriod = false;
        } else {
          const orderDate = new Date(orderDateStr);
          if (Number.isNaN(orderDate.getTime())) {
            matchesPeriod = false;
          } else {
            const orderDateOnly = new Date(orderDate.getFullYear(), orderDate.getMonth(), orderDate.getDate());

            if (dateFrom.value && dateFrom.value.length === 10) {
              const fromDate = parseDateFromMask(dateFrom.value);
              if (fromDate && !Number.isNaN(fromDate.getTime())) {
                fromDate.setHours(0, 0, 0, 0);
                if (orderDateOnly < fromDate) {
                  matchesPeriod = false;
                }
              }
            }

            if (dateTo.value && dateTo.value.length === 10 && matchesPeriod) {
              const toDate = parseDateFromMask(dateTo.value);
              if (toDate && !Number.isNaN(toDate.getTime())) {
                toDate.setHours(23, 59, 59, 999);
                const toDateOnly = new Date(toDate.getFullYear(), toDate.getMonth(), toDate.getDate());
                if (orderDateOnly > toDateOnly) {
                  matchesPeriod = false;
                }
              }
            }
          }
        }
      }

      // Filtro de revendedor (apenas para admin)
      let matchesUser = true;
      if (isAdmin.value && selectedUserId.value !== null) {
        const orderUserId = order.user_id || order.userId || order.user?.id;
        matchesUser = Number(orderUserId) === Number(selectedUserId.value);
      }

      return matchesQuery && matchesStatus && matchesPeriod && matchesUser;
    });
  });

  const currentStatusLabel = computed(() => {
    if (statusFilter.value === 'all') {
      return 'Situação';
    }

    const match = statusOptions.find((option) => option.value === statusFilter.value);
    return match ? match.label : 'Situação';
  });

  async function fetchOrders() {
    try {
      loading.value = true;

      const { data } = await axios.get('v1/orders');

      const payload = Array.isArray(data?.data)
        ? data.data.map(normalizeOrder)
        : Array.isArray(data?.data?.data)
          ? data.data.data.map(normalizeOrder)
          : [];

      orders.value = payload;
    } catch (error) {
      console.error('Erro ao carregar pedidos:', error);
      orders.value = [];
    } finally {
      loading.value = false;
    }
  }

  function formatStatusLabel(status) {
    if (!status) {
      return '—';
    }
    const normalized = status.toString().toLowerCase();
    const match = statusOptions.find((option) => option.value === normalized);
    if (match) {
      return match.label;
    }
    return status;
  }

  function getStatusVariant(status) {
    const normalized = (status || '').toString().toLowerCase();
    if (normalized.includes('cancel')) {
      return 'danger';
    }
    if (normalized.includes('aprov')) {
      return 'success';
    }
    return 'info';
  }

  function setStatusFilter(value) {
    statusFilter.value = value;
  }

  function viewOrder(order) {
    router.push({ name: 'ShowOrderDetails', params: { id: order.id } });
  }

  async function fetchUsers() {
    if (!isAdmin.value && !isCommercialUser.value) {
      return;
    }

    try {
      loadingUsers.value = true;
      const response = await axios.get('v1/users/search');

      if (response.data?.success && response.data?.data) {
        // Se a resposta estiver paginada, pegar o array de dados
        if (response.data.data.data && Array.isArray(response.data.data.data)) {
          users.value = response.data.data.data;
        } else if (Array.isArray(response.data.data)) {
          users.value = response.data.data;
        } else {
          users.value = [];
        }
      }
    } catch (error) {
      console.error('Erro ao buscar usuários:', error);
      users.value = [];
    } finally {
      loadingUsers.value = false;
    }
  }

  function clearFilters() {
    dateFrom.value = '';
    dateTo.value = '';
    selectedUserId.value = null;
  }

  onMounted(() => {
    fetchOrders();
    if (isAdmin.value || isCommercialUser.value) {
      fetchUsers();
    }
    document.title = 'Pedidos';
  });
</script>

<style scoped>
  .search-input .form-control,
  .search-input .input-group-text {
    border-radius: 0.375rem;
    padding-block: 0.85rem;
  }

  .search-input .input-group-text {
    border-right: none;
  }

  .search-input .form-control {
    border-left: none;
  }

  .search-input .form-control:focus {
    border-color: var(--bs-secondary);
    box-shadow: none;
  }

  .status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
  }

  .status-dot-success {
    background-color: var(--bs-success);
  }

  .status-dot-danger {
    background-color: var(--bs-danger);
  }

  .status-dot-info {
    background-color: var(--bs-info);
  }

  .input-group-text, .buttons-filters button, input {
      height: 36px !important;
  }
</style>
