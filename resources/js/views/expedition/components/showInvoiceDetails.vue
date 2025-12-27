<template>
  <section class="content">
    <Page :title="`Nota Fiscal #${invoiceId}`" back-to="/expedicao">
      <div class="border-0 shadow-sm">
        <div class="card-body p-4" v-if="loading">
          <div class="text-center text-muted fw-semibold">
            Carregando detalhes da nota fiscal...
          </div>
        </div>

        <div class="card-body p-4" v-else-if="!invoiceData">
          <div class="text-center text-muted fw-semibold">
            Nota fiscal não encontrada.
          </div>
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
                      <label class="form-label text-muted small">Número</label>
                      <div class="fw-semibold">{{ invoiceData.numero || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Série</label>
                      <div class="fw-semibold">{{ invoiceData.serie || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Data de Emissão</label>
                      <div class="fw-semibold">{{ invoiceData.data_emissao || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Situação</label>
                      <div class="fw-semibold">{{ invoiceData.descricao_situacao || '—' }}</div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-muted small">Chave de Acesso</label>
                      <div class="fw-semibold small">{{ invoiceData.chave_acesso || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Nº E-commerce</label>
                      <div class="fw-semibold">{{ invoiceData.numero_ecommerce || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Tipo</label>
                      <div class="fw-semibold">{{ invoiceData.tipo || '—' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card: Cliente -->
            <div class="col-12" v-if="invoiceData.cliente">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">Cliente</h5>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label text-muted small">Nome</label>
                      <div class="fw-semibold">{{ invoiceData.cliente.nome || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">CPF/CNPJ</label>
                      <div class="fw-semibold">{{ invoiceData.cliente.cpf_cnpj || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Tipo Pessoa</label>
                      <div class="fw-semibold">{{ invoiceData.cliente.tipo_pessoa || '—' }}</div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-muted small">Endereço</label>
                      <div class="fw-semibold">
                        {{ invoiceData.cliente.endereco || '—' }}
                        <span v-if="invoiceData.cliente.numero">, {{ invoiceData.cliente.numero }}</span>
                        <span v-if="invoiceData.cliente.complemento">, {{ invoiceData.cliente.complemento }}</span>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Bairro</label>
                      <div class="fw-semibold">{{ invoiceData.cliente.bairro || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">CEP</label>
                      <div class="fw-semibold">{{ invoiceData.cliente.cep || '—' }}</div>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label text-muted small">Cidade</label>
                      <div class="fw-semibold">{{ invoiceData.cliente.cidade || '—' }}</div>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label text-muted small">UF</label>
                      <div class="fw-semibold">{{ invoiceData.cliente.uf || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Telefone</label>
                      <div class="fw-semibold">{{ invoiceData.cliente.fone || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Email</label>
                      <div class="fw-semibold">{{ invoiceData.cliente.email || '—' }}</div>
                    </div>
                    <div class="col-md-3" v-if="invoiceData.cliente.ie">
                      <label class="form-label text-muted small">IE</label>
                      <div class="fw-semibold">{{ invoiceData.cliente.ie || '—' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card: Endereço de Entrega -->
            <div class="col-12" v-if="invoiceData.endereco_entrega">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">Endereço de Entrega</h5>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label text-muted small">Nome Destinatário</label>
                      <div class="fw-semibold">{{ invoiceData.endereco_entrega.nome_destinatario || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">CPF/CNPJ</label>
                      <div class="fw-semibold">{{ invoiceData.endereco_entrega.cpf_cnpj || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Tipo Pessoa</label>
                      <div class="fw-semibold">{{ invoiceData.endereco_entrega.tipo_pessoa || '—' }}</div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-muted small">Endereço</label>
                      <div class="fw-semibold">
                        {{ invoiceData.endereco_entrega.endereco || '—' }}
                        <span v-if="invoiceData.endereco_entrega.numero">, {{ invoiceData.endereco_entrega.numero }}</span>
                        <span v-if="invoiceData.endereco_entrega.complemento">, {{ invoiceData.endereco_entrega.complemento }}</span>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Bairro</label>
                      <div class="fw-semibold">{{ invoiceData.endereco_entrega.bairro || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">CEP</label>
                      <div class="fw-semibold">{{ invoiceData.endereco_entrega.cep || '—' }}</div>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label text-muted small">Cidade</label>
                      <div class="fw-semibold">{{ invoiceData.endereco_entrega.cidade || '—' }}</div>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label text-muted small">UF</label>
                      <div class="fw-semibold">{{ invoiceData.endereco_entrega.uf || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-muted small">Telefone</label>
                      <div class="fw-semibold">{{ invoiceData.endereco_entrega.fone || '—' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card: Transportador -->
            <div class="col-12" v-if="invoiceData.transportador">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">Transportador</h5>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label text-muted small">Nome</label>
                      <div class="fw-semibold">{{ invoiceData.transportador.nome || '—' }}</div>
                    </div>
                    <div class="col-md-3" v-if="invoiceData.codigo_rastreamento">
                      <label class="form-label text-muted small">Código de Rastreamento</label>
                      <div class="fw-semibold">{{ invoiceData.codigo_rastreamento || '—' }}</div>
                    </div>
                    <div class="col-md-3" v-if="invoiceData.url_rastreamento">
                      <label class="form-label text-muted small">URL Rastreamento</label>
                      <div class="fw-semibold">
                        <a :href="invoiceData.url_rastreamento" target="_blank" class="text-decoration-none">
                          {{ invoiceData.url_rastreamento }}
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card: Valores -->
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">Valores</h5>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label text-muted small">Valor Total</label>
                      <div class="fw-semibold h5 text-primary">
                        {{ formatCurrency(invoiceData.valor) }}
                      </div>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label text-muted small">Valor dos Produtos</label>
                      <div class="fw-semibold">{{ formatCurrency(invoiceData.valor_produtos) }}</div>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label text-muted small">Valor do Frete</label>
                      <div class="fw-semibold">{{ formatCurrency(invoiceData.valor_frete) }}</div>
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

const route = useRoute();
const invoiceId = computed(() => route.params.id);
const invoiceData = ref(null);
const loading = ref(true);

// Verifica se os dados foram salvos no sessionStorage
onMounted(() => {
    const storedData = sessionStorage.getItem(`invoice_${invoiceId.value}`);
    if (storedData) {
        try {
            invoiceData.value = JSON.parse(storedData);
            loading.value = false;
            // Remove os dados do sessionStorage após usar
            sessionStorage.removeItem(`invoice_${invoiceId.value}`);
        } catch (error) {
            console.error('Erro ao parsear dados da nota fiscal:', error);
            loading.value = false;
        }
    } else {
        loading.value = false;
    }
    document.title = `Nota Fiscal #${invoiceId.value}`;
});

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

</script>

