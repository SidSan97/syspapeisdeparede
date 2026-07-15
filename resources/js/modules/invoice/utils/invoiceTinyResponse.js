/**
 * Normaliza retorno da API Tiny (estrutura como teste.json → data.notas_fiscais).
 *
 * @param {object|null|undefined} retorno
 * @returns {object[]}
 */
export function parseNotasFiscaisFromTinyRetorno(retorno) {
  if (!retorno || typeof retorno !== 'object') {
    return [];
  }
  const raw = retorno.notas_fiscais;
  if (!Array.isArray(raw)) {
    return [];
  }
  return raw.map((item) => item?.nota_fiscal).filter(Boolean);
}

/**
 * @param {object} retorno
 * @returns {string|null}
 */
export function messageFromTinyRetornoErro(retorno) {
  if (!retorno || String(retorno.status || '').toLowerCase() !== 'erro') {
    return null;
  }
  const erros = retorno.erros;
  if (Array.isArray(erros)) {
    const msg = erros
      .map((e) => e?.erro || e?.mensagem || JSON.stringify(e))
      .join(' ')
      .trim();
    return msg || 'Erro ao consultar nota fiscal.';
  }
  if (typeof erros === 'string') {
    return erros;
  }
  return 'Erro ao consultar nota fiscal no Tiny.';
}
