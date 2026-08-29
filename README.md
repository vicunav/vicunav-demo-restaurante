# Vicunav Restaurant Demo

Reproducible Full Site Editing composition for Bonasera, the restaurant reference
site in the Vicunav WordPress ecosystem. This repository owns demo content, licensed
media, page composition, and local installation orchestration. It does not own theme
or business logic.

## Package boundaries

The demo assembles these independent repositories at pinned revisions:

- `vicunav-theme-core` for the block theme and the selectable Bonasera variation.
- `vicunav-plugin-core` for shared settings, FAQs, and testimonials.
- `vicunav-pagos` for payment requests and the manual payment lifecycle.
- `vicunav-restaurante` for menu, cart, orders, pizza building, delivery, and
  reservations.

The exact revisions live in `config/dependencies.json`. Packages are linked into an
existing LocalWP site; no package or WordPress installation is copied into this
repository.

The audited Bonasera copy and demo data live in `content/bonasera.json`. Licensed,
self-hosted images live in `assets/images/`, with provenance, alt text, dimensions,
size, and checksums in `config/media.json`. The content is not production restaurant
data and does not replace the runtime contracts of the package repositories.

## Local installation

Requirements:

- an existing and running single-site LocalWP installation;
- PHP, WP-CLI, `jq`, and Git;
- the four package repositories checked out as siblings of this repository;
- a MySQL socket override when the LocalWP PHP CLI cannot use its default socket.

Run a preview first:

```bash
VICUNAV_PHP_BIN="/path/to/local/php" \
VICUNAV_MYSQL_SOCKET="/path/to/mysqld.sock" \
bash bin/install-local.sh \
  --wp-path="/path/to/local-site/app/public" \
  --site-url="https://example.local" \
  --dry-run
```

Remove `--dry-run` to create missing symlinks and activate the pinned theme and
plugins. The installer refuses non-local URLs, dirty or unpinned sources, broken
links, and occupied destinations. Re-running it leaves an already-correct assembly
unchanged.

Once the dependencies are active, apply the audited Bonasera content with:

```bash
wp --path="/absolute/path/to/app/public" eval-file bin/apply-content.php
```

The content application is idempotent. It imports licensed local media, configures the
public restaurant contracts and creates editable Full Site Editing pages without
writing directly to vertical-owned database tables.

## Validation

```bash
bash tests/run.sh
```

See `docs/arquitectura.md` for the internal ownership and installation contract.
See `docs/inventario-contenido-media.md` for the content and licensing audit.
See `docs/visual/baseline-bonasera.md` for the immutable visual source, ownership
matrix, known differences, and reproducible evidence contract.
See `docs/visual/assets-bonasera.md` for recovered originals, licensing, approved
substitutions, and placeholder replacement conditions.
