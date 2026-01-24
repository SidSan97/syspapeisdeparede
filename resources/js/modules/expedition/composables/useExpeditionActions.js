import { useRouter } from 'vue-router';
import { useExpeditionService } from '../services/expeditionService';
import { createLinkAlertConfig } from '@/utils/sweetalertHelpers';

export function useExpeditionActions() {
  const router = useRouter();
  const expeditionService = useExpeditionService();

  async function generateSeparationLabel(expedition, loading) {
    try {
      loading.value = true;
      await expeditionService.generateSeparationLabel(expedition.id);

      const result = await window.Swal.fire({
        title: 'Etiqueta gerada com sucesso!',
        text: 'Deseja visualizar a etiqueta agora?',
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'Visualizar etiqueta',
        cancelButtonText: 'Fechar',
      });

      if (result.isConfirmed) {
        await viewSeparationLabelPdf(expedition.id, loading);
      }
    } catch (error) {
      console.error('Erro ao gerar etiqueta de separação:', error);
      window.Swal.fire({
        title: 'Erro ao gerar etiqueta de separação!',
        text: 'Não foi possível gerar a etiqueta de separação. Tente novamente mais tarde.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
    } finally {
      loading.value = false;
    }
  }

  async function viewSeparationLabelPdf(orderBudgetId, loading) {
    try {
      loading.value = true;
      const blobData = await expeditionService.viewSeparationLabelPdf(orderBudgetId);

      const blob = new Blob([blobData], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);
      window.open(url, '_blank');

      setTimeout(() => {
        URL.revokeObjectURL(url);
      }, 100);
    } catch (error) {
      console.error('Erro ao visualizar PDF da etiqueta:', error);
      window.Swal.fire({
        title: 'Erro ao visualizar etiqueta!',
        text: 'Não foi possível abrir a etiqueta. Tente novamente mais tarde.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
    } finally {
      loading.value = false;
    }
  }

  async function generateInvoice(invoice, loading, fetchInvoices) {
    try {
      loading.value = true;
      await expeditionService.generateInvoice(invoice.order_id);

      window.Swal.fire({
        title: 'Nota Fiscal gerada com sucesso!',
        text: 'A nota fiscal foi gerada com sucesso.',
        icon: 'success',
        confirmButtonText: 'Entendi!',
      });

      await fetchInvoices();
    } catch (error) {
      console.error('Erro ao gerar nota fiscal:', error);
      window.Swal.fire({
        title: 'Erro ao gerar nota fiscal!',
        text: error.response?.data?.message || 'Não foi possível gerar a nota fiscal. Tente novamente mais tarde.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
    } finally {
      loading.value = false;
    }
  }

  async function generateDanfe(id, loading) {
    try {
      loading.value = true;
      const data = await expeditionService.generateDanfe(id);

      const link =
        data?.link_nfe ||
        data?.data?.link_nfe ||
        (Array.isArray(data?.links) ? data.links[0]?.link : null);

      if (link) {
        const result = await window.Swal.fire(
          createLinkAlertConfig({
            title: 'DANFE gerado com sucesso!',
            linkId: 'danfe-link',
            linkValue: link,
            message: 'Deseja abrir o DANFE agora?',
            successMessage: 'O link do DANFE foi copiado para a área de transferência.',
            confirmButtonText: 'Abrir DANFE',
            cancelButtonText: 'Fechar'
          })
        );

        if (result.isConfirmed) {
          window.open(link, '_blank');
        }
      } else {
        window.Swal.fire({
          title: 'Erro ao gerar DANFE!',
          text: 'Não foi possível gerar o DANFE. Tente novamente mais tarde.',
          icon: 'error',
          confirmButtonText: 'Entendi!',
        });
      }
    } catch (error) {
      console.error('Erro ao gerar DANFE:', error);
      window.Swal.fire({
        title: 'Erro ao gerar DANFE!',
        text: error.response?.data?.message || 'Não foi possível gerar o DANFE. Tente novamente mais tarde.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
    } finally {
      loading.value = false;
    }
  }

  async function printCarrierLabels(groupingId, loading) {
    try {
      loading.value = true;
      const data = await expeditionService.printCarrierLabels(groupingId);

      const link =
        data?.links?.[0]?.link ||
        data?.data?.links?.[0]?.link ||
        data?.link ||
        null;

      if (link) {
        const result = await window.Swal.fire(
          createLinkAlertConfig({
            title: 'Etiqueta gerada com sucesso!',
            linkId: 'label-link',
            linkValue: link,
            message: 'Deseja visualizar a etiqueta agora?',
            successMessage: 'O link da etiqueta foi copiado para a área de transferência.',
            confirmButtonText: 'Visualizar etiqueta',
            cancelButtonText: 'Fechar'
          })
        );

        if (result.isConfirmed) {
          window.open(link, '_blank');
        }
      } else {
        window.Swal.fire({
          title: 'Erro ao gerar etiqueta!',
          text: 'Não foi possível gerar a etiqueta. Tente novamente mais tarde.',
          icon: 'error',
          confirmButtonText: 'Entendi!',
        });
      }
    } catch (error) {
      console.error('Erro ao imprimir etiquetas:', error);
      window.Swal.fire({
        title: 'Erro ao imprimir etiquetas!',
        text: error.response?.data?.message || 'Não foi possível imprimir as etiquetas. Tente novamente mais tarde.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
    } finally {
      loading.value = false;
    }
  }

  function viewDetails(expedition) {
    // TODO: Implementar ação de ver detalhes
    console.log('Ver detalhes da expedição:', expedition);
  }

  function viewInvoiceDetails(invoice) {
    const invoiceId = invoice.nota_fiscal?.id;
    if (invoiceId) {
      sessionStorage.setItem(`invoice_${invoiceId}`, JSON.stringify(invoice.nota_fiscal));
      router.push({
        name: 'ShowInvoiceDetails',
        params: { id: invoiceId }
      });
    }
  }

  function viewGroupingDetails(grouping) {
    const groupingId = grouping.idAgrupamento;
    if (groupingId) {
      sessionStorage.setItem(`grouping_${groupingId}`, JSON.stringify(grouping));
      router.push({
        name: 'ShowGroupingDetails',
        params: { id: groupingId }
      });
    }
  }

  return {
    generateSeparationLabel,
    viewSeparationLabelPdf,
    generateInvoice,
    generateDanfe,
    printCarrierLabels,
    viewDetails,
    viewInvoiceDetails,
    viewGroupingDetails,
  };
}
