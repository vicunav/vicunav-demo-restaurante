# Gate final Bonasera

## Alcance y resultado

DEMO-REST-01D compara el prototipo auditado en
`1e1f62787e088c0ca9701500e764802499d1b253` con la composición FSE iniciada en
`bdc0a1536c8cd7f80a85a1084dfa6c7194c57580`. El gate final pasa en WordPress 7.1 y
PHP 8.2.29.

Dependencias verificadas:

| Paquete | Commit |
| --- | --- |
| `vicunav-plugin-core` | `12870b0d5e297d715c985037e76898067a749909` |
| `vicunav-pagos` | `16280c3bd74977ac025f0085ccdf22ae5b995277` |
| `vicunav-restaurante` | `f14ee43be4f9e6757f572ecc93d87487073f8666` |
| `vicunav-theme-core` | `4a5eeb5741fea50f9d2e6d7ae09346ae2b7afe89` |

La comparación busca equivalencia de intención, jerarquía y función. No declara
paridad de activos: el video hero y los mapas de Zulia y Maracaibo no fueron
entregados. La portada usa una imagen local licenciada y las zonas se presentan como
datos operativos, sin inventar cartografía.

## Gate reproducible

Con el sitio iniciado desde LocalWP y sus symlinks en los commits fijados:

```bash
VICUNAV_PHP_BIN="/path/to/local/php" \
VICUNAV_MYSQL_SOCKET="/path/to/mysqld.sock" \
bash bin/install-local.sh \
  --wp-path="/path/to/site/app/public" \
  --site-url="https://example.local" \
  --dry-run

VICUNAV_PHP_BIN="/path/to/local/php" \
VICUNAV_MYSQL_SOCKET="/path/to/mysqld.sock" \
"/path/to/local/php" \
  -d mysqli.default_socket="/path/to/mysqld.sock" \
  -d pdo_mysql.default_socket="/path/to/mysqld.sock" \
  /path/to/wp --path="/path/to/site/app/public" \
  eval-file tests/qa-runtime.php
```

El instalador en modo `--dry-run` confirmó los cuatro symlinks, paquetes activos y
commits exactos sin cambiar el sitio. La suite de runtime es de solo lectura y pasó
sin fallos: nueve rutas 200, un H1 por ruta, cero hotlinks, siete bloques del vertical,
37 productos, ocho categorías, tres entidades compartidas, 35 entidades operativas y
nueve páginas.

## Matriz visual

| Ancho | Portada | Nueve rutas | Header y overflow | Controles de comercio | Estado |
| ---: | --- | --- | --- | --- | --- |
| 1440 px | Pasa | Pasa | Pasa | Pasa | Aprobado |
| 1024 px | Pasa | Pasa | Pasa | Pasa | Aprobado |
| 768 px | Pasa | Pasa | Pasa | Pasa | Aprobado |
| 390 px | Pasa | Pasa | Pasa | Pasa | Aprobado |
| 375 px | Pasa | Pasa | Pasa | Pasa | Aprobado |

Las 45 combinaciones pasan con un `main` y un H1, cero overflow horizontal, cero
imágenes rotas y cero controles de formulario sin etiqueta. La consola pública quedó
limpia después de recorrer las nueve rutas.

Evidencia visual versionada:

- [Portada en 1440 px](evidence/bonasera-home-1440.png)
- [Portada en 390 px](evidence/bonasera-home-390.png)
- [Disponibilidad de reservas en 390 px](evidence/bonasera-reservas-390.png)

## Flujos funcionales

- Menú: búsqueda de `Diavola` devuelve un resultado; la categoría Pizze devuelve
  nueve y su combinación con Vegetariano devuelve cinco.
- Pizza y carrito: una pizza mediana con champiñones se confirmó en `$10.75`; el
  carrito calculó `$11.61` para retiro y `$13.11` con delivery de `$1.50`.
- Checkout: resumen, proveedor manual y campos condicionales respondieron sin enviar
  datos personales ni crear un pedido. Inputs, selects y textareas ocupan el ancho
  disponible desde 375 px.
- Reservas: el horario semanal devolvió nueve slots para dos personas el
  2026-09-01; el formulario de confirmación quedó disponible sin crear una reserva.
- Pedido y pizzas guardadas: sus estados públicos sin credencial son coherentes y no
  exponen información privada.

## FSE, accesibilidad y responsive

El Site Editor se abrió con un usuario local temporal, eliminado inmediatamente tras
la prueba. La plantilla Bonasera cargó con estado `Saved`, sin bloques inválidos ni
errores de consola. Los templates y template parts siguen almacenados como entidades
editables de WordPress.

La revisión manual confirmó:

- skip link visible al recibir foco y landmarks únicos;
- foco de 3 px con offset de 3 px en búsqueda, filtros y acciones;
- targets táctiles de 44 px o superficies de etiqueta mayores;
- selector de zona de 390 x 44 px en móvil;
- acordeón FAQ cerrado al iniciar, respuesta visible sin JavaScript y estados
  `aria-expanded`, `aria-controls`, `aria-label` y `hidden` sincronizados;
- menú móvil, sticky UI y formularios sin contenido recortado.

## Rendimiento, media y límites

Los HTML medidos están entre 66 381 y 98 354 bytes. Las nueve imágenes WebP locales
suman 916 450 bytes; la mayor pesa 169 986 bytes, por debajo del presupuesto de
200 000 bytes por imagen. No existen imágenes remotas ni assets sin atribución en el
inventario versionado.

Límites explícitos del demo:

- no incluye el video hero ni los dos mapas ausentes del handoff;
- no presenta los cuatro métodos de pago teatrales del prototipo;
- teléfono, correo, dirección, personas y testimonios son demostrativos;
- no se realizaron transferencias, pedidos ni reservas reales durante el gate.

## Defectos corregidos durante el gate

- `vicunav-restaurante` #37 / PR #38: `legend` del filtro sin desborde de 4 px.
- `vicunav-restaurante` #39 / PR #40: campos multilínea del checkout a ancho completo.
- `vicunav-theme-core` #43 / PR #44: FAQ cerradas inicialmente como en el prototipo.
- `vicunav-demo-restaurante`: landmark principal de portada, copy sin mapas ni
  WhatsApp inexistentes, serialización válida del bloque Cover y orden canónico del
  horario semanal.
