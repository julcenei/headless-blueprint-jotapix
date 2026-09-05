# Origem

Copiado de https://github.com/pbakaus/impeccable (Apache 2.0), commit `8dac6ae`,
da distribuição `.claude/skills/impeccable/`. Licença em `LICENSE`, avisos em
`NOTICE.md`. O binário (`scripts/impeccable`, v3.6.0) roda neste ambiente.

## Como usar aqui

```bash
.claude/skills/impeccable/scripts/impeccable detect css/style.css *.html
```

**Não passe `dist/`.** O single-file tem o CSS embutido, então cada achado
aparece duplicado — e, se o artifact estiver desatualizado, o detector reporta
CSS que já não existe.

## O que ele pega bem

O vocabulário de 196 anti-padrões nomeados. Boa parte do que este projeto
encontrou à mão está lá como regra: `pulsing-dot`, `em-dash-overuse`,
`kicker-above-heading`, `numbered-section-labels`, `content-hidden-at-rest`,
`oversized-h1`, `flat-type-hierarchy`, `monotonous-spacing`.

## O que ele NÃO pega

Acessibilidade. Entre as 196 regras, `low-contrast` é a única de a11y — e ela
é estática, ou seja, não confiável (ver abaixo). Ele não teria encontrado
nenhum dos três defeitos mais sérios corrigidos neste projeto:

- o anel de foco invisível porque o `clip-path` recorta o `outline`;
- os alvos de toque de 20px;
- o clique no card que não abria a ficha, por causa da captura de ponteiro.

Esses vieram dos scripts de Playwright. **O detector não substitui a auditoria
em navegador.**

## Falsos positivos conhecidos, medidos neste site

Numa rodada de 285 achados, 126 eram falsos, e sempre pela mesma causa: o
detector lê o CSS como texto, não a página composta.

| regra | achados | reais | por quê |
|---|---|---|---|
| `low-contrast` | 74 | **0** | vê `color:#fff` e o `background` literal mais próximo. Não segue o navy que vem de `.section--navy` acima na árvore, nem percebe que `.product-card__more` é `display:none`. Conferido elemento a elemento no DOM. |
| `cramped-padding` | 52 | **0** | diz que a topbar tem texto colado na borda; o texto está a 80px, e o recuo vem do `.container` filho. |
| `dark-glow` | 8 | 0 | nossas sombras têm deslocamento e 6% de opacidade; a regra mira halo neon de deslocamento zero. |
| `repeating-stripes-gradient` | 8 | 0 | hoje casa com o filete tracejado de `.hr-dashed`, que é divisória funcional. |
| `clipped-overflow-container` | 8 | 0 | o body usa `overflow-x: clip`, que recorta sem criar contexto de rolagem — `position: sticky` continua funcionando (verificado). A regra não distingue `clip` de `hidden`. |

**Sempre triar antes de agir.** Um achado do detector é uma hipótese, não um
defeito.

## Achados que aceitamos e não vamos "corrigir"

- `side-tab` nos 5 passos do orçamento: o brief pede a borda amarela à esquerda.
- `side-tab` no erro e no sucesso do formulário: a cor carrega o estado.
- `marquee`: o brief pede a faixa de setores.
- `layout-transition` na topbar e na navbar: medido em três rodadas com e sem a
  transição — 28-35ms contra 30-34ms de mediana. Sem diferença; os 33ms são a
  taxa do Chromium headless, não a animação.
- `radial-halo`: é tonal (navy sobre navy), não cromático, e é o que impede o
  bloco de 472px do cabeçalho interno de ficar chapado.
- `all-caps-body`: são 44 caracteres da sobrelinha do hero, que é rótulo.
