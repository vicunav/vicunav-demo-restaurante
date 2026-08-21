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
API pública ni una segunda autoridad de negocio. DEMO-REST-01C los aplicará mediante
los contratos de cada paquete y creará las páginas FSE. DEMO-REST-01D validará el
resultado completo antes de considerar el demo terminado.
