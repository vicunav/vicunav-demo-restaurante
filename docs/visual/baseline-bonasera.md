# Baseline visual Bonasera

## Propósito y estado

DESIGN-REST-02 congela la referencia aprobada y el estado previo a la corrección de
WordPress. Este documento no declara paridad. La matriz automatizada identifica las
diferencias como deuda abierta para THEME-REST-01 a THEME-REST-03 y
DEMO-REST-01A a DEMO-REST-01D.

El gate histórico de DEMO-REST-01D acreditó rutas, estructura, accesibilidad básica,
funciones y ausencia de overflow en WordPress. No comparó píxeles, tipografía,
jerarquía, ritmo ni composición con la fuente. Su antigua palabra `Aprobado` en la
matriz visual se interpreta desde ahora como `gate estructural aprobado`, nunca como
fidelidad 1:1.

## Contrato inmutable de fuente

| Campo | Valor |
| --- | --- |
| Repositorio | `vicunav-design-to-claude-demo-restaurante` |
| Branch auditada | `main` |
| Commit | `1e1f62787e088c0ca9701500e764802499d1b253` |
| Instalación | `npm ci` |
| Validaciones | `npm run lint`, `npm test`, `npm run build` |
| Ejecución | `npm run dev -- --host 127.0.0.1 --port 4173` |

En la revisión de este baseline, lint pasó, las 66 pruebas pasaron y el build pasó.
`npm run format` continúa reportando 56 archivos: es un hallazgo de la fuente, no un
cambio autorizado sobre el commit auditado.

La fuente es una SPA sin rutas reales. Sus siete pantallas son `inicio`, `menu`,
`pizzas`, `carrito`, `checkout`, `reserva` y `mispizzas`. Las acciones declaradas en
el manifiesto reproducen cada pantalla desde un contexto nuevo.

## Entorno comparable

- Chromium 151.0.7922.34, escala 1 y esquema claro.
- Viewports: 1440 x 1000, 1024 x 900, 768 x 1024, 390 x 844 y 375 x 812.
- Locale de captura `es-VE`, zona horaria `America/Caracas` y movimiento reducido.
- Captura de viewport fijo, no página completa, para conservar dimensiones idénticas
  entre una SPA de alturas distintas y las páginas reales de WordPress. El gate final
  añadirá recorridos por sección y estados interactivos antes de aprobar paridad.
- Fuente: Big Shoulders Display para display y Jost para cuerpo.
- Objetivo observado: WordPress 7.1, PHP 8.2.29, locale persistido `en_US` y zona
  horaria `+00:00`. La captura fuerza las condiciones comparables sin ocultar que la
  configuración persistida debe corregirse en la pista de integración.

El estado WordPress congelado corresponde al commit del demo
`737d027f78ad301b4e0c80c2b316e131a1b807a5` y a los cuatro pins de
`config/dependencies.json`. La inspección fue de solo lectura: no se crearon pedidos,
carritos, reservas, usuarios ni cambios de Global Styles.

## Superficies y equivalencia

| WordPress | Estado fuente | Comparación | Observación |
| --- | --- | --- | --- |
| `/` | `inicio` | Sí | Portada, header, hero, secciones y footer |
| `/menu/` | `menu` | Sí | Menú completo en estado inicial |
| `/pizzas/` | `pizzas` | Sí | Catálogo y constructor en estado inicial |
| `/carrito/` | `carrito` | Sí | Carrito vacío en ambos lados |
| `/checkout/` | `checkout` | Sí, con limitación | La fuente necesita un artículo local para alcanzar la pantalla; WordPress se conserva vacío para no escribir en la base local |
| `/reservas/` | `reserva` | Sí | Formulario inicial sin enviar datos |
| `/mis-pizzas/` | `mispizzas` | Sí | Estado público sin pizzas guardadas |
| `/pedido/` | No existe | No | Superficie real de seguimiento propia de WordPress |
| `/privacidad/` | No existe | No | Superficie editorial y legal propia de WordPress |

No se inventa una captura fuente para `/pedido/` o `/privacidad/`. Ambas rutas forman
parte del QA WordPress, pero no del contrato de paridad con la SPA.

## Inventario de estados

La matriz congelada cubre el estado inicial de cada superficie en los cinco
viewports. Los siguientes estados quedan indexados para la implementación y el gate
final:

| Superficie | Estados adicionales aplicables |
| --- | --- |
| Portada | FAQ expandida, dirección verificada, zona seleccionada, drawer de carrito |
| Menú | búsqueda, categoría, vegetariano, picante, favoritos, modal de personalización, vacío |
| Pizzas | mitad izquierda/derecha, topping seleccionado, agotado, máximo de toppings, cotización, guardado |
| Carrito | poblado, delivery, zona, propina, descuento válido, error, conflicto |
| Checkout | poblado, delivery, validación, ocupado, error, pedido creado y vínculo de pago manual |
| Reservas | fecha, horarios, sin cupo, alternativa concurrente, confirmada, cancelada, error |
| Mis pizzas | autenticación requerida, vacío autenticado, poblado, renombrado, compartido, conflicto |
| Global | hover, focus-visible, disabled, loading, error, success y reduced motion |

Los estados que crean entidades no se ejecutan en DESIGN-REST-02. Se capturarán con
fixtures desechables y limpieza documentada en los issues propietarios, no contra
datos persistidos del sitio de referencia.

## Tokens visuales de la fuente

| Grupo | Valores de referencia |
| --- | --- |
| Color | crema `#faebd7`, crema tenue `#f0dfc4`, papel `#fffdf8`, tinta `#0d0d0d`, marrón `#4a3b33`, salvia `#9daaaa` |
| Tipografía | Big Shoulders Display y Jost |
| Espaciado | escala 4, 8, 12, 16, 24, 32, 48, 64, 96 y 140 px |
| Sección | `clamp(64px, 10vw, 140px)` vertical y `clamp(24px, 5vw, 96px)` horizontal |
| Contenedor | 1240 px |
| Breakpoints | 480, 768, 1024 y 1440 px |
| Forma | radio píldora y geometría rectangular predominante |

Estos valores son entradas para la variación Bonasera de `vicunav-theme-core`. No se
copian como identidad global del theme ni se incrustan como marca en el plugin.

## Propiedad por componente

| Elemento | Propietario |
| --- | --- |
| Tokens, tipografía, presets, variación Bonasera, templates, parts y patterns reutilizables | `vicunav-theme-core` |
| Markup semántico, estados y comportamiento del menú, pizza, carrito, checkout, pedidos, delivery y reservas | `vicunav-restaurante` |
| Solicitud y ciclo de pago enlazado por `external_type` y `external_id` | `vicunav-pagos` |
| FAQ, testimonios y ajustes compartidos | `vicunav-plugin-core` |
| Copy, media licenciada, páginas, composición FSE y selección de la variación | `vicunav-demo-restaurante` |

El checkout v1 usa el proveedor manual real. La recomendación legacy de WooCommerce
en la fuente está revocada por el contrato REST-01 y no se convierte en dependencia.

## Assets y bloqueos

El video `public/uploads/hero-video.mp4` y los mapas
`public/assets/mapa-zulia.png` y `public/assets/mapa-maracaibo.png` no están en el
commit auditado. Permanecen `missing` en el manifiesto. Las imágenes WebP locales del
demo tienen procedencia, licencia, alt, dimensiones y checksums documentados, pero no
son sustitutos aprobados para esos tres faltantes.

No se versionan hotlinks, cookies, nonces, credenciales, rutas personales ni datos
privados como parte del baseline.

## Defectos que no deben preservarse

- header y overflow rotos en 768 px y 390 px;
- input de zona reducido a aproximadamente 30 px en móvil;
- H1 duplicados en carrito, checkout, reservas y pizzas guardadas;
- video hero y dos mapas ausentes;
- dependencia de imágenes remotas sin garantía de producción.

El constructor de pizzas sí renderiza cuando se alcanza su posición real. El supuesto
primer clic fallido de navegación tampoco quedó demostrado. Ninguno se registra como
bug confirmado.

Responsive y accesibilidad se corrigen en Gutenberg aunque eso produzca una diferencia
intencional frente al defecto de la fuente. Esa diferencia deberá enlazar el estándar
o issue correctivo; no se aprueba de forma implícita.

## Lectura del baseline actual

La inspección efectiva mostró una divergencia inmediata: la fuente calcula Jost,
fondo crema y texto tinta; la portada WordPress calcula Times, fondo transparente y
texto negro. Esta evidencia confirma que la configuración visual existente no está
aplicada como Bonasera y evita volver a confundir un gate funcional con un gate 1:1.

La comparación produjo 35 de 35 filas en estado `different`, cero coincidencias y
cero diferencias aprobadas. La diferencia perceptual medida está entre 31,55 % y
90,47 % según la superficie y el viewport. El gate final falla de forma esperada con
38 bloqueos: las 35 diferencias y los tres assets ausentes. Ese fallo es la prueba de
que el tooling impide declarar terminada la migración actual.

El manifiesto y el reporte viven junto a este documento. `different` significa deuda
abierta. `pending` no significa aprobado. Solo un gate posterior con evidencia
completa, inspección manual y cero diferencias no autorizadas puede cerrar la paridad.
