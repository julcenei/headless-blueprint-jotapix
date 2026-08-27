import { icon } from './icons.mjs';
import { data, esc, img, bySlug } from './data.mjs';
import { ctaBand } from './layout.mjs';

/* ---------------------------------------------------------------------------
   Páginas de catálogo (Produtos / Serviços / Setores)
   Padrão comum: hero navy + breadcrumb, barra de âncoras com ícones,
   seções numeradas alternando fundo, texto à esquerda e foto chanfrada.
--------------------------------------------------------------------------- */

function pageHero({ title, accent, text, crumb }) {
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

function anchorBar(items, page) {
  return `
  <nav class="anchor-bar" aria-label="Navegar entre ${page}">
    <div class="container anchor-bar__scroller">
      ${items
        .map(
          (i) => `<a class="anchor-item" href="#${i.slug}">${icon(i.slug, 24, 1.5)}<span>${esc(i.label)}</span></a>`
        )
        .join('')}
    </div>
  </nav>`;
}

/** Bloco de lista com checkmarks (Benefícios, Aplicações, etc.). */
function listBlock(title, items) {
  if (!items || !items.length) return '';
  return `
      <div class="entry__block">
        <h3>${esc(title)}</h3>
        <ul class="check-list">${items.map((i) => `<li>${esc(i)}</li>`).join('')}</ul>
      </div>`;
}

/** Bloco de chips clicáveis (setores relacionados). */
function chipBlock(title, slugs, kind) {
  if (!slugs || !slugs.length) return '';
  const chips = slugs
    .map((slug) => {
      const item = kind === 'sector' ? bySlug.sector(slug) : bySlug.service(slug);
      if (!item) return '';
      const page = kind === 'sector' ? 'setores.html' : 'servicos.html';
      const label = kind === 'sector' ? item.name : item.shortName || item.name;
      return `<a class="chip" href="${page}#${slug}">${icon(slug, 16, 1.7)} ${esc(label)}</a>`;
    })
    .join('');
  return `
      <div class="entry__block">
        <h3>${esc(title)}</h3>
        <div class="chips">${chips}</div>
      </div>`;
}

/** Bloco de mini-cards com foto (produtos relacionados). */
function productBlock(title, slugs) {
  if (!slugs || !slugs.length) return '';
  const cards = slugs
    .map((slug) => {
      const p = bySlug.product(slug);
      if (!p) return '';
      return `<a class="mini-card" href="produtos.html#${p.slug}">
              <span class="mini-card__thumb"><img src="${img(p.image)}" alt="" loading="lazy" width="104" height="104"></span>
              <span class="mini-card__name">${esc(p.name)}</span>
            </a>`;
    })
    .join('');
  return `
      <div class="entry__block">
        <h3>${esc(title)}</h3>
        <div class="mini-grid">${cards}</div>
      </div>`;
}

/**
 * Uma seção numerada do catálogo.
 * @param {object} item registro de produto/serviço/setor
 * @param {number} index posição (para a numeração amarela)
 * @param {string} kind 'product' | 'service' | 'sector'
 */
function entry(item, index, kind) {
  const n = String(index + 1);
  const label = kind === 'product' ? item.categoryLabel : null;
  const quoteHref = `solicitar-orcamento.html?${kind === 'product' ? 'produto' : kind === 'service' ? 'servico' : 'setor'}=${item.slug}`;

  let blocks = '';
  if (kind === 'product') {
    blocks =
      listBlock('Benefícios', item.benefits) +
      listBlock('Características técnicas', item.features) +
      listBlock('Aplicações', item.applications) +
      chipBlock('Setores que utilizam', item.relatedSectors, 'sector') +
      chipBlock('Serviços relacionados', item.relatedServices, 'service');
  } else if (kind === 'service') {
    blocks =
      listBlock('Informações técnicas', item.techInfo) +
      listBlock('Benefícios', item.benefits) +
      listBlock('Características', item.features) +
      listBlock('Atividades do acompanhamento', item.activities) +
      productBlock('Produtos relacionados', item.relatedProducts) +
      chipBlock('Setores atendidos', item.relatedSectors, 'sector');
  } else {
    // Setores usam título condicional conforme o campo presente
    blocks =
      listBlock('Desafios do setor', item.challenges) +
      listBlock('Necessidades atendidas', item.needs) +
      listBlock('Aplicações possíveis', item.applications) +
      listBlock('Possibilidades', item.possibilities) +
      chipBlock('Serviços relacionados', item.relatedServices, 'service') +
      productBlock('Produtos relacionados', item.relatedProducts);
  }

  return `
  <section class="entry" id="${item.slug}">
    <div class="container entry__grid">
      <div class="reveal">
        ${item.highlight ? `<p class="badge-highlight">${icon('spark', 14, 2)} Principal área de atuação</p>` : ''}
        ${label ? `<p class="chip" style="pointer-events:none">${esc(label)}</p>` : ''}
        <h2 class="h-2 entry__title"><span class="entry__num">${n}.</span> ${esc(item.name)}</h2>
        <p class="entry__summary">${esc(item.summary || item.subtitle)}</p>
        <p class="entry__desc">${esc(item.description)}</p>
        ${blocks}
        <div class="entry__actions">
          <a class="btn btn--primary" href="${quoteHref}">${esc(item.cta || 'Solicitar orçamento')} ${icon('arrowRight', 16, 2.4)}</a>
          <a class="link-arrow" href="solicitar-orcamento.html">Enviar fotos e medidas ${icon('arrowRight', 15, 2.4)}</a>
        </div>
      </div>

      <div class="entry__media reveal" style="--i:1">
        <div class="media chamfer-lg"><img src="${img(item.image)}" alt="${esc(item.name)}" loading="lazy" width="900" height="740"></div>
      </div>
    </div>
  </section>`;
}

function catalogPage({ file, nav, title, description, heroTitle, heroAccent, heroText, crumb, items, kind, anchorLabel, cta }) {
  return {
    file,
    nav,
    title,
    description,
    image: items[0].image,
    hasProgress: true,
    breadcrumb: [
      { label: 'Início', href: '' },
      { label: crumb, href: file },
    ],
    body: () => `
  <div class="read-progress" aria-hidden="true"></div>
  <main id="conteudo">
    ${pageHero({ title: heroTitle, accent: heroAccent, text: heroText, crumb })}
    ${anchorBar(
      items.map((i) => ({ slug: i.slug, label: kind === 'service' ? i.shortName || i.name : i.name })),
      anchorLabel
    )}
    <div class="entries">
      ${items.map((item, i) => entry(item, i, kind)).join('')}
    </div>
    ${ctaBand(cta.title, cta.text, cta.label)}
  </main>`,
  };
}

export const produtosPage = catalogPage({
  file: 'produtos.html',
  nav: 'menu-produtos',
  title: 'Produtos | Correias, canecas, rolos e revestimentos — Elotec',
  description:
    'Correias em PU, PVC e borracha, canecas para elevadores, rolos, roletes e material para revestimento industrial. Soluções para transporte, elevação, higiene e condições severas.',
  heroTitle: 'Produtos para',
  heroAccent: 'diferentes necessidades industriais',
  heroText:
    'Soluções para transporte, elevação, tração, higiene, resistência química, abrasão e condições severas de operação.',
  crumb: 'Produtos',
  items: data.products,
  kind: 'product',
  anchorLabel: 'os produtos',
  cta: {
    title: 'Não sabe qual correia <span class="text-accent">atende sua aplicação?</span>',
    text: 'Envie as medidas, o produto transportado e as condições da linha. A equipe técnica indica o material e a configuração mais adequados.',
    label: 'Solicitar recomendação',
  },
});

export const servicosPage = catalogPage({
  file: 'servicos.html',
  nav: 'menu-servicos',
  title: 'Serviços | Instalação, conserto e manutenção de correias — Elotec',
  description:
    'Instalação e conserto de correias em PU, PVC, borracha, nylon e transmissão, revestimento de rolos, aplicação de guias e taliscas e consultoria preventiva.',
  heroTitle: 'Serviços técnicos para',
  heroAccent: 'reduzir paradas na sua operação',
  heroText:
    'Vulcanização, colagem a frio, revestimento de rolos, personalização de correias e acompanhamento preventivo — com assistência técnica 24h.',
  crumb: 'Serviços',
  items: data.services,
  kind: 'service',
  anchorLabel: 'os serviços',
  cta: {
    title: 'Equipamento parado ou correia <span class="text-accent">com desgaste?</span>',
    text: 'Acione a assistência técnica da Elotec. Avaliamos o equipamento, indicamos a intervenção e executamos o serviço com processo adequado ao material.',
    label: 'Acionar assistência',
  },
});

export const setoresPage = catalogPage({
  file: 'setores.html',
  nav: 'menu-setores',
  title: 'Setores Atendidos | Frigoríficos, cerealistas, britadores e mais — Elotec',
  description:
    'Soluções em correias transportadoras para frigoríficos, indústria farmacêutica, panificação, cerealistas, britadores, móveis, fabricantes de equipamentos e lenha e cavacos.',
  heroTitle: 'Soluções por',
  heroAccent: 'setor industrial',
  heroText:
    'Cada processo exige um material, uma configuração e uma rotina de manutenção. Veja como a Elotec atende os principais setores da região e do Brasil.',
  crumb: 'Setores Atendidos',
  items: data.sectors,
  kind: 'sector',
  anchorLabel: 'os setores',
  cta: {
    title: 'Seu setor não está na lista? <span class="text-accent">Fale com a gente.</span>',
    text: 'A Elotec avalia aplicações em diferentes segmentos industriais e desenvolve a solução conforme o produto transportado e as condições do equipamento.',
    label: 'Descrever minha aplicação',
  },
});
