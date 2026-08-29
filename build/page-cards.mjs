import { data, esc } from './data.mjs';
import { productCard } from './page-home.mjs';

/**
 * Página de comparação (só existe na versão single-file / artifact).
 * Mesmos 4 produtos, mesmas fotos, mesma largura de coluna: muda só onde o
 * texto mora — sobre a foto ou embaixo dela.
 */
const AMOSTRA = data.products.slice(0, 4);

const OPCOES = [
  {
    tag: 'A',
    nome: 'Texto sobre a foto',
    estado: 'tratamento anterior',
    nota:
      'A foto ocupa o card inteiro e um gradiente navy escurece a base para o texto caber por cima. ' +
      'Categoria em amarelo versal, nome em branco, e no hover aparecem o resumo e o "Ver detalhes". ' +
      'Quatro elementos de texto no mesmo retângulo, mais o gradiente que existe só para tornar isso possível.',
    variante: 'sobreposto',
  },
  {
    tag: 'B',
    nome: 'Legenda embaixo da foto',
    estado: 'no ar agora',
    nota:
      'A foto fica limpa, sem gradiente e sem nada escrito em cima; o nome vai para uma faixa branca no pé do card, ' +
      'com a categoria acima em cinza. O hover dá zoom na foto e traz uma seta ao lado do nome — o resumo sai, ' +
      'porque uma faixa que cresce empurraria a foto. Um elemento de texto por vez, como faz a Plasmetal.',
    variante: '',
  },
];

export const cardsPage = {
  file: 'teste-cards.html',
  nav: '',
  noindex: true,
  title: 'Card de produto — 2 opções | Elotec',
  description: 'Comparação entre o card atual e a variante com legenda embaixo da foto.',
  breadcrumb: [],
  body: () => `
  <main id="conteudo">
    <section class="page-hero">
      <div class="container">
        <p class="eyebrow">Teste de card</p>
        <h1 class="h-1 page-hero__title" style="margin-top:1rem">Card de produto <span class="text-accent">— 2 opções</span></h1>
        <p class="lead page-hero__text">Os mesmos quatro produtos, as mesmas fotos e a mesma largura de coluna nas duas. A única variável é onde o texto mora.</p>
      </div>
    </section>

    ${OPCOES.map(
      (o, i) => `
    <div${i % 2 ? ' class="section--alt"' : ''}>
      <div class="container" style="padding:clamp(3rem,6vw,4.5rem) 0 clamp(3rem,6vw,4.5rem)">
        <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;border-bottom:1px solid var(--rule);padding-bottom:1.25rem">
          <span class="badge-highlight">Opção ${o.tag}</span>
          <h2 class="h-3" style="margin:0">${esc(o.nome)}</h2>
          <span class="chip" style="pointer-events:none">${esc(o.estado)}</span>
        </div>
        <p class="muted" style="margin-top:1.25rem;max-width:60ch">${esc(o.nota)}</p>
        <div class="card-grid comparacao-cards" style="margin-top:clamp(2rem,4vw,3rem)">
          ${AMOSTRA.map((p, n) => productCard(p, n, false, o.variante)).join('')}
        </div>
      </div>
    </div>`
    ).join('')}
  </main>`,
};
