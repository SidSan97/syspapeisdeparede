export function useFormatting() {
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

    function formatNumber(value) {
        if (value === null || value === undefined) {
            return '-';
        }
        const numericValue = Number(value);
        return Number.isFinite(numericValue) ? numericValue.toFixed(2) : value;
    }

    function formatDeliveryTime(days) {
        if (!days) {
            return 'Não informado';
        }
        return `${days} ${days === 1 ? 'dia' : 'dias'}`;
    }

    function formatPaymentMethod(method) {
        const methods = {
            credit_card: 'Cartão',
            pix: 'PIX',
            installment: 'Parcelado',
        };
        return methods[method] || method || '-';
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }
        try {
            const date = value instanceof Date ? value : new Date(value);
            if (Number.isNaN(date.getTime())) {
                return typeof value === 'string' ? value : '-';
            }
            return date.toLocaleString('pt-BR');
        } catch (error) {
            return typeof value === 'string' ? value : '-';
        }
    }

    function formatDateOnly(value) {
        if (!value) {
            return '-';
        }
        try {
            const date = value instanceof Date ? value : new Date(value);
            if (Number.isNaN(date.getTime())) {
                return typeof value === 'string' ? value : '-';
            }
            return date.toLocaleDateString('pt-BR');
        } catch (error) {
            return typeof value === 'string' ? value : '-';
        }
    }

    function formatDirection(direction) {
        const directions = {
            'left-to-right': 'Da esquerda para direita',
            'right-to-left': 'Da direita para esquerda',
        };
        return directions[direction] || direction || '-';
    }

    function resolveStorageUrl(path) {
        if (!path) {
            return '#';
        }
        if (typeof path === 'object' && path.url) {
            return path.url;
        }
        if (typeof path === 'object' && path.path) {
            path = path.path;
        }
        if (/^https?:\/\//i.test(path)) {
            return path;
        }
        const baseUrl = window.location.origin.replace(/\/$/, '');
        return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
    }

    function extractFileName(file) {
        if (!file) {
            return 'Arquivo';
        }
        if (typeof file === 'object') {
            return file.name || file.fileName || 'Arquivo';
        }
        const segments = String(file).split('/');
        return segments[segments.length - 1] ?? file;
    }

    function resolveImageUrl(path) {
        if (!path) {
            return '';
        }
        if (/^https?:\/\//i.test(path)) {
            return path;
        }
        const baseUrl = window.location.origin.replace(/\/$/, '');
        return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
    }

    function formatPhone(phone) {
        if (!phone) return '—';
        const cleaned = phone.replace(/\D/g, '');
        if (cleaned.length === 10) {
            return cleaned.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        }
        if (cleaned.length === 11) {
            return cleaned.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
        return phone;
    }

    function formatAddressLine1(dropshipping) {
        if (!dropshipping) return '—';
        const parts = [];
        if (dropshipping.public_space) {
            parts.push(dropshipping.public_space);
        }
        if (dropshipping.number) {
            parts.push(`Nº ${dropshipping.number}`);
        }
        if (dropshipping.complement) {
            parts.push(dropshipping.complement);
        }
        if (dropshipping.neighborhood) {
            parts.push(`Bairro: ${dropshipping.neighborhood}`);
        }
        return parts.length > 0 ? parts.join('. ') : '—';
    }

    function formatAddressLine2(dropshipping) {
        if (!dropshipping) return '';
        const parts = [];
        if (dropshipping.cep) {
            const cep = dropshipping.cep.replace(/\D/g, '');
            const formattedCep = cep.length === 8 ? cep.replace(/(\d{5})(\d{3})/, '$1-$2') : dropshipping.cep;
            parts.push(formattedCep);
        }
        if (dropshipping.city && dropshipping.uf) {
            parts.push(`${dropshipping.city}, ${dropshipping.uf}`);
        }
        return parts.length > 0 ? parts.join(' - ') : '';
    }

    function formatEstimatedDate(deliveryTime) {
        if (!deliveryTime) return '—';
        const today = new Date();
        const estimated = new Date(today);
        estimated.setDate(today.getDate() + parseInt(deliveryTime));
        return estimated.toLocaleDateString('pt-BR');
    }

    return {
        formatCurrency,
        formatNumber,
        formatDeliveryTime,
        formatPaymentMethod,
        formatDate,
        formatDateOnly,
        formatDirection,
        resolveStorageUrl,
        extractFileName,
        resolveImageUrl,
        formatPhone,
        formatAddressLine1,
        formatAddressLine2,
        formatEstimatedDate,
    };
}

