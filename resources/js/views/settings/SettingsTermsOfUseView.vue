<script setup>
import { computed, onMounted, ref } from 'vue';
import { IconPlus, IconTrash } from '@tabler/icons-vue';
import Page from '@/components/page/Page.vue';
import RichTextEditor from '@/components/common/RichTextEditor.vue';
import { useDialog } from '@/composables/useDialog';
import { useToast } from '@/composables/useToast';
import { http } from '@/lib/http';

const toast = useToast();
const dialog = useDialog();

const breadcrumbs = [
  { path: '/settings', breadcrumbName: 'Configurações' },
  { path: '/settings/terms-of-use', breadcrumbName: 'Termos de uso' },
];

const DEFAULT_TERM = {
  id: 'budget_approval',
  title: 'Termo de aprovação do orçamento',
  body: '',
};

const terms = ref([{ ...DEFAULT_TERM }]);
const openIndex = ref(0);
const isLoading = ref(false);
const isSubmitting = ref(false);

const canDelete = computed(() => terms.value.length > 1);

function normalizeTerms(list) {
  if (!Array.isArray(list) || list.length === 0) {
    return [{ ...DEFAULT_TERM }];
  }

  return list.map((term, index) => ({
    id: term.id || `term_${index + 1}`,
    title: term.title || `Termo ${index + 1}`,
    body: term.body || '',
  }));
}

function createEmptyTerm() {
  return {
    id: `term_${Date.now()}`,
    title: 'Novo termo',
    body: '',
  };
}

function collapseId(term) {
  return `term-collapse-${term.id}`;
}

function setOpenIndex(index) {
  openIndex.value = index;
}

function updateTermBody(index, html) {
  if (!terms.value[index]) {
    return;
  }

  terms.value[index].body = html;
}

function addTerm() {
  terms.value.push(createEmptyTerm());
  openIndex.value = terms.value.length - 1;
}

async function removeTerm(index) {
  if (!canDelete.value || !terms.value[index]) {
    return;
  }

  const title = terms.value[index].title || 'este termo';
  const confirmed = await dialog.confirmDelete({
    title: 'Excluir termo?',
    text: `O termo "${title}" será removido. Salve as alterações para confirmar.`,
  });

  if (!confirmed) {
    return;
  }

  terms.value.splice(index, 1);

  if (openIndex.value >= terms.value.length) {
    openIndex.value = terms.value.length - 1;
  } else if (openIndex.value > index) {
    openIndex.value -= 1;
  }
}

async function loadTerms() {
  isLoading.value = true;

  try {
    const { data } = await http.get('v1/terms-of-use');
    terms.value = normalizeTerms(data.data);
    openIndex.value = 0;
  } catch (error) {
    console.error('Erro ao carregar termos de uso:', error);
    toast.error('Erro ao carregar termos de uso. Recarregue a página.');
    terms.value = [{ ...DEFAULT_TERM }];
  } finally {
    isLoading.value = false;
  }
}

async function handleSubmit() {
  if (isSubmitting.value) {
    return;
  }

  const hasEmptyTitle = terms.value.some((term) => !String(term.title || '').trim());
  if (hasEmptyTitle) {
    toast.warning('Preencha o título de todos os termos.');
    return;
  }

  isSubmitting.value = true;

  try {
    const payload = {
      terms: terms.value.map((term) => ({
        id: term.id,
        title: term.title.trim(),
        body: term.body || '',
      })),
    };

    const { data } = await http.put('v1/terms-of-use', payload);
    terms.value = normalizeTerms(data.data ?? payload.terms);

    if (openIndex.value >= terms.value.length) {
      openIndex.value = Math.max(0, terms.value.length - 1);
    }

    toast.success(data.message || 'Termos de uso salvos com sucesso!');
  } catch (error) {
    console.error('Erro ao salvar termos de uso:', error);
    toast.error(error.response?.data?.message || 'Erro ao salvar termos de uso.');
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(() => {
  document.title = 'Termos de uso';
  loadTerms();
});
</script>

<template>
  <section class="content">
    <Page
      title="Termos de uso"
      subtitle="Gerencie os textos exibidos aos usuários. Abra cada item do accordion para editar."
      :back-to="{ name: 'settings.home' }"
      :breadcrumbs="breadcrumbs"
    >
      <div v-if="isLoading" class="card">
        <div class="card-body text-muted d-flex align-items-center gap-2">
          <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          Carregando termos…
        </div>
      </div>

      <form v-else @submit.prevent="handleSubmit">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
          <div class="text-muted small">{{ terms.length }} termo(s)</div>

          <button type="button" class="btn btn-outline-primary btn-sm" @click="addTerm">
            <IconPlus :size="16" class="me-1" />
            Adicionar termo
          </button>
        </div>

        <div class="accordion" id="termsOfUseAccordion">
          <div v-for="(term, index) in terms" :key="term.id" class="accordion-item">
            <h2 class="accordion-header">
              <button
                class="accordion-button"
                :class="{ collapsed: openIndex !== index }"
                type="button"
                data-bs-toggle="collapse"
                :data-bs-target="`#${collapseId(term)}`"
                :aria-expanded="openIndex === index"
                :aria-controls="collapseId(term)"
                @click="setOpenIndex(index)"
              >
                {{ term.title || `Termo ${index + 1}` }}
              </button>
            </h2>

            <div
              :id="collapseId(term)"
              class="accordion-collapse collapse"
              :class="{ show: openIndex === index }"
              data-bs-parent="#termsOfUseAccordion"
            >
              <div class="accordion-body">
                <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    :disabled="!canDelete"
                    @click="removeTerm(index)"
                  >
                    <IconTrash :size="16" class="me-1" />
                    Excluir termo
                  </button>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold" :for="`term-title-${term.id}`">
                    Título
                  </label>
                  <input
                    :id="`term-title-${term.id}`"
                    v-model.trim="term.title"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    required
                    placeholder="Ex: Termo de aprovação do orçamento"
                  />
                </div>

                <div>
                  <label class="form-label fw-semibold">Texto</label>
                  <RichTextEditor
                    v-if="openIndex === index"
                    :key="term.id"
                    :model-value="term.body"
                    placeholder="Escreva o termo. Use Enter para novos parágrafos e linhas vazias."
                    @update:model-value="updateTermBody(index, $event)"
                  />
                  <div class="form-text">
                    Use negrito e itálico na barra do editor. Parágrafos vazios viram espaço entre
                    blocos no HTML.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary mb-3" :disabled="isSubmitting">
            <span
              v-if="isSubmitting"
              class="spinner-border spinner-border-sm me-2"
              role="status"
              aria-hidden="true"
            ></span>
            {{ isSubmitting ? 'Salvando...' : 'Salvar as alterações' }}
          </button>
        </div>
      </form>
    </Page>
  </section>
</template>
