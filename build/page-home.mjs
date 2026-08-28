import { icon } from './icons.mjs';
import { data, esc, img, waLink } from './data.mjs';
import { ctaBand } from './layout.mjs';

/* ---------------------------------------------------------------------------
   Hero carrossel — 3 slides com crossfade, ken burns e entrada em cascata.
   Marcado como carrossel acessível: aria-roledescription + tablist + live region.
--------------------------------------------------------------------------- */
function hero() {
  const slides = data.heroSlides
    .map(
      (s, i) => `
        <article class="hero__slide${i === 0 ? ' is-active' : ''}" role="group"
                 aria-roledescription="slide" aria-label="${i + 1} de ${data.heroSlides.length}"
                 aria-hidden="${i === 0 ? 'false' : 'true'}">
          <div class="hero__bg"><img src="${img(s.image)}" alt="" ${i === 0 ? 'fetchpriority="high"' : 'loading="lazy"'} width="1600" height="900"></div>
          <div class="container hero__grid">
            <div class="hero__content">
              <p class="hero__eyebrow anim" style="--d:0">${esc(s.eyebrow)}</p>
              <h${i === 0 ? '1' : '2'} class="hero__title anim" style="--d:1">${esc(s.title)}<span class="accent">${esc(s.accent)}</span></h${i === 0 ? '1' : '2'}>
              <p class="hero__text anim" style="--d:2">${esc(s.text)}</p>
              <div class="hero__actions anim" style="--d:3">
                <a class="btn btn--primary" href="${s.cta.to === '/solicitar-orcamento' ? 'solicitar-orcamento.html' : s.cta.to.replace('/setores#', 'setores.html#').replace(/^\/servicos$/, 'servicos.html')}">${esc(s.cta.label)} ${icon('arrowRight', 16, 2.4)}</a>
                ${i === 0 ? '<a class="btn btn--ghost-light" href="produtos.html">Conhecer soluções</a>' : `<a class="btn btn--ghost-light" href="${waLink()}" target="_blank" rel="noopener">${icon('whatsapp', 18, 1.7)} Falar agora</a>`}
              </div>
            </div>
          </div>
        </article>`
    )
    .join('');

  const dots = data.heroSlides
    .map(
      (s, i) =>
        `<button class="hero__dot" type="button" role="tab" aria-selected="${i === 0}" aria-label="Slide ${i + 1}: ${esc(s.title)}"></button>`
    )
    .join('');

  return `
  <section class="hero" data-carousel aria-roledescription="carrossel" aria-label="Destaques da Elotec" tabindex="-1">
    <div class="hero__viewport">
      ${slides}
      <div class="hero__dots" role="tablist" aria-label="Escolher slide">${dots}</div>
      <div class="hero__controls">
        <button class="hero__arrow" type="button" data-hero-prev aria-label="Slide anterior">${icon('chevronLeft', 20, 2.2)}</button>
        <button class="hero__arrow" type="button" data-hero-next aria-label="Próximo slide">${icon('chevronRight', 20, 2.2)}</button>
      </div>
    </div>
    <p class="hero__live visually-hidden" aria-live="polite"></p>

    <div class="hero__trust on-dark">
      <div class="container">
        <p class="trust-badge">${icon('clock', 18, 1.8)} Desde 2001 no chão de fábrica</p>
        <p class="trust-badge">${icon('bolt', 18, 1.8)} Assistência técnica 24h</p>
        <p class="trust-badge">${icon('pin', 18, 1.8)} Atendimento em todo o Brasil</p>
        <p class="trust-badge">${icon('target', 18, 1.8)} 8+ setores industriais</p>
      </div>
    </div>
  </section>`;
}

/* Marquee amarelo com os 8 setores (itens clicáveis, pausa acessível). */
function marquee() {
  const items = data.sectors
    .map((s) => `<a class="marquee__item" href="setores.html#${s.slug}">${esc(s.name)}</a>`)
    .join('');
  return `
  <div class="marquee" data-paused="false" aria-label="Setores atendidos">
    <div class="marquee__track">${items}</div>
    <button class="marquee__pause" type="button" aria-pressed="false" aria-label="Pausar rolagem dos setores">${icon('pause', 14, 0)}</button>
  </div>`;
}

/* Card de produto reutilizado na home e nos blocos de relacionados. */
export function productCard(p, i = 0, feature = false) {
  return `
        <a class="product-card${feature ? ' product-card--feature' : ''} reveal" style="--i:${i}" href="produtos.html#${p.slug}">
          <img src="${img(p.image)}" alt="${esc(p.name)}" loading="lazy" width="640" height="480">
          <div class="product-card__body">
            <p class="product-card__cat">${esc(p.categoryLabel)}</p>
            <h3 class="product-card__name">${esc(p.name)}</h3>
            <div class="product-card__more"><div>
              <p class="product-card__summary">${esc(p.summary)}</p>
              <span class="product-card__cta">Ver detalhes ${icon('arrowRight', 14, 2.4)}</span>
            </div></div>
          </div>
        </a>`;
}

/* ---------------------------------------------------------------------------
   Seção "Nossos produtos" — três tratamentos possíveis.
   Troque PRODUCTS_LAYOUT para escolher qual vai para a home.
   Todos usam o mesmo card e os mesmos dados; muda só a composição.
--------------------------------------------------------------------------- */
export const PRODUCTS_LAYOUT = 'carrossel'; // 'mosaico' | 'carrossel' | 'coluna'

const PRODUCTS_TEXT = {
  eyebrow: 'Catálogo técnico',
  soft: 'nossos',
  strong: 'produtos',
  lead: 'Correias em PU, PVC e borracha, canecas, rolos, roletes e revestimentos para diferentes aplicações industriais.',
};

/** Cabeçalho comum: título com filete amarelo à esquerda, apoio à direita. */
function productsHead(extra = '') {
  return `
      <div class="section-head">
        <div class="section-head__main reveal">
          <p class="eyebrow">${PRODUCTS_TEXT.eyebrow}</p>
          <h2 class="h-2 section-title" style="margin-top:0.9rem">
            <span class="t-soft">${PRODUCTS_TEXT.soft}</span>
            <span class="t-strong">${PRODUCTS_TEXT.strong}</span>
          </h2>
        </div>
        <div class="section-head__aside reveal" style="--i:1">
          <p class="lead">${PRODUCTS_TEXT.lead}</p>
          <div style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:center">
            <a class="btn btn--outline btn--sm" href="produtos.html">Ver todos os produtos ${icon('arrowRight', 15, 2.4)}</a>
            ${extra}
          </div>
        </div>
      </div>`;
}

/** A — mosaico assimétrico: um card alto, um largo e blocos menores. */
export function productsMosaic(id = 'produtos') {
  const featured = data.products.filter((p) => p.featured).slice(0, 6);
  return `
  <section class="section" id="${id}">
    <div class="container">
      ${productsHead()}
      <div class="product-mosaic">
        ${featured.map((p, i) => productCard(p, Math.min(i, 3), i === 0)).join('')}
      </div>
    </div>
  </section>`;
}

/** B — carrossel: os 9 produtos em trilho com arrasto, setas e avanço suave. */
export function productsRail(id = 'produtos') {
  return `
  <section class="section" id="${id}">
    <div class="container">
      ${productsHead()}
      <div class="product-rail" data-rail>
        <div class="product-rail__track" data-rail-track tabindex="0" role="group" aria-label="Produtos Elotec — arraste ou use as setas">
          ${data.products.map((p, i) => productCard(p, Math.min(i, 3))).join('')}
        </div>
        <div class="product-rail__foot">
          <div class="product-rail__bar" aria-hidden="true"><span data-rail-bar></span></div>
          <div class="product-rail__nav">
            <button class="rail-arrow" type="button" data-rail-prev aria-label="Produtos anteriores">${icon('chevronLeft', 20, 2.2)}</button>
            <button class="rail-arrow" type="button" data-rail-next aria-label="Próximos produtos">${icon('chevronRight', 20, 2.2)}</button>
          </div>
        </div>
      </div>
    </div>
  </section>`;
}

/** C — layout atual: painel de texto sticky + grade 2×3. */
export function productsSplit(id = 'produtos') {
  const featured = data.products.filter((p) => p.featured).slice(0, 6);
  return `
  <section class="section" id="${id}">
    <div class="container split-grid">
      <div class="split-grid__aside reveal">
        <p class="eyebrow">${PRODUCTS_TEXT.eyebrow}</p>
        <h2 class="h-2 section-title" style="margin-top:1.25rem">
          <span class="t-soft">${PRODUCTS_TEXT.soft}</span>
          <span class="t-strong">${PRODUCTS_TEXT.strong}</span>
        </h2>
        <p class="lead" style="margin-top:1.25rem">${PRODUCTS_TEXT.lead}</p>
        <div style="margin-top:2rem">
          <a class="btn btn--outline" href="produtos.html">Ver todos os produtos ${icon('arrowRight', 16, 2.4)}</a>
        </div>
        <ul class="check-list" style="margin-top:2.5rem;grid-template-columns:1fr">
          <li>Dimensionamento conforme a aplicação</li>
          <li>Emenda por vulcanização ou colagem a frio</li>
          <li>Personalização com guias e taliscas</li>
        </ul>
      </div>
      <div class="card-grid">
        ${featured.map((p, i) => productCard(p, i % 3)).join('')}
      </div>
    </div>
  </section>`;
}

const PRODUCTS_LAYOUTS = { mosaico: productsMosaic, carrossel: productsRail, coluna: productsSplit };

function products() {
  return PRODUCTS_LAYOUTS[PRODUCTS_LAYOUT]();
}

/* Quem somos + estatísticas com count-up. */
function about() {
  return `
  <section class="section section--navy" id="quem-somos">
    <div class="container about-grid">
      <div class="reveal">
        <p class="eyebrow" style="color:#fff">Quem somos</p>
        <h2 class="h-2 section-title" style="margin-top:1.25rem">
          <span class="t-soft">conhecimento</span>
          <span class="t-strong">de chão de fábrica</span>
        </h2>
        <p class="lead" style="margin-top:1.5rem">Desde 2001, a Elotec atua com soluções em correias transportadoras, instalação, conserto, manutenção e avaliação de sistemas industriais — com conhecimento técnico construído no chão de fábrica.</p>

        <div class="stats">
          <div class="stat"><p class="stat__num"><span data-count-to="2001">2001</span></p><p class="stat__label">início das atividades</p></div>
          <div class="stat"><p class="stat__num"><span data-count-to="24" data-suffix="h">24h</span></p><p class="stat__label">assistência técnica</p></div>
          <div class="stat"><p class="stat__num"><span data-count-to="2">2</span></p><p class="stat__label">unidades: Chapecó–SC e Toledo–PR</p></div>
          <div class="stat"><p class="stat__num"><span data-count-to="8" data-suffix="+">8+</span></p><p class="stat__label">setores industriais atendidos</p></div>
        </div>

        <div style="margin-top:2.75rem">
          <a class="btn btn--primary" href="a-elotec.html">Saiba mais sobre a Elotec ${icon('arrowRight', 16, 2.4)}</a>
        </div>
      </div>

      <div class="about-media reveal" style="--i:1">
        <div class="media chamfer-lg"><img src="images/servicos/vulcanizacao.webp" alt="Técnico da Elotec realizando manutenção em correia transportadora" loading="lazy" width="900" height="760"></div>
      </div>
    </div>
  </section>`;
}

/* Quatro serviços em destaque. */
function services() {
  const picks = ['instalacao-conserto-correias-pu', 'revestimento-de-rolos', 'aplicacao-guias-taliscas', 'consultoria-acompanhamento-preventivo'];
  const cards = picks
    .map((slug, i) => {
      const s = data.services.find((x) => x.slug === slug);
      const title = slug === 'instalacao-conserto-correias-pu' ? 'Instalação e conserto de correias' : s.name;
      return `
        <article class="service-card reveal" style="--i:${i}">
          <div class="media"><img src="${img(s.image)}" alt="${esc(title)}" loading="lazy" width="560" height="350"></div>
          <span class="service-card__icon" aria-hidden="true">${icon(s.slug, 22, 1.7)}</span>
          <div class="service-card__body">
            <h3 class="h-4">${esc(title)}</h3>
            <p>${esc(s.summary)}</p>
            <a class="link-arrow" href="servicos.html#${s.slug}" style="margin-top:0.5rem">Conhecer ${icon('arrowRight', 15, 2.4)}</a>
          </div>
        </article>`;
    })
    .join('');

  return `
  <section class="section section--alt" id="servicos">
    <div class="container">
      <div style="display:flex;flex-wrap:wrap;gap:1.5rem;align-items:flex-end;justify-content:space-between">
        <div class="reveal">
          <p class="eyebrow">Serviços técnicos</p>
          <h2 class="h-2 section-title" style="margin-top:1.25rem">
            <span class="t-soft">nossos</span>
            <span class="t-strong">serviços</span>
          </h2>
        </div>
        <p class="lead reveal" style="--i:1;max-width:30rem">Equipe própria para instalação, conserto, revestimento e acompanhamento preventivo — reduzindo paradas e prolongando a vida útil dos equipamentos.</p>
      </div>
      <div class="card-grid" style="margin-top:3rem">${cards}</div>
      <div class="reveal" style="margin-top:2.5rem">
        <a class="btn btn--outline" href="servicos.html">Ver todos os serviços ${icon('arrowRight', 16, 2.4)}</a>
      </div>
    </div>
  </section>`;
}

/* Onde estamos + solicite um orçamento (split navy / claro). */
function contact() {
  const c = data.contact;
  return `
  <section class="split-panels" id="contato">
    <div class="container split-panels__grid">
    <div class="panel panel--navy on-dark">
      <div class="reveal">
        <p class="eyebrow" style="color:#fff">Onde estamos</p>
        <h2 class="h-3" style="margin-top:1.25rem;color:#fff">Duas unidades, atendimento em todo o Brasil</h2>

        <div class="unit" style="margin-top:2rem">
          <span class="unit__badge">Matriz</span>
          <h3 class="h-4" style="color:#fff">Chapecó — SC</h3>
          <address>${esc(c.address.street)}<br>${esc(c.address.district)} — ${esc(c.address.city)}/${esc(c.address.state)}<br>CEP ${esc(c.address.zip)}</address>
        </div>
        <div class="unit">
          <span class="unit__badge">Unidade</span>
          <h3 class="h-4" style="color:#fff">Toledo — PR</h3>
          <address>Atendimento técnico regional para o oeste do Paraná.</address>
        </div>

        <div class="contact-lines">
          <p class="contact-line">${icon('whatsapp', 20, 1.7)} <a href="https://wa.me/${c.whatsapp}" target="_blank" rel="noopener"><span>WhatsApp</span>${esc(c.whatsappDisplay)}</a></p>
          <p class="contact-line">${icon('phone', 20, 1.7)} <a href="tel:+554933286223"><span>Administrativo</span>${esc(c.phoneAdmin)}</a></p>
          <p class="contact-line">${icon('mail', 20, 1.7)} <a href="mailto:${c.email}"><span>E-mail</span>${esc(c.email)}</a></p>
        </div>
      </div>
    </div>

    <div class="panel panel--light">
      <div class="reveal">
        <p class="eyebrow">Solicite um orçamento</p>
        <h2 class="h-3" style="margin-top:1.25rem">Está com problemas na correia ou no transportador?</h2>
        <p class="lead" style="margin-top:1.25rem">Envie fotos, medidas e informações do equipamento para uma avaliação inicial. Nossa equipe técnica responde com a recomendação mais adequada à sua operação.</p>

        <ul class="check-list" style="margin-top:2rem;grid-template-columns:1fr">
          <li>Avaliação inicial sem compromisso</li>
          <li>Atendimento emergencial 24h</li>
          <li>Recomendação técnica por aplicação</li>
        </ul>

        <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-top:2.5rem">
          <a class="btn btn--primary" href="solicitar-orcamento.html">Solicitar orçamento ${icon('arrowRight', 16, 2.4)}</a>
          <a class="btn btn--whats" href="${waLink()}" target="_blank" rel="noopener">${icon('whatsapp', 18, 1.7)} WhatsApp</a>
        </div>
      </div>
    </div>
    </div>
  </section>`;
}

export const homePage = {
  file: 'index.html',
  nav: 'home',
  title: 'Elotec Correias | Soluções completas em correias transportadoras',
  description:
    'Fornecimento, instalação, conserto e manutenção de correias transportadoras em PU, PVC, borracha, nylon e transmissão. Assistência técnica 24h em todo o Brasil. Desde 2001.',
  image: '/images/hero.webp',
  breadcrumb: [{ label: 'Início', href: '' }],
  body: () => `
  <main id="conteudo">
    ${hero()}
    ${marquee()}
    ${products()}
    ${about()}
    ${services()}
    ${contact()}
    ${ctaBand(
      'Precisa encontrar a correia ideal ou <span class="text-accent">recuperar seu equipamento?</span>',
      'Fale com a equipe técnica da Elotec e receba uma recomendação adequada ao seu processo, ao produto transportado e às condições da sua linha.'
    )}
  </main>`,
};
