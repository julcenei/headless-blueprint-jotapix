=== Eleições TSE – Nova FM ===
Contributors: novafmportal
Tags: eleições, tse, apuração, resultados, shortcode
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

Apuração e resultados oficiais das eleições direto dos arquivos públicos do TSE.

== Description ==

Mostra os resultados oficiais divulgados pelo TSE em resultados.tse.jus.br:
Presidente, Governador, Senador, Deputado Federal e Deputado Estadual, no Brasil,
por estado e por município. Atualiza sozinho e detecta o 2º turno.

* Dados oficiais: lidos dos mesmos arquivos JSON que o app "Resultados" do TSE usa.
* Cache no servidor: os visitantes leem do seu site; o TSE é consultado no máximo uma vez por intervalo.
* Se o TSE ficar fora do ar, exibe os últimos dados recebidos.
* Situação dos candidatos (eleito, 2º turno, eleito por QP/média, suplente) e cadeiras por partido/federação.
* Fotos oficiais dos candidatos.
* Busca, "só eleitos" e "mostrar todos" nos cargos proporcionais.
* Layout responsivo, que usa as cores e a fonte do tema (variáveis --accent, --ink).

== Shortcodes ==

[eleicoes_tse_painel]
  Painel com abas de cargo e botões de local (Brasil, estado, municípios em destaque, lista de todos os municípios).
  Atributos: cargos="presidente,governador,senador,deputado-federal,deputado-estadual" locais="br,sc,pinhalzinho"
             cargo="presidente" local="sc" limite="20" fotos="sim"

[eleicoes_tse_secoes local="pinhalzinho"]
  Seções do município agrupadas por local de votação; ao clicar, o boletim de urna oficial da seção.
  Também disponível como aba "Por seção" no painel.

[eleicoes_tse cargo="governador" local="pinhalzinho"]
  cargo:  presidente | governador | senador | deputado-federal | deputado-estadual
  local:  br | sigla da UF (sc) | nome do município (pinhalzinho, sao-lourenco-do-oeste) | código TSE (82538) | uf-código (sc-82538)
  turno:  auto (padrão) | 1 | 2
  layout: completo (padrão) | compacto
  limite: quantos candidatos mostrar (compacto: 3/5; proporcional completo: 20 + "mostrar todos")
  fotos:  sim | nao
  titulo: título personalizado
  link:   link "Ver apuração completa" (layout compacto)

== Installation ==

1. Plugins → Adicionar novo → Enviar plugin → escolha novafm-eleicoes-tse.zip → Ativar.
2. Configurações → Eleições TSE: confira o estado (SC) e os municípios em destaque.
3. Crie uma página "Apuração" com [eleicoes_tse_painel].

== Changelog ==

= 1.1.0 =
* Resultados por seção: boletim de urna oficial (leitor ASN.1), locais de votação de SC, aba "Por seção" no painel.

= 1.0.0 =
* Primeira versão.
