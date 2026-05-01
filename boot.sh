#!/bin/sh

set -e

trap 'kill ${FPM_PID:-} ${LIGHTTPD_PID:-} 2>/dev/null || true' EXIT INT TERM

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
CURRENT_USER="$(whoami)"
CURRENT_GROUP="$(id -gn)"

LIGHTTPD_BIN="lighttpd"
PHP_FPM_BIN="php-fpm"

while [ $# -gt 0 ]; do
  case "$1" in
    lighttpd*) LIGHTTPD_BIN="$1"; shift ;;
    *fpm*) PHP_FPM_BIN="$1"; shift ;;
    *) echo "Usage: $0 [lighttpd*] [*fpm*]" >&2; exit 1 ;;
  esac
done

substitute() {
  sed \
    -e "s|{ROOT}|$SCRIPT_DIR|g" \
    -e "s|{USER}|$CURRENT_USER|g" \
    -e "s|{GROUP}|$CURRENT_GROUP|g" \
    "$1" > "$2"
}

substitute "$SCRIPT_DIR/boot.lighttpd.conf" "$SCRIPT_DIR/var/dat/lighttpd.conf"
substitute "$SCRIPT_DIR/boot.php-fpm.conf" "$SCRIPT_DIR/var/dat/php-fpm.conf"
substitute "$SCRIPT_DIR/boot.php.ini" "$SCRIPT_DIR/var/dat/php.ini"

command "$PHP_FPM_BIN" -y "$SCRIPT_DIR/var/dat/php-fpm.conf" -c "$SCRIPT_DIR/var/dat/php.ini" --nodaemonize &
FPM_PID=$!

command "$LIGHTTPD_BIN" -D -f "$SCRIPT_DIR/var/dat/lighttpd.conf" &
LIGHTTPD_PID=$!

while kill -0 "$FPM_PID" 2>/dev/null && kill -0 "$LIGHTTPD_PID" 2>/dev/null; do
  sleep 5
done