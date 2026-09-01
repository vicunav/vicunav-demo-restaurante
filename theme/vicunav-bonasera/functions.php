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

/**
 * Oculta el campo "Añadir título" del editor de bloques en las páginas de
 * este demo: cada página trae su propio encabezado diseñado (hero, banner
 * interior) y el campo de título nativo de wp-admin queda redundante,
 * pidiendo que "se vea el diseño directamente" tal como pidió el usuario.
 *
 * No usa remove_post_type_support('page', 'title'): eso quita el título
 * también de listados, Edición rápida y la API REST — más de lo pedido. Solo
 * oculta el campo visualmente en el editor de bloques, con la misma técnica
 * que ya usa el ecosistema para neutralizar clamps del editor
 * (wp_add_inline_style() colgado de enqueue_block_editor_assets, ver
 * translation-map.md del skill transform-claude-to-gutenberg). El título
 * real se conserva en la base de datos: sigue siendo necesario para el
 * admin bar, el <title> del navegador y el editor de bloques internamente.
 *
 * @return void
 */
function vicunav_bonasera_hide_editor_title_field() {
	$screen = get_current_screen();
	if ( ! $screen || 'page' !== $screen->post_type ) {
		return;
	}

	wp_add_inline_style(
		'wp-edit-blocks',
		'.editor-post-title, .edit-post-visual-editor__post-title-wrapper { display: none !important; }'
	);
}
add_action( 'enqueue_block_editor_assets', 'vicunav_bonasera_hide_editor_title_field' );
