<?php
/**
 * Acesso aos arquivos públicos de divulgação do TSE (resultados.tse.jus.br).
 *
 * Estrutura usada (descrita em /oficial/comum/config/ele-c.json):
 *   <base>/<ambiente>/comum/config/ele-c.json                       pleitos e eleições
 *   <base>/<ambiente>/<ciclo>/<eleicao>/config/mun-e<eleicao>-cm.json  municípios
 *   <base>/<ambiente>/<ciclo>/<eleicao>/dados/<uf>/<uf><mun>-c<cargo>-e<eleicao>-u.json
 *   <base>/<ambiente>/<ciclo>/<eleicao>/fotos/<uf>/<sqcand>.jpeg
 *
 * Todas as respostas passam por cache (transients) no servidor, para que os
 * visitantes do site nunca consultem o TSE diretamente.
 */

defined( 'ABSPATH' ) || exit;

class NFE_TSE {

	const CARGOS = array(
		'presidente'        => array( 'cd' => 1, 'nome' => 'Presidente', 'escopo' => 'br', 'prop' => false ),
		'governador'        => array( 'cd' => 3, 'nome' => 'Governador', 'escopo' => 'uf', 'prop' => false ),
		'senador'           => array( 'cd' => 5, 'nome' => 'Senador', 'escopo' => 'uf', 'prop' => false ),
		'deputado-federal'  => array( 'cd' => 6, 'nome' => 'Deputado Federal', 'escopo' => 'uf', 'prop' => true ),
		'deputado-estadual' => array( 'cd' => 7, 'nome' => 'Deputado Estadual', 'escopo' => 'uf', 'prop' => true ),
	);

	const UFS = array(
		'ac' => 'Acre', 'al' => 'Alagoas', 'ap' => 'Amapá', 'am' => 'Amazonas', 'ba' => 'Bahia',
		'ce' => 'Ceará', 'df' => 'Distrito Federal', 'es' => 'Espírito Santo', 'go' => 'Goiás',
		'ma' => 'Maranhão', 'mt' => 'Mato Grosso', 'ms' => 'Mato Grosso do Sul', 'mg' => 'Minas Gerais',
		'pa' => 'Pará', 'pb' => 'Paraíba', 'pr' => 'Paraná', 'pe' => 'Pernambuco', 'pi' => 'Piauí',
		'rj' => 'Rio de Janeiro', 'rn' => 'Rio Grande do Norte', 'rs' => 'Rio Grande do Sul',
		'ro' => 'Rondônia', 'rr' => 'Roraima', 'sc' => 'Santa Catarina', 'sp' => 'São Paulo',
		'se' => 'Sergipe', 'to' => 'Tocantins',
	);

	/* ------------------------------------------------------------------ *
	 * Configuração
	 * ------------------------------------------------------------------ */

	public static function base_url() {
		return untrailingslashit( apply_filters( 'nfe_tse_base_url', 'https://resultados.tse.jus.br' ) );
	}

	/** "oficial" na eleição; o TSE também publica um ambiente "simulado" para testes. */
	public static function ambiente() {
		return apply_filters( 'nfe_tse_ambiente', 'oficial' );
	}

	public static function ciclo() {
		return NFE_Options::get( 'ciclo' );
	}

	public static function uf_padrao() {
		return NFE_Options::get( 'uf' );
	}

	public static function url( $path ) {
		return self::base_url() . '/' . self::ambiente() . '/' . ltrim( $path, '/' );
	}

	/* ------------------------------------------------------------------ *
	 * HTTP + cache
	 * ------------------------------------------------------------------ */

	public static function http_json( $path ) {
		$body = self::http_get( $path );
		if ( is_wp_error( $body ) ) {
			return $body;
		}
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'nfe_json', 'Resposta inválida do TSE.' );
		}
		return $data;
	}

	/** Corpo bruto de um arquivo do TSE (JSON ou binário, como o -bu.dat). */
	public static function http_get( $path ) {
		$res = wp_remote_get(
			self::url( $path ),
			array(
				'timeout'    => 15,
				'user-agent' => 'NovaFM-Eleicoes-TSE/' . NFE_VERSION . ' (+' . home_url( '/' ) . ')',
				'headers'    => array( 'Accept' => 'application/json' ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'nfe_http', 'Falha ao consultar o TSE: ' . $res->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		// O armazenamento do TSE responde 404 (ou 403) para arquivos ainda não publicados.
		if ( 404 === $code || 403 === $code ) {
			return new WP_Error( 'nfe_404', 'Dados ainda não publicados pelo TSE.' );
		}
		if ( 200 !== $code ) {
			return new WP_Error( 'nfe_http', 'O TSE respondeu HTTP ' . $code . '.' );
		}
		return wp_remote_retrieve_body( $res );
	}

	private static function cache_key( $key ) {
		return 'nfe' . (int) get_option( 'nfe_tse_cache_v', 1 ) . '_' . md5( NFE_VERSION . $key );
	}

	/** Invalida todo o cache trocando o prefixo das chaves (funciona também com object cache). */
	public static function flush_cache() {
		update_option( 'nfe_tse_cache_v', (int) get_option( 'nfe_tse_cache_v', 1 ) + 1, false );
	}

	/**
	 * Cache com cópia "velha" de reserva: se o TSE falhar, devolve o último dado bom.
	 *
	 * @param string       $key      Identificador.
	 * @param int|callable $ttl      Segundos, ou função que recebe o valor e devolve segundos.
	 * @param callable     $producer Produz o valor (ou WP_Error).
	 * @return mixed|WP_Error Valor; quando veio da reserva, $value['_stale'] = true.
	 */
	public static function cached( $key, $ttl, $producer ) {
		$k     = self::cache_key( $key );
		$fresh = get_transient( $k . '_f' );
		if ( false !== $fresh ) {
			if ( is_array( $fresh ) && isset( $fresh['_err'] ) ) {
				return new WP_Error( $fresh['_err'], $fresh['_msg'] );
			}
			return $fresh;
		}

		$stale = get_transient( $k . '_s' );

		// Outro processo já está buscando: serve a reserva em vez de bater no TSE de novo.
		if ( false !== $stale && get_transient( $k . '_l' ) ) {
			return self::mark_stale( $stale, false );
		}
		set_transient( $k . '_l', 1, 20 );

		$value = call_user_func( $producer );
		delete_transient( $k . '_l' );

		if ( is_wp_error( $value ) ) {
			if ( false !== $stale ) {
				return self::mark_stale( $stale, true );
			}
			// Evita martelar o TSE enquanto o arquivo não existe.
			set_transient(
				$k . '_f',
				array( '_err' => $value->get_error_code(), '_msg' => $value->get_error_message() ),
				'nfe_404' === $value->get_error_code() ? 60 : 20
			);
			return $value;
		}

		$seconds = is_callable( $ttl ) ? (int) call_user_func( $ttl, $value ) : (int) $ttl;
		set_transient( $k . '_f', $value, max( 10, $seconds ) );
		set_transient( $k . '_s', $value, DAY_IN_SECONDS );
		return $value;
	}

	private static function mark_stale( $value, $failed ) {
		if ( is_array( $value ) && $failed ) {
			$value['_stale'] = true;
		}
		return $value;
	}

	/* ------------------------------------------------------------------ *
	 * Pleitos / eleições
	 * ------------------------------------------------------------------ */

	/** Eleições do ciclo configurado, indexadas pelo código. */
	public static function eleicoes() {
		$ciclo = self::ciclo();
		return self::cached(
			'cfg_' . $ciclo,
			1800,
			function () use ( $ciclo ) {
				$d = self::http_json( 'comum/config/ele-c.json' );
				if ( is_wp_error( $d ) ) {
					return $d;
				}
				$out = array();
				foreach ( isset( $d['pl'] ) ? $d['pl'] : array() as $pl ) {
					if ( ! isset( $pl['c'] ) || $pl['c'] !== $ciclo ) {
						continue;
					}
					foreach ( isset( $pl['e'] ) ? $pl['e'] : array() as $e ) {
						$abr = array();
						foreach ( isset( $e['abr'] ) ? $e['abr'] : array() as $a ) {
							$cps = array();
							foreach ( isset( $a['cp'] ) ? $a['cp'] : array() as $cp ) {
								$cps[] = (int) $cp['cd'];
							}
							$abr[ strtolower( $a['cd'] ) ] = $cps;
						}
						$out[ (string) $e['cd'] ] = array(
							'cd'    => (string) $e['cd'],
							'cdt2'  => isset( $e['cdt2'] ) ? (string) $e['cdt2'] : '',
							'turno' => (int) $e['t'],
							'nome'  => (string) $e['nm'],
							'data'   => (string) $pl['dt'],
							'pleito' => (string) $pl['cd'],
							'abr'    => $abr,
						);
					}
				}
				if ( ! $out ) {
					return new WP_Error( 'nfe_cfg', 'O ciclo ' . $ciclo . ' não foi encontrado na configuração do TSE.' );
				}
				return $out;
			}
		);
	}

	private static function aplica( $e, $cargo_cd, $uf ) {
		foreach ( array( 'br', $uf ) as $k ) {
			if ( isset( $e['abr'][ $k ] ) && in_array( $cargo_cd, $e['abr'][ $k ], true ) ) {
				return true;
			}
		}
		return false;
	}

	private static function data_ymd( $dmy ) {
		$p = explode( '/', $dmy );
		return 3 === count( $p ) ? $p[2] . '-' . $p[1] . '-' . $p[0] : '';
	}

	private static function hoje() {
		$dt = new DateTime( 'now', new DateTimeZone( 'America/Sao_Paulo' ) );
		return $dt->format( 'Y-m-d' );
	}

	/**
	 * Escolhe a eleição (1º ou 2º turno) para um cargo/abrangência.
	 *
	 * @param int        $cargo_cd Código do cargo no TSE.
	 * @param string     $uf       'br' ou sigla da UF.
	 * @param string|int $turno    'auto', 1 ou 2.
	 * @return array|WP_Error { e1, e2, eleicao, turno }
	 */
	public static function eleicao_para( $cargo_cd, $uf, $turno = 'auto' ) {
		$els = self::eleicoes();
		if ( is_wp_error( $els ) ) {
			return $els;
		}
		$e1 = null;
		foreach ( $els as $e ) {
			if ( 1 !== $e['turno'] || ! self::aplica( $e, $cargo_cd, $uf ) ) {
				continue;
			}
			// Prefere a eleição ordinária a eventuais suplementares do mesmo ciclo.
			if ( null === $e1 || ( false !== stripos( $e['nome'], 'ordin' ) && false === stripos( $e1['nome'], 'ordin' ) ) ) {
				$e1 = $e;
			}
		}
		if ( ! $e1 ) {
			return new WP_Error( 'nfe_cfg', 'Não há eleição para este cargo no ciclo configurado.' );
		}

		// O 2º turno só aparece no ele-c.json depois que o TSE o configura, e só para as UFs que o terão.
		$e2 = null;
		if ( $e1['cdt2'] && isset( $els[ $e1['cdt2'] ] ) && self::aplica( $els[ $e1['cdt2'] ], $cargo_cd, $uf ) ) {
			$e2 = $els[ $e1['cdt2'] ];
		}

		if ( 2 === (int) $turno && ! $e2 ) {
			return new WP_Error( 'nfe_sem_t2', 'Não há 2º turno para este cargo nesta abrangência.' );
		}
		$usa_t2 = $e2 && ( 2 === (int) $turno || ( 'auto' === $turno && self::hoje() >= self::data_ymd( $e2['data'] ) ) );

		return array(
			'e1'      => $e1,
			'e2'      => $e2,
			'eleicao' => $usa_t2 ? $e2 : $e1,
			'turno'   => $usa_t2 ? 2 : 1,
		);
	}

	/* ------------------------------------------------------------------ *
	 * Municípios e abrangências
	 * ------------------------------------------------------------------ */

	/** Municípios da UF: [ codigo_tse => [cd, nome, ibge] ], em ordem alfabética. */
	public static function municipios( $uf ) {
		$uf = strtolower( $uf );
		if ( ! isset( self::UFS[ $uf ] ) ) {
			return new WP_Error( 'nfe_uf', 'UF inválida.' );
		}
		$el = self::eleicao_para( 3, $uf, 1 );
		if ( is_wp_error( $el ) ) {
			return $el;
		}
		$ele   = $el['e1']['cd'];
		$ciclo = self::ciclo();
		return self::cached(
			'mun_' . $ciclo . '_' . $ele . '_' . $uf,
			DAY_IN_SECONDS,
			function () use ( $ciclo, $ele, $uf ) {
				$d = self::http_json( sprintf( '%s/%s/config/mun-e%06d-cm.json', $ciclo, $ele, $ele ) );
				if ( is_wp_error( $d ) ) {
					return $d;
				}
				$out = array();
				foreach ( isset( $d['abr'] ) ? $d['abr'] : array() as $a ) {
					if ( strtolower( $a['cd'] ) !== $uf ) {
						continue;
					}
					foreach ( $a['mu'] as $m ) {
						$out[ (string) $m['cd'] ] = array(
							'cd'   => (string) $m['cd'],
							'nome' => NFE_Render::titulo( $m['nm'] ),
							'ibge' => isset( $m['cdi'] ) ? (string) $m['cdi'] : '',
						);
					}
				}
				if ( ! $out ) {
					return new WP_Error( 'nfe_cfg', 'Municípios não encontrados para a UF.' );
				}
				uasort(
					$out,
					function ( $a, $b ) {
						return strcmp( self::slug( $a['nome'] ), self::slug( $b['nome'] ) );
					}
				);
				return $out;
			}
		);
	}

	public static function slug( $s ) {
		return sanitize_title( remove_accents( (string) $s ) );
	}

	/**
	 * Interpreta "br", uma UF ("sc"), um código TSE de município ("82538") ou um nome ("pinhalzinho").
	 *
	 * @return array|WP_Error { tipo: br|uf|mun, uf, mun, nome, chave }
	 */
	public static function local( $local, $uf = '' ) {
		$uf    = strtolower( $uf ? $uf : self::uf_padrao() );
		$local = strtolower( trim( (string) $local ) );

		if ( '' === $local ) {
			$local = $uf;
		}
		if ( 'br' === $local || 'brasil' === $local ) {
			return array( 'tipo' => 'br', 'uf' => 'br', 'mun' => '', 'nome' => 'Brasil', 'chave' => 'br' );
		}
		if ( isset( self::UFS[ $local ] ) ) {
			return array( 'tipo' => 'uf', 'uf' => $local, 'mun' => '', 'nome' => self::UFS[ $local ], 'chave' => $local );
		}

		// "sc-82538" / "sc:pinhalzinho" permitem município de outra UF.
		if ( preg_match( '/^([a-z]{2})[-:](.+)$/', $local, $m ) && isset( self::UFS[ $m[1] ] ) ) {
			$uf    = $m[1];
			$local = $m[2];
		}

		$muns = self::municipios( $uf );
		if ( is_wp_error( $muns ) ) {
			return $muns;
		}
		$achado = null;
		if ( ctype_digit( $local ) ) {
			$cd = str_pad( $local, 5, '0', STR_PAD_LEFT );
			if ( isset( $muns[ $cd ] ) ) {
				$achado = $muns[ $cd ];
			}
		} else {
			$alvo = self::slug( $local );
			foreach ( $muns as $m ) {
				if ( self::slug( $m['nome'] ) === $alvo ) {
					$achado = $m;
					break;
				}
			}
		}
		if ( ! $achado ) {
			return new WP_Error( 'nfe_local', 'Município não encontrado: ' . $local );
		}
		return array(
			'tipo'  => 'mun',
			'uf'    => $uf,
			'mun'   => $achado['cd'],
			'nome'  => $achado['nome'],
			'chave' => $uf . '-' . $achado['cd'],
		);
	}

	/** Lista de locais em destaque definida nas configurações. */
	public static function destaques() {
		$out = array();
		foreach ( preg_split( '/\s*,\s*/', (string) NFE_Options::get( 'destaques' ), -1, PREG_SPLIT_NO_EMPTY ) as $item ) {
			$loc = self::local( $item );
			if ( ! is_wp_error( $loc ) ) {
				$out[ $loc['chave'] ] = $loc;
			}
		}
		return $out;
	}

	/* ------------------------------------------------------------------ *
	 * Resultados
	 * ------------------------------------------------------------------ */

	/**
	 * Resultado normalizado de um cargo em um local.
	 *
	 * @param string     $cargo Slug de self::CARGOS.
	 * @param string     $local Ver self::local().
	 * @param string|int $turno 'auto', 1 ou 2.
	 * @return array|WP_Error
	 */
	public static function resultado( $cargo, $local = '', $turno = 'auto' ) {
		if ( ! isset( self::CARGOS[ $cargo ] ) ) {
			return new WP_Error( 'nfe_cargo', 'Cargo inválido.' );
		}
		$cfg = self::CARGOS[ $cargo ];
		$loc = self::local( $local );
		if ( is_wp_error( $loc ) ) {
			return $loc;
		}
		// Só Presidente tem resultado nacional.
		if ( 'br' === $loc['tipo'] && 'br' !== $cfg['escopo'] ) {
			$loc = self::local( self::uf_padrao() );
		}

		$el = self::eleicao_para( $cfg['cd'], $loc['uf'], $turno );
		if ( is_wp_error( $el ) ) {
			return $el;
		}

		$aviso = '';
		$res   = self::carregar( $el['eleicao'], $el['e1'], $cargo, $loc );
		if ( is_wp_error( $res ) && 2 === $el['turno'] && 'auto' === $turno ) {
			$res   = self::carregar( $el['e1'], $el['e1'], $cargo, $loc );
			$el['turno'] = 1;
			$aviso = 'Os dados do 2º turno ainda não foram publicados pelo TSE. Exibindo o 1º turno.';
		}
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		// Arquivos de município/UF não trazem a situação final (eleito, 2º turno…):
		// ela vem do arquivo da abrangência de totalização (BR para Presidente, UF para os demais).
		$topo = 'br' === $cfg['escopo'] ? 'br' : 'uf';
		if ( $loc['tipo'] !== $topo ) {
			$eleicao_atual = 2 === $el['turno'] ? $el['e2'] : $el['e1'];
			$sup           = self::carregar( $eleicao_atual, $el['e1'], $cargo, self::local( 'br' === $topo ? 'br' : $loc['uf'] ) );
			if ( ! is_wp_error( $sup ) ) {
				$sit = array();
				foreach ( $sup['candidatos'] as $c ) {
					$sit[ $c['sq'] ] = array( $c['eleito'], $c['situacao'] );
				}
				foreach ( $res['candidatos'] as $i => $c ) {
					if ( isset( $sit[ $c['sq'] ] ) ) {
						list( $res['candidatos'][ $i ]['eleito'], $res['candidatos'][ $i ]['situacao'] ) = $sit[ $c['sq'] ];
					}
				}
				$res['situacao_de'] = $sup['local']['nome'];
			}
		}

		$res['cargo_slug'] = $cargo;
		$res['turno']      = $el['turno'];
		$res['tem_t2']     = (bool) $el['e2'];
		$res['aviso']      = $aviso;
		return $res;
	}

	private static function carregar( $eleicao, $e1, $cargo, $loc ) {
		if ( is_wp_error( $loc ) ) {
			return $loc;
		}
		$cfg   = self::CARGOS[ $cargo ];
		$ciclo = self::ciclo();
		$ele   = $eleicao['cd'];
		$path  = sprintf( '%s/%s/dados/%s/%s%s-c%04d-e%06d-u.json', $ciclo, $ele, $loc['uf'], $loc['uf'], $loc['mun'], $cfg['cd'], $ele );
		$fotos = sprintf( '%s/%s/fotos/%s/', $ciclo, $e1['cd'], 'br' === $cfg['escopo'] ? 'br' : $loc['uf'] );
		$int   = (int) NFE_Options::get( 'intervalo' );

		return self::cached(
			'res_' . $path,
			function ( $v ) use ( $int ) {
				return ! empty( $v['finalizada'] ) ? 15 * MINUTE_IN_SECONDS : max( 20, $int - 15 );
			},
			function () use ( $path, $cfg, $loc, $eleicao, $fotos ) {
				$raw = self::http_json( $path );
				if ( is_wp_error( $raw ) ) {
					return $raw;
				}
				return self::normalizar( $raw, $cfg, $loc, $eleicao, $fotos );
			}
		);
	}

	private static function num( $v ) {
		return (float) str_replace( ',', '.', (string) $v );
	}

	private static function normalizar( $raw, $cfg, $loc, $eleicao, $fotos ) {
		if ( empty( $raw['carg'][0] ) ) {
			return new WP_Error( 'nfe_json', 'Arquivo do TSE sem dados do cargo.' );
		}
		$c = $raw['carg'][0];
		$s = isset( $raw['s'] ) ? $raw['s'] : array();
		$e = isset( $raw['e'] ) ? $raw['e'] : array();
		$v = isset( $raw['v'] ) ? $raw['v'] : array();

		$cands = array();
		$agrs  = array();
		foreach ( isset( $c['agr'] ) ? $c['agr'] : array() as $agr ) {
			$tp       = isset( $agr['tp'] ) ? $agr['tp'] : 'i';
			$votos_ag = isset( $agr['tvan'] ) ? (int) $agr['tvan'] : 0;
			$tem_tvan = isset( $agr['tvan'] );
			$siglas   = array();
			foreach ( isset( $agr['par'] ) ? $agr['par'] : array() as $par ) {
				$siglas[] = $par['sg'];
				if ( ! $tem_tvan ) {
					$votos_ag += isset( $par['tvan'] ) ? (int) $par['tvan'] : 0;
				}
				foreach ( isset( $par['cand'] ) ? $par['cand'] : array() as $cd ) {
					$vices = array();
					foreach ( isset( $cd['vs'] ) ? $cd['vs'] : array() as $vs ) {
						$vices[] = array(
							'tipo'    => $vs['tp'],
							'nome'    => NFE_Render::titulo( isset( $vs['nmu'] ) ? $vs['nmu'] : $vs['nm'] ),
							'partido' => isset( $vs['sgp'] ) ? $vs['sgp'] : '',
						);
					}
					$cands[] = array(
						'sq'        => (string) $cd['sqcand'],
						'numero'    => (string) $cd['n'],
						'nome'      => NFE_Render::titulo( isset( $cd['nmu'] ) && '' !== $cd['nmu'] ? $cd['nmu'] : $cd['nm'] ),
						'completo'  => NFE_Render::titulo( $cd['nm'] ),
						'partido'   => (string) $par['sg'],
						'agrup'     => in_array( $tp, array( 'c', 'f' ), true ) ? NFE_Render::titulo( $agr['nm'] ) : '',
						'agrup_tp'  => $tp,
						'agrup_com' => isset( $agr['com'] ) ? (string) $agr['com'] : '',
						'votos'     => (int) $cd['vap'],
						'pct'       => self::num( isset( $cd['pvapn'] ) ? $cd['pvapn'] : $cd['pvap'] ),
						'eleito'    => isset( $cd['e'] ) && 's' === $cd['e'],
						'situacao'  => isset( $cd['st'] ) ? (string) $cd['st'] : '',
						'validade'  => isset( $cd['dvt'] ) ? (string) $cd['dvt'] : '',
						'vices'     => $vices,
					);
				}
			}
			$agrs[] = array(
				'nome'  => 'i' === $tp ? implode( '/', $siglas ) : NFE_Render::titulo( $agr['nm'] ),
				'sigla' => isset( $agr['com'] ) ? (string) $agr['com'] : implode( '/', $siglas ),
				'extenso' => isset( $agr['nm'] ) ? NFE_Render::titulo( $agr['nm'] ) : '',
				'tipo'  => $tp,
				'votos' => $votos_ag,
				'vagas' => isset( $agr['vag'] ) ? (int) $agr['vag'] : 0,
			);
		}

		usort(
			$cands,
			function ( $a, $b ) {
				return $b['votos'] <=> $a['votos'] ?: strcmp( $a['nome'], $b['nome'] );
			}
		);
		usort(
			$agrs,
			function ( $a, $b ) {
				return $b['vagas'] <=> $a['vagas'] ?: $b['votos'] <=> $a['votos'];
			}
		);

		// Presidente/Governador matematicamente definidos sem maioria: o TSE só marca "2º turno"
		// ao encerrar a totalização, mas os dois primeiros já estão garantidos.
		$definida = isset( $raw['md'] ) && 's' === $raw['md'];
		if ( $definida && in_array( $cfg['cd'], array( 1, 3 ), true ) && count( $cands ) > 1 && $cands[0]['pct'] <= 50
			&& ! array_filter( wp_list_pluck( $cands, 'eleito' ) ) && '' === $cands[0]['situacao'] && '' === $cands[1]['situacao'] ) {
			$cands[0]['situacao'] = '2º turno';
			$cands[1]['situacao'] = '2º turno';
		}

		$tv = isset( $v['tv'] ) ? (int) $v['tv'] : 0;
		$pv = function ( $n ) use ( $tv ) {
			return $tv > 0 ? 100 * $n / $tv : 0;
		};

		return array(
			'cargo'        => $cfg['nome'],
			'cargo_cd'     => $cfg['cd'],
			'proporcional' => $cfg['prop'],
			'eleicao'      => $eleicao['cd'],
			'eleicao_nome' => $eleicao['nome'],
			'data'         => $eleicao['data'],
			'local'        => array( 'tipo' => $loc['tipo'], 'uf' => $loc['uf'], 'nome' => $loc['nome'], 'chave' => $loc['chave'] ),
			'atualizado'   => trim( ( isset( $raw['dt'] ) ? $raw['dt'] : '' ) . ' ' . ( isset( $raw['ht'] ) ? $raw['ht'] : '' ) ),
			'finalizada'   => isset( $raw['tf'] ) && 's' === $raw['tf'],
			'definida'     => isset( $raw['md'] ) && 's' === $raw['md'],
			'vagas'        => isset( $c['nv'] ) ? (int) $c['nv'] : 1,
			'qe'           => isset( $c['qe'] ) ? (int) $c['qe'] : 0,
			'secoes'       => array(
				'total'       => isset( $s['ts'] ) ? (int) $s['ts'] : 0,
				'totalizadas' => isset( $s['st'] ) ? (int) $s['st'] : 0,
				'pct'         => self::num( isset( $s['pstn'] ) ? $s['pstn'] : ( isset( $s['pst'] ) ? $s['pst'] : 0 ) ),
			),
			'eleitorado'   => isset( $e['te'] ) ? (int) $e['te'] : 0,
			'comparec'     => array( isset( $e['c'] ) ? (int) $e['c'] : 0, self::num( isset( $e['pcn'] ) ? $e['pcn'] : 0 ) ),
			'abstencao'    => array( isset( $e['a'] ) ? (int) $e['a'] : 0, self::num( isset( $e['pan'] ) ? $e['pan'] : 0 ) ),
			'validos'      => array( isset( $v['vv'] ) ? (int) $v['vv'] : 0, $pv( isset( $v['vv'] ) ? (int) $v['vv'] : 0 ) ),
			'brancos'      => array( isset( $v['vb'] ) ? (int) $v['vb'] : 0, $pv( isset( $v['vb'] ) ? (int) $v['vb'] : 0 ) ),
			'nulos'        => array( isset( $v['tvn'] ) ? (int) $v['tvn'] : 0, $pv( isset( $v['tvn'] ) ? (int) $v['tvn'] : 0 ) ),
			'legenda'      => isset( $v['vl'] ) ? (int) $v['vl'] : 0,
			'fotos'        => self::url( $fotos ),
			'candidatos'   => $cands,
			'agrupamentos' => $cfg['prop'] ? $agrs : array(),
		);
	}
}
