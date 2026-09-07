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
    'Soluções completas em correias transportadoras: fornecimento, instalação, conserto e manutenção em PU, PVC, borracha, nylon e transmissão. Atendimento emergencial fora do horário comercial, em todo o Brasil.',
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

/** Converte um telefone exibido ("(49) 3328-6223") em href tel: com DDI. */
export function telLink(display) {
  return 'tel:+55' + String(display).replace(/\D/g, '');
}

/* Mensagem de abertura da página que está sendo gerada. O build renderiza uma
   página por vez, então guardar isso aqui evita passar o texto por dentro de
   head/header/footer/ctaBand só para chegar aos links. */
let contextoWa = '';
export function setContextoWa(valor) {
  contextoWa = valor || '';
}

/**
 * Link do WhatsApp. Sem argumento, a mensagem já diz de onde a pessoa veio e
 * termina com dois-pontos: o cursor cai depois deles e ela continua a frase.
 * Uma pessoa recebe todas as mensagens do site, sem triagem — cada contexto
 * que o link já entrega é uma pergunta que ela não precisa fazer.
 */
export function waLink(text) {
  const msg = text || contextoWa || 'Olá! Vim pelo site da Elotec. Preciso de: ';
  return `https://wa.me/${data.contact.whatsapp}?text=${encodeURIComponent(msg)}`;
}

/** Buscas por slug usadas nos blocos de "relacionados". */
export const bySlug = {
  product: (slug) => data.products.find((p) => p.slug === slug),
  service: (slug) => data.services.find((s) => s.slug === slug),
  sector: (slug) => data.sectors.find((s) => s.slug === slug),
};
