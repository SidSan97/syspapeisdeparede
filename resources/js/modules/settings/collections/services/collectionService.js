import axios from 'axios';

/**
 * Service para gerenciar chamadas de API de coleções, categorias e imagens.
 */
export function useCollectionService() {
  async function getCollections(tree = true) {
    const { data } = await axios.get('v1/collection-categories', {
      params: { tree },
    });
    return data;
  }

  async function getCollectionChildren(categoryId) {
    const { data } = await axios.get(`v1/collection-categories/children/${categoryId}`);
    return data;
  }

  async function getCollectionCategory(categoryId) {
    const { data } = await axios.get(`v1/collection-categories/${categoryId}`);
    return data;
  }

  async function createCollection(formData) {
    formData.append('parent_id', '');
    const { data } = await axios.post('v1/collection-categories', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
  }

  async function updateCollection(id, formData) {
    formData.append('_method', 'PUT');
    const { data } = await axios.post(`v1/collection-categories/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
  }

  async function deleteCollection(id) {
    await axios.delete(`v1/collection-categories/${id}`);
  }

  async function createSubcategory(formData) {
    const { data } = await axios.post('v1/collection-categories', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
  }

  async function updateSubcategory(id, formData) {
    formData.append('_method', 'PUT');
    const { data } = await axios.post(`v1/collection-categories/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
  }

  async function deleteSubcategory(id) {
    await axios.delete(`v1/collection-categories/${id}`);
  }

  async function uploadImages(formData) {
    const { data } = await axios.post('v1/collection-images', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
  }

  async function deleteImage(id) {
    await axios.delete(`v1/collection-images/${id}`);
  }

  return {
    getCollections,
    getCollectionChildren,
    getCollectionCategory,
    createCollection,
    updateCollection,
    deleteCollection,
    createSubcategory,
    updateSubcategory,
    deleteSubcategory,
    uploadImages,
    deleteImage,
  };
}
