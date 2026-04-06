/**
 * Campos de referência do modelo na parede (BudgetWall / payload de orçamento).
 * Exibe apenas o que estiver preenchido no front.
 */

const FIELD_LABELS = {
  comment_referring_model: 'Descrição do modelo',
  request_layout_referring_model: 'Solicitação de layout',
  link_referring_model: 'Link de referência',
  files_referring_model: 'Arquivos de referência',
  collection_referring_model: 'Arte da coleção (referência)',
};

const FIELD_ORDER = [
  'comment_referring_model',
  'request_layout_referring_model',
  'link_referring_model',
  'files_referring_model',
  'collection_referring_model',
];

function wallField(wall, snakeKey) {
  if (!wall || typeof wall !== 'object') {
    return undefined;
  }
  const camel = snakeKey.replace(/_([a-z])/g, (_, c) => c.toUpperCase());
  if (Object.prototype.hasOwnProperty.call(wall, snakeKey)) {
    return wall[snakeKey];
  }
  if (Object.prototype.hasOwnProperty.call(wall, camel)) {
    return wall[camel];
  }
  return undefined;
}

/**
 * Valor de `collection_referring_model` costuma ser o ID em `collection_images`.
 * @param {unknown} value
 * @returns {number|null}
 */
export function parseCollectionReferringModelImageId(value) {
  if (value === null || value === undefined) {
    return null;
  }
  if (typeof value === 'number') {
    return Number.isInteger(value) && value > 0 ? value : null;
  }
  const s = String(value).trim();
  if (s === '' || !/^\d+$/.test(s)) {
    return null;
  }
  const n = parseInt(s, 10);
  return n > 0 ? n : null;
}

function basenamePath(p) {
  if (p == null || p === '') {
    return 'Arquivo';
  }
  const segments = String(p).split(/[/\\]/);
  return segments[segments.length - 1] || 'Arquivo';
}

/**
 * Resolve URL pública para path ou objeto com path/url (espelha useFormatting.resolveStorageUrl).
 * @param {string|{ path?: string, url?: string, name?: string }} input
 * @returns {string}
 */
export function resolveReferringAssetUrl(input) {
  if (input == null || input === '') {
    return '#';
  }
  let path = input;
  if (typeof input === 'object') {
    if (input.url) {
      return input.url;
    }
    path = input.path ?? input.file_path ?? '';
  }
  if (!path || typeof path !== 'string') {
    return '#';
  }
  if (/^https?:\/\//i.test(path)) {
    return path;
  }
  if (typeof window === 'undefined' || !window.location?.origin) {
    return `/storage/${String(path).replace(/^storage\//, '')}`;
  }
  const baseUrl = window.location.origin.replace(/\/$/, '');
  return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
}

/**
 * @param {unknown} raw
 * @returns {{ url: string, label: string }[]}
 */
export function normalizeReferringFileItems(raw) {
  if (raw == null) {
    return [];
  }
  const list = Array.isArray(raw) ? raw : [raw];
  const out = [];
  for (const item of list) {
    if (item == null || item === '') {
      continue;
    }
    if (typeof item === 'string') {
      const url = resolveReferringAssetUrl(item);
      if (url && url !== '#') {
        out.push({ url, label: basenamePath(item) });
      }
      continue;
    }
    if (typeof item === 'object' && !Array.isArray(item)) {
      const path = item.path ?? item.file_path ?? '';
      const url =
        item.url && typeof item.url === 'string'
          ? item.url
          : resolveReferringAssetUrl(path || item);
      const label =
        (item.name && String(item.name)) ||
        (item.file_name && String(item.file_name)) ||
        basenamePath(path || '');
      if (url && url !== '#') {
        out.push({ url, label: label || 'Arquivo' });
      }
    }
  }
  return out;
}

/**
 * @param {unknown} value
 * @param {string} fieldKey
 * @returns {boolean}
 */
export function isReferringFieldPresent(value, fieldKey) {
  if (value === null || value === undefined) {
    return false;
  }
  if (fieldKey === 'files_referring_model' || fieldKey === 'request_layout_referring_model') {
    if (!Array.isArray(value)) {
      return false;
    }
    return normalizeReferringFileItems(value).length > 0;
  }
  if (typeof value === 'string') {
    return value.trim() !== '';
  }
  if (typeof value === 'number') {
    return !Number.isNaN(value);
  }
  return false;
}

/**
 * Blocos ordenados para exibição (somente campos preenchidos).
 * @param {Record<string, unknown>|null|undefined} wall
 * @returns {Array<
 *   | { key: string, label: string, type: 'text', text: string }
 *   | { key: string, label: string, type: 'link', href: string, text: string }
 *   | { key: string, label: string, type: 'files', items: { url: string, label: string }[] }
 *   | { key: string, label: string, type: 'collection_image', imageId: number }
 * >}
 */
export function getWallModelReferringBlocks(wall) {
  if (!wall || typeof wall !== 'object') {
    return [];
  }

  const blocks = [];

  for (const key of FIELD_ORDER) {
    const val = wallField(wall, key);
    if (!isReferringFieldPresent(val, key)) {
      continue;
    }

    const label = FIELD_LABELS[key] ?? key;

    if (key === 'link_referring_model') {
      const text = String(val).trim();
      blocks.push({ key, label, type: 'link', href: text, text });
      continue;
    }

    if (key === 'comment_referring_model') {
      blocks.push({ key, label, type: 'text', text: String(val).trim() });
      continue;
    }

    if (key === 'collection_referring_model') {
      const imageId = parseCollectionReferringModelImageId(val);
      if (imageId !== null) {
        blocks.push({ key, label, type: 'collection_image', imageId });
      } else {
        blocks.push({ key, label, type: 'text', text: String(val).trim() });
      }
      continue;
    }

    if (key === 'files_referring_model' || key === 'request_layout_referring_model') {
      const items = normalizeReferringFileItems(val);
      if (items.length === 0) {
        continue;
      }
      blocks.push({ key, label, type: 'files', items });
    }
  }

  return blocks;
}

/**
 * @param {Record<string, unknown>|null|undefined} wall
 * @returns {boolean}
 */
export function hasWallModelReferringContent(wall) {
  return getWallModelReferringBlocks(wall).length > 0;
}
