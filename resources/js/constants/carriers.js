/**
 * Lista de transportadoras disponíveis
 * Baseado em app/Services/TinyErpService.php
 */
export const CARRIERS = [
  'Correios',
  'Transportadora',
  'Mercado Envios',
  'Correios E-fulfillment',
  'B2W Entrega',
  'Customizada',
  'Conectalá Etiquetas',
  'Jadlog',
  'Sem Frete',
  'Total Express',
  'Gateway logistico',
  'Magalu Entregas',
  'Magalu Fulfillment',
  'Shopee Envios',
  'Netshoes Entregas',
  'Via Varejo Envvias',
  'AliExpress Envios',
  'Madeira Envios',
  'Loggi',
  'Amazon DBA',
  'Magalu Entregas por Netshoes',
  'Olist',
];

/**
 * Retorna a lista de transportadoras ordenadas alfabeticamente
 */
export function getCarriersList() {
  return [...CARRIERS].sort();
}
