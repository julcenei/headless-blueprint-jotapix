# Eleições TSE – plugin WordPress para o Portal Nova FM

Plugin que mostra no **novafmportal.com.br** a apuração e os resultados oficiais das eleições,
lidos diretamente dos arquivos públicos do TSE (`resultados.tse.jus.br`). É a mesma fonte que
alimenta o app *Resultados* do TSE e páginas como a `eleicoes.rco.com.br/oficial`.

- **Cargos:** Presidente, Governador, Senador, Deputado Federal e Deputado Estadual
- **Abrangência:** Brasil, estado (SC por padrão) e qualquer município. Pinhalzinho e São Lourenço do Oeste já vêm em destaque
- **2º turno:** detectado automaticamente a partir do dia da eleição, apenas onde o TSE o configurar
- **Atualização automática** a cada 60 s, com cache no servidor (o TSE nunca é consultado pelo navegador dos visitantes)
- Situação dos candidatos (eleito, 2º turno, eleito por QP/média, suplente), cadeiras por partido/federação, comparecimento, abstenção, brancos e nulos
- **Por seção:** boletim de urna oficial de cada seção, com as seções agrupadas por local de votação (escola, endereço). Funciona em qualquer município de SC
- Cores e fonte do tema `nova-portal` (`--accent: #ff6600`, `--ink: #0B192C`, Encode Sans)

## Instalação

1. Baixe [`dist/novafm-eleicoes-tse.zip`](dist/novafm-eleicoes-tse.zip).
2. No WordPress: **Plugins → Adicionar novo → Enviar plugin**, escolha o .zip e clique em **Ativar**.
3. Em **Configurações → Eleições TSE**, confira o estado, os municípios em destaque e o link da página da apuração.
4. Crie uma página **Apuração** com o shortcode `[eleicoes_tse_painel]`.

## Shortcodes

| Shortcode | O que mostra |
|---|---|
| `[eleicoes_tse_painel]` | Painel completo: abas de cargo e botões Brasil / SC / Pinhalzinho / São Lourenço do Oeste, além de uma lista com todos os municípios de SC |
| `[eleicoes_tse_secoes local="pinhalzinho"]` | Seções de Pinhalzinho por local de votação; clicando, o boletim de urna da seção (todos os cargos) |
| `[eleicoes_tse cargo="presidente" local="br"]` | Presidente, Brasil |
| `[eleicoes_tse cargo="governador" local="pinhalzinho"]` | Governador, votos em Pinhalzinho |
| `[eleicoes_tse cargo="senador" local="sc"]` | Senado em SC |
| `[eleicoes_tse cargo="deputado-estadual" local="sao-lourenco-do-oeste"]` | Deputado Estadual em São Lourenço do Oeste (com busca e "mostrar todos") |
| `[eleicoes_tse cargo="presidente" local="sc" layout="compacto"]` | Versão compacta (top 3) para a home ou a barra lateral |

Atributos de `[eleicoes_tse]`: `cargo`, `local` (`br`, UF, nome ou código TSE do município),
`turno` (`auto`, `1`, `2`), `layout` (`completo`, `compacto`), `limite`, `fotos` (`sim`/`nao`), `titulo`, `link`.

## Como funciona

```
navegador ──(a cada 60 s)──▶ /wp-json/nfe-tse/v1/resultado ──▶ cache (transients) ──(1x/intervalo)──▶ resultados.tse.jus.br
```

| Arquivo do TSE | Uso |
|---|---|
| `oficial/comum/config/ele-c.json` | pleitos e eleições do ciclo (códigos do 1º/2º turno) |
| `oficial/ele2026/<eleição>/config/mun-e<eleição>-cm.json` | municípios e seus códigos TSE |
| `oficial/ele2026/<eleição>/dados/<uf>/<uf><mun>-c<cargo>-e<eleição>-u.json` | resultado por cargo e abrangência |
| `oficial/ele2026/<eleição>/fotos/<uf>/<sqcand>.jpeg` | fotos dos candidatos |
| `oficial/ele2026/arquivo-urna/<pleito>/config/<uf>/<uf>-p<pleito>-cs.json` | seções de cada município/zona (e agregações) |
| `oficial/ele2026/arquivo-urna/<pleito>/dados/<uf>/<mun>/<zona>/<seção>/…-aux.json` | arquivos publicados de cada urna |
| `…/<hash>/…-bu.dat` | **boletim de urna** (ASN.1/DER), lido por `class-nfe-bu.php` |

### Por seção

O boletim de urna traz os votos por número de candidato/partido, brancos, nulos, aptos, comparecimento e os horários
da urna. Os nomes dos candidatos vêm do resultado do município. O boletim traz só o **número** do local de votação;
o nome e o endereço vêm de `data/locais-sc-2026.json`, gerado dos dados abertos do TSE
(<https://dadosabertos.tse.jus.br>, *Eleitorado → Locais de votação*):

```
curl -O https://cdn.tse.jus.br/estatistica/sead/odsele/eleitorado_locais_votacao/eleitorado_local_votacao_2026.zip
unzip eleitorado_local_votacao_2026.zip eleitorado_local_votacao_2026_SC.csv
python3 bin/gerar-locais.py eleitorado_local_votacao_2026_SC.csv sc 2026
```

Seções agregadas (ex.: a 62, que votou junto com a 58) abrem o boletim da seção principal, com um aviso.
Links diretos funcionam: `/apuracao/?nfe_cargo=secoes&nfe_local=sc-82538&nfe_secao=66-50`.

Se o TSE ficar fora do ar, o plugin continua exibindo os últimos dados recebidos, com um aviso.
Em **Configurações → Eleições TSE** há uma tabela que mostra quais eleições (1º e 2º turno) foram
detectadas, além de um botão para limpar o cache.

## Estrutura

```
novafm-eleicoes-tse/
├── novafm-eleicoes-tse.php        cabeçalho e bootstrap do plugin
├── includes/
│   ├── class-nfe-tse.php          leitura dos arquivos do TSE, cache, turnos, municípios
│   ├── class-nfe-render.php       HTML dos resultados (usado na página e nas atualizações)
│   ├── class-nfe-secoes.php       por seção: lista de seções/locais e boletim de urna
│   ├── class-nfe-bu.php           leitor do boletim de urna (ASN.1 DER)
│   ├── class-nfe-shortcodes.php   [eleicoes_tse] e [eleicoes_tse_painel]
│   ├── class-nfe-rest.php         endpoint público de atualização
│   ├── class-nfe-options.php      configurações
│   └── class-nfe-admin.php        tela Configurações → Eleições TSE
├── assets/css/eleicoes.css
├── assets/js/eleicoes.js          atualização automática, abas, busca (sem dependências)
├── data/locais-sc-2026.json       locais de votação de SC (dados abertos do TSE)
├── readme.txt
└── uninstall.php
```

Para gerar o .zip de novo: `./bin/build-zip.sh`.
