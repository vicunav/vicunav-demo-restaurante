# vicunav-demo-restaurante

Propósito: Composición FSE, contenido y ensamblaje reproducible del demo Bonasera.

## Reglas aplicables

Las reglas transversales del repositorio están en [`docs/standards/`](docs/standards/). Consúltalas antes de realizar cambios.

No repitas esas reglas aquí; este archivo solo contiene el contexto específico del repositorio.

## Validación

```sh
bash tests/run.sh
```

Ejecuta la validación antes de entregar cualquier cambio.

## Límites del repositorio

- Este repositorio conserva composición, copy, media licenciada y automatización del
  demo. No contiene lógica reutilizable de theme, pagos ni restaurante.
- Las dependencias se consumen desde repositorios hermanos mediante symlinks. No se
  copian paquetes dentro del demo ni se versiona un sitio WordPress completo.
- El sitio LocalWP existente es el único destino local. Los scripts deben exigir un
  path y una URL `.local`, fallar ante colisiones y ser idempotentes.
- No publicar credenciales, bases de datos, uploads privados ni configuración de
  `wp-config.php`.
