#!/usr/bin/env node

import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { pathToFileURL } from 'node:url';
import path from 'node:path';

const EXPECTED_COMMIT = '1e1f62787e088c0ca9701500e764802499d1b253';
const sourceArgument = process.argv.find((argument) => argument.startsWith('--source='));

if (!sourceArgument) {
	throw new Error('Falta --source con el repositorio auditado.');
}

const sourceRoot = path.resolve(sourceArgument.slice('--source='.length));
const repoRoot = path.resolve(import.meta.dirname, '..');
const actualCommit = execFileSync('git', ['-C', sourceRoot, 'rev-parse', 'HEAD'], {
	encoding: 'utf8',
}).trim();

if (actualCommit !== EXPECTED_COMMIT) {
	throw new Error(`La fuente está en ${actualCommit}, no en ${EXPECTED_COMMIT}.`);
}

if (execFileSync('git', ['-C', sourceRoot, 'status', '--porcelain'], { encoding: 'utf8' }).trim()) {
	throw new Error('La fuente auditada tiene cambios locales.');
}

const importData = async (name) => import(pathToFileURL(path.join(sourceRoot, 'src', 'data', `${name}.js`)));
const [categoriesModule, contentModule, ingredientsModule, menuModule, pizzaModule, restaurantModule] =
	await Promise.all(
		['categories', 'content', 'ingredients', 'menu', 'pizzaCatalog', 'restaurant'].map(importData)
	);

const normalize = (value) => {
	if (typeof value === 'string') {
		return value.replaceAll('\u2014', ' - ');
	}
	if (Array.isArray(value)) {
		return value.map(normalize);
	}
	if (value && typeof value === 'object') {
		return Object.fromEntries(Object.entries(value).map(([key, entry]) => [key, normalize(entry)]));
	}
	return value;
};

const faqs = contentModule.FAQS.map((faq) => ({ question: faq.q, answer: faq.a }));
faqs[0].answer =
	'Sí, cubrimos las zonas publicadas en el sitio. Selecciona tu zona en el carrito para confirmar disponibilidad, tarifa y tiempo estimado.';
faqs[1].answer =
	'Entregamos en Maracaibo (Casco Central), La Lago / Bella Vista, San Francisco, Cabimas, Ciudad Ojeda y Santa Bárbara del Zulia. El carrito confirma si el pedido está dentro de cobertura.';
faqs[2].answer =
	'El costo y el tiempo estimado varían según tu zona. El carrito muestra ambos valores antes del checkout; el tiempo estimado está entre 20 y 65 minutos.';
faqs[3] = {
	question: '¿Cómo funciona el pago?',
	answer:
		'El checkout crea una solicitud en el proveedor manual de Vicunav Pagos. Sigue las instrucciones y adjunta la evidencia cuando corresponda; el pedido refleja después el estado público del pago.',
};
faqs[7].answer =
	'Las reservas se gestionan desde el flujo del sitio. Esta demostración no publica un canal real para cambios o cancelaciones posteriores.';

const testimonials = contentModule.TESTIMONIOS.map(({ id, imgSrc: _imgSrc, ...testimonial }) => ({
	...testimonial,
	id,
}));
testimonials[0].quote =
	'Pedí la pizza Diavola desde la web y en 30 minutos ya estaba en mi puerta. El envío del comprobante fue muy fácil.';

const sourceRestaurant = restaurantModule.RESTAURANT;
const content = normalize({
	schema_version: 1,
	source: {
		repository: 'vicunav-design-to-claude-demo-restaurante',
		commit: EXPECTED_COMMIT,
	},
	classification: {
		brand: 'fictitious-demo',
		people_and_testimonials: 'fictitious-demo',
		contact_details: 'non-routable-demo',
		operational_values: 'seed-data-not-production',
	},
	adjustments: [
		'Reemplaza los cuatro métodos teatrales por el proveedor manual real de Vicunav Pagos.',
		'Retira retratos de testimonios para no sugerir respaldo de personas reales.',
		'Reemplaza teléfono, correo y dirección exacta no verificados por datos demostrativos.',
		'Ajusta las preguntas frecuentes para no prometer mapas, WhatsApp ni atención operativa ausentes.',
		'Normaliza la raya tipográfica larga según los estándares del ecosistema.',
	],
	brand: {
		name: 'Bonasera',
		descriptor: 'Trattoria italiana familiar',
		location_label: 'Maracaibo, Zulia',
		address: 'Ubicación demostrativa en Maracaibo, Zulia',
		phone: '+58 000-0000000',
		email: 'hola@example.invalid',
	},
	navigation: [
		{ label: 'Inicio', path: '/' },
		{ label: 'Menú', path: '/menu/' },
		{ label: 'Crea tu pizza', path: '/pizzas/' },
		{ label: 'Reservar', path: '/reservas/' },
		{ label: 'Mis pizzas', path: '/mis-pizzas/' },
	],
	pages: [
		{
			id: 'inicio',
			path: '/',
			// h1, ghostHeading y lead se copian literales de HomeScreen.js (el
			// componente real del hero), no del resumen editorial de esta lista:
			// ese resumen nunca estuvo sincronizado con el copy que de verdad
			// se renderiza, y quedó demostrado comparando contra Claude Design.
			h1: 'Cucina italiana, tradición viva.',
			ghostHeading: 'Cucina italiana',
			lead:
				'Trattoria familiar en Maracaibo: pastas, pizzas al horno de piedra y recetas de la Nonna, listas para pedir en segundos desde tu teléfono y recibir calientitas en tu puerta, o retirar tú mismo en el local.',
			sections: [
				'Platillos destacados',
				'Nuestro menú',
				'Nuestra historia',
				'Dónde estamos ubicados',
				'Lo que dicen nuestros clientes',
				'Preguntas frecuentes',
				'Contáctanos',
			],
		},
		{
			id: 'menu',
			path: '/menu/',
			h1: 'Nuestro menú',
			lead: 'Antipasti, pizze al horno de piedra, pasta fresca y postres caseros.',
		},
		{
			id: 'pizzas',
			path: '/pizzas/',
			// H1 y lead copiados literales del HomeScreen real ("Nuestras
			// pizzas"), no del resumen editorial: la página real tiene una
			// vitrina de pizzas predefinidas antes del constructor, y el
			// título anterior ("Crea tu pizza") describía solo esa segunda
			// sección, no la página completa.
			h1: 'Nuestras pizzas',
			lead:
				'Pizzas napolitanas al horno de piedra en Maracaibo, o armadas a tu gusto con nuestro creador de pizzas.',
			sections: [ 'Directo del horno de piedra, listas para pedir.', 'Crea tu pizza' ],
		},
		{
			id: 'carrito',
			path: '/carrito/',
			h1: 'Tu pedido',
			lead: 'Revisa productos, cantidades y totales antes de continuar.',
		},
		{
			id: 'checkout',
			path: '/checkout/',
			h1: 'Confirmar pedido',
			lead: 'Completa los datos de entrega o retiro y continúa con el pago manual.',
		},
		{
			id: 'pedido',
			path: '/pedido/',
			h1: 'Estado del pedido',
			lead: 'Consulta el estado real del pedido y de su solicitud de pago.',
		},
		{
			id: 'reservas',
			path: '/reservas/',
			h1: 'Reservar mesa',
			lead: 'Reserva una mesa según horarios, capacidad y disponibilidad reales.',
		},
		{
			id: 'mis-pizzas',
			path: '/mis-pizzas/',
			h1: 'Mis pizzas',
			lead: 'Tus creaciones guardadas, listas para revisar o pedir de nuevo.',
		},
	],
	editorial: {
		// Copiado literal de HomeScreen.js (sección "Nuestra historia"), no
		// parafraseado: es la misma fuente que definimos como contrato para el
		// resto de la portada.
		story: [
			'Bonasera nació en la cocina de la familia Ferraro, italianos que llegaron a Maracaibo en los años 60 y nunca dejaron de cocinar como en Nápoles. Los domingos, toda la cuadra se enteraba de que había lasagna en el horno.',
			'El nombre viene de "buonasera", el saludo con el que el abuelo Vittorio recibía a cada cliente en la puerta, sin excepción. Seguimos recibiendo así: sin apuro, con la mesa lista y la masa recién amasada.',
			'Hoy seguimos siendo una trattoria familiar, con recetas que pasan de generación en generación. Lo único que cambió es que ahora puedes pedirlo desde tu teléfono.',
		],
		contact_heading: 'Contáctanos',
		contact_copy:
			'Para pedidos y reservas utiliza los flujos del sitio. Los datos de contacto visibles son demostrativos y no reciben mensajes reales.',
	},
	faqs,
	testimonials: {
		disclaimer: 'Nombres, contextos y testimonios ficticios creados para esta demostración.',
		items: testimonials,
	},
	menu: {
		categories: categoriesModule.CATEGORIES,
		items: menuModule.MENU_ITEMS,
		featured_item_ids: menuModule.FEATURED_ITEM_IDS,
	},
	pizza: {
		max_toppings: pizzaModule.MAX_TOPPINGS,
		default_size_id: pizzaModule.DEFAULT_PIZZA_SIZE_ID,
		default_crust_id: pizzaModule.DEFAULT_CRUST_ID,
		default_sauce_id: pizzaModule.DEFAULT_SAUCE_ID,
		default_cheese_id: pizzaModule.DEFAULT_CHEESE_ID,
		sizes: pizzaModule.PIZZA_SIZES,
		crusts: pizzaModule.CRUSTS,
		sauces: pizzaModule.SAUCES,
		cheeses: pizzaModule.CHEESES,
		toppings: pizzaModule.TOPPINGS,
		ingredients: ingredientsModule.INGREDIENTS,
	},
	operations: {
		timezone: 'America/Caracas',
		opening_hours: sourceRestaurant.openingHours,
		special_hours: sourceRestaurant.specialHours,
		blocked_dates: sourceRestaurant.blockedDates,
		capacity_per_slot: sourceRestaurant.capacityPerSlot,
		max_party_size: sourceRestaurant.maxPartySize,
		min_party_size: sourceRestaurant.minPartySize,
		reservation_duration_minutes: sourceRestaurant.reservationDurationMinutes,
		slot_interval_minutes: sourceRestaurant.slotIntervalMinutes,
		min_booking_notice_hours: sourceRestaurant.minBookingNoticeHours,
		delivery_zones: sourceRestaurant.deliveryZones,
		tax_rate: sourceRestaurant.taxRate,
		tip_options_percent: restaurantModule.TIP_OPTIONS_PERCENT,
		promo_codes: restaurantModule.PROMO_CODES,
	},
	payment: {
		provider: 'manual',
		owner: 'vicunav-pagos',
		external_type: 'vicu_order',
		copy:
			'El pago se gestiona mediante una solicitud manual vinculada al pedido. El total siempre lo calcula Vicunav Restaurante en el servidor.',
	},
});

const destination = path.join(repoRoot, 'content', 'bonasera.json');
mkdirSync(path.dirname(destination), { recursive: true });
writeFileSync(destination, `${JSON.stringify(content, null, 2)}\n`, 'utf8');
console.log(`Contenido generado: ${destination}`);
