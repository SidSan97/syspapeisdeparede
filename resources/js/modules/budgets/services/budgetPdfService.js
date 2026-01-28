import axios from 'axios';
import { downloadFile } from '@/utils/fileDownload';

/**
 * Service para gerenciar chamadas de API relacionadas a geração de PDF de orçamentos
 */
export function useBudgetPdfService() {
    /**
     * Gera PDF do orçamento
     * @param {number} budgetId - ID do orçamento
     * @param {number} totalAmount - Valor total à vista
     * @param {number} totalAmountInstallments - Valor total a prazo
     * @param {number} mockupPercentage - Percentual do mockup
     * @returns {Promise<void>}
     */
    async function generatePdf(budgetId, totalAmount, totalAmountInstallments, mockupPercentage) {
        const response = await axios.post(
            'v1/budgets/generate-pdf',
            {
                id: budgetId,
                total_amount: totalAmount,
                total_amount_installments: totalAmountInstallments,
                mockup_percentage: mockupPercentage,
            },
            {
                responseType: 'blob',
            }
        );

        // Usar a função utilitária de download
        downloadFile(response, `orcamento-${budgetId}.pdf`);

        // Mostrar mensagem de sucesso
        if (window.Toast) {
            window.Toast.fire({
                icon: 'success',
                title: 'PDF gerado com sucesso',
            });
        }
    }

    return {
        generatePdf
    };
}