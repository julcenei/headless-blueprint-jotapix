import { icon } from './icons.mjs';
import { data, esc, img, telLink, waLink, SITE } from './data.mjs';

/* ==========================================================================
   <head> — meta, Open Graph, fontes e JSON-LD
   ========================================================================== */
function jsonLd(page) {
  const c = data.contact;
  const business = {
    '@context': 'https://schema.org',
    '@type': 'LocalBusiness',
    '@id': SITE.url + '/#elotec',
    name: 'Elotec — Serviços Técnicos em Correias',
    description: SITE.description,
    url: SITE.url + '/',
    telephone: '+55' + c.whatsapp.slice(2),
    email: c.email,
    foundingDate: '2001',
    image: SITE.url + '/images/hero.webp',
    priceRange: '$$',
    address: {
      '@type': 'PostalAddress',
      streetAddress: c.address.street,
      addressLocality: c.address.city,
      addressRegion: c.address.state,
      postalCode: c.address.zip,
      addressCountry: 'BR',
    },
    areaServed: { '@type': 'Country', name: 'Brasil' },
    openingHoursSpecification: [
      {
        '@type': 'OpeningHoursSpecification',
        dayOfWeek: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        opens: '08:00',
        closes: '18:00',
      },
    ],
    makesOffer: data.products.map((p) => ({
      '@type': 'Offer',
      itemOffered: { '@type': 'Product', name: p.name, description: p.summary },
    })),
  };

  const crumbs = page.breadcrumb && page.breadcrumb.length
    ? {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: page.breadcrumb.map((b, i) => ({
          '@type': 'ListItem',
          position: i + 1,
          name: b.label,
          item: SITE.url + '/' + (b.href || ''),
        })),
      }
    : null;

  return [business, crumbs]
    .filter(Boolean)
    .map((o) => `<script type="application/ld+json">${JSON.stringify(o)}</script>`)
    .join('\n  ');
}

export function head(page) {
  const title = page.title;
  const desc = page.description;
  const url = SITE.url + '/' + (page.file === 'index.html' ? '' : page.file);
  return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${esc(title)}</title>
  <meta name="description" content="${esc(desc)}">
  <meta name="theme-color" content="#232A5C">
  <meta name="author" content="Elotec Serviços Técnicos em Correias">
  <link rel="canonical" href="${esc(url)}">
  ${page.noindex ? '<meta name="robots" content="noindex, follow">' : '<meta name="robots" content="index, follow">'}

  <!-- Open Graph / Twitter -->
  <meta property="og:type" content="website">
  <meta property="og:locale" content="pt_BR">
  <meta property="og:site_name" content="Elotec Correias">
  <meta property="og:title" content="${esc(title)}">
  <meta property="og:description" content="${esc(desc)}">
  <meta property="og:url" content="${esc(url)}">
  <meta property="og:image" content="${SITE.url}/${img(page.image || '/images/hero.webp')}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="${esc(title)}">
  <meta name="twitter:description" content="${esc(desc)}">

  <!-- Favicon inline: losango amarelo com o "e" da marca -->
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cpath d='M16 1l11 4 3 11-4 11-11 4-11-4-3-11 4-11z' fill='%23F7C90C'/%3E%3Ctext x='16' y='23' font-family='Arial,sans-serif' font-size='19' font-weight='700' fill='%23232A5C' text-anchor='middle'%3Ee%3C/text%3E%3C/svg%3E">

  <!-- Tipografia: Familjen Grotesk (display) + Public Sans (corpo) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:wght@400;700&family=Public+Sans:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">

  ${jsonLd(page)}
</head>
<body>
  <a class="skip-link" href="#conteudo">Ir para o conteúdo</a>`;
}

/* ==========================================================================
   Header: top bar + navbar com mega dropdowns + menu mobile
   ========================================================================== */
function logo(light = false) {
  return `<a class="logo${light ? ' logo--light' : ''}" href="index.html" aria-label="Elotec — página inicial">
        <span class="logo__mark" aria-hidden="true"><span>e</span></span>
        <span>
          <span class="logo__word">elotec</span>
          <span class="logo__desc">Serviços Técnicos em Correias</span>
        </span>
      </a>`;
}

/** Colunas do dropdown de Produtos: uma por categoria. */
function productColumns() {
  return data.productCategories
    .map((cat) => {
      const items = data.products.filter((p) => p.category === cat.slug);
      return `<div class="dropdown__col">
            <p class="dropdown__col-title">${esc(cat.label)}</p>
            <ul>${items
              .map((p) => `<li><a href="produtos.html#${p.slug}">${esc(p.name)}</a></li>`)
              .join('')}</ul>
          </div>`;
    })
    .join('');
}

const SERVICE_GROUPS = [
  { key: 'instalacao-conserto', label: 'Instalação e conserto' },
  { key: 'revestimento', label: 'Revestimentos' },
  { key: 'personalizacao', label: 'Personalização' },
  { key: 'consultoria', label: 'Consultoria' },
];

function serviceColumns() {
  return SERVICE_GROUPS.map((g) => {
    const items = data.services.filter((s) => s.category === g.key);
    if (!items.length) return '';
    return `<div class="dropdown__col">
            <p class="dropdown__col-title">${esc(g.label)}</p>
            <ul>${items
              .map((s) => `<li><a href="servicos.html#${s.slug}">${esc(s.shortName || s.name)}</a></li>`)
              .join('')}</ul>
          </div>`;
  }).join('');
}

function sectorColumns() {
  const half = Math.ceil(data.sectors.length / 2);
  const cols = [data.sectors.slice(0, half), data.sectors.slice(half)];
  return cols
    .map(
      (list, i) => `<div class="dropdown__col">
            <p class="dropdown__col-title">${i === 0 ? 'Alimentos e higiene' : 'Indústria pesada e madeira'}</p>
            <ul>${list
              .map((s) => `<li><a href="setores.html#${s.slug}">${esc(s.name)}</a></li>`)
              .join('')}</ul>
          </div>`
    )
    .join('');
}

function dropdown(id, columns, allHref, allLabel) {
  return `<div class="dropdown" id="${id}">
          <div class="container dropdown__inner">${columns}</div>
          <div class="container dropdown__foot">
            <a class="link-arrow" href="${allHref}">${allLabel} ${icon('arrowRight', 16, 2)}</a>
          </div>
        </div>`;
}

export function header(page) {
  const c = data.contact;
  const current = page.nav || '';
  const navItem = (id, label, columns, allHref, allLabel) => `
        <li class="nav__item nav__item--has-menu" data-open="false">
          <button class="nav__link" type="button" aria-expanded="false" aria-controls="${id}"${
    current === id ? ' aria-current="page"' : ''
  }>${label} ${icon('chevronDown', 15, 2.2)}</button>
          ${dropdown(id, columns, allHref, allLabel)}
        </li>`;

  return `
  <header class="site-header">
    <div class="topbar">
      <div class="container">
        <p class="topbar__msg">Atendimento em todo o Brasil · Assistência técnica 24h</p>
        <div class="topbar__links">
          <a href="tel:+55${c.whatsapp.slice(2)}">${icon('phone', 15, 1.8)} ${esc(c.whatsappDisplay)}</a>
          <a href="mailto:${c.email}">${icon('mail', 15, 1.8)} ${esc(c.email)}</a>
        </div>
      </div>
    </div>

    <div class="navbar">
      <div class="container">
        ${logo()}
        <nav class="nav" aria-label="Navegação principal">
          <ul class="nav__list">
            <li class="nav__item"><a class="nav__link" href="a-elotec.html"${
              current === 'a-elotec' ? ' aria-current="page"' : ''
            }>A Elotec</a></li>
            ${navItem('menu-produtos', 'Produtos', productColumns(), 'produtos.html', 'Ver todos os produtos')}
            ${navItem('menu-servicos', 'Serviços', serviceColumns(), 'servicos.html', 'Ver todos os serviços')}
            ${navItem('menu-setores', 'Setores Atendidos', sectorColumns(), 'setores.html', 'Ver todos os setores')}
          </ul>
        </nav>
        <a class="btn btn--primary nav__cta" href="solicitar-orcamento.html">Solicitar orçamento ${icon('arrowRight', 16, 2.4)}</a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="Abrir menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <!-- Menu mobile: acordeão. Só os 5 destinos principais ficam à vista;
       cada grupo abre sob demanda, um de cada vez. -->
  <div class="mobile-menu" id="mobile-menu" data-open="false">
    <nav class="mnav" aria-label="Navegação principal (mobile)">
      <a class="mnav__row" href="index.html">Início</a>
      <a class="mnav__row" href="a-elotec.html">A Elotec</a>
      ${[
        { id: 'produtos', label: 'Produtos', pagina: 'produtos.html', todos: 'Ver todos os produtos', itens: data.products.map((p) => ({ href: `produtos.html#${p.slug}`, nome: p.name })) },
        { id: 'servicos', label: 'Serviços', pagina: 'servicos.html', todos: 'Ver todos os serviços', itens: data.services.map((s) => ({ href: `servicos.html#${s.slug}`, nome: s.shortName || s.name })) },
        { id: 'setores', label: 'Setores Atendidos', pagina: 'setores.html', todos: 'Ver todos os setores', itens: data.sectors.map((s) => ({ href: `setores.html#${s.slug}`, nome: s.name })) },
      ]
        .map(
          (g) => `
      <div class="mnav__item">
        <button class="mnav__row" type="button" aria-expanded="false" aria-controls="m-${g.id}">
          ${esc(g.label)}
          <span class="mnav__chevron" aria-hidden="true">${icon('chevronDown', 18, 2)}</span>
        </button>
        <div class="mnav__panel" id="m-${g.id}"><div>
          <ul>
            ${g.itens.map((i) => `<li><a href="${i.href}">${esc(i.nome)}</a></li>`).join('')}
            <li><a class="mnav__all" href="${g.pagina}">${esc(g.todos)} ${icon('arrowRight', 14, 2.4)}</a></li>
          </ul>
        </div></div>
      </div>`
        )
        .join('')}
    </nav>

    <div class="mobile-menu__foot">
      <a class="btn btn--primary btn--block" href="solicitar-orcamento.html">Solicitar orçamento ${icon('arrowRight', 16, 2.4)}</a>
      <a class="btn btn--ghost-light btn--block" href="${waLink()}" target="_blank" rel="noopener">${icon('whatsapp', 18, 1.7)} Falar no WhatsApp</a>
      <div class="mnav__contato">
        <a href="${telLink(c.whatsappDisplay)}">${icon('phone', 16, 1.8)} ${esc(c.whatsappDisplay)}</a>
        <a href="mailto:${c.email}">${icon('mail', 16, 1.8)} ${esc(c.email)}</a>
      </div>
    </div>
  </div>`;
}

/* ==========================================================================
   Footer + elementos flutuantes + scripts
   ========================================================================== */
export function footer() {
  const c = data.contact;
  const list = (items, allHref, allLabel) => `
          <ul>${items.map((i) => `<li><a href="${i.href}">${esc(i.label)}</a></li>`).join('')}</ul>
          <a class="all-link" href="${allHref}">${allLabel} &rarr;</a>`;

  return `
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col footer-brand">
          ${logo(true)}
          <address>
            ${esc(c.address.street)}<br>
            ${esc(c.address.district)}, ${esc(c.address.city)}/${esc(c.address.state)}<br>
            CEP ${esc(c.address.zip)}<br><br>
            <a href="https://wa.me/${c.whatsapp}" target="_blank" rel="noopener">WhatsApp ${esc(c.whatsappDisplay)}</a><br>
            <a href="${telLink(c.phoneAdmin)}">Adm ${esc(c.phoneAdmin)}</a><br>
            <a href="mailto:${c.email}">${esc(c.email)}</a>
          </address>
        </div>

        <div class="footer-col">
          <h2>Produtos</h2>
          ${list(
            data.products.slice(0, 6).map((p) => ({ href: `produtos.html#${p.slug}`, label: p.name })),
            'produtos.html',
            'Todos os produtos'
          )}
        </div>

        <div class="footer-col">
          <h2>Serviços</h2>
          ${list(
            data.services.slice(0, 6).map((s) => ({ href: `servicos.html#${s.slug}`, label: s.shortName || s.name })),
            'servicos.html',
            'Todos os serviços'
          )}
        </div>

        <div class="footer-col">
          <h2>Setores</h2>
          ${list(
            data.sectors.slice(0, 6).map((s) => ({ href: `setores.html#${s.slug}`, label: s.name })),
            'setores.html',
            'Todos os setores'
          )}
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; <span data-year>2026</span> Elotec Serviços Técnicos em Correias. Todos os direitos reservados.</p>
        <nav aria-label="Links legais">
          <a href="solicitar-orcamento.html">Solicitar orçamento</a>
          <a href="politica-de-privacidade.html">Política de Privacidade</a>
        </nav>
      </div>
    </div>
  </footer>

  <div class="floaters">
    <a class="float-btn float-btn--quote" href="solicitar-orcamento.html" data-hidden="true">
      ${icon('spark', 16, 2)} Solicitar orçamento
    </a>
    <a class="float-btn float-btn--whats" href="${waLink()}" target="_blank" rel="noopener" aria-label="Falar no WhatsApp com a Elotec">
      ${icon('whatsapp', 26, 1.7)}
    </a>
  </div>

  <script src="js/main.js" defer></script>
</body>
</html>`;
}

/* Faixa CTA navy usada no fim das páginas internas. */
export function ctaBand(title, text, primaryLabel = 'Solicitar orçamento') {
  return `
  <section class="cta-band">
    <div class="container cta-band__inner">
      <div class="reveal">
        <h2 class="h-2" style="margin-top:1rem">${title}</h2>
        <p class="lead" style="margin-top:1.25rem">${esc(text)}</p>
      </div>
      <div class="cta-band__actions reveal" style="--i:1">
        <a class="btn btn--primary" href="solicitar-orcamento.html">${esc(primaryLabel)} ${icon('arrowRight', 16, 2.4)}</a>
        <a class="btn btn--ghost-light" href="${waLink()}" target="_blank" rel="noopener">${icon('whatsapp', 18, 1.7)} Falar no WhatsApp</a>
      </div>
    </div>
  </section>`;
}

/* ==========================================================================
   Blocos compartilhados entre páginas
   ========================================================================== */

/** Hero das páginas internas: breadcrumb + título com destaque + subtítulo. */
export function pageHero({ title, accent, text, crumb }) {
  return `
  <section class="page-hero">
    <div class="container">
      <nav aria-label="Você está aqui">
        <ol class="breadcrumb">
          <li><a href="index.html">Início</a></li>
          <li><span aria-current="page">${esc(crumb)}</span></li>
        </ol>
      </nav>
      <h1 class="h-1 page-hero__title">${esc(title)} <span class="text-accent">${esc(accent)}</span></h1>
      <p class="lead page-hero__text">${esc(text)}</p>
    </div>
  </section>`;
}

/** Linhas de contato (WhatsApp / administrativo / e-mail). */
export function contactLines(waHref = waLink()) {
  const c = data.contact;
  return `
        <div class="contact-lines">
          <p class="contact-line">${icon('whatsapp', 20, 1.7)} <a href="${waHref}" target="_blank" rel="noopener"><span>WhatsApp</span>${esc(c.whatsappDisplay)}</a></p>
          <p class="contact-line">${icon('phone', 20, 1.7)} <a href="${telLink(c.phoneAdmin)}"><span>Administrativo</span>${esc(c.phoneAdmin)}</a></p>
          <p class="contact-line">${icon('mail', 20, 1.7)} <a href="mailto:${c.email}"><span>E-mail</span>${esc(c.email)}</a></p>
        </div>`;
}

/** Os quatro números da empresa, com count-up ao entrar na viewport. */
export function statsBlock(style = '') {
  const stats = [
    { to: '2001', label: 'início das atividades' },
    { to: '24', suffix: 'h', label: 'assistência técnica' },
    { to: '2', label: 'unidades: Chapecó/SC e Toledo/PR' },
    { to: '8', suffix: '+', label: 'setores industriais atendidos' },
  ];
  return `
        <div class="stats"${style ? ` style="${style}"` : ''}>
          ${stats
            .map(
              (s) => `<div class="stat">
            <p class="stat__num"><span data-count-to="${s.to}"${s.suffix ? ` data-suffix="${s.suffix}"` : ''}>${s.to}${s.suffix || ''}</span></p>
            <p class="stat__label">${esc(s.label)}</p>
          </div>`
            )
            .join('')}
        </div>`;
}
