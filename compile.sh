#!/bin/sh

php -c "$(dirname "$0")/bin/php/php.ini" "$(dirname "$0")/bin/compile.php" "$@"
