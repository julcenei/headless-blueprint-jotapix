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

As 8 páginas do site viram rotas em hash (mais a de comparação, 9 no total) (`#/produtos/correia-pvc`), CSS e JS entram
embutidos e as 22 fotos viram data URIs — cada arquivo uma única vez, atribuído
em runtime a partir de um mapa. Serve para publicar como Artifact ou mandar o
arquivo por e-mail: abre em qualquer navegador, sem servidor. `dist/` não é
versionado; é sempre regerado a partir do site.

## Layout da seção de produtos (home)

Existem três tratamentos prontos em `build/page-home.mjs`; a constante
`PRODUCTS_LAYOUT` decide qual vai para a home:

| valor | o que é |
|---|---|
| `mosaico` | grade assimétrica: um card alto, um largo e blocos menores |
| `carrossel` | trilho com os 9 produtos em card retrato, avanço automático, arrasto, setas e barra (padrão) |
| `coluna` | painel de texto sticky + grade 2×3 (o primeiro layout) |

As páginas de comparação existem apenas na versão single-file e não são
geradas no site publicado: `#/teste-produtos` (`build/page-teste.mjs`) com os
três layouts da seção de produtos e `#/teste-cards` (`build/page-cards.mjs`) com
os dois tratamentos do card. A tipografia inteira passa por dois tokens —
`--font-display` e `--font-body` — então trocar de pareamento são duas linhas.

## Escala tipográfica

O CSS declara **8 tamanhos** abaixo dos títulos — `0.6875 / 0.75 / 0.8125 /
0.875 / 1 / 1.125 / 1.375 / 1.5rem` — e nada fora deles. Os títulos usam
`clamp()` em `.h-1` … `.h-4`. Antes eram 19 tamanhos escolhidos caso a caso
(`0.92`, `0.93`, `0.94`, `0.95`, `0.97`, `0.98`…), o que produzia uma rampa
contínua sem hierarquia perceptível e um corpo de texto de 15px.

São **2 pesos**: 400 e 700, nas duas famílias. A fonte é carregada só com
esses pesos. A hierarquia é feita por **tamanho e espaço**, não por peso — é o
que separa um layout calmo de um layout que grita.

**Caixa alta é rótulo, nunca ação nem título.** Vale para sobrelinhas,
categorias de card, selos e títulos de coluna. Botões, links e títulos ficam em
caixa de frase; junto com a caixa alta saiu o `letter-spacing` positivo, que só
existe para tornar versal legível e em caixa de frase apenas estica o texto.

## Contorno e foco em elementos chanfrados

`border`, `box-shadow: inset` e `outline` são pintados no retângulo e só depois
recortados pelo `clip-path`: na aresta diagonal do chanfro não sobra linha
nenhuma. Isso deixava os botões de contorno com a borda aberta e — mais grave —
o **anel de foco do teclado invisível** em tudo que é chanfrado, apesar de o
`outline-width` computado dizer 3px.

A solução é uma pintura só: um anel (polígono com furo, o miolo percorrido no
sentido inverso) num pseudo-elemento que recebe o mesmo recorte. Serve para o
contorno permanente e para o foco, e continua correto com cor translúcida,
porque não são duas camadas se somando. Vale para `.btn`, `.float-btn`,
`.hero__eyebrow`, `.hero__arrow`, `.rail-arrow`, `.nav-toggle` e
`.product-card`; a espessura é `--contorno` e a cor, `--contorno-cor`.

A cor do foco é o token `--foco`: azul-marinho no claro, amarelo no escuro.
Amarelo sobre branco dá 1,7:1, abaixo dos 3:1 que a WCAG 2.4.11 exige para o
indicador de foco. Sobre o amarelo e sobre o verde do WhatsApp o anel volta a
ser escuro por `--foco-cor`. Medido: 9,34:1 no CTA amarelo, 14,41:1 no botão de
contorno, 10,61:1 no botão fantasma do hero, 7,27:1 no botão do WhatsApp.

## Skills instaladas

`.claude/skills/` traz duas skills de design, ambas apenas markdown:

- **frontend-design** (Anthropic) — direção visual e tipografia.
- **taste-skill** (Leonxlnx, MIT) — lista de "AI tells" e um pre-flight de ~60
  itens. `ORIGEM.md` registra o commit de origem e, mais importante, **quais
  regras dela ficam suspensas aqui e por quê**: ela pressupõe React/Tailwind e
  projeto em inglês, então a proibição de SVG feito à mão, a proibição total do
  travessão e todo o pre-flight de React não se aplicam.

O que a taste-skill encontrou e já foi corrigido, com os números medidos:

| regra | antes | depois |
|---|---|---|
| eyebrows por página ≤ `ceil(seções/3)` | 6 na home (teto 2) | 2 |
| travessão como separador decorativo | 27 nas 8 páginas | 9, todos pontuação de frase |
| pontinho de status decorativo | 1 (`.pulse` na barra superior) | 0 |

O critério dos eyebrows: **um eyebrow se justifica quando diz o que o título não
diz.** "CATÁLOGO TÉCNICO" sobre "nossos produtos" é repetição; "QUEM SOMOS"
sobre "conhecimento de chão de fábrica" não é, porque o título ali é figurado.
Sobraram o do hero e esse.

Numa segunda passada, o pre-flight inteiro (~60 itens) pegou mais dois:
**quatro rótulos diferentes para a mesma ação** — "Falar no WhatsApp", "Falar
agora", "Abrir conversa" e "WhatsApp" abriam todos a mesma conversa —, agora
unificados em "Falar no WhatsApp" nos 17 botões; e a **navegação em 84px**,
acima do teto de 80px da regra, agora em 80.

Quatro regras do pre-flight **não** foram aplicadas, e a razão é a mesma nas
quatro: elas contradizem a Plasmetal, que é a referência escolhida para este
projeto.

| regra | nosso estado | por que fica |
|---|---|---|
| manchete do hero ≤ 2 linhas | 5 linhas | o texto é do brief, palavra por palavra |
| um tema por página, sem inverter | 4 inversões na home | faixas navy de ponta a ponta são o recurso industrial do projeto, e a Plasmetal faz igual |
| sem "título à esquerda + apoio à direita" | 1 na home | é o cabeçalho de seção que veio da própria Plasmetal |
| sem barra de progresso com trilho | 1 no carrossel | a regra mira gráficos de comparação, não indicador de posição |

A numeração ordinal das fichas de produto ("1. Correia PU Sanitária") também
seria pega pela regra de eyebrows numerados, e fica porque o brief pede.

## Regras de uso do amarelo e do movimento

**Amarelo** é reservado a três papéis: **ação** (botões, links em hover),
**estado** (foco, item ativo da barra de âncoras, progresso) e **marca**
(logotipo, faixa de setores, selos que o brief pede). Divisórias, filetes e
marcadores de conteúdo usam o token `--rule`, que é neutro no claro e
translúcido no escuro. A única exceção é o filete dos 5 passos do orçamento,
onde a numeração é uma sequência de verdade.

**Assinatura**: o chanfro, e ele tem **uma forma só** — o canto inferior direito
cortado. Vale para blocos, selos, setas e botões; os botões tinham a aresta
direita inteira inclinada, o que os fazia gritar mais alto que todo o resto e
exigia padding assimétrico para compensar. O corte é proporcional à altura:
`--btn-cut` de 14px no botão normal e 10px no pequeno, na mesma família dos
10–14px dos outros componentes. Em escala grande o chanfro aparece uma vez só,
no painel do hero — que também entrega a quebra de grid que o brief pedia: o
texto sobrepõe a foto em vez de flutuar sobre um véu.

**Movimento**: a página tem um único loop ambiente — a faixa de setores, que o
brief pede — e um momento orquestrado, o hero. Todo o resto responde a uma ação
do visitante. O carrossel de produtos não anda sozinho: arrasto, setas, teclado
e scroll-snap.

## Decisões de design (o que mudou em relação ao layout original)

- **Marca preservada, apresentação reconstruída.** Azul-marinho + amarelo, chanfros por
  `clip-path` e o logotipo continuam iguais. O que mudou foi a escala: tipografia display
  fluida, espaço negativo generoso e hierarquia mais firme entre título, resumo e detalhe.
- **Pareamento tipográfico.** **Familjen Grotesk** nos títulos e **Public Sans** no
  corpo, com `letter-spacing` negativo e `clamp()` em todos os tamanhos, sem breakpoints
  de fonte. A Familjen Grotesk vai só até 700 — pedir 800 faz a API do Google devolver
  HTTP 400 e o navegador sintetizar um falso negrito —, então o display desceu de 800
  para 700 e o site passou a ter dois pesos. Como a Familjen 700 tem caixa alta 6% menor
  que a Archivo 800, os títulos com `clamp()` subiram 6% para manter a mesma presença; os
  tamanhos fixos ficaram na escala, porque 6% num rótulo de 12px é 0,7px. No corpo o
  movimento é o inverso: a Public Sans tem altura de x 6% maior que a Cabin, então 16px
  continua 16px e apenas lê melhor — mas é 13% mais larga, e por isso a medida do
  `.prose` foi de 57ch para 62ch, o que devolve ~73 caracteres por linha.
- **Hero cinematográfico.** Crossfade entre os 3 slides + ken burns lento na foto,
  entrada do texto em cascata e gradiente navy diagonal (em vez do véu uniforme). Setas,
  indicadores com barra de progresso, teclado, swipe e pausa automática no hover/foco/aba
  oculta. A faixa de selos que existia entre o hero e o marquee saiu: os quatro selos
  repetiam a barra superior ("Atendimento em todo o Brasil · Assistência técnica 24h") e
  três das quatro estatísticas de "quem somos" (2001, 24h, 8+ setores), e empilhava uma
  terceira faixa horizontal antes do primeiro conteúdo real da página.
- **Marquee de setores.** Amarelo, itens clicáveis e separador quadradinho, como o brief
  pede. O que mudou foi o acabamento: as pontas dissolvem por máscara em vez de cortar a
  palavra no meio, a faixa afinou de 67 para 57px, o quadradinho virou losango (o mesmo
  vocabulário diagonal do chanfro) e o botão de pausa ficou só ícone — um bloco navy de
  44px era o elemento mais pesado da faixa. Ele ganha corpo quando `aria-pressed="true"`,
  porque aí é estado. A máscara vive no `.marquee__viewport`, não na faixa: assim o
  amarelo continua sangrando de ponta a ponta e o controle de pausa não desbota junto.
- **Grid quebrado e assimetria controlada.** A home deixou de ser uma pilha de blocos
  centrados: a seção de produtos usa painel de texto *sticky* + grade de cards, "quem
  somos" tem moldura amarela deslocada sobre a foto, e o bloco final é um split
  navy/claro de ponta a ponta.
- **Cards de produto com a legenda fora da foto.** A imagem fica limpa e o nome vai numa
  faixa clara no pé do card, com a categoria acima; o hover dá zoom na foto e traz uma
  seta ao lado do nome. É o padrão de 31 dos 40 blocos com foto do site — a sobreposição
  de texto ficou reservada ao hero, onde o painel chanfrado a transforma em assinatura.
  Sobrepor texto a fotografia faria o contraste depender de cada imagem, inclusive das
  que ainda vão entrar; na faixa ele é fixado por token (5,29:1 na categoria, 13,11:1 no
  nome). A foto tem proporção fixa e a faixa absorve a diferença de altura, então um nome
  de três linhas não estica as fotos da fileira. `productCard(..., 'sobreposto')` devolve
  o tratamento antigo, usado só na rota de comparação `#/teste-cards`.
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
