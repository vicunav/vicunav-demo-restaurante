<?php
/**
 * Funciones del child theme Vicunav Bonasera.
 *
 * Solo identidad visual (motivos decorativos propios de esta marca). Sin CPT, sin
 * lógica de negocio, sin contenido: ver docs/tokens.md para la procedencia de cada
 * valor de theme.json.
 *
 * @package Vicunav_Bonasera
 */

/**
 * Registra los estilos scoped de los motivos decorativos propios de Bonasera
 * (encabezado fantasma, tarjeta estilo ticket). Usa wp_enqueue_block_style() para
 * que carguen tanto en frontend como en el Editor del sitio sin depender de
 * add_editor_style().
 *
 * @return void
 */
function vicunav_bonasera_register_motif_style() {
	$style_path = get_theme_file_path( 'assets/css/bonasera-motifs.css' );

	wp_enqueue_block_style(
		'core/group',
		array(
			'handle' => 'vicunav-bonasera-motifs',
			'src'    => get_theme_file_uri( 'assets/css/bonasera-motifs.css' ),
			'path'   => $style_path,
			'ver'    => file_exists( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' ),
		)
	);
}
add_action( 'init', 'vicunav_bonasera_register_motif_style' );
