#!/usr/bin/env bash

set -euo pipefail

repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
installer="$repo_dir/bin/install-local.sh"
test_root="$(mktemp -d "${TMPDIR:-/tmp}/vicunav-demo-restaurante.XXXXXX")"
trap 'rm -rf "${test_root:?}"' EXIT

fail() {
	printf 'Fallo: %s\n' "$*" >&2
	exit 1
}

node "$repo_dir/tests/validate-content.mjs"

repos_root="$test_root/repos"
wp_root="$test_root/site/app/public"
manifest="$test_root/dependencies.json"
wp_stub="$test_root/wp-stub.php"
state_dir="$test_root/state"
activation_log="$test_root/activations.log"

mkdir -p "$repos_root" "$wp_root/wp-content/plugins" "$wp_root/wp-content/themes" "$state_dir"
: > "$wp_root/wp-load.php"
: > "$wp_root/wp-config.php"
: > "$activation_log"

create_repository() {
	local name="$1"
	local path="$repos_root/$name"
	mkdir -p "$path"
	git -C "$path" init -q
	git -C "$path" config user.name 'Pruebas Vicunav'
	git -C "$path" config user.email 'tests@example.invalid'
	printf '%s\n' "$name" > "$path/README.md"
	git -C "$path" add README.md
	git -C "$path" commit -q -m 'test: crear fixture'
}

for repository in vicunav-plugin-core vicunav-pagos vicunav-restaurante vicunav-theme-core; do
	create_repository "$repository"
done

core_commit="$(git -C "$repos_root/vicunav-plugin-core" rev-parse HEAD)"
pagos_commit="$(git -C "$repos_root/vicunav-pagos" rev-parse HEAD)"
restaurante_commit="$(git -C "$repos_root/vicunav-restaurante" rev-parse HEAD)"
theme_commit="$(git -C "$repos_root/vicunav-theme-core" rev-parse HEAD)"

cat > "$manifest" <<EOF
{
  "schema_version": 1,
  "wordpress": { "minimum": "6.6", "php_minimum": "8.1" },
  "packages": [
    { "repository": "vicunav-plugin-core", "slug": "vicunav-plugin-core", "type": "plugin", "commit": "$core_commit", "activate": true },
    { "repository": "vicunav-pagos", "slug": "vicunav-pagos", "type": "plugin", "commit": "$pagos_commit", "activate": true },
    { "repository": "vicunav-restaurante", "slug": "vicunav-restaurante", "type": "plugin", "commit": "$restaurante_commit", "activate": true },
    { "repository": "vicunav-theme-core", "slug": "vicunav-theme-core", "type": "theme", "commit": "$theme_commit", "activate": true }
  ]
}
EOF

cat > "$wp_stub" <<'PHP'
<?php
$arguments = array_values(
	array_filter(
		array_slice( $argv, 1 ),
		static fn( $argument ) => ! str_starts_with( $argument, '--' )
	)
);
$type   = $arguments[0] ?? '';
$action = $arguments[1] ?? '';
$slug   = $arguments[2] ?? '';

if ( 'option' === $type && 'get' === $action ) {
	echo getenv( 'VICUNAV_TEST_SITE_URL' );
	exit( 0 );
}
if ( 'core' === $type && 'version' === $action ) {
	echo '6.6';
	exit( 0 );
}
if ( in_array( $type, array( 'plugin', 'theme' ), true ) && 'is-active' === $action ) {
	exit( file_exists( getenv( 'VICUNAV_TEST_STATE_DIR' ) . "/$type-$slug" ) ? 0 : 1 );
}
if ( in_array( $type, array( 'plugin', 'theme' ), true ) && 'activate' === $action ) {
	touch( getenv( 'VICUNAV_TEST_STATE_DIR' ) . "/$type-$slug" );
	file_put_contents( getenv( 'VICUNAV_TEST_LOG' ), "$type:$slug\n", FILE_APPEND );
	exit( 0 );
}
fwrite( STDERR, 'Comando WP-CLI inesperado.' );
exit( 2 );
PHP

export VICUNAV_MANIFEST_PATH="$manifest"
export VICUNAV_PHP_BIN="$(command -v php)"
export VICUNAV_WP_CLI_BIN="$wp_stub"
export VICUNAV_TEST_SITE_URL='https://fixture.local'
export VICUNAV_TEST_STATE_DIR="$state_dir"
export VICUNAV_TEST_LOG="$activation_log"

run_installer() {
	bash "$installer" \
		"--wp-path=$wp_root" \
		'--site-url=https://fixture.local' \
		"--repos-root=$repos_root"
}

run_installer >/dev/null
run_installer >/dev/null

[[ -L "$wp_root/wp-content/plugins/vicunav-plugin-core" ]] || fail 'faltó el symlink de core.'
[[ -L "$wp_root/wp-content/plugins/vicunav-pagos" ]] || fail 'faltó el symlink de pagos.'
[[ -L "$wp_root/wp-content/plugins/vicunav-restaurante" ]] || fail 'faltó el symlink de restaurante.'
[[ -L "$wp_root/wp-content/themes/vicunav-theme-core" ]] || fail 'faltó el symlink del theme.'
[[ "$(wc -l < "$activation_log" | tr -d ' ')" == '4' ]] || fail 'la segunda ejecución reactivó paquetes.'

printf 'cambio local\n' > "$repos_root/vicunav-pagos/cambio-local.txt"
if run_installer >"$test_root/dirty.log" 2>&1; then
	fail 'una fuente con cambios locales fue aceptada.'
fi
grep -q 'cambios sin publicar' "$test_root/dirty.log" || fail 'faltó el error de fuente sucia.'
rm "$repos_root/vicunav-pagos/cambio-local.txt"

printf 'cambio\n' >> "$repos_root/vicunav-theme-core/README.md"
git -C "$repos_root/vicunav-theme-core" add README.md
git -C "$repos_root/vicunav-theme-core" commit -q -m 'test: cambiar revisión'
if run_installer >"$test_root/revision.log" 2>&1; then
	fail 'una revisión incorrecta fue aceptada.'
fi
grep -q 'revisión inesperada' "$test_root/revision.log" || fail 'faltó el error de revisión.'
git -C "$repos_root/vicunav-theme-core" checkout -q --detach "$theme_commit"

rm "$wp_root/wp-content/plugins/vicunav-pagos"
mkdir "$wp_root/wp-content/plugins/vicunav-pagos"
if run_installer >"$test_root/collision.log" 2>&1; then
	fail 'una colisión de destino fue aceptada.'
fi
grep -q 'destino está ocupado' "$test_root/collision.log" || fail 'faltó el error de colisión.'

printf 'Pruebas de instalación completadas.\n'
