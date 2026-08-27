# Brief — Redesign Premium do Site Elotec Correias

> **Instrução para o Claude:** Analise o layout e o conteúdo deste brief junto com os arquivos anexados (imagens reais do site e dados estruturados em JSON). Crie um projeto **HTML + CSS + JS puro** (sem frameworks, single-page ou multi-page conforme julgar melhor) com acabamento **premium e comercial**, no estado da arte do que existe hoje em web design. A marca, os textos e as imagens são reais — preserve o conteúdo, mas eleve drasticamente a apresentação.

---

## 1. Quem é a Elotec

**Elotec — Serviços Técnicos em Correias.** Desde 2001 atua com soluções em correias transportadoras, instalação, conserto, manutenção e avaliação de sistemas industriais, com conhecimento técnico construído no chão de fábrica.

- **Tagline:** "Soluções completas em correias transportadoras para sua indústria não parar."
- **Diferenciais:** assistência técnica 24h, atendimento em todo o Brasil, 2 unidades (Chapecó–SC matriz e Toledo–PR), 8+ setores industriais atendidos.
- **Principal área de atuação:** frigoríficos (correias sanitárias — higiene, resistência e confiabilidade).
- **Contatos:** WhatsApp (49) 9 9955-1100 · Adm: (49) 3328-6223 · elotec@elotec.ind.br · Rua São Miguel do Oeste, 895-E — Efapi, Chapecó–SC, CEP 89809-550
- **Link WhatsApp:** `https://wa.me/5549999551100?text=Olá!%20Vim%20pelo%20site%20da%20Elotec%20e%20gostaria%20de%20atendimento.`

## 2. Design system atual (referência visual)

| Token | Valor |
|---|---|
| Primária (azul-marinho) | `hsl(232 58% 24%)` ≈ `#232A5C` |
| Accent (amarelo) | `hsl(48 96% 52%)` ≈ `#F7C90C` |
| Texto | `hsl(225 30% 12%)` |
| Texto secundário | `hsl(225 12% 42%)` |
| Fundo neutro | `hsl(220 20% 96%)` |
| Fonte | **Cabin** (400/500/600/700 + itálico) |
| Assinatura visual | cantos **chanfrados** via clip-path — botões: `polygon(0 0, calc(100% - 20px) 0, 100% 100%, 0 100%)`; cards: chanfro de 18px no canto inferior direito |
| WhatsApp | `#25D366` (botão flutuante no canto inferior direito) |

**O que preservar:** a dupla azul-marinho + amarelo, os chanfros como identidade, o tom técnico-industrial, o logotipo (losango/mancha amarela com "e" minúsculo azul + wordmark "elotec" + descriptor "Serviços Técnicos em Correias").

## 3. Estrutura de páginas e navegação

Site institucional com 6 páginas + 2 auxiliares:

1. **Home** (`/`)
2. **A Elotec** (`/a-elotec`) — história e diferenciais
3. **Produtos** (`/produtos`) — 9 produtos detalhados, barra de âncoras com ícones
4. **Serviços** (`/servicos`) — 8 serviços detalhados
5. **Setores Atendidos** (`/setores`) — 8 setores detalhados
6. **Solicitar Orçamento** (`/solicitar-orcamento`) — formulário que abre WhatsApp com dados preenchidos
7. **Política de Privacidade** (LGPD)
8. **404**

**Header:** top bar navy ("Atendimento em todo o Brasil · Assistência técnica 24h" + telefone/e-mail), barra principal branca com logo, nav (A Elotec · Produtos ▾ · Serviços ▾ · Setores Atendidos ▾) e CTA amarelo chanfrado "Solicitar orçamento". Dropdowns com colunas de categorias.

**Footer (navy):** 4 colunas — marca+contato+endereço / Produtos / Serviços / Setores, cada lista com 6 links + "Todos os →" em amarelo; barra inferior com © e links Solicitar orçamento / Privacidade.

### Home — seções (na ordem)

1. **Hero carrossel** (3 slides, auto 7s, setas + indicadores) com foto de fundo + gradiente navy:
   - Slide 1 — eyebrow "Soluções técnicas para transporte industrial" · "Soluções completas em correias transportadoras" + **"para sua indústria não parar."** (amarelo) · "Fornecimento, instalação, conserto e manutenção de correias em PU, PVC, borracha, nylon e transmissão." · CTA "Solicitar orçamento" + "Conhecer soluções" (outline) · imagem `hero.webp`
   - Slide 2 — eyebrow "Principal área de atuação" · "Correias sanitárias para frigoríficos" + **"higiene, resistência e confiabilidade."** · "Resistência a cortes, graxas, óleos, baixas temperaturas e processos frequentes de higienização." · CTA "Ver soluções para frigoríficos" · imagem `setores/frigorificos.webp`
   - Slide 3 — eyebrow "Assistência técnica 24h" · "Instalação, conserto e manutenção" + **"para reduzir suas paradas."** · "Vulcanização, colagem, revestimento de rolos e acompanhamento preventivo da sua operação." · CTA "Conhecer serviços" · imagem `servicos/vulcanizacao.webp`
2. **Marquee amarelo** com os 8 setores (scroll infinito, itens clicáveis, separador quadradinho)
3. **Nossos produtos** — título em 2 linhas ("nossos" apagado / "produtos" navy bold), texto, CTA "Ver todos" + grid 2×3 dos 6 produtos em destaque (card com foto + gradiente navy + nome; canto chanfrado)
4. **Quem somos** (fundo navy) — texto desde 2001 + 4 stats (2001 início das atividades / 24h assistência técnica / 2 unidades: Chapecó–SC e Toledo–PR / 8+ setores industriais atendidos) + foto + "Saiba mais"
5. **Nossos serviços** (fundo cinza-claro) — 4 cards com foto: Instalação e conserto de correias · Revestimento de rolos · Aplicação de guias e taliscas · Consultoria preventiva (cada um com link "Conhecer →" amarelo)
6. **Onde estamos + Solicite um orçamento** (split navy/branco) — Chapecó–SC (matriz, endereço, CEP), Toledo–PR (unidade), telefones; lado direito: texto "Está com problemas na correia ou no transportador? Envie fotos, medidas e informações do equipamento para uma avaliação inicial." + telefone/e-mail + CTAs amarelo e WhatsApp

### Páginas de catálogo (Produtos / Serviços / Setores)

Padrão comum: hero navy com breadcrumb + título grande + subtítulo; barra de âncoras com ícones lineares; seções numeradas ("**1.** Nome" — número amarelo) com fundo alternando branco/cinza, layout 2 colunas (texto à esquerda, foto chanfrada à direita). Sub-blocos: listas com checkmarks amarelos em 2 colunas, chips clicáveis, cards de produtos relacionados, CTA amarelo ao final de cada seção. Fechamento com CTA final navy ("Precisa encontrar a correia ideal ou recuperar seu equipamento?").

**Títulos de sub-bloco por tipo de conteúdo:** produtos usam "Benefícios", "Características técnicas" (quando houver `features`), "Aplicações", "Setores que utilizam", "Serviços relacionados"; serviços usam "Informações técnicas" (`techInfo`), "Benefícios", "Características" (`features`), "Atividades do acompanhamento" (`activities`); setores usam título condicional — `challenges`→"Desafios do setor", `needs`→"Necessidades atendidas", `applications`→"Aplicações possíveis", `possibilities`→"Possibilidades" — mais "Serviços relacionados" e "Produtos relacionados". O setor "Frigoríficos" leva selo amarelo "Principal área de atuação".

### Solicitar Orçamento

Hero navy; faixa de 5 passos numerados (cards com borda amarela à esquerda): 1 Informe seus dados · 2 Selecione o produto ou serviço · 3 Descreva a aplicação · 4 Envie fotos ou arquivos · 5 Aguarde o contato da equipe. Formulário: Nome*, Empresa*, Telefone ou WhatsApp*, E-mail*, Cidade, Estado (UF), Setor industrial (select), Produto de interesse (select), Serviço de interesse (select), Mensagem*, nota "Anexar fotos, ficha ou desenho técnico (enviados na conversa do WhatsApp)", checkbox LGPD obrigatório com link para a Política. Submit abre `wa.me` com mensagem formatada e mostra confirmação: "Solicitação encaminhada! Abrimos uma conversa no WhatsApp com seus dados preenchidos. Se anexou arquivos, envie-os na conversa. Nossa equipe entrará em contato."

## 4. Conteúdo completo (dados reais)

O arquivo `dados.json` (anexo) contém, já em português e prontos para uso:

- `heroSlides` — os 3 slides do hero
- `products` — 9 produtos: Correia PU Sanitária, Correia PU de Tração Positiva, Correia PU Lonada, Correia PVC, Correia PVC Elevadora de Canecas, Correia de Borracha, Canecas para Elevadores, Rolos e Roletes Industriais, Material para Revestimento Industrial (cada um com `summary`, `description`, `benefits`, `applications`/`features`, imagem, CTA e relacionados)
- `productCategories` — 5 categorias para os dropdowns: Correias em PU · Correias em PVC · Correias de Borracha · Componentes Industriais · Revestimentos
- `services` — 8 serviços (5 de instalação/conserto por material + Revestimento de Rolos, Guias e Taliscas, Consultoria Preventiva)
- `sectors` — 8 setores: Frigoríficos, Indústria Farmacêutica, Panificação e Biscoitos, Cerealistas, Britadores, Indústria de Móveis, Fabricantes de Equipamentos, Lenha e Cavacos
- `contact` — contatos, endereço e unidades

As 22 imagens reais estão em `images/` (hero + 9 produtos + 4 serviços + 8 setores, todas em `.webp`).

## 5. O que eu quero: premium e estado da arte

O site atual é funcional e correto, mas visualmente conservador. Quero que você **mantenha marca, conteúdo e estrutura de informação**, mas reconstrua a experiência no nível dos melhores sites industriais/B2B de 2025+. Propostas concretas de melhoria (sinta-se livre para ir além):

### Motion & interação
- Animações de entrada suaves (fade-up com stagger) ao rolar, via IntersectionObserver — sem libs pesadas
- Hero com transições entre slides mais cinematográficas (crossfade + leve ken-burns/zoom na foto, entrada de texto em cascata)
- Marquee com velocidade que reage ao hover (desacelera) e pausa acessível
- Micro-interações: botões com deslocamento do chanfro/brilho no hover, cards de produto com zoom sutil + reveal do CTA, links com underline animado
- Scroll suave com âncoras funcionais; header que encolhe/ganha blur ao rolar; barra de progresso de leitura nas páginas de catálogo
- Respeitar `prefers-reduced-motion`

### Layout & tipografia
- Grids mais ousados: quebra de grid no hero (texto sobrepondo imagem), seções com assimetria controlada, uso generoso de espaço negativo
- Tipografia display maior e mais expressiva no hero (clamp fluido), mantendo Cabin ou propondo pairing mais contemporâneo (ex.: Cabin para corpo + uma display condensada), desde que sirva via Google Fonts
- Cards de produto com mais hierarquia: nome, categoria, resumo no hover/expand
- Tratamento de imagem consistente: máscaras chanfradas, duotone navy sutil ou overlay gradiente para uniformizar as fotos

### Comercial / conversão
- CTA "Solicitar orçamento" sempre à vista (header sticky + CTA flutuante discreto além do WhatsApp)
- Seção de prova social/números mais forte (stats com count-up animado ao entrar na viewport)
- Bloco "como funciona" em 5 passos com linha do tempo visual
- FAQ curto na página de orçamento para reduzir fricção
- Selos de confiança (desde 2001, assistência 24h, atendimento nacional) repetidos em pontos estratégicos

### Técnica
- HTML semântico completo, ARIA nos componentes interativos, foco visível, contraste AA
- SEO: meta/OG tags por página, JSON-LD de `LocalBusiness` com endereço, telefone e área de atendimento
- Performance: imagens `loading="lazy"`, CSS/JS sem dependências de build, tudo funcionando ao abrir o `index.html` localmente ou hospedado estático
- Responsivo impecável (mobile-first; menu mobile fullscreen com os mesmos grupos de links dos dropdowns)

## 6. Entregável esperado

Projeto estático completo: `index.html` + páginas (ou SPA com hash routing), `css/`, `js/`, reutilizando `images/` e `dados.json` anexos. Comente o código nas partes-chave. Ao final, explique em 5-8 bullets as decisões de design tomadas e o que mudou em relação ao layout original.
