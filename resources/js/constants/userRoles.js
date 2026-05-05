export const USER_ROLE = Object.freeze({
  RESELLER: 'reseller',
  DESIGNER: 'designer',
  PRODUCTION: 'production',
  COMMERCIAL: 'commercial',
  EXPEDITION: 'expedition',
  REPRESENTATIVES: 'representatives',
  ARCHITECTS: 'architects',
  ADMIN: 'admin',
});

export const USER_ROLE_LABELS = {
  [USER_ROLE.RESELLER]: 'Revendedor',
  [USER_ROLE.DESIGNER]: 'Designer',
  [USER_ROLE.PRODUCTION]: 'Produção',
  [USER_ROLE.COMMERCIAL]: 'Comercial',
  [USER_ROLE.EXPEDITION]: 'Expedição',
  [USER_ROLE.REPRESENTATIVES]: 'Representante',
  [USER_ROLE.ARCHITECTS]: 'Arquiteto',
  [USER_ROLE.ADMIN]: 'Administrador',
};

export const USER_ROLE_OPTIONS = Object.values(USER_ROLE).map((role) => ({
  value: role,
  label: USER_ROLE_LABELS[role],
}));
