import { http } from '@/lib/http';

export const DEFAULT_COVER = '/assets/img/no-image.jpg';

function buildStorageUrl(path) {
  if (!path) return DEFAULT_COVER;
  if (/^https?:\/\//i.test(path)) return path;
  const baseUrl = window.location.origin.replace(/\/$/, '');
  return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
}

function resolveImageUrl(url, pathName) {
  if (url && /^https?:\/\//i.test(url)) return url;
  if (url && url.startsWith('/')) {
    const baseUrl = window.location.origin.replace(/\/$/, '');
    return `${baseUrl}${url}`;
  }
  return buildStorageUrl(url || pathName || '');
}

function normalizeImage(image) {
  return {
    id: Number(image.id ?? 0),
    name: image.name ?? '',
    path_name: image.path_name ?? image.pathName ?? '',
    url: resolveImageUrl(image.url, image.path_name ?? image.pathName ?? ''),
    is_favorited: true,
  };
}

/**
 * Service para gerenciar imagens favoritas da coleção
 */
export function useFavoriteImagesService() {
  async function fetchFavoriteImages() {
    const { data } = await http.get('v1/my-favorite-collection-images');
    const payload = data?.data ?? data ?? [];
    const imagesList = Array.isArray(payload) ? payload : [];
    return imagesList.map(normalizeImage);
  }

  async function toggleFavorite(imageId) {
    const { data } = await http.post(`v1/collection-images/${imageId}/toggle-favorite`);
    return data?.data?.is_favorited ?? false;
  }

  async function downloadImages(images) {
    for (let i = 0; i < images.length; i++) {
      const img = images[i];
      const name = img.name || img.path_name || `imagem-${img.id}`;
      const ext = (name.match(/\.(jpe?g|png|gif|webp)$/i) || [])[1] || 'jpg';
      const baseName = name.replace(/\.(jpe?g|png|gif|webp)$/i, '') || `imagem-${img.id}`;
      const filename = `${baseName}.${ext}`;

      try {
        const res = await fetch(img.url);
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      } catch {
        window.open(img.url, '_blank');
      }

      if (i < images.length - 1) {
        await new Promise((r) => setTimeout(r, 300));
      }
    }
  }

  return {
    fetchFavoriteImages,
    toggleFavorite,
    downloadImages,
  };
}
