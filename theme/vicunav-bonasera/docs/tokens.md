# Tokens del child theme Bonasera

## Fuente

- Repositorio: `vicunav-design-to-claude-demo-restaurante`, commit `1e1f62787e088c0ca9701500e764802499d1b253`.
- Export real de Claude Design: `legacy/Restaurante Guasábara.dc.html` +
  `legacy/_ds/the-burger-lab-design-system-4b9e73e2-001d-4229-8b27-b5228db53508/tokens/*.css`.
  **No** el `src/` (reimplementación vanilla-JS reconstruida a partir del diseño, con
  varias pasadas de refactor encima) — ese fue el error del ciclo de migración
  anterior (ver `docs/visual/baseline-bonasera.md` del demo).

## Paleta

| Slug | Valor | Origen |
| --- | --- | --- |
| `vicunav-primary` | `#0D0D0D` | `--color-ink` |
| `vicunav-secondary` | `#4A3B33` | `--color-malt-brown` |
| `vicunav-accent` | `#9DAAAA` | `--color-sage` |
| `vicunav-neutral-100` | `#FAEBD7` | `--color-cream` |
| `vicunav-neutral-200` | `#F0DFC4` | `--color-cream-dim` |
| `vicunav-neutral-300` | `#FFFDF8` | derivado (blanco papel, no está en `colors.css`; conservado del ciclo anterior por ser un neutral plausible, no un dato inventado de negocio) |
| `vicunav-neutral-400/500/600/700` | `rgba(13,13,13,.12/.5/.62/.75)` | overlays de `--color-ink` a distintas opacidades |
| `vicunav-neutral-800/900` | `#4A3B33` / `#0D0D0D` | malta / tinta |
| `bonasera-ink-soft` | `#181410` | `--color-ink-soft` — **no capturado en el ciclo anterior**, confirmado en esta auditoría |
| `bonasera-dusty-rose` | `#D99B93` | `--color-dusty-rose` — **no capturado en el ciclo anterior**, confirmado en esta auditoría |

Las dos últimas filas son entradas propias de este child theme, más allá de los 16
slugs que publica `vicunav-theme-core`: un child theme puede añadir presets propios
sin romper el contrato del padre.

`vicunav-positive` (`#4D673B`), `vicunav-warning` (`#9F4527`), `vicunav-danger`
(`#A8432B`) y `vicunav-info` (`#557259`) son variantes oscurecidas de los colores de
estado que usaba la fuente (`#C1592F`, `#5B7A45`, `#7C9A7E`), que no alcanzaban 4.5:1
de contraste sobre crema. Verificado con luminancia relativa WCAG 2.1 (no solo
heredado del ciclo anterior — recalculado en esta auditoría, ver
`tests/validate-theme-contract.mjs`):

| Combinación | Relación |
| --- | ---: |
| Positivo sobre crema | 5.40:1 |
| Advertencia sobre crema | 5.34:1 |
| Peligro sobre crema | 5.12:1 |
| Información sobre crema | 4.55:1 |
| Tinta sobre crema | 16.59:1 |
| Tinta sobre salvia | 8.11:1 |

## Tipografía

Big Shoulders Display (700–900) y Jost (400–700), autoalojadas bajo SIL Open Font
License 1.1 (`assets/fonts/licenses/`), confirmadas en `_ds/tokens/fonts.css` de la
fuente. Los ocho pasos de tamaño publicados por `vicunav-theme-core` se completan con
siete valores reales de `_ds/tokens/typography.css` (`--text-eyebrow`, `--text-small`,
`--text-body`, `--text-body-lg`, `--text-display-md/lg/xl`); la fuente solo define
siete pasos distintos, así que `vicunav-display-sm` y `vicunav-display-lg` comparten
el mismo valor (`clamp(3.5rem, 9vw, 7.5rem)`) en vez de inventar un octavo paso que el
diseño no tiene.

## Espaciado

Escala 1:1 con `_ds/tokens/spacing.css` (`--space-1` a `--space-10`: 4, 8, 12, 16, 24,
32, 48, 64, 96, 140 px), mapeada a los diez slugs que publica `vicunav-theme-core`.

## Assets pendientes

El video del hero (`uploads/YTDown.com_YouTube_Osteria-Lucio-Italian-Restaurant-Promo...mp4`)
y los mapas de zona de entrega siguen sin existir en el commit auditado — el nombre
del archivo de video indica que es un rip de un video de YouTube de otro restaurante
("Osteria Lucio"), sin licencia verificable; **no debe recuperarse ni sustituirse por
ese archivo si alguna vez aparece**. El usuario subirá los medios reales desde
wp-admin cuando los tenga; mientras tanto se usan placeholders editables sin alterar
geometría (mismo criterio ya aprobado en `docs/visual/assets-bonasera.md` del demo).
