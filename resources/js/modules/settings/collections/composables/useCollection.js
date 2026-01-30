import { computed, reactive, ref } from 'vue';
import { swalConfirmation, swalSuccess, swalError } from '@/utils/alerts';
import { useCollectionService } from '../services/collectionService';

/**
 * Composable para gerenciar coleções, subcategorias e imagens (CollectionArts).
 */
export function useCollection() {
  const service = useCollectionService();

  // State: collections
  const collections = ref([]);
  const isLoadingCollections = ref(false);
  const deletingCollectionId = ref(null);
  const showCollectionModal = ref(false);
  const collectionForEdit = ref(null);

  // State: subcategories
  const collectionSubcategories = reactive({});
  const expandedCollections = ref(new Set());
  const loadingSubcategories = reactive({});
  const deletingSubcategoryId = ref(null);
  const showSubcategoryModal = ref(false);
  const subcategoryForEdit = ref(null);
  const parentCollectionForSubcategory = ref(null);

  // State: images & upload
  const collectionImages = reactive({});
  const isLoadingImages = reactive({});
  const selectedSubcategoryId = ref(null);
  const activeTab = ref('upload');
  const isUploading = ref(false);
  const deletingImageId = ref(null);
  const selectedFiles = ref([]);
  let fileIdCounter = 0;

  // --- Utils ---
  function buildStorageUrl(path) {
    if (!path) return '';
    const base = window.location.origin.replace(/\/$/, '');
    return `${base}/storage/${path.replace(/^\//, '')}`;
  }

  function resolveImageUrl(url, path) {
    if (url && /^https?:\/\//i.test(url)) return url;
    if (url && url.startsWith('/')) {
      const base = window.location.origin.replace(/\/$/, '');
      return `${base}${url}`;
    }
    return buildStorageUrl(url || path || '');
  }

  function normalizeCollection(item = {}) {
    return {
      id: Number(item.id ?? 0),
      name: (item.name ?? '').toString(),
      image_cover: item.image_cover ?? null,
      image_cover_url:
        item.image_cover_url ||
        (item.image_cover ? resolveImageUrl(null, item.image_cover) : null),
      children: item.children ?? [],
    };
  }

  function normalizeSubcategory(item = {}) {
    return {
      id: Number(item.id ?? 0),
      name: (item.name ?? '').toString(),
      parent_id: Number(item.parent_id ?? 0),
      image_cover: item.image_cover ?? null,
      image_cover_url:
        item.image_cover_url ||
        (item.image_cover ? resolveImageUrl(null, item.image_cover) : null),
      images_count: Number(item.images_count ?? 0),
    };
  }

  function sortCollections(items = []) {
    return [...items].sort((a, b) =>
      a.name.localeCompare(b.name, 'pt-BR', { sensitivity: 'base' })
    );
  }

  function formatCount(count) {
    const total = Number(count ?? 0);
    return total === 1 ? '1 imagem' : `${total} imagens`;
  }

  function getCollectionName(collectionId) {
    const c = collections.value.find((x) => x.id === collectionId);
    return c?.name ?? '—';
  }

  function getCollectionById(id) {
    if (id == null) return null;
    return collections.value.find((c) => c.id === id) ?? null;
  }

  // --- Computed ---
  const selectedSubcategory = computed(() => {
    if (!selectedSubcategoryId.value) return null;
    for (const col of collections.value) {
      const subs = collectionSubcategories[col.id] || [];
      const found = subs.find((s) => s.id === selectedSubcategoryId.value);
      if (found) return found;
    }
    return null;
  });

  const currentImages = computed(() => {
    if (!selectedSubcategoryId.value) return [];
    return collectionImages[selectedSubcategoryId.value] ?? [];
  });

  const currentLoading = computed(() => {
    if (!selectedSubcategoryId.value) return false;
    return Boolean(isLoadingImages[selectedSubcategoryId.value]);
  });

  // --- Fetch ---
  async function fetchCollections() {
    isLoadingCollections.value = true;
    try {
      const data = await service.getCollections(true);
      const payload = data?.data ?? data ?? {};
      const items = Array.isArray(payload) ? payload : payload.items ?? [];
      const roots = items.filter((i) => !i.parent_id);
      const list = roots.map(normalizeCollection);
      collections.value = sortCollections(list);
      collections.value.forEach((col) => {
        if (col.children && Array.isArray(col.children)) {
          collectionSubcategories[col.id] = col.children.map(normalizeSubcategory);
        }
      });
    } catch (e) {
      window.Swal.fire({
        title: 'Erro!',
        text: 'Não foi possível carregar as coleções.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
      collections.value = [];
    } finally {
      isLoadingCollections.value = false;
    }
  }

  async function fetchSubcategories(categoryId) {
    if (loadingSubcategories[categoryId]) return;
    loadingSubcategories[categoryId] = true;
    try {
      const data = await service.getCollectionChildren(categoryId);
      const payload = data?.data ?? data ?? [];
      const items = Array.isArray(payload) ? payload : [];
      collectionSubcategories[categoryId] = items.map(normalizeSubcategory);
    } catch (e) {
      window.Swal.fire({
        title: 'Erro!',
        text: 'Não foi possível carregar as subcategorias.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
      collectionSubcategories[categoryId] = [];
    } finally {
      loadingSubcategories[categoryId] = false;
    }
  }

  async function fetchSubcategoryImages(categoryId) {
    if (!categoryId || isLoadingImages[categoryId]) return;
    isLoadingImages[categoryId] = true;
    try {
      const data = await service.getCollectionCategory(categoryId);
      const payload = data?.data ?? data ?? {};
      const imgs = Array.isArray(payload.images) ? payload.images : [];
      collectionImages[categoryId] = imgs.map((img) => ({
        id: Number(img.id ?? 0),
        name: img.name ?? '',
        path_name: img.path_name ?? img.pathName ?? '',
        url: resolveImageUrl(img.url, img.path_name ?? img.pathName ?? ''),
      }));
    } catch (e) {
      collectionImages[categoryId] = [];
      window.Swal.fire({
        title: 'Erro!',
        text: 'Não foi possível carregar as imagens.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
    } finally {
      isLoadingImages[categoryId] = false;
    }
  }

  // --- Collection actions ---
  async function toggleCollection(collectionId) {
    if (expandedCollections.value.has(collectionId)) {
      expandedCollections.value.delete(collectionId);
    } else {
      expandedCollections.value.add(collectionId);
      if (!collectionSubcategories[collectionId]) {
        await fetchSubcategories(collectionId);
      }
    }
  }

  function openCollectionModal(collection = null) {
    collectionForEdit.value = collection;
    showCollectionModal.value = true;
  }

  function closeCollectionModal() {
    showCollectionModal.value = false;
    collectionForEdit.value = null;
  }

  function onCollectionSaved(saved) {
    if (!saved?.id) {
      fetchCollections();
    } else {
      const idx = collections.value.findIndex((i) => i.id === saved.id);
      if (idx !== -1) {
        const next = [...collections.value];
        next.splice(idx, 1, saved);
        collections.value = sortCollections(next);
      } else {
        collections.value = sortCollections([saved, ...collections.value]);
      }
    }
    closeCollectionModal();
  }

  async function confirmDeleteCollection(collection) {
    if (!collection?.id || deletingCollectionId.value !== null) return;
    const ok = await swalConfirmation(
      'Excluir coleção?',
      'Essa ação é <strong>irreversível!</strong>',
      'warning',
      'Excluir',
      'Cancelar'
    );
    if (!ok.isConfirmed) return;
    deletingCollectionId.value = collection.id;
    try {
      await service.deleteCollection(collection.id);
      collections.value = collections.value.filter((i) => i.id !== collection.id);
      swalSuccess('Coleção excluída com sucesso.', 'Coleção excluída!');
      if (showCollectionModal.value && collectionForEdit.value?.id === collection.id) {
        closeCollectionModal();
      }
    } catch (e) {
      const msg = e?.response?.data?.message ?? 'Não foi possível excluir a coleção.';
      swalError(msg);
    } finally {
      deletingCollectionId.value = null;
    }
  }

  // --- Subcategory actions ---
  async function openSubcategoryModal(subcategory, parentCollection) {
    const parent = parentCollection ?? (subcategory ? getCollectionById(subcategory.parent_id) : null);
    if (!parent?.id && !subcategory?.parent_id) return;
    if (!subcategory && parent) {
      expandedCollections.value = new Set([...expandedCollections.value, parent.id]);
      if (!collectionSubcategories[parent.id]) await fetchSubcategories(parent.id);
    }
    subcategoryForEdit.value = subcategory ?? null;
    parentCollectionForSubcategory.value = parent ?? null;
    showSubcategoryModal.value = true;
  }

  function closeSubcategoryModal() {
    showSubcategoryModal.value = false;
    subcategoryForEdit.value = null;
    parentCollectionForSubcategory.value = null;
  }

  async function onSubcategorySaved(saved) {
    const cid = saved.parent_id;
    if (!collectionSubcategories[cid]) collectionSubcategories[cid] = [];
    const idx = collectionSubcategories[cid].findIndex((i) => i.id === saved.id);
    if (idx !== -1) {
      collectionSubcategories[cid].splice(idx, 1, saved);
    } else {
      collectionSubcategories[cid].push(saved);
    }
    const wasCreate = !subcategoryForEdit.value?.id;
    closeSubcategoryModal();
    if (wasCreate) {
      selectedSubcategoryId.value = saved.id;
      activeTab.value = 'upload';
      await fetchSubcategoryImages(saved.id);
    }
  }

  async function selectSubcategory(subcategory) {
    if (!subcategory?.id) return;
    selectedSubcategoryId.value = subcategory.id;
    activeTab.value = 'upload';
    await fetchSubcategoryImages(subcategory.id);
  }

  function clearSubcategorySelection() {
    selectedSubcategoryId.value = null;
    activeTab.value = 'upload';
  }

  async function confirmDeleteSubcategory(subcategory) {
    if (!subcategory?.id || deletingSubcategoryId.value !== null) return;
    const ok = await swalConfirmation(
      'Excluir subcategoria?',
      'Essa ação é <strong>irreversível!</strong> Todas as imagens associadas serão removidas.',
      'warning',
      'Excluir',
      'Cancelar'
    );
    if (!ok.isConfirmed) return;
    deletingSubcategoryId.value = subcategory.id;
    try {
      await service.deleteSubcategory(subcategory.id);
      const cid = subcategory.parent_id;
      if (collectionSubcategories[cid]) {
        collectionSubcategories[cid] = collectionSubcategories[cid].filter(
          (i) => i.id !== subcategory.id
        );
      }
      if (selectedSubcategoryId.value === subcategory.id) clearSubcategorySelection();
      swalSuccess('Subcategoria excluída com sucesso.', 'Subcategoria excluída!');
    } catch (e) {
      const msg = e?.response?.data?.message ?? 'Não foi possível excluir a subcategoria.';
      swalError(msg);
    } finally {
      deletingSubcategoryId.value = null;
    }
  }

  // --- Image / upload actions ---
  function handleFileChange(event) {
    const files = event?.target?.files ? Array.from(event.target.files) : [];
    selectedFiles.value = files.map((file) => {
      const name = file.name;
      const dot = name.lastIndexOf('.');
      const base = dot > 0 ? name.substring(0, dot) : name;
      return { id: ++fileIdCounter, file, name: base || '' };
    });
  }

  function resetForm(fileInputRef = null) {
    selectedFiles.value = [];
    fileIdCounter = 0;
    const el = fileInputRef?.value ?? fileInputRef;
    if (el && typeof el !== 'string' && el.tagName) {
      el.value = '';
    }
  }

  async function handleUpload(fileInputRef = null) {
    if (isUploading.value || currentLoading.value) return;
    if (!selectedSubcategoryId.value) {
      window.Swal.fire({
        title: 'Erro!',
        text: 'Selecione uma subcategoria.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
      return;
    }
    if (!selectedFiles.value.length) {
      window.Swal.fire({
        title: 'Erro!',
        text: 'Selecione ao menos uma imagem para enviar.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
      return;
    }
    const missing = selectedFiles.value.filter((i) => !i.name?.trim());
    if (missing.length) {
      window.Swal.fire({
        title: 'Erro!',
        text: 'Preencha o nome para todas as imagens.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
      return;
    }
    isUploading.value = true;
    try {
      const formData = new FormData();
      formData.append('collection_category_id', selectedSubcategoryId.value);
      selectedFiles.value.forEach((item, i) => {
        formData.append('images[]', item.file);
        formData.append(`names[${i}]`, item.name.trim());
      });

      const res = await service.uploadImages(formData);

      if (res?.success === false) {
        throw new Error(res?.message ?? 'Erro ao enviar imagens.');
      }

      const list = res?.data ?? [];

      if (Array.isArray(list) && list.length && Array.isArray(list[0]?.images)) {
        collectionImages[selectedSubcategoryId.value] = list[0].images.map((img) => ({
          id: Number(img.id ?? 0),
          name: img.name ?? '',
          path_name: img.path_name ?? img.pathName ?? '',
          url: resolveImageUrl(img.url, img.path_name ?? img.pathName ?? ''),
        }));
        const sub = selectedSubcategory.value;
        if (sub) sub.images_count = collectionImages[selectedSubcategoryId.value].length;
      } else if (selectedSubcategoryId.value) {
        await fetchSubcategoryImages(selectedSubcategoryId.value);
      }

      window.Swal.fire({
        title: 'Imagens adicionadas!',
        text: res?.message ?? 'Imagens adicionadas com sucesso.',
        icon: 'success',
        confirmButtonText: 'Entendi!',
      });
      resetForm(fileInputRef);
      activeTab.value = 'images';

    } catch (e) {
      const msg =
        e?.message ??
        e?.response?.data?.message ??
        e?.response?.data?.errors?.images?.[0] ??
        'Não foi possível enviar as imagens.';
      window.Swal.fire({ title: 'Erro!', text: msg, icon: 'error', confirmButtonText: 'Entendi!' });
    } finally {
      isUploading.value = false;
    }
  }

  async function confirmDeleteImage(image) {
    if (!image?.id || deletingImageId.value !== null) return;
    const ok = await swalConfirmation(
      'Remover imagem?',
      'Essa ação é <strong>irreversível!</strong>',
      'warning',
      'Remover',
      'Cancelar'
    );
    if (!ok.isConfirmed) return;
    deletingImageId.value = image.id;
    try {
      await service.deleteImage(image.id);
      if (selectedSubcategoryId.value) {
        collectionImages[selectedSubcategoryId.value] = (
          collectionImages[selectedSubcategoryId.value] ?? []
        ).filter((i) => i.id !== image.id);
        const sub = selectedSubcategory.value;
        if (sub) sub.images_count = collectionImages[selectedSubcategoryId.value].length;
      }
      swalSuccess('Imagem removida com sucesso.', 'Imagem removida!');
    } catch (e) {
      const msg = e?.response?.data?.message ?? 'Não foi possível remover a imagem.';
      swalError(msg);
    } finally {
      deletingImageId.value = null;
    }
  }

  return {
    // State
    collections,
    isLoadingCollections,
    deletingCollectionId,
    showCollectionModal,
    collectionForEdit,
    collectionSubcategories,
    expandedCollections,
    loadingSubcategories,
    deletingSubcategoryId,
    showSubcategoryModal,
    subcategoryForEdit,
    parentCollectionForSubcategory,
    collectionImages,
    isLoadingImages,
    selectedSubcategoryId,
    activeTab,
    isUploading,
    deletingImageId,
    selectedFiles,
    // Computed
    selectedSubcategory,
    currentImages,
    currentLoading,
    // Utils
    formatCount,
    getCollectionName,
    getCollectionById,
    // Fetch
    fetchCollections,
    fetchSubcategories,
    fetchSubcategoryImages,
    // Collection
    toggleCollection,
    openCollectionModal,
    closeCollectionModal,
    onCollectionSaved,
    confirmDeleteCollection,
    // Subcategory
    openSubcategoryModal,
    closeSubcategoryModal,
    onSubcategorySaved,
    selectSubcategory,
    clearSubcategorySelection,
    confirmDeleteSubcategory,
    // Images / upload
    handleFileChange,
    resetForm,
    handleUpload,
    confirmDeleteImage,
  };
}
