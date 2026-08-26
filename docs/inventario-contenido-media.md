# Inventario de contenido y media Bonasera

## Baseline

La fuente auditada es `vicunav-design-to-claude-demo-restaurante` en el commit
`1e1f62787e088c0ca9701500e764802499d1b253`. `content/bonasera.json` conserva el
copy y los datos demostrativos necesarios para componer WordPress. No es un contrato
de runtime: DEMO-REST-01C sembrará esos datos a través de las superficies públicas de
los paquetes propietarios.

Bonasera, su historia, sus testimonios y sus datos operativos son ficticios. El
teléfono, correo y dirección exacta no verificados del prototipo se sustituyeron por
datos no enrutables. Esta clasificación evita presentar el demo como un comercio
real o publicar datos que pudieran pertenecer a terceros.

## Superficies editoriales

| Ruta prevista | H1 único | Contenido principal | Propietario en WordPress |
| --- | --- | --- | --- |
| `/` | Bonasera | Hero, destacados, categorías, historia, ubicación, testimonios, FAQ y contacto | Composición FSE del demo |
| `/menu/` | Nuestro menú | Filtros y catálogo estructurado | Bloque dinámico de `vicunav-restaurante` |
| `/pizzas/` | Crea tu pizza | Catálogo y constructor | Bloques dinámicos de `vicunav-restaurante` |
| `/carrito/` | Tu pedido | Líneas y totales autoritativos | Bloque dinámico de `vicunav-restaurante` |
| `/checkout/` | Confirmar pedido | Datos, entrega o retiro y pago manual | Bloque dinámico de `vicunav-restaurante` |
| `/pedido/` | Estado del pedido | Estado real de pedido y pago | Bloque dinámico de `vicunav-restaurante` |
| `/reservas/` | Reservar mesa | Disponibilidad y formulario | Bloque dinámico de `vicunav-restaurante` |
| `/mis-pizzas/` | Mis pizzas | Configuraciones de la cuenta | Bloque dinámico de `vicunav-restaurante` |

El JSON contiene 37 platos en ocho categorías, 20 ingredientes, opciones del
constructor, ocho FAQ y tres testimonios ficticios. También conserva horarios,
capacidad, zonas, descuentos y propinas como datos de siembra, nunca como lógica ni
como autoridad de precios del demo.

## Ajustes contractuales al prototipo

- El checkout menciona únicamente el proveedor manual real de `vicunav-pagos`.
- No se importaron los cuatro métodos de pago teatrales ni sus pantallas simuladas.
- La historia conserva su tono, pero ninguna foto identifica a personas reales como
  miembros de la familia ficticia.
- Los testimonios conservan copy demostrativo sin retratos y declaran que son
  ficticios.
- Los defectos responsive, de accesibilidad y los H1 duplicados no forman parte del
  contenido canónico.

## Media versionada

`config/media.json` es el inventario verificable de schema 2. Cada imagen WebP es local, tiene
uso previsto, texto alternativo, autor, fuente, licencia, dimensiones, peso y SHA-256.
Las fotos de Unsplash se usan bajo la
[Unsplash License](https://unsplash.com/license) y las de Pexels bajo la
[Pexels License](https://www.pexels.com/legal-pages/license/), verificadas el
2026-08-26. La atribución no es obligatoria en esas licencias, pero se conserva para
trazabilidad y crédito.

Se excluyeron los tres retratos testimoniales, la foto de dos trabajadores presentada
como familia y la foto de postre con una marca visible. No hay hotlinks en el contenido
que consumirá WordPress; las URL externas solo sobreviven como evidencia en el
inventario. Ocho archivos recuperan la misma foto de la fuente auditada. `dolci.webp`
es una sustitución conservadora y continúa pendiente de aprobación visual.

El estado 1:1 por activo, incluidas las omisiones de seguridad, está en
[`visual/assets-bonasera.md`](visual/assets-bonasera.md).

## Ausencias confirmadas

El video hero y los mapas de Zulia y Maracaibo no están en el commit auditado ni se
entregaron por otra vía. Permanecen registrados como `missing-original` y no se crean
sustitutos que aparenten ser mapas reales. La composición actual usa una imagen hero
local y una lista textual de zonas sin declararlas paridad. Incorporar video o
cartografía exige un activo nuevo con licencia y procedencia comprobables.
