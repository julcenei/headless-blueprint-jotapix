import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

export const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');

/** Conteúdo real da Elotec (produtos, serviços, setores, contatos). */
export const data = JSON.parse(readFileSync(join(ROOT, 'data', 'dados.json'), 'utf8'));

export const SITE = {
  url: 'https://www.elotec.ind.br',
  name: 'Elotec — Serviços Técnicos em Correias',
  description:
    'Soluções completas em correias transportadoras: fornecimento, instalação, conserto e manutenção em PU, PVC, borracha, nylon e transmissão. Assistência técnica 24h em todo o Brasil.',
};

/** Escapa texto para inserção segura em HTML. */
export function esc(value = '') {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/** Os caminhos do JSON começam com "/" — o site é estático e relativo. */
export function img(path = '') {
  return String(path).replace(/^\//, '');
}

/** Mantém os links relativos (o site abre direto do sistema de arquivos). */
export function href(path = '') {
  return path;
}

/** Link do WhatsApp com mensagem padrão (ou personalizada). */
export function waLink(text) {
  const msg = text || 'Olá! Vim pelo site da Elotec e gostaria de atendimento.';
  return `https://wa.me/${data.contact.whatsapp}?text=${encodeURIComponent(msg)}`;
}

/** Buscas por slug usadas nos blocos de "relacionados". */
export const bySlug = {
  product: (slug) => data.products.find((p) => p.slug === slug),
  service: (slug) => data.services.find((s) => s.slug === slug),
  sector: (slug) => data.sectors.find((s) => s.slug === slug),
};
