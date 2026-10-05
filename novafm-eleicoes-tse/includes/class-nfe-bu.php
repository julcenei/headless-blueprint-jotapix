<?php
/**
 * Leitor do Boletim de Urna (arquivo -bu.dat publicado pelo TSE em arquivo-urna/).
 *
 * O BU é ASN.1 codificado em DER: um EntidadeEnvelopeGenerico cujo campo "conteudo"
 * (OCTET STRING) carrega o EntidadeBoletimUrna. A leitura procura os blocos pela forma
 * (e não por posição fixa), para tolerar campos novos que o TSE acrescente entre versões.
 *
 *   EntidadeBoletimUrna
 *     identificacaoSecao          { municipioZona { municipio, zona }, local, secao }
 *     dataHoraEmissao             GeneralString "AAAAMMDDTHHMMSS"
 *     dadosSecaoSA [0]            { dataHoraAbertura, dataHoraEncerramento }
 *     resultadosVotacaoPorEleicao SEQUENCE OF { idEleicao, qtdEleitoresAptos, ..., resultadosVotacao }
 *       resultadosVotacao         SEQUENCE OF { tipoCargo, qtdComparecimento, totaisVotosCargo }
 *         totaisVotosCargo        SEQUENCE OF { codigoCargo [1], ordemImpressao, votosVotaveis }
 *           votosVotaveis         SEQUENCE OF { tipoVoto [1], quantidadeVotos [2], identificacaoVotavel [3] { partido, codigo } }
 */

defined( 'ABSPATH' ) || exit;

class NFE_BU {

	const TIPO_VOTO = array(
		1 => 'nominal',
		2 => 'branco',
		3 => 'nulo',
		4 => 'legenda',
	);

	/**
	 * Decodifica DER em árvore: [ ['c' => classe, 't' => tag, 'k' => filhos] | ['c', 't', 'v' => bytes] ].
	 */
	public static function der( $b, $s = 0, $e = null, $nivel = 0 ) {
		$e   = null === $e ? strlen( $b ) : $e;
		$out = array();
		if ( $nivel > 40 ) {
			throw new RuntimeException( 'BU: aninhamento excessivo' );
		}
		while ( $s < $e ) {
			$t    = ord( $b[ $s++ ] );
			$cls  = $t >> 6;
			$cons = ( $t >> 5 ) & 1;
			$num  = $t & 31;
			if ( 31 === $num ) {
				$num = 0;
				do {
					$x   = ord( $b[ $s++ ] );
					$num = ( $num << 7 ) | ( $x & 127 );
				} while ( $x & 128 );
			}
			$l = ord( $b[ $s++ ] );
			if ( $l & 128 ) {
				$n = $l & 127;
				if ( 0 === $n || $n > 4 ) {
					throw new RuntimeException( 'BU: tamanho inválido' );
				}
				$l = 0;
				for ( $i = 0; $i < $n; $i++ ) {
					$l = ( $l << 8 ) | ord( $b[ $s++ ] );
				}
			}
			if ( $s + $l > $e ) {
				throw new RuntimeException( 'BU: arquivo truncado' );
			}
			$node = array( 'c' => $cls, 't' => $num );
			if ( $cons ) {
				$node['k'] = self::der( $b, $s, $s + $l, $nivel + 1 );
			} else {
				$node['v'] = substr( $b, $s, $l );
			}
			$out[] = $node;
			$s    += $l;
		}
		return $out;
	}

	private static function int( $n ) {
		if ( ! isset( $n['v'] ) || '' === $n['v'] ) {
			return 0;
		}
		$v = 0;
		$l = strlen( $n['v'] );
		for ( $i = 0; $i < $l; $i++ ) {
			$v = ( $v << 8 ) | ord( $n['v'][ $i ] );
		}
		if ( ord( $n['v'][0] ) & 0x80 ) {
			$v -= 1 << ( 8 * $l );
		}
		return $v;
	}

	private static function is_seq( $n ) {
		return 0 === $n['c'] && 16 === $n['t'] && isset( $n['k'] );
	}

	private static function is_int( $n ) {
		return 0 === $n['c'] && ( 2 === $n['t'] || 10 === $n['t'] ) && isset( $n['v'] );
	}

	private static function str( $n ) {
		return isset( $n['v'] ) ? (string) $n['v'] : '';
	}

	private static function ctx( $n, $tag ) {
		foreach ( isset( $n['k'] ) ? $n['k'] : array() as $f ) {
			if ( 2 === $f['c'] && $tag === $f['t'] ) {
				return $f;
			}
		}
		return null;
	}

	private static function first_seq( $n ) {
		foreach ( isset( $n['k'] ) ? $n['k'] : array() as $f ) {
			if ( self::is_seq( $f ) ) {
				return $f;
			}
		}
		return null;
	}

	/** "20261004T173509" → "04/10/2026 17:35:09" */
	private static function data( $s ) {
		return preg_match( '/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})$/', $s, $m ) ? "$m[3]/$m[2]/$m[1] $m[4]:$m[5]:$m[6]" : $s;
	}

	/**
	 * @param string $bin Conteúdo do -bu.dat.
	 * @return array|WP_Error
	 */
	public static function ler( $bin ) {
		try {
			$env = self::der( $bin );
			if ( ! $env || ! self::is_seq( $env[0] ) ) {
				return new WP_Error( 'nfe_bu', 'BU em formato inesperado.' );
			}
			// Conteúdo do envelope: a maior OCTET STRING que começa com SEQUENCE.
			$conteudo = '';
			foreach ( $env[0]['k'] as $f ) {
				if ( 0 === $f['c'] && 4 === $f['t'] && isset( $f['v'][0] ) && "\x30" === $f['v'][0] && strlen( $f['v'] ) > strlen( $conteudo ) ) {
					$conteudo = $f['v'];
				}
			}
			$bu = $conteudo ? self::der( $conteudo ) : array();
			if ( ! $bu || ! self::is_seq( $bu[0] ) ) {
				return new WP_Error( 'nfe_bu', 'BU sem conteúdo.' );
			}
			return self::boletim( $bu[0]['k'] );
		} catch ( Exception $e ) {
			return new WP_Error( 'nfe_bu', $e->getMessage() );
		}
	}

	private static function boletim( $campos ) {
		$out = array(
			'municipio'    => 0,
			'zona'         => 0,
			'local'        => 0,
			'secao'        => 0,
			'emissao'      => '',
			'abertura'     => '',
			'encerramento' => '',
			'eleicoes'     => array(),
		);

		foreach ( $campos as $f ) {
			// identificacaoSecao { { municipio, zona }, local, secao }
			if ( ! $out['secao'] && self::is_seq( $f ) && 3 === count( $f['k'] )
				&& self::is_seq( $f['k'][0] ) && 2 === count( $f['k'][0]['k'] ) && self::is_int( $f['k'][1] ) && self::is_int( $f['k'][2] ) ) {
				$out['municipio'] = self::int( $f['k'][0]['k'][0] );
				$out['zona']      = self::int( $f['k'][0]['k'][1] );
				$out['local']     = self::int( $f['k'][1] );
				$out['secao']     = self::int( $f['k'][2] );
				continue;
			}
			if ( '' === $out['emissao'] && 0 === $f['c'] && 27 === $f['t'] && $out['secao'] ) {
				$out['emissao'] = self::data( self::str( $f ) );
				continue;
			}
			// dadosSecaoSA [0] { abertura, encerramento }
			if ( 2 === $f['c'] && 0 === $f['t'] && isset( $f['k'][1] ) && 27 === $f['k'][0]['t'] ) {
				$out['abertura']     = self::data( self::str( $f['k'][0] ) );
				$out['encerramento'] = self::data( self::str( $f['k'][1] ) );
				continue;
			}
			// resultadosVotacaoPorEleicao
			if ( self::is_seq( $f ) && $f['k'] && self::is_seq( $f['k'][0] ) && self::is_int( $f['k'][0]['k'][0] ) && self::first_seq( $f['k'][0] ) ) {
				foreach ( $f['k'] as $el ) {
					$e = self::eleicao( $el );
					if ( $e ) {
						$out['eleicoes'][ $e['id'] ] = $e;
					}
				}
			}
		}

		if ( ! $out['secao'] || ! $out['eleicoes'] ) {
			return new WP_Error( 'nfe_bu', 'BU sem resultados reconhecíveis.' );
		}
		return $out;
	}

	private static function eleicao( $el ) {
		if ( ! self::is_seq( $el ) || count( $el['k'] ) < 3 || ! self::is_int( $el['k'][0] ) ) {
			return null;
		}
		$rv = self::first_seq( $el );
		if ( ! $rv ) {
			return null;
		}
		$e = array(
			'id'     => self::int( $el['k'][0] ),
			'aptos'  => self::int( $el['k'][1] ),
			'cargos' => array(),
		);
		foreach ( $rv['k'] as $res ) {
			if ( ! self::is_seq( $res ) || count( $res['k'] ) < 3 ) {
				continue;
			}
			$comparec = self::int( $res['k'][1] );
			$totais   = self::first_seq( $res );
			foreach ( $totais ? $totais['k'] : array() as $tvc ) {
				$cod = self::ctx( $tvc, 1 );
				$vv  = self::first_seq( $tvc );
				if ( ! $cod || ! $vv ) {
					continue;
				}
				$votos = array();
				foreach ( $vv['k'] as $v ) {
					$tipo = self::ctx( $v, 1 );
					$qtd  = self::ctx( $v, 2 );
					if ( ! $tipo || ! $qtd ) {
						continue;
					}
					$id      = self::ctx( $v, 3 );
					$votos[] = array(
						'tipo'    => self::int( $tipo ),
						'qtd'     => self::int( $qtd ),
						'partido' => $id && isset( $id['k'][0] ) ? self::int( $id['k'][0] ) : 0,
						'numero'  => $id && isset( $id['k'][1] ) ? self::int( $id['k'][1] ) : 0,
					);
				}
				$e['cargos'][ self::int( $cod ) ] = array(
					'comparecimento' => $comparec,
					'votos'          => $votos,
				);
			}
		}
		return $e;
	}
}
