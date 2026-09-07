# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Três perfis, em proporções parecidas — nenhum é o principal, e o site precisa
servir os três sem privilegiar nenhum (confirmado pelo cliente):

- **Manutenção com linha parada.** Técnico ou encarregado resolvendo uma parada
  agora. Precisa de contato à mão e de saber que existe atendimento fora do
  horário comercial.
- **Compras comparando fornecedores.** Analista de suprimentos levantando preço
  e prazo entre concorrentes. Precisa de catálogo completo e de conseguir
  descrever o item com precisão.
- **Engenharia especificando material.** Projetista definindo correia para
  equipamento novo ou reforma. Precisa de dados de aplicação e compatibilidade.

Todos chegam de indústrias dos 8 setores atendidos, com predominância de
frigoríficos, que o brief nomeia como principal área de atuação.

## Product Purpose

A Elotec fornece, instala, conserta e faz a manutenção de correias
transportadoras desde 2001, com duas unidades (Chapecó/SC, sede; Toledo/PR) e
atendimento em todo o Brasil.

O site tem uma função só: **transformar um problema de correia numa conversa
qualificada no WhatsApp.** Sucesso é a mensagem chegar com o suficiente para a
equipe responder com recomendação técnica sem uma rodada de perguntas básicas —
quem é, de que empresa, qual setor, qual produto ou serviço, e a descrição da
aplicação. O site não vende, não tem carrinho e não publica preço.

## Positioning

Conhecimento de chão de fábrica, não de catálogo: a Elotec avalia o processo do
cliente antes de indicar o material, e executa o serviço com equipe própria —
fornecimento, instalação, vulcanização, colagem a frio, revestimento de rolos e
acompanhamento preventivo. Um distribuidor que só entrega o rolo não pode dizer
o mesmo.

## Operating Context

- **Canal único de conversão: WhatsApp.** O formulário de orçamento não envia
  nada para um servidor; ele monta uma mensagem formatada e abre `wa.me` com os
  dados preenchidos. Anexos (fotos, ficha técnica, desenho) são enviados pelo
  visitante dentro da conversa.
- **Não há triagem: uma pessoa recebe tudo** (confirmado pelo cliente). Não há
  fila, roteamento por setor nem plantão separado. Toda promessa de tempo de
  resposta no site depende dessa única pessoa.
- Canais secundários, sem automação: telefone administrativo (49) 3328-6223 e
  e-mail elotec@elotec.ind.br.

## Capabilities and Constraints

- **Site estático**, sem backend, sem banco e sem CMS. As 8 páginas são geradas
  por scripts Node a partir de `data/dados.json`; o consumidor final recebe HTML,
  CSS e JS puros, sem etapa de build.
- Conteúdo atual: 9 produtos, 8 serviços, 8 setores, 3 slides de hero, 2 unidades.
- Consentimento LGPD obrigatório no formulário, com link para a Política de
  Privacidade.
- **Decisões em aberto**, a não inventar: o domínio final (o código usa
  `https://www.elotec.ind.br` como suposição, e ele alimenta sitemap, canonical e
  Open Graph) e onde o site será hospedado.

## Brand Commitments

Vinculantes, do brief e da marca existente:

- Azul-marinho `hsl(232 58% 24%)` e amarelo `hsl(48 96% 52%)`; o amarelo é
  reservado a ação, estado e marca.
- O chanfro no canto inferior direito como assinatura de forma, aplicado por
  `clip-path`.
- O logotipo existente e a assinatura "Serviços Técnicos em Correias".
- Marquee amarelo de setores, com itens clicáveis e separador quadradinho.
- Numeração ordinal nas fichas de produto.
- Tipografia atual: Familjen Grotesk (títulos) e Public Sans (corpo), em dois
  pesos. Escolha do cliente nesta sessão.

## Evidence on Hand

- `data/dados.json` — produtos, serviços, setores, unidades, telefones e e-mail
  reais.
- 22 fotos em `images/` (produtos, serviços, setores) e 1 de hero. **São imagens
  de banco, não registros de trabalhos da Elotec.**
- `docs/brief-elotec-premium.md` — o brief que rege conteúdo e arquitetura.

**Ausências que trabalhos futuros não podem preencher com invenção**
(confirmado pelo cliente: "nada por enquanto"):

- Nenhum depoimento, nome de cliente citável ou caso de sucesso.
- Nenhuma certificação, laudo ou norma atendida declarável.
- Nenhuma foto de trabalho real da equipe — nem de emenda, nem de instalação.
- Nenhuma foto das duas unidades, incluindo a sede.
- Nenhum número de clientes atendidos, de metros instalados ou de tempo de
  resposta.

O site hoje não tem prova social, e isso é estado de fato, não lacuna a ser
tapada com placeholder plausível.

## Product Principles

1. **A mensagem qualificada é o produto.** Cada decisão de superfície se mede por
   quanto reduz a rodada de perguntas básicas depois do primeiro contato.
2. **Três leitores, um caminho.** Manutenção, compras e engenharia chegam com
   pressas diferentes; o caminho até o WhatsApp é o mesmo, e o que muda é a
   profundidade disponível antes dele.
3. **Nunca fabricar prova.** Sem depoimento, sem cliente, sem certificação, sem
   número inventado. A credibilidade vem da especificidade técnica do conteúdo.
4. **Uma pessoa atende.** Promessas de disponibilidade e tempo de resposta são
   limitadas pela capacidade real de uma pessoa, não pela ambição da copy.
5. **O conteúdo é de chão de fábrica.** Vocabulário técnico correto — emenda,
   vulcanização, talisca, caneca, PU sanitária — em vez de linguagem de
   marketing genérica.

## Accessibility & Inclusion

WCAG 2.1 AA como piso, verificado por medição e não por estimativa: contraste
composto no DOM renderizado, alvos de toque de no mínimo 24px (WCAG 2.5.8),
anel de foco visível em todo elemento chanfrado — o `clip-path` recorta o
`outline`, então o foco é desenhado por dentro — e `prefers-reduced-motion`
respeitado em toda animação.

O público é industrial e boa parte acessa do chão de fábrica, no celular, com
luz forte e possivelmente com luvas: contraste alto e alvos generosos são
requisito de uso, não só de conformidade.
