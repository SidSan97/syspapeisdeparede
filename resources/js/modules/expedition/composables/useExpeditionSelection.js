import { computed, ref } from 'vue';
import { expeditionService } from '../services/expeditionService';

export function useExpeditionSelection(invoicesList, searchInvoices) {
  const selectedInvoices = ref(new Set());

  const filteredExpeditions = computed(() => {
    return invoicesList.value;
  });

  const isAllSelected = computed(() => {
    if (filteredExpeditions.value.length === 0) return false;
    return filteredExpeditions.value.every((invoice) =>
      selectedInvoices.value.has(invoice.nota_fiscal?.id),
    );
  });

  function toggleSelectAll() {
    if (isAllSelected.value) {
      filteredExpeditions.value.forEach((invoice) => {
        selectedInvoices.value.delete(invoice.nota_fiscal?.id);
      });
    } else {
      filteredExpeditions.value.forEach((invoice) => {
        if (invoice.nota_fiscal?.id) {
          selectedInvoices.value.add(invoice.nota_fiscal.id);
        }
      });
    }
  }

  function toggleSelectInvoice(invoiceId) {
    if (selectedInvoices.value.has(invoiceId)) {
      selectedInvoices.value.delete(invoiceId);
    } else {
      selectedInvoices.value.add(invoiceId);
    }
  }

  async function expedir(loading) {
    try {
      loading.value = true;

      const invoiceIds = Array.from(selectedInvoices.value);

      if (invoiceIds.length === 0) {
        loading.value = false;
        window.Swal.fire({
          title: 'Nenhuma nota fiscal selecionada!',
          text: 'Por favor, selecione pelo menos uma nota fiscal para expedir.',
          icon: 'warning',
          confirmButtonText: 'Entendi!',
        });
        return;
      }

      const selectedInvoicesData = filteredExpeditions.value.filter((invoice) =>
        invoiceIds.includes(invoice.nota_fiscal?.id),
      );

      const orderIds = selectedInvoicesData
        .map((invoice) => invoice.nota_fiscal?.numero_ecommerce)
        .filter((orderId) => orderId);

      const transporters = selectedInvoicesData
        .map((invoice) => invoice.nota_fiscal?.transportador?.nome)
        .filter((transporter) => transporter);

      if (transporters.length === 0 || transporters.length !== selectedInvoicesData.length) {
        loading.value = false;
        window.Swal.fire({
          title: 'Erro ao validar transportadoras!',
          text: 'Algumas notas fiscais selecionadas não possuem transportador.',
          icon: 'error',
          confirmButtonText: 'Entendi!',
        });
        return;
      }

      const firstTransporter = transporters[0];
      const allSameTransporter = transporters.every(
        (transporter) => transporter === firstTransporter,
      );

      if (!allSameTransporter) {
        loading.value = false;
        window.Swal.fire({
          title: 'Transportadoras diferentes!',
          text: 'Todas as notas fiscais selecionadas devem ter exatamente o mesmo transportador para serem agrupadas.',
          icon: 'warning',
          confirmButtonText: 'Entendi!',
        });
        return;
      }

      const data = await expeditionService.sendInvoiceToExpedition(
        invoiceIds,
        firstTransporter,
        orderIds,
      );

      if (data.success) {
        window.Swal.fire({
          title: 'Notas fiscais enviadas com sucesso!',
          text: data.message || 'As notas fiscais foram enviadas para expedição.',
          icon: 'success',
          confirmButtonText: 'Entendi!',
        });

        selectedInvoices.value.clear();
        await searchInvoices();
      }
    } catch (error) {
      console.error('Erro ao enviar notas fiscais para expedição:', error);
      window.Swal.fire({
        title: 'Erro ao enviar notas fiscais!',
        text:
          error.response?.data?.message ||
          'Não foi possível enviar as notas fiscais para expedição. Tente novamente mais tarde.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
    } finally {
      loading.value = false;
    }
  }

  return {
    selectedInvoices,
    filteredExpeditions,
    isAllSelected,
    toggleSelectAll,
    toggleSelectInvoice,
    expedir,
  };
}
