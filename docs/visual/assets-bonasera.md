# Contrato de assets Bonasera 1:1

## Resultado

DEMO-REST-02A clasifica la media de la fuente auditada sin modificar la composición
ni el sitio LocalWP. Ocho imágenes locales recuperan la misma fotografía de la fuente,
una imagen local es un sustituto todavía no aprobado, cinco originales se retienen por
seguridad o representación responsable y tres archivos nunca fueron entregados.

El gate final permanece bloqueado. Una licencia compatible permite usar un archivo,
pero no convierte una omisión o sustitución en paridad visual ni autoriza a atribuir
identidades o respaldo ficticios.

## Fuente y verificación

| Campo | Valor |
| --- | --- |
| Repositorio | `vicunav-design-to-claude-demo-restaurante` |
| Commit | `1e1f62787e088c0ca9701500e764802499d1b253` |
| Fuente de referencias | `src/data/media.js` |
| Inventario local | `config/media.json`, schema 2 |
| Licencias revisadas | 2026-08-26 |

La [licencia de Unsplash](https://unsplash.com/license) permite descargar, usar y
modificar imágenes para fines comerciales y no comerciales, con las restricciones que
publica el proveedor. La [licencia de Pexels](https://www.pexels.com/license/) permite
uso y modificación, pero prohíbe sugerir respaldo de personas o marcas presentes en
la imagen. Se conservan autor, fuente y licencia aunque la atribución no sea obligatoria.

## Originales recuperados

Estos archivos son locales, no hotlinks. Cada fila conserva dimensiones, peso y
SHA-256 en `config/media.json`.

| Asset local | Referencia de la fuente | Estado |
| --- | --- | --- |
| `antipasti.webp` | `CATEGORY_IMG.antipasti` | `exact-source-recovered` |
| `insalate.webp` | `CATEGORY_IMG.insalate` | `exact-source-recovered` |
| `pizze.webp` | `CATEGORY_IMG.pizze` | `exact-source-recovered` |
| `pasta.webp` | `CATEGORY_IMG.pasta` | `exact-source-recovered` |
| `secondi.webp` | `CATEGORY_IMG.secondi` | `exact-source-recovered` |
| `contorni.webp` | `CATEGORY_IMG.contorni` | `exact-source-recovered` |
| `bevande.webp` | `CATEGORY_IMG.bevande` | `exact-source-recovered` |
| `reservas.webp` | `RESERVA_IMG` | `exact-source-recovered` |

La identidad de la fotografía se comprueba mediante la referencia y el identificador
estable del proveedor. La integridad del archivo local se comprueba mediante su hash.

## Sustitución pendiente

`dolci.webp` usa una fotografía Pexels de tiramisú en lugar de
`CATEGORY_IMG.dolci`. El original de Unsplash muestra una marca visible. Mantener el
sustituto es una decisión conservadora, pero todavía no existe aprobación humana que
lo convierta en diferencia aceptada. Su estado es `substitute-pending-approval` y
bloquea el gate final.

No se elimina el sustituto actual porque forma parte del runtime ya integrado y este
issue no altera contenido. Tampoco se restaura el original sin resolver la diferencia.

## Originales retenidos

| ID | Referencia | Estado | Motivo |
| --- | --- | --- | --- |
| `historia-original` | `HISTORIA_IMG` | `omitted-policy-pending-approval` | Personas identificables presentadas como familia ficticia |
| `testimonial-avatar-t1` | `AVATAR_MAP.t1` | `omitted-policy-pending-approval` | Podría sugerir respaldo a un testimonio ficticio |
| `testimonial-avatar-t2` | `AVATAR_MAP.t2` | `omitted-policy-pending-approval` | Podría sugerir respaldo a un testimonio ficticio |
| `testimonial-avatar-t3` | `AVATAR_MAP.t3` | `omitted-policy-pending-approval` | Podría sugerir respaldo a un testimonio ficticio |
| `dolci-original` | `CATEGORY_IMG.dolci` | `substitute-pending-approval` | Marca ajena visible; existe sustituto local no aprobado |

Los archivos no se descargan al repositorio. Sus URLs sobreviven solo como evidencia
de procedencia en el inventario. Las cinco diferencias requieren aprobación humana en
el gate final, aunque la omisión sea la opción recomendada por seguridad.

## Originales no entregados

| ID | Ruta esperada en la fuente | Estado |
| --- | --- | --- |
| `hero-video` | `public/uploads/hero-video.mp4` | `missing-original` |
| `map-zulia` | `public/assets/mapa-zulia.png` | `missing-original` |
| `map-maracaibo` | `public/assets/mapa-maracaibo.png` | `missing-original` |

No existe binario, procedencia ni licencia para esos archivos en el commit auditado o
en otra entrega autorizada. No se generan aproximaciones, cartografía ficticia ni un
video nuevo como si fueran los originales.

## Condiciones para desbloquear el gate

- Un original entregado debe incluir procedencia, licencia, dimensiones, peso, hash y
  texto alternativo aplicable antes de entrar al repositorio.
- Un sustituto necesita referencia de aprobación humana y debe registrarse como
  `approved-substitute` en el manifiesto visual.
- Una omisión por privacidad o representación responsable necesita la misma aprobación
  como diferencia intencional, sin reintroducir retratos en testimonios ficticios.
- La captura y el reporte visual se regeneran después de cualquier cambio de media.

Hasta entonces, el manifiesto conserva seis grupos `missing`: tres no entregados y
tres diferencias disponibles pero retenidas o sustituidas. Sumados a las 35 filas
`different`, el gate reporta 41 bloqueos.

## Límites de propiedad

`vicunav-demo-restaurante` posee estos assets y su composición. El theme puede definir
ratios, recortes y estilos reutilizables, pero no incorpora fotografías Bonasera.
`vicunav-restaurante` y `vicunav-pagos` no poseen media editorial de la marca. El
tooling de migración valida evidencia y nunca es dependencia de runtime.
