#!/bin/sh

set -e

trap 'kill ${FPM_PID:-} ${LIGHTTPD_PID:-} 2>/dev/null || true' 0 2 15

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
CURRENT_USER="$(whoami)"
CURRENT_GROUP="$(id -gn)"

LIGHTTPD_BIN="lighttpd"
PHP_FPM_BIN="php-fpm"

while [ $# -gt 0 ]; do
  case "$1" in
    -l)
      if [ -z "$2" ]; then
        printf "%s: -l requires a value\n" "$0" >&2
        exit 1
      fi
      LIGHTTPD_BIN="$2"
      shift 2
      ;;
    -p)
      if [ -z "$2" ]; then
        printf "%s: -p requires a value\n" "$0" >&2
        exit 1
      fi
      PHP_FPM_BIN="$2"
      shift 2
      ;;
    *)
      printf "%s: Unknown option '%s'\n" "$0" "$1" >&2
      printf "Usage: %s [-l lighttpd_path] [-p php-fpm_path]\n" "$0" >&2
      exit 1
      ;;
  esac
done

substitute() {
  sed \
    -e "s|{ROOT}|$SCRIPT_DIR|g" \
    -e "s|{USER}|$CURRENT_USER|g" \
    -e "s|{GROUP}|$CURRENT_GROUP|g" \
    "$1" > "$2"
}

substitute "$SCRIPT_DIR/init.lighttpd.conf" "$SCRIPT_DIR/var/dat/lighttpd.conf"
substitute "$SCRIPT_DIR/init.php-fpm.conf" "$SCRIPT_DIR/var/dat/php-fpm.conf"
substitute "$SCRIPT_DIR/init.php.ini" "$SCRIPT_DIR/var/dat/php.ini"

while true; do
    if ! kill -0 "$FPM_PID" 2>/dev/null; then
        command "$PHP_FPM_BIN" -y "$SCRIPT_DIR/var/dat/php-fpm.conf" -c "$SCRIPT_DIR/var/dat/php.ini" --nodaemonize &
        FPM_PID=$!
    fi

    if ! kill -0 "$LIGHTTPD_PID" 2>/dev/null; then
        command "$LIGHTTPD_BIN" -D -f "$SCRIPT_DIR/var/dat/lighttpd.conf" &
        LIGHTTPD_PID=$!
    fi

    sleep 5
done