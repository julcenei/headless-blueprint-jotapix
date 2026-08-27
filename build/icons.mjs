/**
 * Conjunto de ícones lineares (stroke) desenhados em viewBox 24x24.
 * Todos herdam `currentColor` e o traço fino mantém o tom técnico da marca.
 */
const PATHS = {
  arrowRight: '<path d="M5 12h14M13 6l6 6-6 6"/>',
  arrowUpRight: '<path d="M7 17 17 7M8 7h9v9"/>',
  chevronDown: '<path d="m6 9 6 6 6-6"/>',
  chevronLeft: '<path d="m15 18-6-6 6-6"/>',
  chevronRight: '<path d="m9 18 6-6-6-6"/>',
  phone: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
  mail: '<path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/><path d="m3.5 6.5 8.5 6 8.5-6"/>',
  pin: '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
  whatsapp: '<path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3Z"/><path d="M8.8 8.4c.3-.7 1.5-.6 1.7-.1l.5 1.2c.1.4-.4.8-.6 1 .5 1 1.3 1.8 2.3 2.3.2-.2.6-.7 1-.6l1.2.5c.5.2.6 1.4-.1 1.7-1.4.6-3.5-.4-4.6-1.5-1.1-1.1-2-3.1-1.4-4.5Z"/>',
  check: '<path d="m4 12.5 5 5L20 6.5"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  shieldCheck: '<path d="M12 3 5 6v5.5c0 4.3 2.9 8.3 7 9.5 4.1-1.2 7-5.2 7-9.5V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
  arrowsH: '<path d="M3 12h18"/><path d="m7 8-4 4 4 4M17 8l4 4-4 4"/>',
  layers: '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
  grid: '<rect x="3" y="3" width="18" height="18" rx="1"/><path d="M9 3v18M15 3v18M3 9h18M3 15h18"/>',
  elevator: '<path d="M12 3v10"/><path d="m8 7 4-4 4 4"/><path d="M4 13h16v8H4z"/>',
  box: '<path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
  cog: '<circle cx="12" cy="12" r="3.2"/><path d="M12 2v2.5M12 19.5V22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M2 12h2.5M19.5 12H22M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8"/>',
  roller: '<rect x="3" y="4" width="13" height="6" rx="1"/><path d="M16 7h3a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-7v3"/><rect x="10" y="15" width="4" height="6" rx="1"/>',
  wrench: '<path d="M15.5 3a5.5 5.5 0 0 0-5 7.7L3 18.2 5.8 21l7.5-7.5A5.5 5.5 0 1 0 15.5 3Z"/>',
  ruler: '<path d="m3 15 12-12 6 6-12 12z"/><path d="m7 11 2 2M11 7l2 2M10.5 14.5l1.5 1.5"/>',
  clipboardCheck: '<path d="M9 4h6v3H9z"/><path d="M15 5h3v16H6V5h3"/><path d="m9.5 13 2 2 3.5-3.5"/>',
  snowflake: '<path d="M12 2v20M4.5 6.5l15 11M19.5 6.5l-15 11"/><path d="m9 4 3 2 3-2M9 20l3-2 3 2"/>',
  flask: '<path d="M9 3h6M10 3v6L4.6 18a2 2 0 0 0 1.7 3h11.4a2 2 0 0 0 1.7-3L14 9V3"/><path d="M7.5 14h9"/>',
  bread: '<path d="M4 10a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4c0 1.4-1 2-2 2v6a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-6c-1 0-2-.6-2-2Z"/>',
  wheat: '<path d="M12 22V9"/><path d="M12 9c0-2 1.5-4 3.5-4.5C15.5 6.5 14 9 12 9Zm0 0C12 7 10.5 5 8.5 4.5 8.5 6.5 10 9 12 9Z"/><path d="M12 15c0-2 1.5-4 3.5-4.5C15.5 12.5 14 15 12 15Zm0 0c0-2-1.5-4-3.5-4.5C8.5 12.5 10 15 12 15Z"/>',
  mountain: '<path d="m3 20 6.5-12 4 6.5 2.5-4L21 20H3Z"/>',
  chair: '<path d="M6 4h12v8H6z"/><path d="M4 12h16M7 12v8M17 12v8"/>',
  factory: '<path d="M3 21V10l5 3.5V10l5 3.5V10l5 3.5V6h3v15H3Z"/><path d="M7 21v-4M12 21v-4M17 21v-4"/>',
  tree: '<path d="m12 3 5 7h-3l4 6H6l4-6H7l5-7Z"/><path d="M12 16v5"/>',
  spark: '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18"/>',
  info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
  paperclip: '<path d="M20 11.5 12 19.5a5 5 0 0 1-7-7l8.5-8.5a3.4 3.4 0 0 1 4.8 4.8L9.8 17.3a1.8 1.8 0 0 1-2.5-2.5l7.7-7.7"/>',
  play: '<path d="M7 4.5v15l12-7.5-12-7.5Z"/>',
  pause: '<path d="M8 4h3v16H8zM13 4h3v16h-3z"/>',
  target: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1"/>',
  route: '<circle cx="6" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="M8.5 6H15a3 3 0 0 1 0 6H9a3 3 0 0 0 0 6h6.5"/>',
  users: '<circle cx="9" cy="8" r="3.2"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16.5 5.5a3.2 3.2 0 0 1 0 6M17 20a6 6 0 0 0-2.2-4.6"/>',
  bolt: '<path d="M13 3 5 14h6l-1 7 8-11h-6l1-7Z"/>',
};

/** Mapa slug -> ícone, usado nas barras de âncoras e nos cards. */
export const ICON_BY_SLUG = {
  // produtos
  'correia-pu-sanitaria': 'shieldCheck',
  'correia-pu-tracao-positiva': 'arrowsH',
  'correia-pu-lonada': 'layers',
  'correia-pvc': 'grid',
  'correia-pvc-elevadora-canecas': 'elevator',
  'correia-de-borracha': 'shieldCheck',
  'canecas-para-elevadores': 'box',
  'rolos-e-roletes': 'cog',
  'material-para-revestimento': 'roller',
  // serviços
  'instalacao-conserto-correias-pu': 'wrench',
  'instalacao-conserto-correias-pvc': 'wrench',
  'instalacao-conserto-correias-borracha': 'wrench',
  'instalacao-conserto-correias-nylon': 'wrench',
  'instalacao-conserto-correias-transmissao': 'bolt',
  'revestimento-de-rolos': 'roller',
  'aplicacao-guias-taliscas': 'ruler',
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
 * @param {string} name chave em PATHS ou ICON_BY_SLUG
 * @param {number} size tamanho em px
 * @param {number} sw espessura do traço
 */
export function icon(name, size = 24, sw = 1.6) {
  const key = PATHS[name] ? name : ICON_BY_SLUG[name] || 'spark';
  return `<svg viewBox="0 0 24 24" width="${size}" height="${size}" fill="none" stroke="currentColor" stroke-width="${sw}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${PATHS[key]}</svg>`;
}
