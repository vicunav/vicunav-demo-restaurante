#!/usr/bin/env bash

set -euo pipefail

usage() {
	cat <<'EOF'
Uso:
  bash bin/install-local.sh --wp-path=RUTA --site-url=URL [opciones]

Opciones:
  --repos-root=RUTA  Raíz que contiene los repositorios hermanos.
  --dry-run          Valida y describe acciones sin modificar el sitio.
  --help             Muestra esta ayuda.

Variables:
  VICUNAV_PHP_BIN          Binario PHP. Valor predeterminado: php.
  VICUNAV_WP_CLI_BIN       Archivo ejecutable de WP-CLI. Valor predeterminado: wp.
  VICUNAV_MYSQL_SOCKET     Socket MySQL de LocalWP, cuando sea necesario.
  VICUNAV_MANIFEST_PATH    Manifiesto alternativo, reservado para pruebas.
EOF
}

fail() {
	printf 'Error: %s\n' "$*" >&2
	exit 1
}

log_command() {
	printf '+' >&2
	printf ' %q' "$@" >&2
	printf '\n' >&2
}

canonical_directory() {
	(
		cd "$1"
		pwd -P
	)
}

script_dir="$(canonical_directory "$(dirname "${BASH_SOURCE[0]}")")"
repo_dir="$(canonical_directory "$script_dir/..")"
repos_root="$(canonical_directory "$repo_dir/..")"
manifest_path="${VICUNAV_MANIFEST_PATH:-$repo_dir/config/dependencies.json}"
wp_path=''
site_url=''
dry_run=false

for argument in "$@"; do
	case "$argument" in
		--wp-path=*) wp_path="${argument#*=}" ;;
		--site-url=*) site_url="${argument#*=}" ;;
		--repos-root=*) repos_root="${argument#*=}" ;;
		--dry-run) dry_run=true ;;
		--help)
			usage
			exit 0
			;;
		*) fail "argumento no reconocido: $argument" ;;
	esac
done

[[ -n "$wp_path" ]] || fail 'falta --wp-path.'
[[ -n "$site_url" ]] || fail 'falta --site-url.'
[[ "$site_url" =~ ^https?://[^/]+\.local/?$ ]] || fail 'la URL debe pertenecer a un host .local.'
[[ -d "$wp_path" ]] || fail "el root de WordPress no existe: $wp_path"
[[ -f "$wp_path/wp-load.php" && -f "$wp_path/wp-config.php" ]] || fail 'el destino no es una instalación WordPress reconocible.'
[[ -d "$repos_root" ]] || fail "la raíz de repositorios no existe: $repos_root"
[[ -f "$manifest_path" ]] || fail "no existe el manifiesto: $manifest_path"

wp_path="$(canonical_directory "$wp_path")"
repos_root="$(canonical_directory "$repos_root")"
site_url="${site_url%/}"

php_bin="${VICUNAV_PHP_BIN:-php}"
wp_cli_bin="${VICUNAV_WP_CLI_BIN:-wp}"
mysql_socket="${VICUNAV_MYSQL_SOCKET:-}"

if [[ "$php_bin" == */* ]]; then
	[[ -x "$php_bin" ]] || fail "el binario PHP no es ejecutable: $php_bin"
else
	php_bin="$(command -v "$php_bin" || true)"
	[[ -n "$php_bin" ]] || fail 'no se encontró PHP.'
fi

if [[ "$wp_cli_bin" == */* ]]; then
	[[ -f "$wp_cli_bin" ]] || fail "no existe WP-CLI: $wp_cli_bin"
else
	wp_cli_bin="$(command -v "$wp_cli_bin" || true)"
	[[ -n "$wp_cli_bin" ]] || fail 'no se encontró WP-CLI.'
fi

command -v jq >/dev/null 2>&1 || fail 'no se encontró jq.'
command -v git >/dev/null 2>&1 || fail 'no se encontró Git.'
jq -e '.schema_version == 1 and (.packages | type == "array")' "$manifest_path" >/dev/null || fail 'el manifiesto no cumple el schema 1.'

php_command=("$php_bin")
if [[ -n "$mysql_socket" ]]; then
	[[ -S "$mysql_socket" || -e "$mysql_socket" ]] || fail "el socket MySQL no existe: $mysql_socket"
	php_command+=(
		-d "mysqli.default_socket=$mysql_socket"
		-d "pdo_mysql.default_socket=$mysql_socket"
	)
fi

run_wp() {
	log_command "${php_command[@]}" "$wp_cli_bin" "--path=$wp_path" "--url=$site_url" "$@"
	"${php_command[@]}" "$wp_cli_bin" "--path=$wp_path" "--url=$site_url" "$@"
}

printf 'Instalación local Vicunav: %s\n' "$(date -u '+%Y-%m-%dT%H:%M:%SZ')"
printf 'Sitio: %s\n' "$site_url"
printf 'WordPress: %s\n' "$wp_path"

site_home="$(run_wp option get home)"
[[ "${site_home%/}" == "$site_url" ]] || fail "la URL real del sitio es ${site_home%/}, no $site_url."

core_version="$(run_wp core version)"
minimum_wp="$(jq -r '.wordpress.minimum' "$manifest_path")"
minimum_php="$(jq -r '.wordpress.php_minimum' "$manifest_path")"
php_version="$("${php_command[@]}" -r 'echo PHP_VERSION;')"

"${php_command[@]}" -r 'exit(version_compare($argv[1], $argv[2], ">=") ? 0 : 1);' "$core_version" "$minimum_wp" || fail "WordPress $core_version no cumple el mínimo $minimum_wp."
"${php_command[@]}" -r 'exit(version_compare($argv[1], $argv[2], ">=") ? 0 : 1);' "$php_version" "$minimum_php" || fail "PHP $php_version no cumple el mínimo $minimum_php."

while IFS=$'\t' read -r repository slug package_type expected_commit activate; do
	[[ "$package_type" == 'plugin' || "$package_type" == 'theme' ]] || fail "tipo inválido para $repository: $package_type"
	[[ "$activate" == 'true' || "$activate" == 'false' ]] || fail "activate inválido para $repository."

	source_path="$repos_root/$repository"
	[[ -d "$source_path" ]] || fail "la fuente no existe: $source_path"
	git -C "$source_path" rev-parse --is-inside-work-tree >/dev/null 2>&1 || fail "la fuente no es un repositorio Git: $source_path"
	source_path="$(canonical_directory "$source_path")"
	[[ -z "$(git -C "$source_path" status --porcelain)" ]] || fail "la fuente tiene cambios sin publicar: $repository"
	actual_commit="$(git -C "$source_path" rev-parse HEAD)"
	[[ "$actual_commit" == "$expected_commit" ]] || fail "revisión inesperada para $repository: $actual_commit"

	if [[ "$package_type" == 'plugin' ]]; then
		destination="$wp_path/wp-content/plugins/$slug"
	else
		destination="$wp_path/wp-content/themes/$slug"
	fi

	if [[ -L "$destination" ]]; then
		link_target="$(readlink "$destination")"
		if [[ "$link_target" != /* ]]; then
			link_target="$(dirname "$destination")/$link_target"
		fi
		[[ -d "$link_target" ]] || fail "el symlink está roto: $destination"
		link_target="$(canonical_directory "$link_target")"
		[[ "$link_target" == "$source_path" ]] || fail "el symlink apunta a otra fuente: $destination"
		printf 'Correcto: %s -> %s\n' "$destination" "$source_path"
	elif [[ -e "$destination" ]]; then
		fail "el destino está ocupado y no es un symlink: $destination"
	elif [[ "$dry_run" == true ]]; then
		printf 'Crearía: %s -> %s\n' "$destination" "$source_path"
	else
		log_command ln -s "$source_path" "$destination"
		ln -s "$source_path" "$destination"
	fi
done < <(jq -r '.packages[] | [.repository, .slug, .type, .commit, .activate] | @tsv' "$manifest_path")

while IFS=$'\t' read -r slug package_type activate; do
	[[ "$activate" == 'true' ]] || continue
	if run_wp "$package_type" is-active "$slug" >/dev/null 2>&1; then
		printf 'Activo: %s\n' "$slug"
	elif [[ "$dry_run" == true ]]; then
		printf 'Activaría %s: %s\n' "$package_type" "$slug"
	else
		run_wp "$package_type" activate "$slug"
		run_wp "$package_type" is-active "$slug" >/dev/null
	fi
done < <(jq -r '.packages[] | [.slug, .type, .activate] | @tsv' "$manifest_path")

if jq -e '.child_theme' "$manifest_path" >/dev/null 2>&1; then
	child_theme_path="$(jq -r '.child_theme.path' "$manifest_path")"
	child_theme_slug="$(jq -r '.child_theme.slug' "$manifest_path")"
	child_theme_activate="$(jq -r '.child_theme.activate' "$manifest_path")"
	child_theme_source="$repo_dir/$child_theme_path"
	[[ -d "$child_theme_source" ]] || fail "el child theme no existe: $child_theme_source"
	child_theme_source="$(canonical_directory "$child_theme_source")"
	child_theme_destination="$wp_path/wp-content/themes/$child_theme_slug"

	if [[ -L "$child_theme_destination" ]]; then
		link_target="$(readlink "$child_theme_destination")"
		if [[ "$link_target" != /* ]]; then
			link_target="$(dirname "$child_theme_destination")/$link_target"
		fi
		[[ -d "$link_target" ]] || fail "el symlink del child theme está roto: $child_theme_destination"
		link_target="$(canonical_directory "$link_target")"
		[[ "$link_target" == "$child_theme_source" ]] || fail "el symlink del child theme apunta a otra fuente: $child_theme_destination"
		printf 'Correcto: %s -> %s\n' "$child_theme_destination" "$child_theme_source"
	elif [[ -e "$child_theme_destination" ]]; then
		fail "el destino del child theme está ocupado y no es un symlink: $child_theme_destination"
	elif [[ "$dry_run" == true ]]; then
		printf 'Crearía: %s -> %s\n' "$child_theme_destination" "$child_theme_source"
	else
		log_command ln -s "$child_theme_source" "$child_theme_destination"
		ln -s "$child_theme_source" "$child_theme_destination"
	fi

	if [[ "$child_theme_activate" == 'true' ]]; then
		if run_wp theme is-active "$child_theme_slug" >/dev/null 2>&1; then
			printf 'Activo: %s\n' "$child_theme_slug"
		elif [[ "$dry_run" == true ]]; then
			printf 'Activaría theme: %s\n' "$child_theme_slug"
		else
			run_wp theme activate "$child_theme_slug"
			run_wp theme is-active "$child_theme_slug" >/dev/null
		fi
	fi
fi

if [[ "$dry_run" == true ]]; then
	printf 'Dry run completado sin cambios.\n'
else
	printf 'Instalación completada.\n'
fi
