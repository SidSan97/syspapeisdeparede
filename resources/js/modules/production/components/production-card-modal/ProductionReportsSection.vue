<template>
  <div v-if="card.id" class="production-reports-section">
    <h3 class="production-reports-section-title">
      <IconFileTypePdf />

      Relatórios de Produção
    </h3>
    <div v-if="loading" class="production-reports-section-loading text-muted">
      <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>

      Carregando relatórios...
    </div>
    <div v-else-if="reports.length === 0" class="production-reports-section-empty text-muted">
      Nenhum relatório de produção encontrado para este card.
    </div>
    <div v-else class="production-reports-section-list">
      <div v-for="report in reports" :key="report.id" class="production-reports-section-item">
        <div class="production-reports-section-item-body">
          <div class="production-reports-section-item-header">
            <div class="production-reports-section-item-content">
              <h6 class="production-reports-section-item-title">
                <IconFileTypePdf class="me-2" />

                Relatório #{{ report.id }}
              </h6>
              <div class="production-reports-section-item-meta">
                <small class="text-muted">
                  {{ formatDate(report.action_date) }}
                </small>
                <span class="badge" :class="getReportBadgeClass(report.action_type)">
                  {{ getReportActionTypeLabel(report.action_type) }}
                </span>
              </div>
              <div v-if="report.user" class="production-reports-section-item-user small">
                <IconUser class="me-1" />

                {{ report.user.name }}
              </div>
            </div>
            <div class="production-reports-section-item-actions">
              <button
                class="btn btn-sm btn-primary"
                @click="handleDownload(report.id)"
                :disabled="downloadingReportId === report.id"
                title="Baixar PDF"
              >
                <span
                  v-if="downloadingReportId === report.id"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                ></span>
                <IconDownload v-else class="me-2" />

                {{ downloadingReportId === report.id ? 'Baixando...' : 'Baixar PDF' }}
              </button>
            </div>
          </div>
          <div
            v-if="report.column_name"
            class="production-reports-section-item-column border-top pt-3 small"
          >
            <span class="fw-semibold text-muted">Coluna:</span>
            <span class="ms-2">{{ report.column_name }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { http } from '@/lib/http';
import { useToast } from '@/composables/useToast';
import { IconDownload, IconFileTypePdf, IconUser } from '@tabler/icons-vue';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
  reports: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const toast = useToast();

const downloadingReportId = ref(null);

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

function getReportActionTypeLabel(actionType) {
  const labels = {
    mark_as_produced: 'Marcado como Produzido',
    production_percentage_100: 'Produção 100%',
  };
  return (
    labels[actionType] || actionType.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())
  );
}

function getReportBadgeClass(actionType) {
  const classes = {
    mark_as_produced: 'bg-success',
    production_percentage_100: 'bg-info',
  };
  return classes[actionType] || 'bg-secondary';
}

async function handleDownload(reportId) {
  if (!reportId || downloadingReportId.value === reportId) {
    return;
  }

  downloadingReportId.value = reportId;

  try {
    const response = await http.get(`v1/orders/production-reports/${reportId}/download-pdf`, {
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

    toast.success('PDF baixado com sucesso');
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
</script>

<style lang="scss" scoped>
.production-reports-section {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.production-reports-section-title {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: var(--bs-body-color);
  display: flex;
  align-items: center;
  gap: 0.5rem;

  i {
    color: var(--bs-primary);
  }
}

.production-reports-section-loading,
.production-reports-section-empty {
  padding: 1rem;
  text-align: center;
}

.production-reports-section-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.production-reports-section-item {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
  overflow: hidden;
  background-color: var(--bs-card-bg);
}

.production-reports-section-item-body {
  padding: 1rem;
}

.production-reports-section-item-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.production-reports-section-item-content {
  flex-grow: 1;
}

.production-reports-section-item-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
}

.production-reports-section-item-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.production-reports-section-item-user {
  display: flex;
  align-items: center;
}

.production-reports-section-item-actions {
  flex-shrink: 0;
}

.production-reports-section-item-column {
  padding-top: 0.75rem;
  margin-top: 0.75rem;
}
</style>
