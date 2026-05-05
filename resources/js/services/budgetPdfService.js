import { http } from '@/lib/http';

const endpoint = '/v1/budgets';

export const budgetPdfService = {
  async generatePdf(id, totalAmount, totalAmountInstallments, mockupPercentage) {
    const payload = {
      total_amount: totalAmount,
      total_amount_installments: totalAmountInstallments,
      mockup_percentage: mockupPercentage,
    };

    const response = await http.post(`${endpoint}/${id}/pdf`, payload, {
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
