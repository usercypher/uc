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
    lighttpd) LIGHTTPD_BIN=lighttpd; shift ;;
    php-fpm*) PHP_FPM_BIN="$1"; shift ;;
    *) echo "Usage: $0 [lighttpd] [php-fpm|php-fpm*|...]" >&2; exit 1 ;;
  esac
done

substitute() {
  sed \
    -e "s|{ROOT}|$SCRIPT_DIR|g" \
    -e "s|{USER}|$CURRENT_USER|g" \
    -e "s|{GROUP}|$CURRENT_GROUP|g" \
    "$1" >  "$2"
}

substitute "$SCRIPT_DIR/boot.php-fpm.conf" "$SCRIPT_DIR/var/data/php-fpm.conf"
substitute "$SCRIPT_DIR/boot.lighttpd.conf" "$SCRIPT_DIR/var/data/lighttpd.conf"

"$PHP_FPM_BIN" -y "$SCRIPT_DIR/var/data/php-fpm.conf" --nodaemonize &
FPM_PID=$!

"$LIGHTTPD_BIN" -D -f "$SCRIPT_DIR/var/data/lighttpd.conf" &
LIGHTTPD_PID=$!

wait $FPM_PID $LIGHTTPD_PID