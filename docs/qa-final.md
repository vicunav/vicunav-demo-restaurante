# Gate final Bonasera

## Alcance y veredicto

DEMO-REST-02E compara el prototipo auditado en
`1e1f62787e088c0ca9701500e764802499d1b253` con la recomposición Gutenberg
consolidada en `7b43e4508a13616ec976060dc33b4a1a4d01a1ac`.

El gate queda aprobado con diferencias explícitas. No se declara coincidencia píxel
a píxel. La implementación conserva la dirección visual Bonasera, la jerarquía, la
paleta, las familias tipográficas, el chrome, los banners, los controles y el
comportamiento responsive mediante bloques core editables y contratos del plugin.
Las diferencias restantes corresponden a densidad transaccional de la SPA, iconos y
activos no entregados. El usuario autorizó similitud y placeholders en el issue #19.

| Paquete | Commit validado |
| --- | --- |
| `vicunav-plugin-core` | `12870b0d5e297d715c985037e76898067a749909` |
| `vicunav-pagos` | `16280c3bd74977ac025f0085ccdf22ae5b995277` |
| `vicunav-restaurante` | `a46d1d746e0b880dca949a875d2dceb4b9207c61` |
| `vicunav-theme-core` | `7c30b2ce250bb85572dae4a4cd51841921c4e98a` |

## Evidencia visual reproducible

La matriz contiene siete superficies y cinco viewports: 35 combinaciones. Cada fila
versiona captura fuente, captura target, lado a lado, overlay, diff, hashes y métricas
exactas y perceptuales.

| Estado | Cantidad |
| --- | ---: |
| Coincidencia exacta | 0 |
| Diferencia sin resolver | 0 |
| Diferencia revisada y aprobada | 35 |

Los viewports son 1440 x 1000, 1024 x 900, 768 x 1024, 390 x 844 y 375 x 812.
La revisión manual cubrió portada, menú, constructor de pizza, carrito vacío,
checkout, reservas y pizzas guardadas. No se detectaron cortes horizontales, targets
inaccesibles, texto ilegible ni jerarquía rota. En móvil, la traducción evita varios
recortes presentes en la propia captura fuente.

El informe navegable está en
[`visual/evidence/visual-report.html`](visual/evidence/visual-report.html) y el
contrato completo en
[`visual/migration-manifest.json`](visual/migration-manifest.json).

## Estructura, accesibilidad y FSE

- Las nueve rutas públicas responden 200 y exponen un solo H1.
- No hay hotlinks ni imágenes remotas en el frontend.
- La portada y las páginas interiores usan bloques válidos, template parts y
  patrones editables.
- El editor de la página no reporta bloques inválidos.
- Header, footer, botones, formularios, filtros y acordeones mantienen foco visible,
  targets táctiles y wrapping en los cinco anchos.
- `prefers-reduced-motion` conserva una experiencia estable.

## Regresión funcional de solo lectura

La suite runtime valida las firmas públicas de siete flujos sin crear pedidos,
reservas, usuarios ni datos privados:

- menú, búsqueda y filtros;
- constructor de pizza y toppings;
- carrito y descuento;
- checkout y formulario;
- consulta de pedido;
- reservas y disponibilidad;
- pizzas guardadas y estado de autenticación.

También valida las nueve rutas, un H1 por ruta, ausencia de hotlinks y los contratos
compartidos. La validación WP-CLI se ejecuta con el PHP y socket de LocalWP sin
imprimir credenciales, salts ni tokens.

## Activos y límites honestos

Se aprobaron sustitutos locales para el video hero, los mapas, la imagen de historia,
los avatares de testimonios y la categoría dolci. Los originales no entregados siguen
identificados en `config/media.json`; el usuario puede reemplazarlos desde WordPress.
No se inventó cartografía, no se introdujo video remoto y no se presentó una marca
ajena como si fuera un original disponible.

## Aprendizajes obligatorios para próximas migraciones

1. Congelar el commit fuente y capturar la matriz visual antes de implementar.
2. Tratar la fuente auditada como contrato base, no como inspiración.
3. Aprobar primero un corte vertical completo en desktop y móvil.
4. Extraer chrome, tokens y patrones compartidos después de validar ese corte.
5. Comparar cada superficie durante la implementación, no al final del proyecto.
6. No confundir checks estructurales con fidelidad visual.
7. Registrar un asset ausente desde el inicio y continuar con un placeholder editable.
8. Mantener por separado coincidencias, diferencias pendientes y diferencias
   aprobadas, siempre con evidencia y métricas.

Este orden reduce retrabajo y evita que una migración técnicamente válida llegue tarde
al gate visual con una composición equivocada.
