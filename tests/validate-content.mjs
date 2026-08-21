import { createHash } from 'node:crypto';
import { readdirSync, readFileSync, statSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(import.meta.dirname, '..');
const readJson = (relativePath) => JSON.parse(readFileSync(path.join(repoRoot, relativePath), 'utf8'));
const assert = (condition, message) => {
	if (!condition) {
		throw new Error(message);
	}
};

const contentPath = path.join(repoRoot, 'content', 'bonasera.json');
const contentRaw = readFileSync(contentPath, 'utf8');
const content = JSON.parse(contentRaw);
const media = readJson('config/media.json');

assert(content.schema_version === 1, 'El contenido no usa el schema 1.');
assert(
	content.source.commit === '1e1f62787e088c0ca9701500e764802499d1b253',
	'El contenido perdió la revisión auditada.'
);
assert(content.pages.length === 8, 'El inventario debe declarar ocho rutas reales.');
assert(new Set(content.pages.map(({ path: pagePath }) => pagePath)).size === 8, 'Hay rutas duplicadas.');
assert(content.pages.every(({ h1 }) => typeof h1 === 'string' && h1.trim()), 'Cada ruta necesita un H1.');
assert(content.menu.categories.length === 8, 'El catálogo debe conservar ocho categorías.');
assert(content.menu.items.length === 37, 'El catálogo debe conservar 37 platos.');
assert(content.faqs.length === 8, 'Deben existir ocho FAQ.');
assert(content.testimonials.items.length === 3, 'Deben existir tres testimonios ficticios.');
assert(content.payment.provider === 'manual', 'El demo debe usar el proveedor manual.');
assert(content.brand.email.endsWith('.invalid'), 'El correo demostrativo debe ser no enrutable.');
assert(!contentRaw.includes('\u2014'), 'El contenido contiene una raya tipográfica larga.');
assert(
	!/(Pago Móvil|Zelle|transferencia bancaria|efectivo)/i.test(contentRaw),
	'El contenido conserva métodos de pago teatrales.'
);
assert(!/https?:\/\//i.test(contentRaw), 'El contenido consumible contiene un hotlink.');

assert(media.schema_version === 1, 'El inventario de media no usa el schema 1.');
assert(media.assets.length === 9, 'El inventario debe contener nueve imágenes aprobadas.');
assert(Object.keys(media.licenses).sort().join(',') === 'pexels,unsplash', 'Licencias inesperadas.');

const expectedPaths = new Set();
for (const asset of media.assets) {
	assert(!asset.path.startsWith('/') && !asset.path.includes('://'), `Ruta no local: ${asset.id}`);
	assert(asset.alt?.trim(), `Falta texto alternativo: ${asset.id}`);
	assert(asset.creator?.trim(), `Falta autor: ${asset.id}`);
	assert(asset.source_url?.startsWith('https://'), `Falta fuente HTTPS: ${asset.id}`);
	assert(media.licenses[asset.license], `Licencia desconocida: ${asset.id}`);
	assert(!expectedPaths.has(asset.path), `Ruta duplicada: ${asset.path}`);
	expectedPaths.add(asset.path);

	const absolutePath = path.join(repoRoot, asset.path);
	const bytes = readFileSync(absolutePath);
	const dimensions = readWebpDimensions(bytes);
	const digest = createHash('sha256').update(bytes).digest('hex');

	assert(statSync(absolutePath).size === asset.bytes, `Peso inesperado: ${asset.id}`);
	assert(asset.bytes <= 200_000, `La imagen supera 200 KB: ${asset.id}`);
	assert(dimensions.width === asset.width, `Ancho inesperado: ${asset.id}`);
	assert(dimensions.height === asset.height, `Alto inesperado: ${asset.id}`);
	assert(digest === asset.sha256, `Checksum inesperado: ${asset.id}`);
}

const actualPaths = readdirSync(path.join(repoRoot, 'assets', 'images'))
	.filter((filename) => filename.endsWith('.webp'))
	.map((filename) => `assets/images/${filename}`);
assert(actualPaths.length === expectedPaths.size, 'Hay imágenes sin inventariar.');
assert(actualPaths.every((assetPath) => expectedPaths.has(assetPath)), 'Hay imágenes ajenas al inventario.');

assert(media.missing.length === 3, 'Deben registrarse tres activos no entregados.');
assert(media.missing.every(({ status }) => status === 'not-delivered'), 'Una ausencia tiene estado inválido.');
assert(
	media.missing.map(({ id }) => id).sort().join(',') === 'hero-video,map-maracaibo,map-zulia',
	'El inventario de ausencias cambió.'
);
assert(media.excluded.some(({ source }) => source.includes('AVATAR_MAP')), 'Falta excluir los retratos.');

console.log('Contenido y media Bonasera validados.');

function readWebpDimensions(buffer) {
	assert(buffer.toString('ascii', 0, 4) === 'RIFF', 'La imagen no es RIFF.');
	assert(buffer.toString('ascii', 8, 12) === 'WEBP', 'La imagen no es WebP.');

	let offset = 12;
	while (offset + 8 <= buffer.length) {
		const chunk = buffer.toString('ascii', offset, offset + 4);
		const size = buffer.readUInt32LE(offset + 4);
		const data = offset + 8;

		if (chunk === 'VP8X') {
			return {
				width: 1 + buffer.readUIntLE(data + 4, 3),
				height: 1 + buffer.readUIntLE(data + 7, 3),
			};
		}
		if (chunk === 'VP8 ') {
			assert(buffer.readUIntLE(data + 3, 3) === 0x2a019d, 'Header VP8 inválido.');
			return {
				width: buffer.readUInt16LE(data + 6) & 0x3fff,
				height: buffer.readUInt16LE(data + 8) & 0x3fff,
			};
		}
		if (chunk === 'VP8L') {
			assert(buffer[data] === 0x2f, 'Header VP8L inválido.');
			const bits = buffer.readUInt32LE(data + 1);
			return {
				width: 1 + (bits & 0x3fff),
				height: 1 + ((bits >>> 14) & 0x3fff),
			};
		}

		offset = data + size + (size % 2);
	}

	throw new Error('No se encontraron dimensiones WebP.');
}
