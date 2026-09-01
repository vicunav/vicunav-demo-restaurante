<?php
/**
 * Valida que WordPress compile el theme.json real (padre vicunav-theme-core +
 * child theme vicunav-bonasera) con la identidad real de la marca, y que el child
 * theme esté activo.
 *
 * Ejecutar con:
 * wp eval-file theme/vicunav-bonasera/tests/validate-wordpress-child-theme.php
 *
 * @package Vicunav_Bonasera
 */

if ( ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
	throw new RuntimeException( 'WordPress no expone las APIs de theme.json requeridas.' );
}

$active_theme = wp_get_theme();
if ( 'vicunav-bonasera' !== $active_theme->get_stylesheet() ) {
	throw new RuntimeException( 'El theme activo no es vicunav-bonasera: ' . $active_theme->get_stylesheet() );
}
if ( 'vicunav-theme-core' !== $active_theme->get_template() ) {
	throw new RuntimeException( 'El child theme no declara vicunav-theme-core como padre.' );
}

$theme_data = WP_Theme_JSON_Resolver::get_theme_data();
$stylesheet = $theme_data->get_stylesheet();

$expected_fragments = array(
	'--wp--preset--color--vicunav-primary: #0D0D0D',
	'--wp--preset--color--vicunav-neutral-100: #FAEBD7',
	'--wp--preset--spacing--vicunav-space-section-lg: 8.75rem',
	'transform: scaleY(1.22)',
	'opacity: 0.72',
);
foreach ( $expected_fragments as $fragment ) {
	if ( false === strpos( $stylesheet, $fragment ) ) {
		throw new RuntimeException(
			sprintf(
				/* translators: %s: fragmento CSS esperado. */
				'El CSS compilado no contiene: %s',
				esc_html( $fragment )
			)
		);
	}
}

$home_html = wp_remote_retrieve_body( wp_remote_get( home_url( '/' ) ) );
if ( false === strpos( $home_html, 'big-shoulders-display-latin.woff2' ) ) {
	throw new RuntimeException( 'El frontend no sirve la fuente autoalojada del child theme.' );
}
if ( false !== strpos( $home_html, 'fonts.googleapis.com' ) ) {
	throw new RuntimeException( 'El frontend depende de Google Fonts en runtime.' );
}

WP_CLI::success( 'WordPress compiló el theme.json real (padre + child theme Bonasera) y el frontend sirve las fuentes autoalojadas.' );
