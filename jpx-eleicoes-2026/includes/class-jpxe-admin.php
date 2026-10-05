<?php
/**
 * Configurações → Eleições 2026.
 */

defined( 'ABSPATH' ) || exit;

class JPXE_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_jpxe_flush', array( __CLASS__, 'flush' ) );
	}

	public static function menu() {
		add_options_page( 'JPX Eleições 2026', 'Eleições 2026', 'manage_options', 'jpx-eleicoes', array( __CLASS__, 'page' ) );
	}

	public static function register() {
		// Atualizar o plugin pelo .zip não dispara a ativação: garante a limpeza diária aqui.
		if ( ! wp_next_scheduled( 'jpxe_limpeza' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'jpxe_limpeza' );
		}
		register_setting(
			'jpxe_tse',
			JPXE_Options::KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'JPXE_Options', 'sanitize' ),
				'default'           => JPXE_Options::defaults(),
			)
		);
	}

	/**
	 * "Buscar dados novos no TSE agora": apaga cache e arquivos estáticos e já baixa de novo
	 * os resultados principais, para que o próximo visitante encontre tudo pronto.
	 */
	public static function flush() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'jpxe_flush' );
		$t = microtime( true );
		JPXE_TSE::flush_cache();

		$uf     = JPXE_TSE::uf_padrao();
		$muns   = array_keys( JPXE_TSE::destaques() );
		$locais = array_merge( array( $uf ), $muns );
		JPXE_TSE::precarregar( 'presidente', array_merge( array( 'br' ), $locais ) );
		foreach ( array( 'governador', 'senador', 'deputado-federal', 'deputado-estadual' ) as $c ) {
			JPXE_TSE::precarregar( $c, $locais );
		}
		$n = 0;
		foreach ( array_keys( JPXE_TSE::CARGOS ) as $c ) {
			foreach ( 'presidente' === $c ? array_merge( array( 'br' ), $locais ) : $locais as $l ) {
				$n += is_wp_error( JPXE_TSE::resultado( $c, $l ) ) ? 0 : 1;
			}
		}
		wp_safe_redirect( admin_url( 'options-general.php?page=jpx-eleicoes&jpxe_flushed=' . $n . '&jpxe_s=' . round( microtime( true ) - $t, 1 ) ) );
		exit;
	}

	private static function bytes( $b ) {
		return $b >= 1048576 ? number_format( $b / 1048576, 1, ',', '.' ) . ' MB' : number_format( $b / 1024, 0, ',', '.' ) . ' KB';
	}

	private static function uso() {
		global $wpdb;
		$e = JPXE_Estatico::uso();
		echo '<table class="widefat striped" style="max-width:960px"><tbody>';
		echo '<tr><td style="width:280px">Arquivos estáticos <code>uploads/' . esc_html( JPXE_Estatico::DIR ) . '/</code></td><td>' . (int) $e['arquivos'] . ' arquivos · ' . esc_html( self::bytes( $e['bytes'] ) ) . ' <span class="description">(limpos automaticamente após 2 dias sem uso; teto de ' . (int) JPXE_Estatico::MAX_ARQUIVOS . ' arquivos ou ' . esc_html( self::bytes( JPXE_Estatico::MAX_BYTES ) ) . ')</span></td></tr>';
		if ( wp_using_ext_object_cache() ) {
			echo '<tr><td>Cache de dados</td><td>No object cache do servidor (Redis/Memcached): não ocupa o banco.</td></tr>';
		} else {
			$q = $wpdb->get_row( "SELECT COUNT(*) n, COALESCE(SUM(LENGTH(option_value)),0) b FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_jpxe%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			echo '<tr><td>Cache de dados (banco, <code>wp_options</code>)</td><td>' . (int) $q->n . ' registros · ' . esc_html( self::bytes( (int) $q->b ) ) . ' <span class="description">(expiram sozinhos; nada é carregado em todas as páginas)</span></td></tr>';
		}
		echo '</tbody></table>';
	}

	private static function campo( $nome, $valor, $tipo = 'text', $extra = '' ) {
		printf(
			'<input type="%s" name="%s[%s]" id="jpxe_%s" value="%s" class="regular-text" %s>',
			esc_attr( $tipo ),
			esc_attr( JPXE_Options::KEY ),
			esc_attr( $nome ),
			esc_attr( $nome ),
			esc_attr( $valor ),
			$extra // phpcs:ignore WordPress.Security.EscapeOutput -- atributos fixos.
		);
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o = JPXE_Options::all();
		?>
		<div class="wrap">
			<h1>JPX Eleições 2026</h1>
			<?php if ( isset( $_GET['jpxe_flushed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success"><p>Dados atualizados direto do TSE: <?php echo (int) $_GET['jpxe_flushed']; // phpcs:ignore ?> resultados principais já baixados em <?php echo esc_html( isset( $_GET['jpxe_s'] ) ? str_replace( '.', ',', (string) (float) $_GET['jpxe_s'] ) : '?' ); // phpcs:ignore ?>s. Seções, mapa e demais municípios são buscados no primeiro acesso.</p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'jpxe_tse' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="jpxe_ciclo">Ciclo eleitoral</label></th>
						<td><?php self::campo( 'ciclo', $o['ciclo'], 'text', 'pattern="ele[0-9]{4}" style="width:8em"' ); ?>
							<p class="description">Como o TSE identifica a eleição: <code>ele2026</code>. O 2º turno é detectado sozinho dentro do ciclo.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="jpxe_uf">Estado padrão</label></th>
						<td>
							<select name="<?php echo esc_attr( JPXE_Options::KEY ); ?>[uf]" id="jpxe_uf">
								<?php foreach ( JPXE_TSE::UFS as $sigla => $nome ) : ?>
									<option value="<?php echo esc_attr( $sigla ); ?>" <?php selected( $o['uf'], $sigla ); ?>><?php echo esc_html( strtoupper( $sigla ) . ' – ' . $nome ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="jpxe_destaques">Municípios em destaque</label></th>
						<td><?php self::campo( 'destaques', $o['destaques'] ); ?>
							<p class="description">Separados por vírgula, por nome ou código TSE. Viram botões no painel. Ex.: <code>pinhalzinho, sao-lourenco-do-oeste</code></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="jpxe_intervalo">Atualizar a cada (segundos)</label></th>
						<td><?php self::campo( 'intervalo', $o['intervalo'], 'number', 'min="30" max="600" style="width:6em"' ); ?>
							<p class="description">O TSE atualiza os arquivos a cada ~1 minuto. Os visitantes leem do cache do site, nunca do TSE.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="jpxe_cor">Cor de destaque</label></th>
						<td><?php self::campo( 'cor', $o['cor'], 'color', 'style="width:4em;padding:0"' ); ?></td>
					</tr>
					<tr>
						<th scope="row">Fotos dos candidatos</th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( JPXE_Options::KEY ); ?>[fotos]" value="1" <?php checked( $o['fotos'], 1 ); ?>> Mostrar fotos (carregadas do TSE)</label></td>
					</tr>
					<tr>
						<th scope="row"><label for="jpxe_aparencia">Aparência</label></th>
						<td>
							<select name="<?php echo esc_attr( JPXE_Options::KEY ); ?>[aparencia]" id="jpxe_aparencia">
								<option value="claro" <?php selected( $o['aparencia'], 'claro' ); ?>>Sempre claro (recomendado: o tema do site é claro)</option>
								<option value="auto" <?php selected( $o['aparencia'], 'auto' ); ?>>Automático (segue o modo escuro do aparelho)</option>
								<option value="escuro" <?php selected( $o['aparencia'], 'escuro' ); ?>>Sempre escuro</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="jpxe_largura">Largura do painel (px)</label></th>
						<td><?php self::campo( 'largura', $o['largura'], 'number', 'min="0" max="1600" step="10" style="width:7em"' ); ?>
							<p class="description">O painel e a página por seção podem ficar mais largos que a coluna de texto do tema (900px), centralizados e sem passar da tela. Use <code>0</code> para seguir a largura do tema.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="jpxe_link">Página da apuração</label></th>
						<td><?php self::campo( 'link', $o['link'], 'url' ); ?>
							<p class="description">Link "Ver apuração completa" dos widgets compactos (ex.: a página onde está o painel).</p></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2>Status da conexão com o TSE</h2>
			<?php self::status(); ?>
			<h2>Atualizar dados</h2>
			<p>Com a apuração encerrada, o plugin guarda os resultados por até 6 horas e confere o TSE a cada 30 minutos.
				Se houver mudança (retotalização, decisão judicial sobre candidatos <em>sub judice</em>, novos eleitos ou suplentes), use o botão para buscar tudo de novo agora.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="jpxe_flush">
				<?php wp_nonce_field( 'jpxe_flush' ); ?>
				<?php submit_button( 'Buscar dados novos no TSE agora', 'primary', 'submit', false ); ?>
			</form>
			<h2>Espaço ocupado</h2>
			<?php self::uso(); ?>

			<h2>Como usar</h2>
			<p>Cole um dos shortcodes em qualquer página ou post (bloco <em>Shortcode</em>):</p>
			<table class="widefat striped" style="max-width:960px">
				<tbody>
					<tr><td><code>[eleicoes_tse_painel]</code></td><td>Painel completo: abas de cargo (incluindo "Por seção") e botões Brasil / estado / municípios em destaque, além de uma lista com todos os municípios.</td></tr>
					<tr><td><code>[eleicoes_tse_mapa]</code></td><td>Mapa do Brasil: quem venceu para Presidente em cada estado (também <code>cargo="governador"</code> ou <code>"senador"</code>). Também é a aba "Mapa" do painel.</td></tr>
					<tr><td><code>[eleicoes_tse_regiao]</code></td><td>Mais votados (deputados) somando os municípios em destaque, com colunas por cidade. Atributos: <code>cargos</code>, <code>locais</code>, <code>limite</code>, <code>titulo</code>.</td></tr>
					<tr><td><code>[eleicoes_tse_secoes local="pinhalzinho"]</code></td><td>Seções do município agrupadas por local de votação; ao clicar, o boletim de urna oficial da seção com todos os cargos.</td></tr>
					<tr><td><code>[eleicoes_tse cargo="presidente" local="br"]</code></td><td>Presidente no Brasil.</td></tr>
					<tr><td><code>[eleicoes_tse cargo="governador" local="pinhalzinho"]</code></td><td>Governador, votos em Pinhalzinho.</td></tr>
					<tr><td><code>[eleicoes_tse cargo="senador" local="sc"]</code></td><td>Senado em Santa Catarina.</td></tr>
					<tr><td><code>[eleicoes_tse cargo="deputado-estadual" local="sao-lourenco-do-oeste" limite="30"]</code></td><td>Deputado Estadual em São Lourenço do Oeste (30 primeiros e busca).</td></tr>
					<tr><td><code>[eleicoes_tse cargo="presidente" local="sc" layout="compacto"]</code></td><td>Versão compacta (top 3) para home ou barra lateral.</td></tr>
				</tbody>
			</table>
			<p><strong>Atributos:</strong> <code>cargo</code> (presidente, governador, senador, deputado-federal, deputado-estadual) ·
				<code>local</code> (br, sigla da UF, nome ou código TSE do município) · <code>turno</code> (auto, 1, 2) ·
				<code>layout</code> (completo, compacto) · <code>limite</code> · <code>fotos</code> (sim, nao) · <code>titulo</code> · <code>link</code>.</p>
		</div>
		<?php
	}

	private static function status() {
		$els = JPXE_TSE::eleicoes();
		if ( is_wp_error( $els ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $els->get_error_message() ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped" style="max-width:960px"><thead><tr><th>Cargo</th><th>1º turno</th><th>2º turno</th><th>Exibindo agora</th></tr></thead><tbody>';
		foreach ( JPXE_TSE::CARGOS as $slug => $c ) {
			$el = JPXE_TSE::eleicao_para( $c['cd'], 'br' === $c['escopo'] ? 'br' : JPXE_TSE::uf_padrao() );
			echo '<tr><td>' . esc_html( $c['nome'] ) . '</td>';
			if ( is_wp_error( $el ) ) {
				echo '<td colspan="3">' . esc_html( $el->get_error_message() ) . '</td></tr>';
				continue;
			}
			echo '<td>' . esc_html( $el['e1']['cd'] . ' – ' . $el['e1']['data'] ) . '</td>';
			echo '<td>' . ( $el['e2'] ? esc_html( $el['e2']['cd'] . ' – ' . $el['e2']['data'] ) : '<span style="color:#888">não configurado pelo TSE</span>' ) . '</td>';
			echo '<td>' . (int) $el['turno'] . 'º turno</td></tr>';
		}
		echo '</tbody></table>';

		$dest = JPXE_TSE::destaques();
		echo '<p>Municípios em destaque reconhecidos: ' . ( $dest ? esc_html( implode( ', ', wp_list_pluck( $dest, 'nome' ) ) ) : '<em>nenhum</em>' ) . '</p>';
	}
}
