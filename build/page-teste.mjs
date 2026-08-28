import { productsMosaic, productsRail, productsSplit, PRODUCTS_LAYOUT } from './page-home.mjs';

/**
 * Página de comparação (só existe na versão single-file / artifact).
 * Mostra os três tratamentos da seção "Nossos produtos" empilhados para
 * escolher qual vai para a home.
 */
const OPCOES = [
  {
    tag: 'Opção A',
    nome: 'Mosaico assimétrico',
    nota: 'Inspirado na Plasmetal: um card alto, um largo e blocos menores. Mostra os 6 produtos em destaque de uma vez, com hierarquia visual — o primeiro card já vem com resumo e CTA abertos.',
    render: () => productsMosaic('opcao-mosaico'),
    atual: PRODUCTS_LAYOUT === 'mosaico',
  },
  {
    tag: 'Opção B',
    nome: 'Carrossel deslizante',
    nota: 'Trilho com os 9 produtos: avança sozinho a cada 4,5 s, para no hover e cede o controle assim que você arrasta, usa as setas ou o teclado. Sangra até a borda para sugerir continuidade.',
    render: () => productsRail('opcao-carrossel'),
    atual: PRODUCTS_LAYOUT === 'carrossel',
  },
  {
    tag: 'Opção C',
    nome: 'Coluna fixa + grade (atual)',
    nota: 'O que está no ar hoje: painel de texto que acompanha a rolagem à esquerda e grade 2×3 à direita.',
    render: () => productsSplit('opcao-coluna'),
    atual: PRODUCTS_LAYOUT === 'coluna',
  },
];

export const testePage = {
  file: 'teste-produtos.html',
  nav: '',
  noindex: true,
  title: 'Produtos na home — 3 opções | Elotec',
  description: 'Comparação dos três tratamentos possíveis para a seção de produtos da home.',
  breadcrumb: [],
  body: () => `
  <main id="conteudo">
    <section class="page-hero">
      <div class="container">
        <p class="eyebrow">Teste de layout</p>
        <h1 class="h-1 page-hero__title" style="margin-top:1rem">Produtos na home <span class="text-accent">— 3 opções</span></h1>
        <p class="lead page-hero__text">Mesmo conteúdo e mesmo card, três composições. Role para comparar e me diga qual fica. Trocar entre elas é uma linha no gerador.</p>
      </div>
    </section>

    ${OPCOES.map(
      (o, i) => `
    <div${i % 2 ? ' class="section--alt"' : ''}>
      <div class="container" style="padding-top:clamp(3rem,6vw,4.5rem)">
        <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;border-bottom:1px solid var(--surface-line);padding-bottom:1.25rem">
          <span class="badge-highlight">${o.tag}</span>
          <h2 class="h-3" style="margin:0">${o.nome}</h2>
          ${o.atual ? '<span class="chip" style="pointer-events:none">no ar agora</span>' : ''}
        </div>
        <p class="muted" style="margin-top:1.25rem;max-width:52rem">${o.nota}</p>
      </div>
      ${o.render()}
    </div>`
    ).join('')}
  </main>`,
};
