import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const apply = fs.readFileSync(path.join(root, 'bin/apply-content.php'), 'utf8');
const content = JSON.parse(fs.readFileSync(path.join(root, 'content/bonasera.json'), 'utf8'));

const expectedPages = ['inicio', 'menu', 'pizzas', 'carrito', 'checkout', 'pedido', 'reservas', 'mis-pizzas'];
const expectedBlocks = [
  'vicunav/restaurante-menu',
  'vicunav/restaurante-pizza-builder',
  'vicunav/restaurante-cart',
  'vicunav/restaurante-checkout',
  'vicunav/restaurante-order-status',
  'vicunav/restaurante-reservations',
  'vicunav/restaurante-saved-pizzas',
];

assert.deepEqual(content.pages.map(({ id }) => id), expectedPages);
for (const block of expectedBlocks) {
  assert.ok(apply.includes(block), `Falta el bloque ${block}`);
}

assert.match(apply, /ManualPaymentProvider::configure/);
assert.doesNotMatch(apply, /wpdb->(?:insert|update|delete|query)/);
assert.doesNotMatch(apply, /woocommerce|tarjeta|pago móvil|zelle/i);
assert.match(apply, /VICU_DEMO_ENTITY_MAP/);
assert.match(apply, /VICU_DEMO_ASSET_SHA256/);
assert.match(apply, /header-restaurant-home/);
assert.match(apply, /footer-restaurant-full/);
assert.match(apply, /contact-info/);
assert.match(apply, /post_name'\s*=>\s*'privacidad'/);
assert.match(apply, /Ver carrito/);
assert.match(apply, /styles\/bonasera\.json/);
assert.match(apply, /wp:heading \{\"textAlign\":\"center\",\"level\":1/);
assert.equal((apply.match(/<h1/g) ?? []).length, 1, 'La portada debe declarar un solo H1 en su contenido');

console.log('Composición DEMO-REST-01C válida.');
