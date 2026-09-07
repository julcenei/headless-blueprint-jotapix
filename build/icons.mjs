/**
 * Ícones em viewBox 24x24.
 *
 * Os genéricos vêm da Lucide v0.469.0 (https://lucide.dev), licença ISC —
 * cópia em `licencas/LUCIDE-ISC.txt`. Os paths ficam embutidos aqui porque o
 * site é HTML/CSS/JS puro, aberto direto do sistema de arquivos: nada pode
 * depender de CDN nem de requisição em runtime.
 *
 * Os de domínio — elevador de canecas, talisca, correia lonada, tração
 * positiva — são desenhados no mesmo grid, porque nenhuma biblioteca os tem e
 * são justamente os que carregam a especificidade técnica do catálogo.
 */

/** Traço renderizado, em px. Constante em qualquer tamanho: um chevron de 15px
 *  e uma chave de 26px precisam ter o mesmo peso ótico, e é essa constância que
 *  faz o conjunto ler como um sistema em vez de um ajuntamento. */
const TRACO = 1.7;

/** Escala de tamanhos. Cinco degraus, não dez: `icon()` aceita qualquer número,
 *  mas fora daqui não deveria haver motivo. Antes o site usava 14, 15, 16, 17,
 *  18, 20, 22, 24, 26 e 28 — quatro deles uma única vez. */
export const TAM = {
  inline: 15, // dentro de uma linha de texto miúda: sobrelinha, chip, rodapé
  botao: 16, // ao lado do rótulo de um botão ou link de ação
  destaque: 18, // botão maior, ícone do WhatsApp
  bloco: 20, // controle isolado, seta de carrossel, cabeçalho de card
  cartao: 26, // ícone que abre um card de serviço ou de diferencial
};

const PATHS = {
  /* --- Lucide (ISC) ------------------------------------------------------ */
  arrowRight: '<path d="M5 12h14"/> <path d="m12 5 7 7-7 7"/>',
  arrowUpRight: '<path d="M7 7h10v10"/> <path d="M7 17 17 7"/>',
  chevronDown: '<path d="m6 9 6 6 6-6"/>',
  chevronLeft: '<path d="m15 18-6-6 6-6"/>',
  chevronRight: '<path d="m9 18 6-6-6-6"/>',
  phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
  mail: '<rect width="20" height="16" x="2" y="4" rx="2"/> <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
  pin: '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/> <circle cx="12" cy="10" r="3"/>',
  clock: '<circle cx="12" cy="12" r="10"/> <polyline points="12 6 12 12 16 14"/>',
  check: '<path d="M20 6 9 17l-5-5"/>',
  plus: '<path d="M5 12h14"/> <path d="M12 5v14"/>',
  shieldCheck: '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/> <path d="m9 12 2 2 4-4"/>',
  wrench: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
  clipboardCheck: '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/> <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/> <path d="m9 14 2 2 4-4"/>',
  snowflake: '<path d="m10 20-1.25-2.5L6 18"/> <path d="M10 4 8.75 6.5 6 6"/> <path d="m14 20 1.25-2.5L18 18"/> <path d="m14 4 1.25 2.5L18 6"/> <path d="m17 21-3-6h-4"/> <path d="m17 3-3 6 1.5 3"/> <path d="M2 12h6.5L10 9"/> <path d="m20 10-1.5 2 1.5 2"/> <path d="M22 12h-6.5L14 15"/> <path d="m4 10 1.5 2L4 14"/> <path d="m7 21 3-6-1.5-3"/> <path d="m7 3 3 6h4"/>',
  flask: '<path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2"/> <path d="M6.453 15h11.094"/> <path d="M8.5 2h7"/>',
  bread: '<path d="m4.6 13.11 5.79-3.21c1.89-1.05 4.79 1.78 3.71 3.71l-3.22 5.81C8.8 23.16.79 15.23 4.6 13.11Z"/> <path d="m10.5 9.5-1-2.29C9.2 6.48 8.8 6 8 6H4.5C2.79 6 2 6.5 2 8.5a7.71 7.71 0 0 0 2 4.83"/> <path d="M8 6c0-1.55.24-4-2-4-2 0-2.5 2.17-2.5 4"/> <path d="m14.5 13.5 2.29 1c.73.3 1.21.7 1.21 1.5v3.5c0 1.71-.5 2.5-2.5 2.5a7.71 7.71 0 0 1-4.83-2"/> <path d="M18 16c1.55 0 4-.24 4 2 0 2-2.17 2.5-4 2.5"/>',
  wheat: '<path d="M2 22 16 8"/> <path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/> <path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/> <path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/> <path d="M20 2h2v2a4 4 0 0 1-4 4h-2V6a4 4 0 0 1 4-4Z"/> <path d="M11.47 17.47 13 19l-1.53 1.53a3.5 3.5 0 0 1-4.94 0L5 19l1.53-1.53a3.5 3.5 0 0 1 4.94 0Z"/> <path d="M15.47 13.47 17 15l-1.53 1.53a3.5 3.5 0 0 1-4.94 0L9 15l1.53-1.53a3.5 3.5 0 0 1 4.94 0Z"/> <path d="M19.47 9.47 21 11l-1.53 1.53a3.5 3.5 0 0 1-4.94 0L13 11l1.53-1.53a3.5 3.5 0 0 1 4.94 0Z"/>',
  mountain: '<path d="m8 3 4 8 5-5 5 15H2L8 3z"/>',
  chair: '<path d="M19 9V6a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v3"/> <path d="M3 16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5a2 2 0 0 0-4 0v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V11a2 2 0 0 0-4 0z"/> <path d="M5 18v2"/> <path d="M19 18v2"/>',
  factory: '<path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/> <path d="M17 18h1"/> <path d="M12 18h1"/> <path d="M7 18h1"/>',
  tree: '<path d="m17 14 3 3.3a1 1 0 0 1-.7 1.7H4.7a1 1 0 0 1-.7-1.7L7 14h-.3a1 1 0 0 1-.7-1.7L9 9h-.2A1 1 0 0 1 8 7.3L12 3l4 4.3a1 1 0 0 1-.8 1.7H15l3 3.3a1 1 0 0 1-.7 1.7H17Z"/> <path d="M12 22v-3"/>',
  info: '<circle cx="12" cy="12" r="10"/> <path d="M12 16v-4"/> <path d="M12 8h.01"/>',
  paperclip: '<path d="M13.234 20.252 21 12.3"/> <path d="m16 6-8.414 8.586a2 2 0 0 0 0 2.828 2 2 0 0 0 2.828 0l8.414-8.586a4 4 0 0 0 0-5.656 4 4 0 0 0-5.656 0l-8.415 8.585a6 6 0 1 0 8.486 8.486"/>',
  target: '<circle cx="12" cy="12" r="10"/> <circle cx="12" cy="12" r="6"/> <circle cx="12" cy="12" r="2"/>',
  route: '<circle cx="6" cy="19" r="3"/> <path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/> <circle cx="18" cy="5" r="3"/>',
  users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/> <circle cx="9" cy="7" r="4"/> <path d="M22 21v-2a4 4 0 0 0-3-3.87"/> <path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  bolt: '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
  spark: '<circle cx="12" cy="12" r="10"/> <circle cx="12" cy="12" r="1"/>',
  destaque: '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>',

  /* --- Domínio: desenhados para este catálogo ---------------------------- */
  elevador: '<path d="M10 2a6 6 0 0 1 6 6v8a6 6 0 0 1-12 0V8a6 6 0 0 1 6-6Z"/><path d="M16 6.5h5.5v4H16"/><path d="M16 13.5h5.5v4H16"/>',
  caneca: '<path d="M2 5h20"/><path d="M5 5v6a7 7 0 0 0 14 0V5"/>',
  rolete: '<rect x="5" y="6" width="14" height="12" rx="6"/><path d="M15 6a6 6 0 0 1 0 12"/><path d="M0.5 12h4.5M19 12h4.5"/>',
  revestimento: '<rect x="2.5" y="5" width="19" height="14" rx="7"/><rect x="6" y="8.5" width="12" height="7" rx="3.5"/><path d="M0 12h2.5M21.5 12h2.5"/>',
  talisca: '<path d="M2 17h20v4H2Z"/><path d="M5.5 17v-6h2.5v6M11 17v-6h2.5v6M16.5 17v-6H19v6"/>',
  tracaoPositiva: '<path d="M2 4h20v8H2Z"/><path d="M5 12v4h3v-4M10.5 12v4h3v-4M16 12v4h3v-4"/>',
  lonada: '<rect x="2" y="6" width="20" height="12" rx="2"/><path d="M2 10h20M2 14h20"/>',
  bobina: '<ellipse cx="12" cy="6.5" rx="9" ry="3.5"/><path d="M3 6.5v11a9 3.5 0 0 0 18 0v-11"/><ellipse cx="12" cy="6.5" rx="2.8" ry="1.1"/>',
  borracha: '<path d="M2 7h20v10H2Z"/><path d="m9 7-4 10M17 7l-4 10"/>',
};

/** Glifos sólidos. Play e pause são preenchidos por convenção, e a marca do
 *  WhatsApp é um glifo sólido — aproximá-la em traço é o que a fazia parecer
 *  um desenho errado da marca. */
const CHEIOS = {
  play: '<path d="M8 5.2v13.6a1 1 0 0 0 1.52.85l11.1-6.8a1 1 0 0 0 0-1.7L9.52 4.35A1 1 0 0 0 8 5.2Z"/>',
  pause: '<rect x="6.5" y="4.5" width="4" height="15" rx="1"/><rect x="13.5" y="4.5" width="4" height="15" rx="1"/>',
  whatsapp: '<path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2Zm0 18.15h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.38c0-4.54 3.7-8.23 8.25-8.23a8.23 8.23 0 0 1 0 16.47Zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.24-.64.8-.78.97-.15.16-.29.18-.53.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.71-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.44.13-.14.17-.24.25-.41.09-.16.04-.3-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.47c-.17 0-.43.06-.66.31-.22.24-.86.85-.86 2.07s.89 2.4 1.01 2.56c.12.17 1.74 2.66 4.22 3.73.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.47-.07 1.47-.6 1.68-1.19.2-.58.2-1.08.14-1.18-.06-.11-.22-.17-.47-.29Z"/>',
};

/** Mapa slug -> ícone, usado nas barras de âncoras e nos cards. */
export const ICON_BY_SLUG = {
  // produtos
  'correia-pu-sanitaria': 'shieldCheck',
  'correia-pu-tracao-positiva': 'tracaoPositiva',
  'correia-pu-lonada': 'lonada',
  'correia-pvc': 'bobina',
  'correia-pvc-elevadora-canecas': 'elevador',
  'correia-de-borracha': 'borracha',
  'canecas-para-elevadores': 'caneca',
  'rolos-e-roletes': 'rolete',
  'material-para-revestimento': 'revestimento',
  // serviços
  'instalacao-conserto-correias-pu': 'wrench',
  'instalacao-conserto-correias-pvc': 'wrench',
  'instalacao-conserto-correias-borracha': 'wrench',
  'instalacao-conserto-correias-nylon': 'wrench',
  'instalacao-conserto-correias-transmissao': 'bolt',
  'revestimento-de-rolos': 'revestimento',
  'aplicacao-guias-taliscas': 'talisca',
  'consultoria-acompanhamento-preventivo': 'clipboardCheck',
  // setores
  frigorificos: 'snowflake',
  'industria-farmaceutica': 'flask',
  'panificacao-biscoitos': 'bread',
  cerealistas: 'wheat',
  britadores: 'mountain',
  'industria-moveis': 'chair',
  'fabricantes-equipamentos': 'factory',
  'lenha-cavacos': 'tree',
};

/**
 * Renderiza um ícone SVG inline.
 *
 * O traço não é parâmetro de propósito: ele é derivado do tamanho para que a
 * espessura na tela seja sempre a mesma. Antes havia 22 combinações de tamanho
 * e traço no site, e era o que mais fazia o conjunto parecer improvisado.
 *
 * @param {string} name chave em PATHS, em CHEIOS ou em ICON_BY_SLUG
 * @param {number} size lado do ícone em px
 */
export function icon(name, size = 20) {
  const chave = PATHS[name] || CHEIOS[name] ? name : ICON_BY_SLUG[name] || 'spark';
  const comum = `viewBox="0 0 24 24" width="${size}" height="${size}" aria-hidden="true" focusable="false"`;
  if (CHEIOS[chave]) {
    return `<svg ${comum} fill="currentColor">${CHEIOS[chave]}</svg>`;
  }
  // stroke-width vive no espaço do viewBox: para o traço sair com TRACO px na
  // tela, ele precisa ser reescalado pelo fator de ampliação do ícone.
  const sw = Math.round((TRACO * 24 / size) * 100) / 100;
  return `<svg ${comum} fill="none" stroke="currentColor" stroke-width="${sw}" stroke-linecap="round" stroke-linejoin="round">${PATHS[chave]}</svg>`;
}
