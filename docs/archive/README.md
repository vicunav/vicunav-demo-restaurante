# Archivo: implementación superada

Estos archivos ya no están en las rutas activas del repo. Se conservan aquí
(y en el historial de git, sin reescribir) como referencia, no como código a
reutilizar.

## `apply-content.php.superseded`

Construía la composición FSE de las nueve páginas del demo (incluyendo el
template registrado en base de datos que activaba la variación Bonasera).
Retirado el 2026-09-01 por instrucción explícita del usuario: reconstruir la
composición de páginas desde cero, sin reutilizar código de un ciclo de
migración anterior, verificando cada página contra la fuente real
(`vicunav-design-to-claude-demo-restaurante`) y contra el gate de paridad
editor/frontend (`verify_editor_frontend_parity.mjs`, G9) recién construido.
La causa raíz del bug de "container" que motivó este reset ya se corrigió a
nivel de `vicunav-theme-core` (fix(theme)#55: un `.alignfull` no escapaba de
un padre `is-layout-constrained` en el frontend); este archivo no es la causa
de ese bug específico, pero su composición nunca se verificó con ese gate
porque no existía cuando se escribió.

## `validate-composition.mjs.superseded`

Validaba la fuente de `apply-content.php` directamente (nombres de bloque,
constantes, patrones esperados). Retirado junto con el archivo que valida;
`tests/validate-content.mjs` sigue vigente porque valida `content/bonasera.json`
como manifiesto de datos, no la composición.

## Lo que sigue vigente

- `content/bonasera.json` y `tests/validate-content.mjs`: el manifiesto de
  copy auditado contra el commit `1e1f627...` de la fuente sigue siendo
  válido — el diseño no cambió (verificado byte a byte contra
  `restaurante.zip`, adjuntado por el usuario el mismo día del reset). Cada
  página reconstruida debe seguir cruzando su copy específico contra
  `legacy/Restaurante Guasábara.dc.html` al implementarla, no solo confiar
  en este JSON.
- `config/media.json`, `assets/images/*.webp`: media licenciada ya auditada,
  sin cambios.
- El child theme `theme/vicunav-bonasera/` (tokens, fuentes): construido y
  verificado contra la misma fuente confirmada sin cambios; no se retira.

Ver `.unlazy/rebuild-bonasera/PLAN.md` (no versionado, local a esta sesión de
trabajo) para el plan de reconstrucción vigente.
