
export function downloadBlob(blob, filename) {
    // Verificar se o navegador suporta a API de download
    if (typeof window === 'undefined' || !blob) {
        console.error('Download não suportado ou blob inválido');
        return;
    }

    const link = document.createElement('a');

    const url = URL.createObjectURL(blob);

    link.href = url;
    link.download = filename || 'download';
    link.style.display = 'none';

    document.body.appendChild(link);

    // Disparar o download imediatamente
    link.click();

    setTimeout(() => {
        if (link.parentNode) {
            document.body.removeChild(link);
        }
        // Revogar a URL do blob para liberar memória
        URL.revokeObjectURL(url);
    }, 200);
}

export function downloadFile(data, filename) {
    let blob;

    if (data instanceof Blob) {
        blob = data;
    } else if (data?.data instanceof Blob) {
        blob = data.data;
    } else {
        blob = new Blob([data], { type: 'application/pdf' });
    }

    downloadBlob(blob, filename);
}
