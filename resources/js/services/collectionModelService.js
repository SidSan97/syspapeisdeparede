import { createCrudService } from './baseCrudService';

const endpoint = '/v1/collection-models';

export const collectionModelService = createCrudService(endpoint);
