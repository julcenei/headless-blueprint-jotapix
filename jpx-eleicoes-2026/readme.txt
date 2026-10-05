=== JPX Eleições 2026 ===
Contributors: jpx
Tags: eleições, tse, apuração, resultados, shortcode
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.4.1
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

[eleicoes_tse_mapa cargo="presidente"]
  Mapa do Brasil por UF (presidente | governador | senador). Também é a aba "Mapa" do painel.

[eleicoes_tse_regiao cargos="deputado-federal,deputado-estadual" locais="pinhalzinho,sao-lourenco-do-oeste" limite="10"]
  Mais votados somando os municípios (padrão: os destaques das configurações).

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

1. Plugins → Adicionar novo → Enviar plugin → escolha jpx-eleicoes-2026.zip → Ativar.
2. Configurações → Eleições 2026: confira o estado (SC) e os municípios em destaque.
3. Crie uma página "Apuração" com [eleicoes_tse_painel].

== Changelog ==

= 1.4.1 =
* Corrige erro 500 na página de configurações na primeira vez que ela é aberta (cópia das configurações do plugin anterior).

= 1.4.0 =
* Novo nome: JPX Eleições 2026 (jpx-eleicoes-2026). Configurações do plugin anterior são copiadas automaticamente.
* Atualizações lidas de arquivos estáticos em uploads/jpx-eleicoes (sem PHP), com teto de 3.000 arquivos / 100 MB e limpeza diária.
* Modo consolidado: com 100% das seções, cache de 6 h e verificação a cada 30 min.
* A página nunca espera o TSE: sem cache, mostra o esqueleto e o navegador completa.
* Botão "Buscar dados novos no TSE agora" e painel de espaço ocupado.
* Boletins de urna guardados em uma cópia só (menos espaço no banco).
* Abas do painel redesenhadas, com ícones.

= 1.3.0 =
* Abas do painel fixas ao rolar (abaixo do cabeçalho do tema), com indicador de rolagem no celular.
* Busca de município digitável no lugar da lista de 295 cidades.
* Botão Compartilhar (copiar link, WhatsApp, Facebook, X) com texto pronto do resultado.
* Esqueleto de carregamento ao trocar de aba.
* Destaque do 1º colocado com vantagem sobre o 2º; duelo lado a lado no 2º turno.
* Aparência: claro, automático ou escuro.
* [eleicoes_tse_regiao]: mais votados somando os municípios da região.
* Boletim de urna: diferença do % na seção para o % na cidade.
* Por seção: mais votado em cada local de votação e em cada seção.
* [eleicoes_tse_mapa] e aba "Mapa": quem venceu em cada UF.

= 1.2.0 =
* Cadeiras por partido/federação em hemiciclo, com legenda e destaque ao passar o mouse.
* Painel e página por seção podem ser mais largos que a coluna do tema (Configurações → Largura do painel, padrão 1130px), sem passar da tela.

= 1.1.0 =
* Resultados por seção: boletim de urna oficial (leitor ASN.1), locais de votação de SC, aba "Por seção" no painel.

= 1.0.0 =
* Primeira versão.
