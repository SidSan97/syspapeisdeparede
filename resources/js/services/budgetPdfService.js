import { http } from '@/lib/http';

export const budgetPdfService = {
  async generatePdf(id, totalAmount, totalAmountInstallments, mockupPercentage, observations = '') {
    const payload = {
      total_amount: totalAmount,
      total_amount_installments: totalAmountInstallments,
      mockup_percentage: mockupPercentage,
      notes: observations || null,
    };

    // Rota API: POST /api/v1/budgets/{budget}/pdf
    const response = await http.post(`/api/v1/budgets/${id}/pdf`, payload, {
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
