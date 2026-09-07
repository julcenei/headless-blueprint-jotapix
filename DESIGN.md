---
name: Elotec Correias
description: Sistema visual industrial tratado com contenção editorial — azul-marinho, amarelo de sinalização e um chanfro só.
colors:
  azul-industrial: "hsl(232 58% 24%)"
  azul-industrial-deep: "hsl(232 60% 15%)"
  azul-industrial-ink: "hsl(232 62% 11%)"
  azul-industrial-soft: "hsl(232 40% 34%)"
  amarelo-sinalizacao: "hsl(48 96% 52%)"
  amarelo-sinalizacao-warm: "hsl(42 96% 50%)"
  amarelo-sinalizacao-ink: "hsl(36 90% 33%)"
  whatsapp: "#25d366"
  texto: "hsl(225 30% 12%)"
  texto-suave: "hsl(225 12% 42%)"
  superficie: "#fff"
  superficie-alt: "hsl(220 20% 96%)"
  filete: "hsl(225 18% 86%)"
  sobre-escuro-forte: "hsl(225 40% 88%)"
  sobre-escuro-suave: "hsl(225 30% 78%)"
  sobre-escuro-fraco: "hsl(225 30% 62%)"
  titulo-apagado: "hsl(225 15% 56%)"
  erro: "#c0392b"
  sucesso: "#1a9e54"
typography:
  display:
    fontFamily: "Familjen Grotesk, Segoe UI, system-ui, sans-serif"
    fontSize: "clamp(2.4rem, 1.35rem + 4.25vw, 4.75rem)"
    fontWeight: 700
    lineHeight: 1.03
    letterSpacing: "-0.025em"
  headline:
    fontFamily: "Familjen Grotesk, Segoe UI, system-ui, sans-serif"
    fontSize: "clamp(2.125rem, 1.4rem + 3.05vw, 4rem)"
    fontWeight: 700
    lineHeight: 1.03
    letterSpacing: "-0.025em"
  title:
    fontFamily: "Familjen Grotesk, Segoe UI, system-ui, sans-serif"
    fontSize: "clamp(1.5rem, 1.2rem + 1.15vw, 2.125rem)"
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "-0.025em"
  subtitle:
    fontFamily: "Familjen Grotesk, Segoe UI, system-ui, sans-serif"
    fontSize: "clamp(1.1875rem, 1.1rem + 0.35vw, 1.375rem)"
    fontWeight: 700
    lineHeight: 1.25
    letterSpacing: "-0.025em"
  body:
    fontFamily: "Public Sans, Segoe UI, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.65
    letterSpacing: "normal"
  lead:
    fontFamily: "Public Sans, Segoe UI, system-ui, sans-serif"
    fontSize: "clamp(1.0625rem, 1rem + 0.4vw, 1.3125rem)"
    fontWeight: 400
    lineHeight: 1.55
    letterSpacing: "normal"
  label:
    fontFamily: "Public Sans, Segoe UI, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 700
    lineHeight: 1.3
    letterSpacing: "0.16em"
rounded:
  none: "0px"
  sm: "4px"
  chamfer: "18px"
  chamfer-lg: "34px"
  chamfer-btn: "14px"
  chamfer-btn-sm: "10px"
spacing:
  gutter: "clamp(1.25rem, 4vw, 3rem)"
  section-y: "clamp(4.5rem, 9vw, 8.5rem)"
  container: "1280px"
components:
  button-primary:
    backgroundColor: "{colors.amarelo-sinalizacao}"
    textColor: "{colors.azul-industrial}"
    typography: "{typography.body}"
    rounded: "{rounded.chamfer-btn}"
    padding: "1rem 1.8rem"
  button-primary-hover:
    backgroundColor: "{colors.amarelo-sinalizacao-warm}"
    textColor: "{colors.azul-industrial}"
  button-outline:
    backgroundColor: "transparent"
    textColor: "{colors.azul-industrial}"
    typography: "{typography.body}"
    rounded: "{rounded.chamfer-btn}"
    padding: "1rem 1.8rem"
  button-outline-hover:
    backgroundColor: "{colors.azul-industrial}"
    textColor: "{colors.superficie}"
  button-whatsapp:
    backgroundColor: "{colors.whatsapp}"
    textColor: "#06331a"
    typography: "{typography.body}"
    rounded: "{rounded.chamfer-btn}"
    padding: "1rem 1.8rem"
  card-product:
    backgroundColor: "{colors.superficie-alt}"
    textColor: "{colors.azul-industrial}"
    rounded: "{rounded.chamfer}"
    padding: "1.15rem 1.35rem 1.4rem"
  input-field:
    backgroundColor: "{colors.superficie-alt}"
    textColor: "{colors.texto}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: "0.85rem 1rem"
---

# Design System: Elotec Correias

## Overview

**Estrela-guia: O Chão de Fábrica.** Materiais honestos, função antes de
ornamento, nada decorativo que não trabalhe. É a mesma frase que o
posicionamento da Elotec já usa em palavras — "conhecimento de chão de fábrica"
— traduzida em forma.

O que impede isso de virar rudeza é o segundo eixo: **o tratamento é calmo e
editorial.** Muito respiro, hierarquia construída por tamanho e espaço em vez de
peso, dois pesos de fonte no site inteiro. O assunto é industrial; a composição
é contida. Essa tensão é o sistema — um catálogo de correias transportadoras
composto com a disciplina de uma publicação de design, não com a densidade de um
catálogo de distribuidor.

**Anti-referências, todas confirmadas pelo cliente.** O site nunca deve parecer:
site de startup ou SaaS (gradiente roxo, glassmorphism, três cards iguais);
catálogo de distribuidor genérico (grade densa, foto pequena, zero hierarquia);
site institucional dos anos 2010 (carrossel gigante centralizado, ícones em
círculo, "missão / visão / valores"); ou portfólio de agência (texto girado,
cursor customizado, faixa "BRAND. MOTION. SPATIAL."). As quatro foram escolhidas
como coisas a evitar, não como uma delas.

## Colors

### Primary

**Azul-Industrial** `hsl(232 58% 24%)` — o azul de equipamento pesado. Carrega
títulos, superfícies escuras de ponta a ponta e o texto de corpo em versão quase
preta. Três graus mais profundos existem para empilhamento: `deep` para fundos de
foto, `ink` para o painel do hero, `soft` para texto secundário sobre claro.

### Secondary

**Amarelo-Sinalização** `hsl(48 96% 52%)` — o amarelo de sinalização de segurança
industrial. **É o token mais regrado do sistema** e existe para exatamente três
papéis: **ação** (botões, links em hover), **estado** (foco, item ativo,
progresso) e **marca** (logotipo, faixa de setores, selos que o brief pede).

### Neutral

Branco puro como superfície base, `hsl(220 20% 96%)` como superfície alternada,
`hsl(225 18% 86%)` para filetes. O texto vive em `hsl(225 30% 12%)` e o
secundário em `hsl(225 12% 42%)`.

### Named Rules

**A Regra do Amarelo.** Divisória, filete e marcador de conteúdo **nunca** usam
amarelo; usam o token `filete`, que é neutro no claro e translúcido no escuro.
Se um elemento amarelo não é clicável, não comunica estado e não é a marca, ele
está errado. A única exceção registrada é o filete dos 5 passos do orçamento,
onde a numeração é uma sequência de verdade e o brief pede a borda amarela.

**A Regra da Superfície Escura.** Superfícies escuras não recebem cores
sobrescritas uma a uma. Uma lista única de seletores redefine os tokens
`--heading`, `--text-muted`, `--accent-ink`, `--rule` e `--foco`, e todo
componente se adapta sozinho. Adicionar uma superfície escura nova significa
entrar nessa lista, não escrever exceções.

## Typography

**Familjen Grotesk** nos títulos, **Public Sans** no corpo. **Dois pesos no site
inteiro: 400 e 700.** A Familjen Grotesk vai só até 700 — pedir 800 faz a API do
Google devolver HTTP 400 e o navegador sintetizar um falso negrito.

### Hierarchy

A escala abaixo dos títulos tem **8 degraus e nada fora deles**: `0.6875 / 0.75 /
0.8125 / 0.875 / 1 / 1.125 / 1.375 / 1.5rem`. Os títulos usam `clamp()` em `.h-1`
a `.h-4`, sem breakpoint de fonte. O corpo é 1rem; texto corrido fica em `62ch`,
o que devolve cerca de 73 caracteres por linha na Public Sans.

### Named Rules

**Hierarquia por tamanho e espaço, não por peso.** Com dois pesos disponíveis, a
distância entre um título e o corpo precisa vir da escala e do respiro. É o que
separa um layout calmo de um layout que grita.

**Caixa alta é rótulo, nunca ação nem título.** Vale para sobrelinhas, categorias
de card, selos e títulos de coluna. Botões, links e títulos ficam em caixa de
frase — e junto com a caixa alta sai o `letter-spacing` positivo, que só existe
para tornar versal legível.

**Um eyebrow por seção não é regra; é exceção.** Um eyebrow se justifica quando
diz o que o título não diz. "CATÁLOGO TÉCNICO" sobre "nossos produtos" é
repetição; "QUEM SOMOS" sobre "conhecimento de chão de fábrica" não é.

## Layout

Container de `1280px` com goteira fluida `clamp(1.25rem, 4vw, 3rem)`. Ritmo
vertical das seções em `clamp(4.5rem, 9vw, 8.5rem)`.

O cabeçalho de seção é uma grade de duas colunas: título grande à esquerda com
filete vertical de 2px, texto de apoio à direita. O respiro sob ele é
`clamp(3rem, 6.5vw, 6.5rem)` — quase o dobro do que era, porque um título de 60px
precisa de espaço proporcional.

Breakpoints em uso: `420`, `640`, `760`, `860`, `899`, `900`, `1025`, e uma
consulta por altura (`max-height: 1000px`) que aperta o painel do hero em telas
de notebook sem encolher o título.

## Elevation & Depth

**Plano, com camadas tonais.** A profundidade vem da troca de superfície — branco,
cinza claro, azul-marinho de ponta a ponta — e não de sombra. As faixas navy
sangram até a borda da tela e são o principal recurso de separação.

### Shadow Vocabulary

Três sombras existem, todas ambiente e de baixa opacidade, nunca estruturais:

- `shadow-sm` `0 2px 10px hsl(232 40% 20% / 0.06)` — repouso de card.
- `shadow-md` `0 18px 40px -18px hsl(232 50% 18% / 0.28)` — painel de formulário.
- `shadow-lg` `0 40px 80px -40px hsl(232 55% 14% / 0.45)` — o painel do hero.

### Named Rules

**A sombra não separa; a superfície separa.** Se dois blocos precisam ser
distinguidos, troque a cor de fundo antes de adicionar sombra.

## Shapes

**Uma forma só: o canto inferior direito cortado.** Aplicada por `clip-path`,
com o tamanho do corte proporcional à altura do elemento — `18px` nos blocos,
`34px` no painel do hero, `14px` no botão normal, `10px` no pequeno, `10-12px`
nas setas e no hambúrguer. Raio de canto praticamente não existe no sistema: só
`4px` no anel de foco e `50%` no botão flutuante do WhatsApp.

### Named Rules

**O chanfro recorta borda, sombra interna e `outline`.** É consequência do
`clip-path`, e é a armadilha mais séria deste sistema: um `border` ou um
`box-shadow: inset` some na aresta diagonal, e o `outline` do foco de teclado
desaparece por inteiro. Contorno permanente e anel de foco são desenhados como um
**anel** — um polígono com furo, o miolo percorrido no sentido inverso — num
pseudo-elemento que recebe o mesmo recorte.

**O chanfro aparece em escala grande uma vez por página**, no painel do hero.
Nos componentes, é miúdo.

## Components

### Buttons

Padding simétrico `1rem 1.8rem`, peso 700, `1rem`, caixa de frase, chanfro de
14px. Três variantes: **primária** (amarelo sólido sobre navy), **contorno**
(anel de 2px navy, preenche no hover) e **WhatsApp** (verde `#25d366`). O ícone
de seta desliza 5px no hover.

### Cards

**Card de produto**: foto limpa em cima com proporção fixa, legenda numa faixa
`superficie-alt` embaixo. O texto nunca fica sobre a foto — o contraste passaria
a depender da imagem. A faixa reserva duas linhas para o nome, senão um nome
curto desalinha a fileira. Grade `auto 1fr`: a foto tem proporção fixa e a faixa
absorve a sobra.

**Card de serviço**: foto no topo, corpo branco embaixo, selo de ícone amarelo no
canto superior direito.

### Inputs / Fields

Fundo `superficie-alt`, sem raio, rótulo em `0.8125rem` peso 700. Erro por
`data-invalid` no campo com filete de 4px à esquerda em vermelho; o alerta do
formulário tem `role="alert"` e o texto é escrito no frame seguinte ao
`data-visible`, senão o leitor de tela não anuncia.

### Navigation

Barra superior de 38px que colapsa por `max-height` na rolagem, navbar de 80px
que encolhe para 68px. Dropdowns abrem no hover e no teclado, fecham no Escape.
No celular vira um acordeão de tela cheia com um grupo aberto por vez.

### Signature Component: o painel do hero

Painel navy translúcido (`hsl(232 62% 11% / 0.82)` com `backdrop-filter`) sobre a
foto, com o chanfro em escala grande (`clamp(30px, 4.5vw, 64px)`). É onde o
sistema mostra a assinatura em tamanho de verdade, e é a única sobreposição de
texto sobre fotografia que o sistema permite.

## Do's and Don'ts

### Do:

- Use amarelo para ação, estado e marca. Nada mais.
- Construa hierarquia com tamanho e espaço; há só dois pesos.
- Mantenha o texto fora da fotografia, exceto no painel do hero.
- Desenhe contorno e foco como anel recortado, nunca como `border` ou `outline`.
- Troque a cor da superfície antes de pensar em sombra.
- Escreva rótulos em caixa alta e ações em caixa de frase.
- Meça o contraste no DOM composto, não no CSS: a cor real vem da pilha.

### Don't:

- Não ponha filete, divisória ou marcador em amarelo.
- Não use um tamanho de fonte fora dos 8 degraus.
- Não peça peso 800 da Familjen Grotesk: ela não tem, e o navegador falsifica.
- Não use `border` nem `box-shadow: inset` num elemento chanfrado.
- Não sobreponha rótulo ou selo a uma foto de produto.
- Não repita no eyebrow o que o título já diz.
- Não invente prova social: não há depoimento, cliente citável nem certificação.
