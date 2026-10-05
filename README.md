# JPX Eleições 2026

Plugin WordPress de apuração e resultados oficiais das eleições, lidos direto dos arquivos públicos do TSE
(`resultados.tse.jus.br`), a mesma fonte do app *Resultados* do TSE. Feito para portais de notícias:
cada site configura o estado e os municípios em destaque e incorpora com shortcodes.

## Recursos

- **Cargos:** Presidente, Governador, Senador, Deputado Federal e Deputado Estadual
- **Abrangência:** Brasil, estado e qualquer município, além da visão **por seção eleitoral** (boletim de urna oficial)
- **2º turno:** detectado sozinho a partir do dia da eleição, só onde o TSE o configurar
- **Painel** com abas fixas ao rolar, busca de município, botões dos municípios em destaque e links compartilháveis
- **Destaque do líder** (vantagem sobre o 2º) e **duelo** lado a lado no 2º turno
- **Hemiciclo** de cadeiras por partido/federação nos cargos proporcionais, com busca, "só eleitos" e "mostrar todos"
- **Mapa do Brasil** por UF e **mais votados da região** (soma de vários municípios)
- **Por seção:** seções agrupadas por local de votação (escola, endereço), mais votado em cada local e seção e
  boletim completo, com ▲▼ do % na seção em relação à cidade
- **Compartilhar** (link, WhatsApp, Facebook, X) com texto pronto
- **Aparência:** claro, automático ou escuro; usa `--accent`/`--ink` do tema quando existem
- **Desempenho:** a página nunca espera o TSE, as atualizações vêm de arquivos estáticos e há um modo consolidado (veja abaixo)

## Instalação

1. Baixe [`dist/jpx-eleicoes-2026.zip`](dist/jpx-eleicoes-2026.zip).
2. No WordPress: **Plugins → Adicionar novo → Enviar plugin** → escolha o .zip → **Ativar**.
3. Em **Configurações → Eleições 2026**, defina o estado, os **municípios em destaque** (ex.: `pinhalzinho, sao-lourenco-do-oeste`)
   e a página da apuração.
4. Crie uma página "Apuração" com `[eleicoes_tse_painel]`.

> Quem usava o plugin anterior (`novafm-eleicoes-tse`) deve desativá-lo e removê-lo: os shortcodes são os mesmos,
> e as configurações são copiadas automaticamente na primeira execução.

## Shortcodes

| Shortcode | O que mostra |
|---|---|
| `[eleicoes_tse_painel]` | Painel completo: abas (cargos, Por seção, Mapa), botões Brasil / UF / destaques e busca de município |
| `[eleicoes_tse cargo="governador" local="pinhalzinho"]` | Um cargo em um local (`br`, UF, nome ou código TSE do município) |
| `[eleicoes_tse cargo="presidente" local="sc" layout="compacto"]` | Versão compacta (top 3) para a home ou a barra lateral |
| `[eleicoes_tse_secoes local="pinhalzinho"]` | Seções por local de votação; ao clicar, o boletim de urna da seção |
| `[eleicoes_tse_mapa]` | Quem venceu em cada UF (`cargo="governador"` ou `"senador"` também) |
| `[eleicoes_tse_regiao]` | Deputados mais votados somando os municípios em destaque |

Atributos de `[eleicoes_tse]`: `cargo`, `local`, `turno` (`auto`, `1`, `2`), `layout` (`completo`, `compacto`),
`limite`, `fotos` (`sim`/`nao`), `titulo`, `link`, `largura`.

## Desempenho

| Situação | Comportamento |
|---|---|
| Página com o shortcode | Montada só com o cache (65–110 ms). Sem cache, mostra o esqueleto e o navegador busca em seguida: **nunca espera o TSE** |
| Páginas sem o shortcode | Nenhum CSS/JS do plugin é carregado |
| Atualização automática | O navegador lê `wp-content/uploads/jpx-eleicoes/<chave>.json`, servido pelo servidor web **sem PHP**; o REST só é chamado quando o arquivo expira (cerca de 2 vezes por minuto por resultado, não por visitante) |
| Apuração em andamento | Atualiza a cada 60 s (configurável); o TSE é consultado no máximo uma vez por intervalo |
| Apuração consolidada (100% das seções) | Cache de 6 h e verificação a cada 30 min; o rodapé mostra "Resultado consolidado" |
| TSE fora do ar | Mostra o último dado recebido, com aviso |

**Espaço no `/uploads`:** cada arquivo tem de 8 KB (Presidente/Governador) a cerca de 270 KB (Deputado Estadual com
todos os candidatos). Um portal típico fica em poucos MB. Há um teto de 3.000 arquivos ou 100 MB, e os arquivos sem
uso há 2 dias são apagados por uma tarefa diária. **Configurações → Eleições 2026 → Espaço ocupado** mostra o uso atual.

**Atualização manual:** o botão **"Buscar dados novos no TSE agora"** apaga o cache e os arquivos e baixa de novo os
resultados principais. Use-o após retotalizações ou decisões judiciais (candidatos *sub judice*).

**Cloudflare (opcional):** as respostas de `/wp-json/jpx-eleicoes/v1/` já enviam `Cache-Control` (20 s ao vivo,
10 min consolidado). Para que o Cloudflare respeite isso, crie uma *Cache Rule*: URI Path começa com
`/wp-json/jpx-eleicoes/` → *Eligible for cache*, *Edge TTL: use cache-control header*.

## Como funciona

```
navegador ──▶ uploads/jpx-eleicoes/<chave>.json ──(expirado)──▶ /wp-json/jpx-eleicoes/v1/resultado ──▶ cache ──▶ resultados.tse.jus.br
```

| Arquivo do TSE | Uso |
|---|---|
| `oficial/comum/config/ele-c.json` | pleitos e eleições do ciclo (1º/2º turno) |
| `oficial/ele2026/<eleição>/config/mun-e<eleição>-cm.json` | municípios e códigos TSE |
| `oficial/ele2026/<eleição>/dados/<uf>/<uf><mun>-c<cargo>-e<eleição>-u.json` | resultado por cargo e abrangência |
| `oficial/ele2026/<eleição>/fotos/<uf>/<sqcand>.jpeg` | fotos dos candidatos |
| `oficial/ele2026/arquivo-urna/<pleito>/config/<uf>/<uf>-p<pleito>-cs.json` | seções por município/zona |
| `…/arquivo-urna/<pleito>/dados/<uf>/<mun>/<zona>/<seção>/…-bu.dat` | **boletim de urna** (ASN.1/DER), lido por `class-jpxe-bu.php` |

Os nomes e endereços dos locais de votação vêm de `data/locais-sc-2026.json`, gerado dos dados abertos do TSE:

```
curl -O https://cdn.tse.jus.br/estatistica/sead/odsele/eleitorado_locais_votacao/eleitorado_local_votacao_2026.zip
unzip eleitorado_local_votacao_2026.zip eleitorado_local_votacao_2026_SC.csv
python3 bin/gerar-locais.py eleitorado_local_votacao_2026_SC.csv sc 2026
```

Para outro estado, gere o arquivo da UF da mesma forma. Sem ele, as seções aparecem agrupadas só por zona.

## Estrutura

```
jpx-eleicoes-2026/
├── jpx-eleicoes-2026.php           cabeçalho e bootstrap
├── includes/
│   ├── class-jpxe-tse.php          arquivos do TSE, cache, turnos, municípios, downloads em paralelo
│   ├── class-jpxe-render.php       HTML dos resultados, líder/duelo, hemiciclo
│   ├── class-jpxe-secoes.php       por seção: locais, boletins, mais votado por local
│   ├── class-jpxe-bu.php           leitor do boletim de urna (ASN.1 DER)
│   ├── class-jpxe-extras.php       mapa do Brasil e mais votados da região
│   ├── class-jpxe-estatico.php     arquivos estáticos em uploads/jpx-eleicoes
│   ├── class-jpxe-shortcodes.php   shortcodes e painel
│   ├── class-jpxe-rest.php         endpoint público de atualização
│   ├── class-jpxe-options.php      configurações
│   └── class-jpxe-admin.php        Configurações → Eleições 2026
├── assets/css/eleicoes.css
├── assets/js/eleicoes.js           atualização, abas, busca, compartilhar (sem dependências)
├── data/locais-sc-2026.json        locais de votação de SC (dados abertos do TSE)
├── readme.txt
└── uninstall.php
```

Gerar o .zip: `./bin/build-zip.sh`.
