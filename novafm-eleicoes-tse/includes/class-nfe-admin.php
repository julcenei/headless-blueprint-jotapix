<?php
/**
 * Configurações → Eleições TSE.
 */

defined( 'ABSPATH' ) || exit;

class NFE_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_nfe_tse_flush', array( __CLASS__, 'flush' ) );
	}

	public static function menu() {
		add_options_page( 'Eleições TSE', 'Eleições TSE', 'manage_options', 'nfe-tse', array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting(
			'nfe_tse',
			NFE_Options::KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'NFE_Options', 'sanitize' ),
				'default'           => NFE_Options::defaults(),
			)
		);
	}

	public static function flush() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'nfe_tse_flush' );
		NFE_TSE::flush_cache();
		wp_safe_redirect( admin_url( 'options-general.php?page=nfe-tse&nfe_flushed=1' ) );
		exit;
	}

	private static function campo( $nome, $valor, $tipo = 'text', $extra = '' ) {
		printf(
			'<input type="%s" name="%s[%s]" id="nfe_%s" value="%s" class="regular-text" %s>',
			esc_attr( $tipo ),
			esc_attr( NFE_Options::KEY ),
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
		$o = NFE_Options::all();
		?>
		<div class="wrap">
			<h1>Eleições TSE</h1>
			<?php if ( isset( $_GET['nfe_flushed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success"><p>Cache limpo. Os próximos acessos buscam dados novos no TSE.</p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'nfe_tse' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="nfe_ciclo">Ciclo eleitoral</label></th>
						<td><?php self::campo( 'ciclo', $o['ciclo'], 'text', 'pattern="ele[0-9]{4}" style="width:8em"' ); ?>
							<p class="description">Como o TSE identifica a eleição: <code>ele2026</code>. O 2º turno é detectado sozinho dentro do ciclo.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="nfe_uf">Estado padrão</label></th>
						<td>
							<select name="<?php echo esc_attr( NFE_Options::KEY ); ?>[uf]" id="nfe_uf">
								<?php foreach ( NFE_TSE::UFS as $sigla => $nome ) : ?>
									<option value="<?php echo esc_attr( $sigla ); ?>" <?php selected( $o['uf'], $sigla ); ?>><?php echo esc_html( strtoupper( $sigla ) . ' – ' . $nome ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nfe_destaques">Municípios em destaque</label></th>
						<td><?php self::campo( 'destaques', $o['destaques'] ); ?>
							<p class="description">Separados por vírgula, por nome ou código TSE. Viram botões no painel. Ex.: <code>pinhalzinho, sao-lourenco-do-oeste</code></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="nfe_intervalo">Atualizar a cada (segundos)</label></th>
						<td><?php self::campo( 'intervalo', $o['intervalo'], 'number', 'min="30" max="600" style="width:6em"' ); ?>
							<p class="description">O TSE atualiza os arquivos a cada ~1 minuto. Os visitantes leem do cache do site, nunca do TSE.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="nfe_cor">Cor de destaque</label></th>
						<td><?php self::campo( 'cor', $o['cor'], 'color', 'style="width:4em;padding:0"' ); ?></td>
					</tr>
					<tr>
						<th scope="row">Fotos dos candidatos</th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( NFE_Options::KEY ); ?>[fotos]" value="1" <?php checked( $o['fotos'], 1 ); ?>> Mostrar fotos (carregadas do TSE)</label></td>
					</tr>
					<tr>
						<th scope="row"><label for="nfe_link">Página da apuração</label></th>
						<td><?php self::campo( 'link', $o['link'], 'url' ); ?>
							<p class="description">Link "Ver apuração completa" dos widgets compactos (ex.: a página onde está o painel).</p></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2>Status da conexão com o TSE</h2>
			<?php self::status(); ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="nfe_tse_flush">
				<?php wp_nonce_field( 'nfe_tse_flush' ); ?>
				<?php submit_button( 'Limpar cache', 'secondary', 'submit', false ); ?>
			</form>

			<h2>Como usar</h2>
			<p>Cole um dos shortcodes em qualquer página ou post (bloco <em>Shortcode</em>):</p>
			<table class="widefat striped" style="max-width:960px">
				<tbody>
					<tr><td><code>[eleicoes_tse_painel]</code></td><td>Painel completo: abas de cargo (incluindo "Por seção") e botões Brasil / estado / municípios em destaque, além de uma lista com todos os municípios.</td></tr>
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
		$els = NFE_TSE::eleicoes();
		if ( is_wp_error( $els ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $els->get_error_message() ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped" style="max-width:960px"><thead><tr><th>Cargo</th><th>1º turno</th><th>2º turno</th><th>Exibindo agora</th></tr></thead><tbody>';
		foreach ( NFE_TSE::CARGOS as $slug => $c ) {
			$el = NFE_TSE::eleicao_para( $c['cd'], 'br' === $c['escopo'] ? 'br' : NFE_TSE::uf_padrao() );
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

		$dest = NFE_TSE::destaques();
		echo '<p>Municípios em destaque reconhecidos: ' . ( $dest ? esc_html( implode( ', ', wp_list_pluck( $dest, 'nome' ) ) ) : '<em>nenhum</em>' ) . '</p>';
	}
}
