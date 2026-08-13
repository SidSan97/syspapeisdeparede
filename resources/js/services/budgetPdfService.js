import { http } from '@/lib/http';

function resolveBudgetPdfUrl(id) {
  const appUrl = String(window.LaravelApp?.appUrl || window.location.origin).replace(/\/$/, '');

  return `${appUrl}/api/v1/budgets/${id}/pdf`;
}

export const budgetPdfService = {
  async generatePdf(
    id,
    totalAmount,
    totalAmountInstallments,
    mockupPercentage,
    observations = '',
    items = [],
  ) {
    const payload = {
      total_amount: totalAmount,
      total_amount_installments: totalAmountInstallments,
      mockup_percentage: mockupPercentage,
      notes: observations || null,
      items,
    };

    const response = await http.post(resolveBudgetPdfUrl(id), payload, {
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `budget-${id}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  },
};
