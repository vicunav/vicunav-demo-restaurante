# Gate final Bonasera

## Alcance y evidencia

El gate compara el prototipo auditado
`1e1f62787e088c0ca9701500e764802499d1b253` con la composición WordPress del commit
`bdc0a1536c8cd7f80a85a1084dfa6c7194c57580`. Las dependencias exactas están en
`config/dependencies.json` y los viewports y presupuestos en `config/qa.json`.

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

El script de runtime es de solo lectura. Verifica dependencias, entidades marcadas,
media, bloques, templates editables, rutas, H1 y ausencia de hotlinks. La revisión
visual y accesible continúa siendo manual porque depende del layout y del navegador.

## Matriz visual

| Ancho | Portada | Rutas funcionales | Header y overflow | Zona y sticky UI | Estado |
| ---: | --- | --- | --- | --- | --- |
| 1440 px | Pendiente | Pendiente | Pendiente | Pendiente | Pendiente |
| 1024 px | Pendiente | Pendiente | Pendiente | Pendiente | Pendiente |
| 768 px | Pendiente | Pendiente | Pendiente | Pendiente | Pendiente |
| 390 px | Pendiente | Pendiente | Pendiente | Pendiente | Pendiente |
| 375 px | Pendiente | Pendiente | Pendiente | Pendiente | Pendiente |

## Comprobaciones finales

- Frontend y Site Editor: pendiente tras reiniciar el equipo.
- Navegación por teclado, foco visible y targets táctiles: pendiente.
- Menú, constructor, carrito, checkout, pedido, reservas y pizzas guardadas: pendiente
  de repetición integral.
- Consola, bloques inválidos, overflow y H1: pendiente de repetición integral.
- Peso de media y ausencia de hotlinks: pasan en la suite versionada.
- Copy, licencias, atribución y clasificación ficticia: pasan en la suite versionada.

LocalWP debe iniciarse desde su aplicación para completar estas filas. El repositorio
no inicia ni altera servicios de LocalWP automáticamente.
