<?php
/**
 * Aplica el contenido demostrativo Bonasera mediante contratos públicos.
 *
 * Uso: wp eval-file bin/apply-content.php
 *
 * @package Vicunav_Demo_Restaurante
 */

use Vicu\Core\Settings;
use Vicu\Pagos\ManualPaymentProvider;
use Vicu\Restaurante\Catalog\IngredientService;
use Vicu\Restaurante\Catalog\PizzaOptionService;
use Vicu\Restaurante\Commerce\DeliveryZoneService;
use Vicu\Restaurante\Commerce\DiscountService;
use Vicu\Restaurante\Menu\MenuCategory;
use Vicu\Restaurante\Menu\MenuItemPostType;
use Vicu\Restaurante\Menu\MenuMeta;
use Vicu\Restaurante\Reservation\ReservationSettings;
use Vicu\Restaurante\Settings\RestaurantSettings;

// Los accesos de archivo son locales, las consultas por meta son acotadas y las
// variables viven en el alcance deliberadamente global de wp eval-file.
// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

const VICU_DEMO_MARKER       = '_vicu_demo_key';
const VICU_DEMO_ASSET_ID     = '_vicu_demo_asset_id';
const VICU_DEMO_ASSET_SHA256 = '_vicu_demo_asset_sha256';
const VICU_DEMO_ENTITY_MAP   = 'vicu_demo_restaurante_entity_map';

$repo_root      = dirname( __DIR__ );
$content        = json_decode( (string) file_get_contents( $repo_root . '/content/bonasera.json' ), true );
$media          = json_decode( (string) file_get_contents( $repo_root . '/config/media.json' ), true );
$required       = array( Settings::class, ManualPaymentProvider::class, IngredientService::class, PizzaOptionService::class, DeliveryZoneService::class, MenuItemPostType::class );
$expected_theme = 'vicunav-theme-core';

if ( ! is_array( $content ) || ! is_array( $media ) ) {
	WP_CLI::error( 'No se pudieron leer los manifiestos Bonasera.' );
}

foreach ( $required as $class_name ) {
	if ( ! class_exists( $class_name ) ) {
		WP_CLI::error( 'Falta una dependencia activa: ' . $class_name );
	}
}

if ( get_stylesheet() !== $expected_theme ) {
	WP_CLI::error( 'El theme activo debe ser vicunav-theme-core.' );
}

/**
 * Convierte un WP_Error en un fallo explícito de WP-CLI.
 *
 * @param mixed  $result    Resultado que se comprobará.
 * @param string $operation Descripción de la operación.
 * @return mixed
 */
function vicu_demo_result( mixed $result, string $operation ): mixed {
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $operation . ': ' . $result->get_error_message() );
	}

	return $result;
}

/**
 * Lee el mapa estable de entidades del vertical.
 *
 * @return array<string, mixed>
 */
function vicu_demo_map(): array {
	$value = get_option( VICU_DEMO_ENTITY_MAP, array() );

	return is_array( $value ) ? $value : array();
}

/**
 * Guarda el mapa estable de entidades del vertical.
 *
 * @param array<string, mixed> $map Mapa completo.
 */
function vicu_demo_save_map( array $map ): void {
	ksort( $map );
	update_option( VICU_DEMO_ENTITY_MAP, $map, false );
}

/**
 * Compara una proyección pública con los datos deseados.
 *
 * @param array<string, mixed> $current Entidad pública actual.
 * @param array<string, mixed> $desired Datos completos deseados.
 * @return bool
 */
function vicu_demo_same_entity( array $current, array $desired ): bool {
	unset( $current['public_id'], $current['revision'], $current['uses_count'] );
	ksort( $current );
	ksort( $desired );

	return $current === $desired;
}

/**
 * Conserva una entidad del vertical con UUID estable y compare-and-swap.
 *
 * @param string               $key     Clave estable del demo.
 * @param class-string         $service Servicio público propietario.
 * @param array<string, mixed> $desired Datos completos deseados.
 * @param array<string, mixed> $map     Mapa estable, actualizado por referencia.
 */
function vicu_demo_upsert_entity( string $key, string $service, array $desired, array &$map ): void {
	$public_id = is_string( $map[ $key ] ?? null ) ? $map[ $key ] : '';
	$current   = '' !== $public_id ? $service::find( $public_id ) : null;

	if ( null === $current ) {
		$created     = vicu_demo_result( $service::create( $desired ), 'Crear ' . $key );
		$map[ $key ] = $created['public_id'];
		return;
	}

	if ( ! vicu_demo_same_entity( $current, $desired ) ) {
		vicu_demo_result( $service::update( $public_id, $current['revision'], $desired ), 'Actualizar ' . $key );
	}
}

/**
 * Convierte una cantidad decimal a unidades monetarias menores.
 *
 * @param int|float $value Cantidad decimal.
 * @return int
 */
function vicu_demo_money( int|float $value ): int {
	return (int) round( (float) $value * 100 );
}

/**
 * Traduce alérgenos del inventario al vocabulario contractual.
 *
 * @param string[] $values Valores auditados.
 * @return string[]
 */
function vicu_demo_allergens( array $values ): array {
	$map = array(
		'gluten'       => 'gluten',
		'lácteos'      => 'milk',
		'huevo'        => 'eggs',
		'pescado'      => 'fish',
		'frutos secos' => 'nuts',
	);

	return array_values( array_intersect_key( $map, array_flip( $values ) ) );
}

/**
 * Traduce etiquetas dietarias al vocabulario contractual.
 *
 * @param string[] $values Valores auditados.
 * @return string[]
 */
function vicu_demo_dietary( array $values ): array {
	$map    = array(
		'vegetariano' => 'vegetarian',
		'vegano'      => 'vegan',
	);
	$result = array();
	foreach ( $values as $value ) {
		if ( isset( $map[ $value ] ) ) {
			$result[] = $map[ $value ];
		}
	}

	return array_values( array_unique( $result ) );
}

/**
 * Importa medios locales verificados sin crear duplicados.
 *
 * @param array<string, mixed> $manifest  Manifiesto de medios.
 * @param string               $repo_root Root absoluto del demo.
 * @return array<string, int>
 */
function vicu_demo_import_media( array $manifest, string $repo_root ): array {
	$ids = array();
	require_once ABSPATH . 'wp-admin/includes/image.php';

	foreach ( $manifest['assets'] as $asset ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 2,
				'meta_key'       => VICU_DEMO_ASSET_ID,
				'meta_value'     => $asset['id'],
				'fields'         => 'ids',
			)
		);
		if ( 1 < count( $query->posts ) ) {
			WP_CLI::error( 'Hay medios duplicados para ' . $asset['id'] . '.' );
		}

		$file = $repo_root . '/' . $asset['path'];
		if ( ! is_file( $file ) || hash_file( 'sha256', $file ) !== $asset['sha256'] ) {
			WP_CLI::error( 'El asset no coincide con el manifiesto: ' . $asset['id'] );
		}

		if ( $query->posts ) {
			$attachment_id = (int) $query->posts[0];
			$attached_file = get_attached_file( $attachment_id );
			if ( get_post_meta( $attachment_id, VICU_DEMO_ASSET_SHA256, true ) !== $asset['sha256'] || ! is_file( $attached_file ) || hash_file( 'sha256', $attached_file ) !== $asset['sha256'] ) {
				WP_CLI::error( 'El medio existente fue alterado: ' . $asset['id'] );
			}
		} else {
			$upload = wp_upload_bits( basename( $file ), null, (string) file_get_contents( $file ) );
			if ( $upload['error'] ) {
				WP_CLI::error( 'Importar ' . $asset['id'] . ': ' . $upload['error'] );
			}
			$attachment_id = wp_insert_attachment(
				array(
					'post_mime_type' => 'image/webp',
					'post_status'    => 'inherit',
					'post_title'     => ucfirst( $asset['id'] ) . ' Bonasera',
				),
				$upload['file']
			);
			if ( is_wp_error( $attachment_id ) || 1 > $attachment_id ) {
				WP_CLI::error( 'No se pudo registrar el medio ' . $asset['id'] . '.' );
			}
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
			update_post_meta( $attachment_id, VICU_DEMO_ASSET_ID, $asset['id'] );
			update_post_meta( $attachment_id, VICU_DEMO_ASSET_SHA256, $asset['sha256'] );
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $asset['alt'] );
		$ids[ $asset['id'] ] = $attachment_id;
	}

	return $ids;
}

/**
 * Crea o actualiza un contenido marcado por el demo.
 *
 * @param string               $type      Tipo de contenido.
 * @param string               $key       Clave estable del demo.
 * @param array<string, mixed> $post_data Datos públicos de WordPress.
 * @return int
 */
function vicu_demo_upsert_post( string $type, string $key, array $post_data ): int {
	$query = new WP_Query(
		array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'posts_per_page' => 2,
			'meta_key'       => VICU_DEMO_MARKER,
			'meta_value'     => $key,
			'fields'         => 'ids',
		)
	);
	if ( 1 < count( $query->posts ) ) {
		WP_CLI::error( 'Hay contenido duplicado para ' . $key . '.' );
	}

	$id = $query->posts ? (int) $query->posts[0] : 0;
	if ( 0 === $id && isset( $post_data['post_name'] ) ) {
		$collision = get_page_by_path( $post_data['post_name'], OBJECT, $type );
		if ( $collision ) {
			if ( 'page' === $type && 'inicio' === $key && 'inicio' === $collision->post_name ) {
				$id = (int) $collision->ID;
			} else {
				WP_CLI::error( 'El slug ya existe sin marcador del demo: ' . $post_data['post_name'] );
			}
		}
	}

	$post_data['post_type']   = $type;
	$post_data['post_status'] = $post_data['post_status'] ?? 'publish';
	if ( $id ) {
		$post_data['ID'] = $id;
		$result          = wp_update_post( wp_slash( $post_data ), true );
	} else {
		$result = wp_insert_post( wp_slash( $post_data ), true );
	}
	$id = (int) vicu_demo_result( $result, 'Guardar ' . $key );
	update_post_meta( $id, VICU_DEMO_MARKER, $key );

	return $id;
}

/**
 * Serializa un bloque de imagen con texto alternativo local.
 *
 * @param int    $id   ID del adjunto.
 * @param string $size Tamaño registrado.
 * @return string
 */
function vicu_demo_image_block( int $id, string $size = 'large' ): string {
	$url = wp_get_attachment_image_url( $id, $size );
	$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );

	return sprintf( '<!-- wp:image {"id":%1$d,"sizeSlug":"%4$s","linkDestination":"none"} --><figure class="wp-block-image size-%4$s"><img src="%2$s" alt="%3$s" class="wp-image-%1$d"/></figure><!-- /wp:image -->', $id, esc_url( $url ), esc_attr( $alt ), esc_attr( $size ) );
}

/**
 * Compone la portada editorial y sus bloques dinámicos.
 *
 * @param array<string, mixed> $content   Inventario Bonasera.
 * @param array<string, int>   $media_ids Adjuntos por clave estable.
 * @return string
 */
function vicu_demo_front_content( array $content, array $media_ids ): string {
	$pizza_url = wp_get_attachment_image_url( $media_ids['pizze'], 'full' );
	$story     = $content['editorial']['story'];

	return '<!-- wp:cover {"url":"' . esc_url( $pizza_url ) . '","id":' . $media_ids['pizze'] . ',"dimRatio":55,"minHeight":680,"minHeightUnit":"px","align":"full","className":"vicu-restaurant-hero"} --><div class="wp-block-cover alignfull vicu-restaurant-hero" style="min-height:680px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-50 has-background-dim"></span><img class="wp-block-cover__image-background wp-image-' . $media_ids['pizze'] . '" alt="" src="' . esc_url( $pizza_url ) . '" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow"} --><p class="has-text-align-center is-style-eyebrow">' . esc_html( $content['pages'][0]['eyebrow'] ) . '</p><!-- /wp:paragraph --><!-- wp:heading {"textAlign":"center","level":1,"fontSize":"xx-large"} --><h1 class="wp-block-heading has-text-align-center has-xx-large-font-size">Bonasera</h1><!-- /wp:heading --><!-- wp:paragraph {"align":"center","fontSize":"large"} --><p class="has-text-align-center has-large-font-size">' . esc_html( $content['pages'][0]['lead'] ) . '</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/menu/">Ver menú</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/reservas/">Reservar mesa</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div></div><!-- /wp:cover -->'
		. '<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} --><section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Nuestro menú</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Explora recetas italianas preparadas al momento o crea tu propia pizza.</p><!-- /wp:paragraph --><!-- wp:vicunav/restaurante-menu /--></section><!-- /wp:group -->'
		. '<!-- wp:group {"tagName":"section","align":"full","backgroundColor":"surface-alt","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} --><section class="wp-block-group alignfull has-surface-alt-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:columns {"verticalAlignment":"center"} --><div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} --><div class="wp-block-column is-vertically-aligned-center">' . vicu_demo_image_block( $media_ids['pasta'] ) . '</div><!-- /wp:column --><!-- wp:column {"verticalAlignment":"center"} --><div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading --><h2 class="wp-block-heading">Nuestra historia</h2><!-- /wp:heading --><!-- wp:paragraph --><p>' . esc_html( $story[0] ) . '</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>' . esc_html( $story[1] ) . '</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --></section><!-- /wp:group -->'
		. '<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} --><section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Dónde entregamos</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Selecciona una zona en el carrito para obtener tarifa y tiempo estimado autoritativos.</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/carrito/">Consultar entrega</a></div><!-- /wp:button --></div><!-- /wp:buttons --></section><!-- /wp:group -->'
		. '<!-- wp:pattern {"slug":"vicunav-theme-core/testimonials-grid"} /--><!-- wp:pattern {"slug":"vicunav-theme-core/faq-accordion"} /--><!-- wp:pattern {"slug":"vicunav-theme-core/contact-info"} /-->';
}

/**
 * Compone el cuerpo de una página funcional interna.
 *
 * @param string   $lead     Introducción editorial.
 * @param string   $block    Nombre del bloque dinámico.
 * @param int|null $image_id Adjunto editorial opcional.
 * @return string
 */
function vicu_demo_page_content( string $lead, string $block, ?int $image_id = null ): string {
	$content = '<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|70"}}}} --><div class="wp-block-group" style="padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">' . esc_html( $lead ) . '</p><!-- /wp:paragraph -->';
	if ( null !== $image_id ) {
		$content .= vicu_demo_image_block( $image_id );
	}

	return $content . '<!-- wp:' . $block . ' /--></div><!-- /wp:group -->';
}

/**
 * Conserva un override FSE de template asociado al theme activo.
 *
 * @param string $slug  Slug contractual.
 * @param string $title Título administrativo.
 * @param string $body  Markup de bloques.
 * @return int
 */
function vicu_demo_upsert_template( string $slug, string $title, string $body ): int {
	$query = new WP_Query(
		array(
			'post_type'      => 'wp_template',
			'post_status'    => 'any',
			'posts_per_page' => 2,
			'meta_key'       => VICU_DEMO_MARKER,
			'meta_value'     => 'template-' . $slug,
			'fields'         => 'ids',
		)
	);
	if ( 1 < count( $query->posts ) ) {
		WP_CLI::error( 'Hay templates duplicados para ' . $slug . '.' );
	}
	$template = $query->posts ? get_post( (int) $query->posts[0] ) : null;
	$data     = array(
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => $body,
		'post_type'    => 'wp_template',
		'post_status'  => 'publish',
	);
	if ( $template ) {
		$data['ID'] = $template->ID;
		$id         = wp_update_post( wp_slash( $data ), true );
	} else {
		$data['meta_input'] = array(
			'origin'         => 'theme',
			VICU_DEMO_MARKER => 'template-' . $slug,
		);
		$id                 = wp_insert_post( wp_slash( $data ), true );
	}

	$id = (int) vicu_demo_result( $id, 'Guardar template ' . $slug );
	vicu_demo_result( wp_set_object_terms( $id, get_stylesheet(), 'wp_theme' ), 'Asignar theme al template ' . $slug );
	update_post_meta( $id, VICU_DEMO_MARKER, 'template-' . $slug );
	update_post_meta( $id, 'origin', 'theme' );

	return $id;
}

/**
 * Conserva un override FSE de template part asociado al theme activo.
 *
 * @param string $slug  Slug contractual.
 * @param string $title Título administrativo.
 * @param string $body  Markup de bloques.
 * @param string $area  Área FSE registrada.
 * @return int
 */
function vicu_demo_upsert_template_part( string $slug, string $title, string $body, string $area ): int {
	$query = new WP_Query(
		array(
			'post_type'      => 'wp_template_part',
			'post_status'    => 'any',
			'posts_per_page' => 2,
			'meta_key'       => VICU_DEMO_MARKER,
			'meta_value'     => 'template-part-' . $slug,
			'fields'         => 'ids',
		)
	);
	if ( 1 < count( $query->posts ) ) {
		WP_CLI::error( 'Hay template parts duplicados para ' . $slug . '.' );
	}
	$data = array(
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => $body,
		'post_type'    => 'wp_template_part',
		'post_status'  => 'publish',
	);
	if ( $query->posts ) {
		$data['ID'] = (int) $query->posts[0];
		$id         = wp_update_post( wp_slash( $data ), true );
	} else {
		$data['meta_input'] = array(
			'origin'         => 'theme',
			VICU_DEMO_MARKER => 'template-part-' . $slug,
		);
		$id                 = wp_insert_post( wp_slash( $data ), true );
	}
	$id = (int) vicu_demo_result( $id, 'Guardar template part ' . $slug );
	vicu_demo_result( wp_set_object_terms( $id, get_stylesheet(), 'wp_theme' ), 'Asignar theme al template part ' . $slug );
	vicu_demo_result( wp_set_object_terms( $id, $area, 'wp_template_part_area' ), 'Asignar área al template part ' . $slug );
	update_post_meta( $id, VICU_DEMO_MARKER, 'template-part-' . $slug );
	update_post_meta( $id, 'origin', 'theme' );

	return $id;
}

$media_ids = vicu_demo_import_media( $media, $repo_root );
$map       = vicu_demo_map();

vicu_demo_result( ManualPaymentProvider::configure( array( 'enabled' => true ) ), 'Configurar proveedor manual' );
update_option(
	'vicu_core_settings',
	Settings::sanitize_settings(
		array(
			'phone'          => $content['brand']['phone'],
			'address'        => $content['brand']['address'],
			'business_hours' => 'Martes a domingo: almuerzo y cena. Lunes cerrado.',
		)
	),
	false
);
update_option(
	RestaurantSettings::OPTION_NAME,
	RestaurantSettings::sanitize(
		array(
			'currency'                    => 'USD',
			'tax_rate_bps'                => (int) round( $content['operations']['tax_rate'] * 10000 ),
			'tip_rates_bps'               => array_map( static fn ( $value ) => (int) $value * 100, $content['operations']['tip_options_percent'] ),
			'cart_lifetime_hours'         => 72,
			'payment_lifetime_minutes'    => 30,
			'manual_payment_instructions' => 'Demostración: adjunta una evidencia ficticia. No realices transferencias ni compartas datos financieros reales.',
		)
	),
	false
);

$days     = array(
	0 => 'sunday',
	1 => 'monday',
	2 => 'tuesday',
	3 => 'wednesday',
	4 => 'thursday',
	5 => 'friday',
	6 => 'saturday',
);
$schedule = array_fill_keys( array_values( $days ), array() );
foreach ( $content['operations']['opening_hours'] as $day ) {
	foreach ( $day['periods'] as $period ) {
		$schedule[ $days[ $day['dayOfWeek'] ] ][] = array(
			'opens_at'  => $period['opensAt'],
			'closes_at' => $period['closesAt'],
		);
	}
}
update_option(
	ReservationSettings::OPTION_NAME,
	ReservationSettings::sanitize(
		array(
			'timezone'              => $content['operations']['timezone'],
			'weekly_schedule'       => $schedule,
			'exceptions'            => array(),
			'recurring_closures'    => array_map( static fn ( $date ) => substr( $date, 2 ), $content['operations']['blocked_dates'] ),
			'interval_minutes'      => $content['operations']['slot_interval_minutes'],
			'duration_minutes'      => $content['operations']['reservation_duration_minutes'],
			'capacity'              => $content['operations']['capacity_per_slot'],
			'min_party_size'        => $content['operations']['min_party_size'],
			'max_party_size'        => $content['operations']['max_party_size'],
			'min_notice_minutes'    => $content['operations']['min_booking_notice_hours'] * 60,
			'limited_threshold_bps' => 2500,
			'auto_confirm'          => false,
		)
	),
	false
);

$option_order = 0;
foreach ( array(
	'sizes'  => 'size',
	'crusts' => 'crust',
	'sauces' => 'sauce',
) as $source => $type ) {
	foreach ( $content['pizza'][ $source ] as $option ) {
		++$option_order;
		$price = 'sizes' === $source ? $option['priceUsd'] : ( $option['priceModUsd'] ?? 0 );
		vicu_demo_upsert_entity(
			'pizza-option:' . $option['id'],
			PizzaOptionService::class,
			array(
				'type'                 => $type,
				'name'                 => $option['label'],
				'price_modifier_minor' => vicu_demo_money( $price ),
				'available'            => true,
				'display_order'        => $option_order,
			),
			$map
		);
	}
}

foreach ( array_merge( $content['pizza']['cheeses'], $content['pizza']['toppings'] ) as $ingredient ) {
	vicu_demo_upsert_entity(
		'ingredient:' . $ingredient['id'],
		IngredientService::class,
		array(
			'name'                 => $ingredient['name'],
			'category'             => 'cheese' === $ingredient['category'] ? 'cheese' : 'topping',
			'price_modifier_minor' => vicu_demo_money( $ingredient['priceModUsd'] ),
			'available'            => $ingredient['available'],
			'allergens'            => vicu_demo_allergens( $ingredient['allergens'] ),
			'dietary_tags'         => vicu_demo_dietary( $ingredient['dietaryTags'] ),
		),
		$map
	);
}

foreach ( $content['operations']['delivery_zones'] as $order => $zone ) {
	preg_match( '/^(\d+)-(\d+) min$/', $zone['etaLabel'], $eta );
	vicu_demo_upsert_entity(
		'delivery-zone:' . $zone['id'],
		DeliveryZoneService::class,
		array(
			'name'            => $zone['label'],
			'active'          => true,
			'fee_minor'       => vicu_demo_money( $zone['feeUsd'] ),
			'eta_min_minutes' => (int) $eta[1],
			'eta_max_minutes' => (int) $eta[2],
			'display_order'   => $order + 1,
		),
		$map
	);
}

foreach ( $content['operations']['promo_codes'] as $promo ) {
	$desired = array(
		'code'                   => $promo['code'],
		'type'                   => $promo['type'],
		'value'                  => 'percent' === $promo['type'] ? (int) $promo['value'] * 100 : vicu_demo_money( $promo['value'] ),
		'active'                 => true,
		'valid_from'             => null,
		'valid_until'            => null,
		'minimum_subtotal_minor' => 0,
		'max_uses'               => null,
	);
	vicu_demo_upsert_entity( 'discount:' . $promo['code'], DiscountService::class, $desired, $map );
}
vicu_demo_save_map( $map );

$category_ids = array();
foreach ( $content['menu']['categories'] as $order => $category ) {
	$term = term_exists( $category['id'], MenuCategory::TAXONOMY );
	if ( ! $term ) {
		$term = vicu_demo_result( wp_insert_term( $category['label'], MenuCategory::TAXONOMY, array( 'slug' => $category['id'] ) ), 'Crear categoría ' . $category['id'] );
	} else {
		$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
		vicu_demo_result( wp_update_term( $term_id, MenuCategory::TAXONOMY, array( 'name' => $category['label'] ) ), 'Actualizar categoría ' . $category['id'] );
	}
	$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	update_term_meta( $term_id, MenuCategory::META_ORDER, $order + 1 );
	update_term_meta( $term_id, MenuCategory::META_VISIBLE, true );
	$category_ids[ $category['id'] ] = $term_id;
}

foreach ( $content['menu']['items'] as $item ) {
	$item_id = vicu_demo_upsert_post(
		MenuItemPostType::POST_TYPE,
		'menu-item:' . $item['id'],
		array(
			'post_title'   => $item['name'],
			'post_name'    => 'bonasera-' . $item['id'],
			'post_excerpt' => $item['description'],
			'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $item['story'] ) . '</p><!-- /wp:paragraph -->',
		)
	);
	wp_set_object_terms( $item_id, array( $category_ids[ $item['categoryId'] ] ), MenuCategory::TAXONOMY );
	if ( '' === (string) get_post_meta( $item_id, MenuMeta::PUBLIC_ID, true ) ) {
		update_post_meta( $item_id, MenuMeta::PUBLIC_ID, wp_generate_uuid4() );
	}
	$dietary = array();
	if ( $item['vegetarian'] ) {
		$dietary[] = 'vegetarian';
	}
	if ( $item['spicy'] ) {
		$dietary[] = 'spicy';
	}
	update_post_meta( $item_id, MenuMeta::PRICE_MINOR, vicu_demo_money( $item['priceUsd'] ) );
	update_post_meta( $item_id, MenuMeta::CURRENCY, 'USD' );
	update_post_meta( $item_id, MenuMeta::AVAILABLE, $item['available'] );
	update_post_meta( $item_id, MenuMeta::CALORIES_KCAL, $item['kcal'] );
	update_post_meta( $item_id, MenuMeta::ALLERGENS, vicu_demo_allergens( $item['allergens'] ) );
	update_post_meta( $item_id, MenuMeta::DIETARY_TAGS, $dietary );
	set_post_thumbnail( $item_id, $media_ids[ $item['categoryId'] ] );
}

foreach ( $content['faqs'] as $order => $faq ) {
	vicu_demo_upsert_post(
		'vicu_faq',
		'faq:' . ( $order + 1 ),
		array(
			'post_title'   => $faq['question'],
			'post_name'    => 'bonasera-faq-' . ( $order + 1 ),
			'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $faq['answer'] ) . '</p><!-- /wp:paragraph -->',
			'menu_order'   => $order + 1,
		)
	);
}
foreach ( $content['testimonials']['items'] as $order => $testimonial ) {
	vicu_demo_upsert_post(
		'vicu_testimonial',
		'testimonial:' . $testimonial['id'],
		array(
			'post_title'   => $testimonial['name'],
			'post_name'    => 'bonasera-' . $testimonial['id'],
			'post_excerpt' => $testimonial['type'] . ' · ' . $testimonial['stars'],
			'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $testimonial['quote'] ) . '</p><!-- /wp:paragraph -->',
			'menu_order'   => $order + 1,
		)
	);
}

$page_blocks = array(
	'menu'       => array( 'vicunav/restaurante-menu', null ),
	'pizzas'     => array( 'vicunav/restaurante-pizza-builder', $media_ids['pizze'] ),
	'carrito'    => array( 'vicunav/restaurante-cart', null ),
	'checkout'   => array( 'vicunav/restaurante-checkout', null ),
	'pedido'     => array( 'vicunav/restaurante-order-status', null ),
	'reservas'   => array( 'vicunav/restaurante-reservations', $media_ids['reservas'] ),
	'mis-pizzas' => array( 'vicunav/restaurante-saved-pizzas', null ),
);
$page_ids    = array();
foreach ( $content['pages'] as $page ) {
	$slug = trim( $page['path'], '/' );
	if ( 'inicio' === $page['id'] ) {
		$page_content = vicu_demo_front_content( $content, $media_ids );
		$slug         = 'inicio';
	} else {
		$page_content = vicu_demo_page_content( $page['lead'], $page_blocks[ $page['id'] ][0], $page_blocks[ $page['id'] ][1] );
	}
	$page_ids[ $page['id'] ] = vicu_demo_upsert_post(
		'page',
		$page['id'],
		array(
			'post_title'   => $page['h1'],
			'post_name'    => $slug,
			'post_content' => $page_content,
		)
	);
}
$page_ids['privacidad'] = vicu_demo_upsert_post(
	'page',
	'privacidad',
	array(
		'post_title'   => 'Privacidad',
		'post_name'    => 'privacidad',
		'post_content' => '<!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">Este sitio es una demostración ficticia. No introduzcas datos personales, financieros ni comprobantes reales.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Datos del flujo</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Las pruebas locales de carrito, pedidos, pagos manuales y reservas se conservan únicamente en esta instalación de desarrollo. Su eliminación corresponde a quien administra el entorno.</p><!-- /wp:paragraph -->',
	)
);

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_ids['inicio'] );
update_option( 'blogname', 'Bonasera' );
update_option( 'blogdescription', 'Trattoria italiana familiar' );

$header_inner_path = get_theme_file_path( 'parts/header-restaurant-inner.html' );
$footer_full_path  = get_theme_file_path( 'parts/footer-restaurant-full.html' );
if ( ! is_file( $header_inner_path ) || ! is_file( $footer_full_path ) ) {
	WP_CLI::error( 'Faltan los template parts contractuales del restaurante.' );
}
$header_inner = str_replace( array( 'href="/contacto/"', '>Contacto</a' ), array( 'href="/carrito/"', '>Ver carrito</a' ), (string) file_get_contents( $header_inner_path ) );
$footer_full  = str_replace(
	array( 'Descripción editable del restaurante.', 'Dirección editable del restaurante.', 'Horario editable del restaurante.', '© Sitio. Todos los derechos reservados.' ),
	array( $content['brand']['descriptor'] . ' en ' . $content['brand']['location_label'] . '.', $content['brand']['address'], 'Martes a domingo: almuerzo y cena. Lunes cerrado.', '© Bonasera. Sitio demostrativo ficticio.' ),
	(string) file_get_contents( $footer_full_path )
);
vicu_demo_upsert_template_part( 'header-restaurant-inner', 'Cabecera interna Bonasera', $header_inner, 'header' );
vicu_demo_upsert_template_part( 'footer-restaurant-full', 'Pie completo Bonasera', $footer_full, 'footer' );

$front_template = '<!-- wp:template-part {"slug":"header-restaurant-home","theme":"vicunav-theme-core","area":"header"} /--><!-- wp:post-content {"layout":{"type":"constrained"}} /--><!-- wp:template-part {"slug":"footer-restaurant-full","theme":"vicunav-theme-core","area":"footer"} /-->';
$page_template  = '<!-- wp:template-part {"slug":"header-restaurant-inner","theme":"vicunav-theme-core","area":"header"} /--><!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} --><main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60)"><!-- wp:post-title {"level":1} /--><!-- wp:post-content {"layout":{"type":"constrained"}} /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer-restaurant-minimal","theme":"vicunav-theme-core","area":"footer"} /-->';
vicu_demo_upsert_template( 'front-page', 'Portada Bonasera', $front_template );
vicu_demo_upsert_template( 'page', 'Página Bonasera', $page_template );

$style_path = get_theme_file_path( 'styles/bonasera.json' );
if ( ! is_file( $style_path ) ) {
	WP_CLI::error( 'Falta la variación Bonasera del theme.' );
}
$style_data = json_decode( (string) file_get_contents( $style_path ), true );
$styles     = get_posts(
	array(
		'post_type'   => 'wp_global_styles',
		'post_status' => array( 'publish', 'draft' ),
		'numberposts' => 1,
		'name'        => 'wp-global-styles-' . get_stylesheet(),
	)
);
$style_post = array(
	'post_type'    => 'wp_global_styles',
	'post_status'  => 'publish',
	'post_name'    => 'wp-global-styles-' . get_stylesheet(),
	'post_title'   => 'Bonasera',
	'post_content' => wp_json_encode( $style_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
);
if ( $styles ) {
	$style_post['ID'] = $styles[0]->ID;
}
vicu_demo_result( wp_insert_post( wp_slash( $style_post ), true ), 'Seleccionar variación Bonasera' );

flush_rewrite_rules( false );
WP_CLI::success( 'Contenido Bonasera aplicado en ' . home_url( '/' ) );
