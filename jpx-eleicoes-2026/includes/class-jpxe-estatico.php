<?php
/**
 * Arquivos estáticos das atualizações automáticas, em wp-content/uploads/jpx-eleicoes/.
 *
 * Cada resposta do endpoint REST também é gravada como <chave>.json. O navegador lê
 * primeiro esse arquivo (servido direto pelo servidor web, sem iniciar o WordPress) e só
 * chama o REST quando ele não existe ou já expirou. Em noite de apuração, com milhares de
 * abas abertas, o PHP passa a rodar cerca de duas vezes por minuto por resultado, e não
 * uma vez por visitante.
 *
 * A chave é calculada igual no PHP e no JavaScript (assets/js/eleicoes.js → chave()).
 */

defined( 'ABSPATH' ) || exit;

class JPXE_Estatico {

	const DIR = 'jpx-eleicoes';

	/** Teto de arquivos: acima disso não cria novos (o REST continua respondendo). */
	const MAX_ARQUIVOS = 3000;

	/** Teto de espaço em disco (bytes). */
	const MAX_BYTES = 104857600;

	public static function ativo() {
		return (bool) apply_filters( 'jpxe_estaticos', true );
	}

	/** @return array { dir, url } */
	public static function base() {
		$u = wp_upload_dir( null, false );
		return array(
			'dir' => trailingslashit( $u['basedir'] ) . self::DIR,
			'url' => set_url_scheme( trailingslashit( $u['baseurl'] ) . self::DIR ),
		);
	}

	/** "cargo=presidente&local=sc" (ordem alfabética, sem vazios) → "presidente-sc-1a2b3c4d". */
	public static function chave( $params ) {
		unset( $params['_'], $params['v'] );
		ksort( $params, SORT_STRING );
		$partes = array();
		foreach ( $params as $k => $v ) {
			if ( null === $v || '' === (string) $v ) {
				continue;
			}
			$partes[] = $k . '=' . $v;
		}
		$pref = array();
		foreach ( array( 'cargo', 'mapa', 'local', 'secao' ) as $k ) {
			if ( isset( $params[ $k ] ) && '' !== (string) $params[ $k ] ) {
				$pref[] = $params[ $k ];
			}
		}
		$pref = substr( preg_replace( '/[^a-z0-9-]/', '', strtolower( implode( '-', $pref ) ) ), 0, 60 );
		return ( $pref ? $pref . '-' : '' ) . sprintf( '%08x', crc32( implode( '&', $partes ) ) );
	}

	public static function gravar( $params, $dados ) {
		if ( ! self::ativo() ) {
			return;
		}
		$b = self::base();
		if ( ! wp_mkdir_p( $b['dir'] ) ) {
			return;
		}
		if ( ! file_exists( $b['dir'] . '/index.php' ) ) {
			@file_put_contents( $b['dir'] . '/index.php', "<?php\n// Silêncio.\n" ); // phpcs:ignore
		}
		$arq = $b['dir'] . '/' . self::chave( $params ) . '.json';
		if ( ! file_exists( $arq ) ) {
			$uso = self::uso();
			if ( $uso['arquivos'] >= self::MAX_ARQUIVOS || $uso['bytes'] >= self::MAX_BYTES ) {
				return;
			}
		}
		// Escreve num temporário e renomeia: quem lê nunca pega um arquivo pela metade.
		$tmp = $arq . '.' . wp_generate_password( 6, false, false ) . '.tmp';
		if ( false !== @file_put_contents( $tmp, wp_json_encode( $dados ) ) ) { // phpcs:ignore
			if ( ! @rename( $tmp, $arq ) ) { // phpcs:ignore
				@unlink( $tmp ); // phpcs:ignore
			}
		}
	}

	/** Apaga arquivos com mais de $idade segundos (0 = todos). */
	public static function limpar( $idade = 0 ) {
		$b = self::base();
		$n = 0;
		foreach ( (array) glob( $b['dir'] . '/*.{json,tmp}', GLOB_BRACE | GLOB_NOSORT ) as $f ) {
			if ( $f && ( 0 === $idade || filemtime( $f ) < time() - $idade ) && @unlink( $f ) ) { // phpcs:ignore
				$n++;
			}
		}
		return $n;
	}

	/** @return array { arquivos, bytes } */
	public static function uso() {
		$b     = self::base();
		$lista = glob( $b['dir'] . '/*.json', GLOB_NOSORT );
		$bytes = 0;
		foreach ( is_array( $lista ) ? $lista : array() as $f ) {
			$bytes += (int) @filesize( $f ); // phpcs:ignore
		}
		return array( 'arquivos' => is_array( $lista ) ? count( $lista ) : 0, 'bytes' => $bytes );
	}
}
