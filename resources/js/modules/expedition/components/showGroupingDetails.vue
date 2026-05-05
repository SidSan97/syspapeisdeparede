<template>
  <section class="content">
    <Page :title="`Agrupamento #${groupingId}`" back-to="/expedicao">
      <div class="card-body p-4" v-if="loading">
        <div class="text-center text-muted fw-semibold">Carregando detalhes do agrupamento...</div>
      </div>

      <div class="card-body p-4" v-else-if="!groupingData">
        <div class="text-center text-muted fw-semibold">Agrupamento não encontrado.</div>
      </div>

      <div class="card-body p-4" v-else>
        <div class="row g-4">
          <!-- Card: Informações Gerais -->
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Informações Gerais</h5>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label text-muted small">ID Agrupamento</label>
                    <div class="fw-semibold">{{ groupingData.idAgrupamento || '—' }}</div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label text-muted small">Data</label>
                    <div class="fw-semibold">{{ formatDate(groupingData.data) }}</div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label text-muted small">ID Forma Envio</label>
                    <div class="fw-semibold">{{ groupingData.idFormaEnvio || '—' }}</div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label text-muted small">Forma de Envio</label>
                    <div class="fw-semibold">{{ groupingData.formaEnvio || '—' }}</div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label text-muted small">Quantidade de Expedições</label>
                    <div class="fw-semibold">{{ groupingData.expedicoes?.length || 0 }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card: Expedições -->
          <div class="col-12" v-if="groupingData.expedicoes && groupingData.expedicoes.length > 0">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Expedições ({{ groupingData.expedicoes.length }})</h5>
              </div>
              <div class="card-body">
                <div
                  v-for="(expedicaoItem, index) in groupingData.expedicoes"
                  :key="index"
                  class="mb-4"
                >
                  <div class="border rounded p-3" v-if="expedicaoItem.expedicao">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="mb-0">Expedição {{ index + 1 }}</h6>
                    </div>

                    <div class="row g-3">
                      <!-- Informações da Expedição -->
                      <div class="col-12">
                        <div class="row g-3">
                          <div class="col-md-3">
                            <label class="form-label text-muted small">ID</label>
                            <div class="fw-semibold">{{ expedicaoItem.expedicao.id || '—' }}</div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">Tipo Objeto</label>
                            <div class="fw-semibold">
                              {{ expedicaoItem.expedicao.tipoObjeto || '—' }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">ID Objeto</label>
                            <div class="fw-semibold">
                              {{ expedicaoItem.expedicao.idObjeto || '—' }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">Situação</label>
                            <div class="fw-semibold">
                              {{ expedicaoItem.expedicao.situacao || '—' }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">Data de Emissão</label>
                            <div class="fw-semibold">
                              {{ expedicaoItem.expedicao.dataEmissao || '—' }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">Forma de Envio</label>
                            <div class="fw-semibold">
                              {{ expedicaoItem.expedicao.formaEnvio || '—' }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">Identificação</label>
                            <div class="fw-semibold">
                              {{ expedicaoItem.expedicao.identificacao || '—' }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">Quantidade de Volumes</label>
                            <div class="fw-semibold">
                              {{ expedicaoItem.expedicao.qtdVolumes || '0' }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">Valor Declarado</label>
                            <div class="fw-semibold">
                              {{ formatCurrency(expedicaoItem.expedicao.valorDeclarado) }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small"
                              >Possui Valor Declarado</label
                            >
                            <div class="fw-semibold">
                              {{
                                expedicaoItem.expedicao.possuiValorDeclarado === 'S' ? 'Sim' : 'Não'
                              }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">Peso Bruto (kg)</label>
                            <div class="fw-semibold">
                              {{ expedicaoItem.expedicao.pesoBruto || '—' }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small"
                              >Código de Rastreamento</label
                            >
                            <div class="fw-semibold">
                              {{ expedicaoItem.expedicao.codigoRastreamento || '—' }}
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">URL Rastreamento</label>
                            <div class="fw-semibold">
                              <a
                                v-if="expedicaoItem.expedicao.urlRastreamento"
                                :href="expedicaoItem.expedicao.urlRastreamento"
                                target="_blank"
                                class="text-decoration-none"
                              >
                                {{ expedicaoItem.expedicao.urlRastreamento }}
                              </a>
                              <span v-else>—</span>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small">Possui AR</label>
                            <div class="fw-semibold">
                              {{ expedicaoItem.expedicao.possuiAR === 'S' ? 'Sim' : 'Não' }}
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Embalagem -->
                      <div class="col-12" v-if="expedicaoItem.expedicao.embalagem">
                        <div class="border-top pt-3 mt-3">
                          <h6 class="text-muted mb-3">Embalagem</h6>
                          <div class="row g-3">
                            <div class="col-md-2">
                              <label class="form-label text-muted small">Tipo</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.embalagem.tipo || '—' }}
                              </div>
                            </div>
                            <div class="col-md-2">
                              <label class="form-label text-muted small">Altura (cm)</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.embalagem.altura || '—' }}
                              </div>
                            </div>
                            <div class="col-md-2">
                              <label class="form-label text-muted small">Largura (cm)</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.embalagem.largura || '—' }}
                              </div>
                            </div>
                            <div class="col-md-2">
                              <label class="form-label text-muted small">Comprimento (cm)</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.embalagem.comprimento || '—' }}
                              </div>
                            </div>
                            <div class="col-md-2">
                              <label class="form-label text-muted small">Diâmetro (cm)</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.embalagem.diametro || '—' }}
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Destinatário -->
                      <div class="col-12" v-if="expedicaoItem.expedicao.destinatario">
                        <div class="border-top pt-3 mt-3">
                          <h6 class="text-muted mb-3">Destinatário</h6>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <label class="form-label text-muted small">Nome</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.destinatario.nome || '—' }}
                              </div>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label text-muted small">Endereço</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.destinatario.endereco || '—' }}
                                <span v-if="expedicaoItem.expedicao.destinatario.numero"
                                  >, {{ expedicaoItem.expedicao.destinatario.numero }}</span
                                >
                                <span v-if="expedicaoItem.expedicao.destinatario.complemento"
                                  >, {{ expedicaoItem.expedicao.destinatario.complemento }}</span
                                >
                              </div>
                            </div>
                            <div class="col-md-3">
                              <label class="form-label text-muted small">Bairro</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.destinatario.bairro || '—' }}
                              </div>
                            </div>
                            <div class="col-md-3">
                              <label class="form-label text-muted small">CEP</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.destinatario.cep || '—' }}
                              </div>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label text-muted small">Cidade</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.destinatario.cidade || '—' }}
                              </div>
                            </div>
                            <div class="col-md-2">
                              <label class="form-label text-muted small">UF</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.destinatario.uf || '—' }}
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Transportadora -->
                      <div
                        class="col-12"
                        v-if="
                          expedicaoItem.expedicao.transportadora &&
                          expedicaoItem.expedicao.transportadora.id
                        "
                      >
                        <div class="border-top pt-3 mt-3">
                          <h6 class="text-muted mb-3">Transportadora</h6>
                          <div class="row g-3">
                            <div class="col-md-3">
                              <label class="form-label text-muted small">ID</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.transportadora.id || '—' }}
                              </div>
                            </div>
                            <div class="col-md-9">
                              <label class="form-label text-muted small">Nome</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.transportadora.nome || '—' }}
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Forma de Frete -->
                      <div
                        class="col-12"
                        v-if="
                          expedicaoItem.expedicao.formaFrete &&
                          expedicaoItem.expedicao.formaFrete.id
                        "
                      >
                        <div class="border-top pt-3 mt-3">
                          <h6 class="text-muted mb-3">Forma de Frete</h6>
                          <div class="row g-3">
                            <div class="col-md-3">
                              <label class="form-label text-muted small">ID</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.formaFrete.id || '—' }}
                              </div>
                            </div>
                            <div class="col-md-9">
                              <label class="form-label text-muted small">Descrição</label>
                              <div class="fw-semibold">
                                {{ expedicaoItem.expedicao.formaFrete.descricao || '—' }}
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
      </div>
    </Page>
  </section>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import Page from '@/components/page/Page.vue';
import { formatDate } from '@/utils/dateUtils';
import { useFormatting } from '@/composables/useFormatting';

const { formatCurrency } = useFormatting();

const route = useRoute();
const groupingId = computed(() => route.params.id);
const groupingData = ref(null);
const loading = ref(true);

// Verifica se os dados foram salvos no sessionStorage
onMounted(() => {
  const storedData = sessionStorage.getItem(`grouping_${groupingId.value}`);
  if (storedData) {
    try {
      groupingData.value = JSON.parse(storedData);
      loading.value = false;
      // Remove os dados do sessionStorage após usar
      sessionStorage.removeItem(`grouping_${groupingId.value}`);
    } catch (error) {
      console.error('Erro ao parsear dados do agrupamento:', error);
      loading.value = false;
    }
  } else {
    loading.value = false;
  }

  document.title = `Agrupamento #${groupingId.value}`;
});
</script>
