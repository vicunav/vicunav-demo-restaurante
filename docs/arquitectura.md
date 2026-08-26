# Arquitectura del demo de restaurante

## Propiedad

`vicunav-demo-restaurante` conserva únicamente el ensamblaje demostrativo de
Bonasera: contenido, media con licencia comprobable, páginas, composición FSE y
automatización local. No define schemas, precios, estados, permisos ni endpoints.

| Repositorio | Responsabilidad consumida |
| --- | --- |
| `vicunav-theme-core` | Tokens, variación Bonasera, templates, parts y patterns reutilizables |
| `vicunav-plugin-core` | Ajustes compartidos, FAQ y testimonios |
| `vicunav-pagos` | Solicitudes y estados de pago mediante su contrato público |
| `vicunav-restaurante` | Menú, pizzas, carrito, pedidos, delivery y reservas |
| `vicunav-demo-restaurante` | Copy, media, rutas y composición Bonasera |

El demo nunca copia esos paquetes ni lee su persistencia interna. El manifiesto
`config/dependencies.json` fija la revisión auditada de cada repositorio y obliga a
actualizar el pin de manera explícita cuando cambie una dependencia.

## Contrato de instalación local

`bin/install-local.sh` opera solo sobre un sitio `.local` indicado explícitamente.
Antes de escribir:

1. comprueba el root de WordPress y la URL real mediante WP-CLI;
2. verifica mínimos de WordPress y PHP;
3. exige que cada fuente sea un repositorio limpio en el commit fijado;
4. acepta un symlink correcto, crea uno ausente y rechaza cualquier otro destino;
5. activa plugins en orden de dependencia y después el theme;
6. omite paquetes ya activos.

No existe modo `--force`: una colisión exige inspección humana. El script no elimina,
reemplaza ni copia archivos, no crea sitios y no imprime secretos. `--dry-run` ejecuta
las comprobaciones de solo lectura y describe las acciones pendientes.

Las variables `VICUNAV_PHP_BIN`, `VICUNAV_WP_CLI_BIN` y
`VICUNAV_MYSQL_SOCKET` adaptan la ejecución al runtime de LocalWP. La contraseña de
base de datos permanece en `wp-config.php` y nunca pasa por el instalador.

## Contenido demostrativo

DEMO-REST-01B versiona el copy y los datos de siembra en `content/bonasera.json` y la
media aprobada en `assets/images/`. Esos archivos son entradas del ensamblaje, no una
API pública ni una segunda autoridad de negocio.

## Aplicación del contenido

`bin/apply-content.php` se ejecuta con `wp eval-file` y usa exclusivamente APIs de
WordPress, `vicunav-plugin-core`, `vicunav-pagos` y `vicunav-restaurante`. Los UUID de
ingredientes, opciones, zonas y descuentos se conservan en un mapa del demo. Los
medios y contenidos usan marcadores privados para que una segunda ejecución actualice
la misma entidad sin duplicarla.

El demo crea overrides FSE de `front-page` y `page`, selecciona la variación Bonasera
y compone las ocho rutas reales. La portada contiene su único H1; las páginas internas
reciben su H1 desde el template y reservan el contenido a bloques editoriales y los
siete bloques dinámicos del vertical.

No se importan las rutas teatrales de la SPA. El checkout usa solo el proveedor manual
real y el sitio no escribe directamente en tablas del vertical. DEMO-REST-01D valida
el resultado completo antes de considerar el demo terminado.

## Contrato visual

DESIGN-REST-02 fija la fuente, el entorno, las siete superficies comparables, cinco
viewports y la propiedad multirrepositorio en
[`visual/migration-manifest.json`](visual/migration-manifest.json). El contexto y los
bloqueos se documentan en
[`visual/baseline-bonasera.md`](visual/baseline-bonasera.md). Una suite funcional o
estructural aprobada no sustituye el gate de fidelidad 1:1.

El estado de cada original, sustituto y omisión se conserva en
[`visual/assets-bonasera.md`](visual/assets-bonasera.md). Solo el demo versiona media
Bonasera; theme y plugins consumen contratos visuales sin apropiarse de esos archivos.
