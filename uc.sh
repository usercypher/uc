#!/bin/sh
#v1.0.1
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
ROOT=$(cd "$(dirname "$0")" && pwd) || exit 1
UID=$(id -u)
GID=$(id -g)

trap 'RUNNING=0' HUP INT TERM

escape_sed() {
    printf '%s' "$1" | sed 's/[\/&]/\\&/g'
}

substitute() {
    sed \
        -e "s|{ROOT}|$(escape_sed "$ROOT")|g" \
        -e "s|{UID}|$(escape_sed "$UID")|g" \
        -e "s|{GID}|$(escape_sed "$GID")|g" \
        "$1" > "$2"
}

substitute "$ROOT/uc.sh.lighttpd.conf" "$ROOT/var/dat/lighttpd.conf"
substitute "$ROOT/uc.sh.php-fpm.conf" "$ROOT/var/dat/php-fpm.conf"
substitute "$ROOT/uc.sh.php.ini" "$ROOT/var/dat/php.ini"

help() {
    printf "Usage: %s [-l lighttpd_path] [-p php-fpm_path]\n" "$0" >&2
    exit 1
}

while [ $# -gt 0 ]; do
    case $1 in
        -l) [ $# -ge 2 ] || help; LIGHTTPD_BIN=$2; shift ;;
        -p) [ $# -ge 2 ] || help; PHP_FPM_BIN=$2; shift ;;
        *)  help ;;
    esac
    shift
done

while [ "$RUNNING" -eq 1 ]; do
    [ -n "${FPM_PID:-}" ] && kill -0 "$FPM_PID" 2>/dev/null || {
        "${PHP_FPM_BIN:-php-fpm}" -y "$ROOT/var/dat/php-fpm.conf" -c "$ROOT/var/dat/php.ini" --nodaemonize &
        FPM_PID=$!
    }

    [ -n "${LIGHTTPD_PID:-}" ] && kill -0 "$LIGHTTPD_PID" 2>/dev/null || {
        "${LIGHTTPD_BIN:-lighttpd}" -D -f "$ROOT/var/dat/lighttpd.conf" &
        LIGHTTPD_PID=$!
    }

    sleep 1
done

for pid in ${FPM_PID:-} ${LIGHTTPD_PID:-}; do
    kill "$pid" 2>/dev/null
done

for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30; do
    alive=
    for pid in ${FPM_PID:-} ${LIGHTTPD_PID:-}; do
        kill -0 "$pid" 2>/dev/null && alive=1 && break
    done
    [ -z "$alive" ] && break
    sleep 1
done

for pid in ${FPM_PID:-} ${LIGHTTPD_PID:-}; do
    kill -0 "$pid" 2>/dev/null && kill -9 "$pid" 2>/dev/null
    wait "$pid" 2>/dev/null
done