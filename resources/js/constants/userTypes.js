/**
 * User Type Constants
 * 
 * Mapeia os IDs dos tipos de usuário da tabela type_users
 * para as roles do Laravel Permissions.
 */
export const USER_TYPES = {
  ADMIN: 1,
  RESELLER: 2,
  DESIGNER: 3,
  PRODUCTION: 4,
  COMMERCIAL: 5,
  EXPEDITION: 6,
  REPRESENTATIVES: 7,
  ARCHITECTS: 8,
};

/**
 * Mapeamento de IDs para roles
 */
export const ROLE_MAP = {
  [USER_TYPES.ADMIN]: 'admin',
  [USER_TYPES.RESELLER]: 'reseller',
  [USER_TYPES.DESIGNER]: 'designer',
  [USER_TYPES.PRODUCTION]: 'production',
  [USER_TYPES.COMMERCIAL]: 'commercial',
  [USER_TYPES.EXPEDITION]: 'expedition',
  [USER_TYPES.REPRESENTATIVES]: 'representatives',
  [USER_TYPES.ARCHITECTS]: 'architects',
};

/**
 * Obter o nome da role baseado no user_type_id
 */
export function getRoleName(userTypeId) {
  return ROLE_MAP[userTypeId] || null;
}

/**
 * Obter o user_type_id baseado no nome da role
 */
export function getUserTypeId(roleName) {
  const entries = Object.entries(ROLE_MAP);
  const found = entries.find(([_, role]) => role === roleName);
  return found ? Number(found[0]) : null;
}

/**
 * Verificar se um user_type_id é válido
 */
export function isValidUserType(userTypeId) {
  return Object.values(USER_TYPES).includes(userTypeId);
}

