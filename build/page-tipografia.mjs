import { icon } from './icons.mjs';
import { data, esc } from './data.mjs';

/**
 * Página de comparação de pareamentos tipográficos (só na versão single-file).
 * Cada amostra troca apenas --font-display e --font-body, que o refactor de
 * tokens deixou como as duas únicas portas de entrada da tipografia.
 */
const PARES = [
  {
    id: 'atual',
    tag: 'Atual',
    nome: 'Archivo + Cabin',
    display: "'Archivo', sans-serif",
    body: "'Cabin', sans-serif",
    nota: 'Cabin é a fonte da marca; Archivo entrou nos títulos no redesign. Funciona, mas as duas são humanistas de contraste baixo — a diferença entre título e corpo vem quase só do peso e do tamanho, não do desenho.',
  },
  {
    id: 'plex',
    tag: 'Opção B',
    nome: 'Archivo + IBM Plex Sans',
    display: "'Archivo', sans-serif",
    body: "'IBM Plex Sans', sans-serif",
    nota: 'Troca só o corpo. A Plex Sans nasceu para documentação técnica: terminais retos, números de largura consistente e melhor legibilidade em 15–17px, que é o tamanho das listas de benefícios e das fichas técnicas. Mantém os títulos como estão hoje.',
  },
  {
    id: 'grotesk',
    tag: 'Opção C',
    nome: 'Space Grotesk + IBM Plex Sans',
    display: "'Space Grotesk', sans-serif",
    body: "'IBM Plex Sans', sans-serif",
    nota: 'Mais personalidade no título: a Space Grotesk tem cortes diagonais e formas mais fechadas, que dão um ar de engenharia contemporânea. O risco é soar mais de startup do que de chão de fábrica.',
  },
  {
    id: 'barlow',
    tag: 'Opção D',
    nome: 'Barlow Condensed + Barlow',
    display: "'Barlow Condensed', sans-serif",
    body: "'Barlow', sans-serif",
    nota: 'Superfamília: display condensada e corpo normal, do mesmo desenho. O argumento é funcional — títulos em português são longos ("Soluções completas em correias transportadoras") e a condensada cabe em menos linhas com corpo maior. Vocabulário visual de sinalização industrial.',
  },
];

const AMOSTRA = (p) => `
      <div class="tipo-amostra" style="--font-display: ${p.display}; --font-body: ${p.body}">
        <p class="eyebrow">Catálogo técnico</p>
        <h2 class="h-2 section-title" style="margin-top:0.9rem">
          <span class="t-soft">nossos</span>
          <span class="t-strong">produtos</span>
        </h2>
        <p class="lead" style="margin-top:1.25rem">Soluções completas em correias transportadoras para sua indústria não parar.</p>
        <p class="muted" style="margin-top:1rem">${esc(data.products[0].description)}</p>

        <div class="entry__block" style="margin-top:1.75rem">
          <h3>Características técnicas</h3>
          <ul class="check-list">
            <li>Largura de até 1.100 mm</li>
            <li>Processo de vulcanização</li>
            <li>Secagem de aproximadamente 3 horas</li>
            <li>Temperaturas de até 100 °C</li>
          </ul>
        </div>

        <div class="stats" style="margin-top:2rem">
          <div class="stat"><p class="stat__num">2001</p><p class="stat__label">início das atividades</p></div>
          <div class="stat"><p class="stat__num">24h</p><p class="stat__label">assistência técnica</p></div>
          <div class="stat"><p class="stat__num">8+</p><p class="stat__label">setores atendidos</p></div>
        </div>

        <div style="margin-top:2rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:center">
          <a class="btn btn--primary" href="#/solicitar-orcamento">Solicitar orçamento ${icon('arrowRight', 16, 2.4)}</a>
          <span class="badge-highlight">${icon('spark', 14, 2)} Principal área de atuação</span>
        </div>
      </div>`;

export const tipografiaPage = {
  file: 'teste-tipografia.html',
  nav: '',
  noindex: true,
  title: 'Tipografia — 4 pareamentos | Elotec',
  description: 'Comparação de combinações tipográficas aplicadas ao conteúdo real da Elotec.',
  breadcrumb: [],
  body: () => `
  <main id="conteudo">
    <section class="page-hero">
      <div class="container">
        <p class="eyebrow">Teste de tipografia</p>
        <h1 class="h-1 page-hero__title" style="margin-top:1rem">Quatro pareamentos <span class="text-accent">no mesmo conteúdo</span></h1>
        <p class="lead page-hero__text">Muda só a fonte de título e a de corpo — cores, espaçamento e escala são idênticos. Trocar entre eles são duas linhas, porque a tipografia inteira passa por dois tokens.</p>
      </div>
    </section>

    ${PARES.map(
      (p, i) => `
    <section class="section${i % 2 ? ' section--alt' : ''}" style="padding-block:clamp(2.5rem,5vw,4rem)">
      <div class="container">
        <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:baseline;border-bottom:1px solid var(--surface-line);padding-bottom:1rem;margin-bottom:2rem">
          <span class="badge-highlight">${p.tag}</span>
          <h2 class="h-3" style="margin:0">${esc(p.nome)}</h2>
        </div>
        <p class="muted" style="max-width:60ch;margin-bottom:2.5rem">${esc(p.nota)}</p>
        ${AMOSTRA(p)}
      </div>
    </section>`
    ).join('')}
  </main>`,
};
