#!/bin/sh
#v1.0.0
#
# Copyright 2025 Lloyd Miles M. Bersabe
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

RUNNING=1

trap 'RUNNING=0' HUP INT TERM

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd) || exit 1
CURRENT_USER=$(id -u)
CURRENT_GROUP=$(id -g)

LIGHTTPD_BIN=lighttpd
PHP_FPM_BIN=php-fpm

# --- args ---
while [ $# -gt 0 ]; do
    case $1 in
        -l) [ $# -ge 2 ] || { printf "%s: -l requires a value\n" "$0" >&2; exit 1; }; LIGHTTPD_BIN=$2; shift 2 ;;
        -p) [ $# -ge 2 ] || { printf "%s: -p requires a value\n" "$0" >&2; exit 1; }; PHP_FPM_BIN=$2; shift 2 ;;
        *) printf "%s: Unknown option '%s'\nUsage: %s [-l lighttpd_path] [-p php-fpm_path]\n" "$0" "$1" "$0" >&2; exit 1 ;;
    esac
done

escape_sed() {
    printf '%s' "$1" | sed 's/[\/&]/\\&/g'
}

substitute() {
    sed \
        -e "s|{ROOT}|$(escape_sed "$SCRIPT_DIR")|g" \
        -e "s|{USER}|$(escape_sed "$CURRENT_USER")|g" \
        -e "s|{GROUP}|$(escape_sed "$CURRENT_GROUP")|g" \
        "$1" > "$2"
}

substitute "$SCRIPT_DIR/uc.sh.lighttpd.conf" "$SCRIPT_DIR/var/dat/lighttpd.conf"
substitute "$SCRIPT_DIR/uc.sh.php-fpm.conf" "$SCRIPT_DIR/var/dat/php-fpm.conf"
substitute "$SCRIPT_DIR/uc.sh.php.ini" "$SCRIPT_DIR/var/dat/php.ini"

while [ "$RUNNING" -eq 1 ]; do
    if [ -z "${FPM_PID:-}" ] || ! kill -0 "$FPM_PID" 2>/dev/null; then
        command "$PHP_FPM_BIN" -y "$SCRIPT_DIR/var/dat/php-fpm.conf" -c "$SCRIPT_DIR/var/dat/php.ini" --nodaemonize &
        FPM_PID=$!
    fi

    if [ -z "${LIGHTTPD_PID:-}" ] || ! kill -0 "$LIGHTTPD_PID" 2>/dev/null; then
        command "$LIGHTTPD_BIN" -D -f "$SCRIPT_DIR/var/dat/lighttpd.conf" &
        LIGHTTPD_PID=$!
    fi

    sleep 1
done

for pid in ${FPM_PID:-} ${LIGHTTPD_PID:-}; do
    [ -n "$pid" ] && kill "$pid" 2>/dev/null
done

i=0
while [ "$i" -lt 30 ]; do
    alive=0
    for pid in ${FPM_PID:-} ${LIGHTTPD_PID:-}; do
        [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null && alive=1 && break
    done
    [ "$alive" -eq 0 ] && break
    i=$((i + 1))
    sleep 1
done

for pid in ${FPM_PID:-} ${LIGHTTPD_PID:-}; do
    [ -n "$pid" ] || continue
    kill -0 "$pid" 2>/dev/null && kill -9 "$pid" 2>/dev/null
    wait "$pid" 2>/dev/null
done