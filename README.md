# Elotec — Redesign premium (HTML + CSS + JS puros)

Site institucional da **Elotec — Serviços Técnicos em Correias** reconstruído a partir do
brief `docs/brief-elotec-premium.md`, preservando marca, conteúdo e arquitetura de
informação e elevando a apresentação.

Sem frameworks, sem build no cliente, sem dependências em runtime: as páginas abrem
direto do sistema de arquivos (`index.html`) ou em qualquer hospedagem estática.

## Estrutura

```
index.html                     Home
a-elotec.html                  A Elotec (história e diferenciais)
produtos.html                  9 produtos, com barra de âncoras
servicos.html                  8 serviços
setores.html                   8 setores atendidos
solicitar-orcamento.html       5 passos + formulário → WhatsApp + FAQ
politica-de-privacidade.html   LGPD
404.html
css/style.css                  Folha única, comentada por seção
js/main.js                     Comportamentos (carrossel, menu, form, reveals…)
images/                        22 fotos reais em .webp
data/dados.json                Conteúdo real (produtos, serviços, setores, contatos)
build/                         Gerador estático em Node (só para desenvolvimento)
docs/brief-elotec-premium.md   Brief original
sitemap.xml · robots.txt       Gerados junto com as páginas
```

## Como o HTML é gerado

O entregável precisa abrir por `file://`, o que impede carregar `dados.json` via `fetch`.
Então o conteúdo é injetado **em tempo de geração** e o resultado versionado é HTML
estático comum:

```bash
node build/build.mjs      # regrava as 8 páginas + sitemap.xml + robots.txt
```

O gerador usa apenas Node (nenhuma dependência instalada). Editou `data/dados.json`,
um template em `build/` ou um texto? Rode o comando e comite o HTML atualizado.
Quem só vai hospedar o site não precisa de nada disso — basta subir a pasta.

- `build/data.mjs` — carrega o JSON, helpers (`esc`, `img`, `waLink`, busca por slug)
- `build/icons.mjs` — ícones SVG inline e o mapa slug → ícone
- `build/layout.mjs` — `<head>` (meta/OG/JSON-LD), header, footer, faixa de CTA
- `build/page-home.mjs`, `build/page-catalog.mjs`, `build/page-misc.mjs` — as páginas

## Versão single-file (link compartilhável)

```bash
node build/build-artifact.mjs   # gera dist/elotec-single.html (~3,5 MB)
```

As 8 páginas viram rotas em hash (`#/produtos/correia-pvc`), CSS e JS entram
embutidos e as 22 fotos viram data URIs — cada arquivo uma única vez, atribuído
em runtime a partir de um mapa. Serve para publicar como Artifact ou mandar o
arquivo por e-mail: abre em qualquer navegador, sem servidor. `dist/` não é
versionado; é sempre regerado a partir do site.

## Layout da seção de produtos (home)

Existem três tratamentos prontos em `build/page-home.mjs`; a constante
`PRODUCTS_LAYOUT` decide qual vai para a home:

| valor | o que é |
|---|---|
| `mosaico` | grade assimétrica: um card alto, um largo e blocos menores (padrão) |
| `carrossel` | trilho com os 9 produtos, avanço automático, arrasto, setas e barra |
| `coluna` | painel de texto sticky + grade 2×3 (o primeiro layout) |

A página de comparação com as três opções empilhadas existe apenas na versão
single-file, na rota `#/teste-produtos` (`build/page-teste.mjs`) — ela não é
gerada no site publicado.

## Decisões de design (o que mudou em relação ao layout original)

- **Marca preservada, apresentação reconstruída.** Azul-marinho + amarelo, chanfros por
  `clip-path` e o logotipo continuam iguais. O que mudou foi a escala: tipografia display
  fluida, espaço negativo generoso e hierarquia mais firme entre título, resumo e detalhe.
- **Pareamento tipográfico.** Cabin ficou no corpo (mantém a voz da marca) e entrou
  **Archivo** nos títulos — grotesca industrial, mais densa e contemporânea, com
  `letter-spacing` negativo e `clamp()` em todos os tamanhos, sem breakpoints de fonte.
- **Hero cinematográfico.** Crossfade entre os 3 slides + ken burns lento na foto,
  entrada do texto em cascata, gradiente navy diagonal (em vez do véu uniforme) e uma
  faixa de selos de confiança logo abaixo. Setas, indicadores com barra de progresso,
  teclado, swipe e pausa automática no hover/foco/aba oculta.
- **Grid quebrado e assimetria controlada.** A home deixou de ser uma pilha de blocos
  centrados: a seção de produtos usa painel de texto *sticky* + grade de cards, "quem
  somos" tem moldura amarela deslocada sobre a foto, e o bloco final é um split
  navy/claro de ponta a ponta.
- **Cards de produto com hierarquia extra.** Categoria, nome e — revelados no hover/foco —
  resumo e CTA, com zoom suave na foto e chanfro no canto inferior direito. As fotos
  ganharam overlay navy padronizado (`.media`), o que uniformiza um acervo heterogêneo.
- **Conversão sempre à mão.** CTA amarelo fixo no header, CTA flutuante discreto que
  aparece depois da primeira dobra (e some ao subir), WhatsApp flutuante, CTA ao fim de
  cada seção de catálogo e faixa navy de fechamento em todas as páginas internas.
- **Catálogos navegáveis.** Barra de âncoras com ícones lineares + scrollspy que centraliza
  o item ativo, barra de progresso de leitura, seções numeradas com fundo alternado e
  blocos de relacionados (chips para setores/serviços, mini-cards com foto para produtos).
- **Orçamento com menos fricção.** Linha do tempo de 5 passos, validação inline em
  português, pré-seleção de produto/serviço/setor pela URL (`?produto=slug`, usada pelos
  CTAs do catálogo), mensagem de WhatsApp formatada com negrito e FAQ curto ao lado.
- **Movimento com freio.** Fade-up com *stagger* via IntersectionObserver, count-up nas
  estatísticas, marquee que desacelera no hover e tem botão de pausa — tudo desligado sob
  `prefers-reduced-motion: reduce`.
- **Acessibilidade e SEO como parte do acabamento.** HTML semântico, um `<h1>` por página,
  skip link, foco visível, ARIA no carrossel/dropdowns/accordion/menu mobile, `alt` em
  todas as fotos, meta + Open Graph por página e JSON-LD de `LocalBusiness` e
  `BreadcrumbList`. O amarelo de texto pequeno sobre fundo claro virou um âmbar escuro
  (`--accent-ink`) para atingir contraste AA sem perder o tom da marca.

## Verificações feitas

- 8 páginas com HTML balanceado, um `<h1>` cada, sem IDs duplicados e sem `img` sem `alt`
- Sem erros de JavaScript no console (home, catálogos, orçamento, mobile)
- Carrossel, dropdowns, menu mobile, âncoras + scrollspy e envio do formulário testados
  em Chromium (1440px e 390px), incluindo o modo `prefers-reduced-motion`
