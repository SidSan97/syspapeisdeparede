/**
 * Utilitários para normalização e manipulação de dados de modelos
 */

/**
 * Normaliza um arquivo para o formato padrão
 * @param {Object} file - Objeto do arquivo
 * @returns {Object} Arquivo normalizado
 */
export function normalizeFile(file = {}) {
    return {
        id: file.id ?? null,
        name: file.name ?? file.fileName ?? file.file_name ?? '',
        url: file.url ?? file.fileUrl ?? null,
    };
}

/**
 * Normaliza um modelo para o formato padrão
 * @param {Object} model - Objeto do modelo
 * @returns {Object} Modelo normalizado
 */
export function normalizeModel(model = {}) {
    const normalizedFiles = Array.isArray(model.files)
        ? model.files.map((file) => normalizeFile(file))
        : [];

    if (normalizedFiles.length === 0 && (model.fileName || model.fileUrl)) {
        normalizedFiles.push(
            normalizeFile({
                id: null,
                name: model.fileName,
                url: model.fileUrl,
            })
        );
    }

    return {
        id: model.id,
        name: model.name ?? '',
        value: Number(model.value ?? 0),
        deadline: Number(model.deadline ?? 0),
        requests: {
            link: Boolean(model?.requests?.link),
            comment: Boolean(model?.requests?.comment),
            file: Boolean(model?.requests?.file),
            collection: Boolean(
                model?.requests?.collection ??
                model?.requestCollection ??
                model?.request_collection
            ),
            layout: Boolean(model?.requests?.layout ?? model?.requestLayout ?? model?.request_layout),
        },
        link: model.link ?? '',
        comment: model.comment ?? '',
        files: normalizedFiles,
    };
}

/**
 * Extrai itens e metadados de paginação da resposta da API
 * @param {Object|Array} payload - Resposta da API
 * @param {Object} currentPagination - Paginação atual (para fallback)
 * @returns {Object} Objeto com items e meta
 */
export function extractItemsFromResponse(payload, currentPagination = {}) {
    if (!payload) {
        return {
            items: [],
            meta: {
                current_page: 1,
                per_page: 15,
                total: 0,
                last_page: 1,
                ...currentPagination,
            },
        };
    }

    if (Array.isArray(payload)) {
        return {
            items: payload,
            meta: {
                current_page: 1,
                per_page: payload.length,
                total: payload.length,
                last_page: 1,
                ...currentPagination,
            },
        };
    }

    const resourceItems = payload.items?.data ?? payload.items ?? [];
    const meta =
        payload.meta ??
        payload.items?.meta ?? {
            current_page: payload.items?.current_page ?? 1,
            per_page: payload.items?.per_page ?? resourceItems.length,
            total: payload.items?.total ?? resourceItems.length,
            last_page: payload.items?.last_page ?? 1,
        };

    return {
        items: resourceItems,
        meta,
    };
}

/**
 * Cria um FormData a partir dos dados do formulário
 * @param {Object} formData - Dados do formulário
 * @returns {FormData} FormData pronto para envio
 */
export function buildModelFormData(formData) {
    const form = new FormData();

    form.append('name', formData.name ?? '');
    form.append('value', formData.value ?? '');
    form.append('deadline', formData.deadline ?? '');
    form.append('requests[link]', formData.requests?.link ? 1 : 0);
    form.append('requests[comment]', formData.requests?.comment ? 1 : 0);
    form.append('requests[file]', formData.requests?.file ? 1 : 0);
    form.append('requests[collection]', formData.requests?.collection ? 1 : 0);
    form.append('requests[layout]', formData.requests?.layout ? 1 : 0);
    return form;
}

